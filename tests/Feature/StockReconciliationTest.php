<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\StockReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_with_gst_and_line_discount_matches_recorded_stock_not_master_rate(): void
    {
        [$user, $company, $item] = $this->context();
        $this->postPurchase($item);
        $response = $this->get(route('admin.stocks.index'));
        $response->assertOk()->assertViewHas('overallValue', 212.4)
            ->assertSee('Purchase vs Stock Valuation')->assertSee('Average stock cost');
        $report = $response->viewData('stockReconciliation');
        $this->assertSame(212.4, $report['purchase_amount']);
        $this->assertSame(212.4, $report['stock_value']);
        $this->assertEquals(0, $report['difference']);
        $this->assertSame([], $report['bill_issues']);
        $this->assertStringContainsString('Master price', $report['rows'][0]['reasons'][0]);
        // Company totals do not turn into table search totals.
        $this->get(route('admin.stocks.index', ['q' => 'not found']))->assertOk()
            ->assertViewHas('overallValue', 0)
            ->assertViewHas('stockReconciliation', fn($r) => $r['stock_value'] === 212.4);
    }

    public function test_overall_bill_discount_is_identified_in_the_difference_details(): void
    {
        [$user, $company, $item] = $this->context();
        $this->postPurchase($item, 10);
        $response = $this->get(route('admin.stocks.index'));
        $response->assertOk()->assertSee('View difference reasons')->assertSee('Overall bill discount');
        $report = $response->viewData('stockReconciliation');
        $this->assertSame(202.4, $report['purchase_amount']);
        $this->assertSame(212.4, $report['stock_value']);
        $this->assertEquals(10, $report['difference']);
        $this->assertEquals(10, $report['unexplained_difference']);
        $this->assertCount(1, $report['bill_issues']);
    }

    public function test_missing_posting_and_price_only_bill_edits_are_reported(): void
    {
        [$user, $company, $item] = $this->context();
        $this->postPurchase($item);
        $bill = PurchaseBill::first();
        $bill->items()->first()->update(['line_total' => 250]);
        $bill->update(['grand_total' => 250]);
        $missing = PurchaseBill::create(['company_id' => $company->id, 'invoice_no' => 'MISSING',
            'billing_date' => '2025-01-01', 'purchase_type' => 'cash', 'status' => 'posted', 'grand_total' => 100]);
        PurchaseBillItem::create(['purchase_bill_id' => $missing->id, 'item_id' => $item->id,
            'quantity' => 1, 'unit' => 'PCS', 'unit_price' => 100, 'line_total' => 100]);
        $otherCompany = Company::create(['name' => 'Other', 'created_by' => $user->id]);
        PurchaseBill::create(['company_id' => $otherCompany->id, 'invoice_no' => 'OTHER', 'billing_date' => '2025-01-01', 'grand_total' => 9999, 'status' => 'posted']);
        PurchaseBill::create(['company_id' => $company->id, 'invoice_no' => 'DRAFT', 'billing_date' => '2025-01-01', 'grand_total' => 9999, 'status' => 'draft']);
        $response = $this->get(route('admin.stocks.index'));
        $response->assertOk()->assertSee('Purchase has no stock posting')->assertSee('Bill quantity or amount differs');
        $report = $response->viewData('stockReconciliation');
        $this->assertEquals(350, $report['purchase_amount']);
        $this->assertCount(2, $report['bill_issues']);
        $this->assertSame(2, $report['bill_count']);
        $this->get(route('admin.stocks.history', ['period' => 'all', 'item_id' => $item->id]))
            ->assertOk()->assertSee('PUR-TEST');
    }

    public function test_production_preserves_total_cost_and_stock_out_is_explained(): void
    {
        [$user, $company, $raw] = $this->context();
        $this->postPurchase($raw);
        $finished = Item::create(['company_id' => $company->id, 'product_type_id' => $raw->product_type_id,
            'item_code' => 'FIN', 'name' => 'Finished', 'unit' => 'PCS', 'purchase_price' => 500, 'track_stock' => true]);
        $accounting = app(AccountingService::class);
        foreach ([[$raw, 'out', 'production_consumption'], [$finished, 'in', 'production_output']] as [$item, $direction, $type]) {
            $accounting->moveStock($item, ['movement_date' => '2026-10-01', 'movement_type' => $type,
                'direction' => $direction, 'quantity' => 1, 'unit_price' => 106.2, 'total_value' => 106.2]);
        }
        $report = app(StockReconciliationService::class)->report($company->id, [], collect());
        $this->assertEquals(0, $report['difference']);
        $this->assertEquals(0, $report['unexplained_difference']);
        $accounting->moveStock($finished, ['movement_date' => '2026-10-02', 'movement_type' => 'special_stock_out',
            'direction' => 'out', 'quantity' => 1, 'unit_price' => 106.2, 'total_value' => 106.2]);
        $report = app(StockReconciliationService::class)->report($company->id, [], collect());
        $this->assertSame(-106.2, $report['difference']);
        $this->assertSame(-106.2, $report['bridge']['special_stock_out']);
        $this->assertEquals(0, $report['unexplained_difference']);
    }

    private function context(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Reconciliation Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create(['company_id' => $company->id, 'code' => 'RAW', 'name' => 'Raw Materials', 'nature' => 'raw_material']);
        $item = Item::create(['company_id' => $company->id, 'product_type_id' => $type->id,
            'item_code' => 'RAW', 'name' => 'Raw Item', 'unit' => 'PCS', 'purchase_price' => 85, 'track_stock' => true]);
        $this->actingAs($user)->withoutMiddleware();
        return [$user, $company, $item];
    }

    private function postPurchase(Item $item, float $discount = 0): void
    {
        $this->post(route('admin.purchases.store'), [
            'purchase_type' => 'cash', 'invoice_no' => 'PUR-TEST', 'billing_date' => '2025-01-01',
            'item_id' => [$item->id], 'quantity' => [2], 'unit' => ['PCS'], 'unit_price' => [100],
            'discount_type' => ['percent'], 'discount_value' => [10], 'tax_percent' => [18],
            'discount_amount' => $discount,
        ])->assertRedirect();
    }
}
