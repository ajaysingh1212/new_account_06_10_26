<?php

namespace App\Services;

class PurchaseStockValueService
{
    /** Allocate the final invoice cost in paise, including tax and overall discount. */
    public function lineValues($lines, float $grandTotal): array
    {
        $lines = collect($lines)->values();
        $total = (float) $lines->sum('line_total');
        $remaining = (int) round($grandTotal * 100);
        $values = [];
        foreach ($lines as $index => $line) {
            $values[$index] = $index === $lines->count() - 1 ? $remaining
                : ($total > 0 ? (int) round($grandTotal * 100 * (float) data_get($line, 'line_total') / $total) : 0);
            $remaining -= $values[$index];
        }
        return $values;
    }

    public function itemValues($lines, float $grandTotal): array
    {
        $lines = collect($lines)->values();
        $values = $this->lineValues($lines, $grandTotal);
        $items = [];
        foreach ($lines as $index => $line) {
            $itemId = data_get($line, 'item_id');
            $items[$itemId] = ($items[$itemId] ?? 0) + $values[$index];
        }
        return $items;
    }
}
