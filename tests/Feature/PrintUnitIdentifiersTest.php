<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\SerialUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintUnitIdentifiersTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_quotation_and_delivery_prints_show_code_and_available_unit_identifiers(): void
    {
        [$user, $company, $item] = $this->context();
        $units = [
            ['serial_no' => 'SERIAL-ONLY', 'key' => 'INTERNAL-KEY-1'],
            ['serial_no' => null, 'vts_sim' => 'VTS-SIM-ONLY', 'key' => 'INTERNAL-KEY-2'],
            ['serial_no' => 'SERIAL-BOTH', 'vts_sim' => 'VTS-SIM-BOTH'],
            ['sim_number' => 'SIM-SEPARATE', 'vts_number' => 'VTS-SEPARATE'],
            ['serial_no' => '<img src=x onerror=alert(1)>'],
            ['key' => 'INTERNAL-KEY-ONLY'],
        ];
        foreach ([
            [SalesInvoice::class, SalesInvoiceItem::class, 'invoice_no', 'billing_date', 'sales_invoice_id', 'sales'],
            [Estimate::class, EstimateItem::class, 'estimate_no', 'estimate_date', 'estimate_id', 'estimates'],
            [DeliveryChallan::class, DeliveryChallanItem::class, 'challan_no', 'challan_date', 'delivery_challan_id', 'delivery-challans'],
        ] as [$documentClass, $lineClass, $number, $date, $foreignKey, $route]) {
            $document = $documentClass::create(['company_id' => $company->id, $number => 'DOC-1',
                $date => '2026-10-13', 'created_by' => $user->id]);
            $lineClass::create([$foreignKey => $document->id, 'item_id' => $item->id,
                'quantity' => 6, 'unit' => 'PCS', 'unit_price' => 100, 'line_total' => 600, 'selected_units' => $units]);
            $response = $this->get(route("admin.$route.print", $document));
            $response->assertOk()->assertSee('ITEM-CODE-1')->assertSee('SERIAL-ONLY')
                ->assertSee('VTS-SIM-ONLY')->assertSee('SERIAL-BOTH')->assertSee('VTS-SIM-BOTH')
                ->assertSee('SIM-SEPARATE')->assertSee('VTS-SEPARATE')
                ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
                ->assertDontSee('<img src=x onerror=alert(1)>', false)
                ->assertDontSee('INTERNAL-KEY')->assertDontSee('No serial');
        }
    }

    public function test_estimate_saves_selected_units_without_reserving_stock_and_carries_them_to_conversion(): void
    {
        [$user, $company, $item] = $this->context();
        app(AccountingService::class)->moveStock($item, [
            'movement_date' => '2026-10-01', 'movement_type' => 'production_output', 'direction' => 'in',
            'quantity' => 2, 'unit_price' => 50, 'total_value' => 100,
            'movement_units' => [['key' => 'U-1', 'serial_no' => 'S-1'], ['key' => 'U-2', 'vts_sim' => 'SIM-2']],
        ]);
        $units = app(SerialUnitService::class)->currentStockUnitsByItem($company->id)[$item->id];
        $data = ['estimate_no' => 'EST-UNITS', 'estimate_date' => '2026-10-13',
            'item_id' => [$item->id], 'quantity' => [2], 'unit_price' => [100], 'unit' => ['PCS'],
            'tax_mode' => ['without_gst'], 'selected_units' => [json_encode($units)]];
        $this->post(route('admin.estimates.store'), $data)->assertRedirect();
        $estimate = Estimate::first();
        $this->assertCount(2, $estimate->items()->first()->selected_units);
        $this->assertSame('2.000', $item->fresh()->current_stock);
        $this->assertSame(1, StockMovement::count());
        $this->get(route('admin.estimates.edit', $estimate))->assertOk()->assertSee('Serial / VTS / SIM');
        $this->get(route('admin.estimates.print', $estimate))->assertOk()->assertSee('S-1')->assertSee('SIM-2');
        $this->get(route('admin.estimates.convert-form', $estimate))->assertOk()
            ->assertViewHas('lineData', fn($lines) => count($lines[0]['selected_units']) === 2);
        $this->put(route('admin.estimates.update', $estimate), $data)->assertRedirect();
        $this->assertCount(2, $estimate->items()->first()->selected_units);
        $data['selected_units'] = [json_encode([['key' => 'not-an-item-unit', 'serial_no' => 'wrong']])];
        $this->put(route('admin.estimates.update', $estimate), $data)->assertStatus(422);
        $this->assertCount(2, $estimate->items()->first()->selected_units);
    }

    private function context(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Print Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create(['company_id' => $company->id, 'code' => 'FG', 'name' => 'Finished Goods', 'nature' => 'finished_goods']);
        $item = Item::create(['company_id' => $company->id, 'product_type_id' => $type->id,
            'item_code' => 'ITEM-CODE-1', 'name' => 'Tracker', 'unit' => 'PCS', 'purchase_price' => 50, 'sale_price' => 100, 'track_stock' => true, 'status' => 'active']);
        $this->actingAs($user);
        return [$user, $company, $item];
    }
}
