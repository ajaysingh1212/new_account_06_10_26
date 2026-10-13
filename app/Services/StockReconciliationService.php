<?php

namespace App\Services;

use App\Models\Item;
use App\Models\PurchaseBill;
use App\Models\StockAdjustment;
use App\Models\StockMovement;

class StockReconciliationService
{
    public function report(int $companyId, array $serialsByItem, $serialItemIds): array
    {
        $items = Item::withTrashed()->with('productType')->where('company_id', $companyId)->orderBy('name')->get();
        $bills = PurchaseBill::with('items')->where('company_id', $companyId)->where('status', 'posted')->get();
        $movements = StockMovement::where('company_id', $companyId)->orderBy('id')->get();
        $adjustments = StockAdjustment::where('company_id', $companyId)->get();
        $movementsByItem = $movements->groupBy('item_id');
        $purchaseMovementsByBill = $movements->where('reference_type', PurchaseBill::class)->groupBy('reference_id');
        $adjustmentsByItem = $adjustments->groupBy('item_id');
        $purchaseByItem = [];
        $billIssues = [];
        $purchasePaise = 0;

        foreach ($bills as $bill) {
            $billPaise = $this->paise($bill->grand_total);
            $purchasePaise += $billPaise;
            $lineTotal = (float) $bill->items->sum('line_total');
            $billValues = app(PurchaseStockValueService::class)->itemValues($bill->items, (float) $bill->grand_total);
            foreach ($billValues as $itemId => $allocated) {
                $purchaseByItem[$itemId] = ($purchaseByItem[$itemId] ?? 0) + $allocated;
            }

            $billMovements = $purchaseMovementsByBill->get($bill->id, collect());
            foreach ($bill->items->groupBy('item_id') as $itemId => $lines) {
                $posted = $billMovements->where('item_id', $itemId);
                $postedValue = $posted->sum(fn($m) => ($m->direction === 'in' ? 1 : -1) * $this->paise($m->total_value));
                $postedQty = $posted->sum(fn($m) => ($m->direction === 'in' ? 1 : -1) * (float) $m->quantity);
                $linePaise = $billValues[$itemId] ?? 0;
                if ($postedValue !== $linePaise || abs($postedQty - (float) $lines->sum('quantity')) > 0.0005) {
                    $billIssues[] = [
                        'bill' => $bill,
                        'item_id' => $itemId,
                        'item_name' => $items->firstWhere('id', $itemId)?->name ?? 'Deleted / missing item',
                        'bill_value' => $linePaise / 100,
                        'posted_value' => $postedValue / 100,
                        'bill_qty' => (float) $lines->sum('quantity'),
                        'posted_qty' => $postedQty,
                        'reason' => $posted->isEmpty() ? 'Purchase has no stock posting. Check stock tracking and bill posting.' : 'Bill quantity or amount differs from its stock postings. Check bill edits, rate, GST and discount.',
                    ];
                }
            }
            $headerDifference = $this->paise($lineTotal) - $billPaise;
            $billPostedPaise = $billMovements->sum(fn($m) => ($m->direction === 'in' ? 1 : -1) * $this->paise($m->total_value));
            if (($headerDifference !== 0 && $billPostedPaise !== $billPaise) || $bill->items->isEmpty()) {
                $billIssues[] = [
                    'bill' => $bill, 'item_id' => null, 'item_name' => 'Bill total / overall discount',
                    'bill_value' => $billPaise / 100, 'posted_value' => $billPostedPaise / 100,
                    'bill_qty' => null, 'posted_qty' => null,
                    'reason' => $bill->items->isEmpty() ? 'Bill has an amount but no item lines.' : 'Final bill amount after overall discount differs from recorded stock postings. Save the corrected purchase bill again to reconcile its stock value.',
                ];
            }
        }

        $rows = [];
        $valuations = [];
        $stockPaise = 0;
        $bridge = [];
        $activeBillIds = $bills->modelKeys();
        foreach ($movements as $movement) {
            if ($movement->reference_type === PurchaseBill::class && in_array((int) $movement->reference_id, $activeBillIds, true)) {
                continue;
            }
            $key = $movement->reference_type === PurchaseBill::class ? 'deleted_or_unposted_purchase_movements' : $movement->movement_type;
            $bridge[$key] = ($bridge[$key] ?? 0) + ($movement->direction === 'in' ? 1 : -1) * $this->paise($movement->total_value);
        }
        $adjustmentPaise = $adjustments->sum(fn($a) => $this->paise($a->new_stock_value) - $this->paise($a->previous_stock_value));
        if ($adjustmentPaise !== 0) {
            $bridge['manual_stock_adjustment'] = $adjustmentPaise;
        }

        foreach ($items as $item) {
            $itemMovements = $movementsByItem->get($item->id, collect());
            $itemAdjustments = $adjustmentsByItem->get($item->id, collect());
            $recordedQty = (float) $item->current_stock;
            $qty = $serialItemIds->contains((int) $item->id) ? count($serialsByItem[$item->id] ?? []) : $recordedQty;
            $masterPaise = $this->paise($qty * (float) $item->purchase_price);
            $hasEntries = $itemMovements->isNotEmpty() || $itemAdjustments->isNotEmpty();
            $value = $qty > 0 && !$item->trashed() ? ($hasEntries ? max(0, $this->paise($item->stock_value)) : $masterPaise) : 0;
            $otherValue = $itemMovements->reject(fn($m) => $m->reference_type === PurchaseBill::class && in_array((int) $m->reference_id, $activeBillIds, true))
                ->sum(fn($m) => ($m->direction === 'in' ? 1 : -1) * $this->paise($m->total_value));
            $adjustmentValue = $itemAdjustments->sum(fn($a) => $this->paise($a->new_stock_value) - $this->paise($a->previous_stock_value));
            $purchase = $purchaseByItem[$item->id] ?? 0;
            $expected = $purchase + $otherValue + $adjustmentValue;
            $reasons = [];
            if ($masterPaise !== $value) {
                $reasons[] = 'Master price × stock gives Rs '.number_format($masterPaise / 100, 2).'; recorded stock cost differs. Check bill rates, GST, discounts and production cost.';
            }
            if (abs($recordedQty - $qty) > 0.0005) {
                $reasons[] = 'Serial balance differs from saved stock quantity ('.number_format($recordedQty, 3).').';
            }
            if ($otherValue !== 0) {
                $reasons[] = 'Other stock movements change value by Rs '.number_format($otherValue / 100, 2).'. See movement history.';
            }
            if ($itemAdjustments->isNotEmpty()) {
                $reasons[] = 'Manual stock adjustments change value by Rs '.number_format($adjustmentValue / 100, 2).'.';
            }
            if ($value !== $expected) {
                $reasons[] = 'Unreconciled amount: Rs '.number_format(($value - $expected) / 100, 2).'. Check purchase posting, bill edits, opening stock and stored stock value.';
            }
            if ($qty <= 0 && $this->paise($item->stock_value) !== 0) {
                $reasons[] = 'No available quantity, but a saved stock value remains.';
            }
            if ($item->trashed() && ($purchase !== 0 || $recordedQty > 0)) {
                $reasons[] = 'Item is deleted and excluded from available stock.';
            }
            $valuations[$item->id] = ['value' => $value / 100, 'quantity' => $qty, 'avg_rate' => $qty > 0 ? $value / 100 / $qty : 0];
            $stockPaise += $value;
            if ($purchase !== 0 || $value !== 0 || $reasons !== []) {
                $rows[] = ['item' => $item, 'purchase' => $purchase / 100, 'stock' => $value / 100,
                    'expected' => $expected / 100, 'difference' => ($value - $purchase) / 100,
                    'unexplained' => ($value - $expected) / 100, 'quantity' => $qty, 'reasons' => $reasons];
            }
        }

        return ['purchase_amount' => $purchasePaise / 100, 'stock_value' => $stockPaise / 100,
            'difference' => ($stockPaise - $purchasePaise) / 100,
            'expected_value' => ($purchasePaise + array_sum($bridge)) / 100,
            'unexplained_difference' => ($stockPaise - $purchasePaise - array_sum($bridge)) / 100,
            'bridge' => array_map(fn($value) => $value / 100, $bridge),
            'rows' => $rows, 'bill_issues' => $billIssues, 'valuations' => $valuations, 'bill_count' => $bills->count()];
    }

    private function paise($amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
