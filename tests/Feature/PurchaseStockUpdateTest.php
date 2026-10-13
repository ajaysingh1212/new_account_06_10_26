<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\PurchaseBill;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseStockUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_only_update_revalues_stock_and_resaving_is_idempotent(): void
    {
        [$item, $bill, $data] = $this->purchase();
        $data['unit_price'] = [10];
        $this->put(route('admin.purchases.update', $bill), $data)->assertRedirect();
        $this->assertSame('500.000', $item->fresh()->current_stock);
        $this->assertSame('5000.00', $item->fresh()->stock_value);
        $this->assertDatabaseHas('stock_movements', ['reference_id' => $bill->id,
            'movement_type' => 'purchase_value_adjustment', 'quantity' => 0, 'total_value' => 2500]);
        $count = StockMovement::count();
        $this->put(route('admin.purchases.update', $bill), $data)->assertRedirect();
        $this->assertSame($count, StockMovement::count());
        $this->assertMatches();
        $data['unit_price'] = [4];
        $this->put(route('admin.purchases.update', $bill), $data)->assertRedirect();
        $this->assertSame('500.000', $item->fresh()->current_stock);
        $this->assertSame('2000.00', $item->fresh()->stock_value);
        $this->assertMatches();
    }

    public function test_unchanged_resave_repairs_an_old_price_edit_and_preserves_line_ids(): void
    {
        [$item, $bill, $data] = $this->purchase();
        // Reproduce the old bug: bill was edited to 10 but its 5-rate posting stayed unchanged.
        $line = $bill->items()->first();
        $line->update(['unit_price' => 10, 'line_total' => 5000]);
        $bill->update(['subtotal' => 5000, 'grand_total' => 5000]);
        $data['unit_price'] = [10];
        $this->put(route('admin.purchases.update', $bill), $data)->assertRedirect();
        $this->assertSame($line->id, $bill->items()->first()->id);
        $this->assertSame('500.000', $item->fresh()->current_stock);
        $this->assertSame('5000.00', $item->fresh()->stock_value);
        $this->assertMatches();
    }

    public function test_quantity_and_value_can_change_in_opposite_directions(): void
    {
        [$item, $bill, $data] = $this->purchase();
        $data['quantity'] = [600];
        $data['unit_price'] = [2];
        $this->put(route('admin.purchases.update', $bill), $data)->assertRedirect();
        $this->assertSame('600.000', $item->fresh()->current_stock);
        $this->assertSame('1200.00', $item->fresh()->stock_value);
        $this->assertMatches();
    }

    public function test_tax_line_discount_and_overall_discount_update_stock_value(): void
    {
        [$item, $bill, $data] = $this->purchase();
        $data['tax_percent'] = [18];
        $data['discount_value'] = [10];
        $data['discount_amount'] = 100;
        $this->put(route('admin.purchases.update', $bill), $data)->assertRedirect();
        $this->assertSame('2555.00', $item->fresh()->stock_value);
        $this->assertMatches();
        $data['discount_amount'] = 200;
        $this->put(route('admin.purchases.update', $bill), $data)->assertRedirect();
        $this->assertSame('2455.00', $item->fresh()->stock_value);
        $this->assertMatches();
    }

    public function test_quantity_reduction_with_higher_cost_keeps_both_balances_correct(): void
    {
        [$item, $bill, $data] = $this->purchase();
        $data['quantity'] = [400];
        $data['unit_price'] = [10];
        $this->put(route('admin.purchases.update', $bill), $data)->assertRedirect();
        $this->assertSame('400.000', $item->fresh()->current_stock);
        $this->assertSame('4000.00', $item->fresh()->stock_value);
        $this->assertMatches();
    }

    public function test_metadata_only_resave_does_not_readd_consumed_stock(): void
    {
        [$item, $bill, $data] = $this->purchase();
        app(\App\Services\AccountingService::class)->moveStock($item, [
            'movement_date' => '2026-06-01', 'movement_type' => 'sale', 'direction' => 'out',
            'quantity' => 500, 'unit_price' => 5, 'total_value' => 2500,
        ]);
        $count = StockMovement::count();
        $data['notes'] = 'Metadata edit after consumption';
        $this->put(route('admin.purchases.update', $bill), $data)->assertRedirect();
        $this->assertSame('0.000', $item->fresh()->current_stock);
        $this->assertSame('0.00', $item->fresh()->stock_value);
        $this->assertSame($count, StockMovement::count());
    }

    private function assertMatches(): void
    {
        $this->get(route('admin.stocks.index'))->assertOk()->assertViewHas('stockReconciliation',
            fn($r) => $r['difference'] == 0 && $r['unexplained_difference'] == 0 && $r['bill_issues'] === []);
    }

    private function purchase(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Purchase Update', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create(['company_id' => $company->id, 'code' => 'RAW', 'name' => 'Raw Material', 'nature' => 'raw_material']);
        $item = Item::create(['company_id' => $company->id, 'product_type_id' => $type->id,
            'item_code' => 'ROLL', 'name' => 'Packing Roll GPS', 'unit' => 'PCS', 'purchase_price' => 10, 'track_stock' => true]);
        $this->actingAs($user);
        $data = ['purchase_type' => 'cash', 'invoice_no' => '00000005', 'billing_date' => '2026-05-26',
            'item_id' => [$item->id], 'quantity' => [500], 'unit' => ['PCS'], 'unit_price' => [5],
            'discount_type' => ['percent'], 'discount_value' => [0], 'tax_percent' => [0], 'discount_amount' => 0];
        $this->post(route('admin.purchases.store'), $data)->assertRedirect();
        return [$item, PurchaseBill::first(), $data];
    }
}
