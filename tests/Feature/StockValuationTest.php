<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockValuationTest extends TestCase
{
    use RefreshDatabase;

    public function test_totals_match_displayed_item_values_and_respect_company_and_search(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Stock Company', 'created_by' => $user->id]);
        $otherCompany = Company::create(['name' => 'Other Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'RAW',
            'name' => 'Raw Materials',
            'nature' => 'raw_material',
        ]);

        foreach ([
            [$company->id, 'Copper A', 'A', 0.333, 1.01],
            [$company->id, 'Copper B', 'B', 0.333, 1.01],
            [$company->id, 'Relay', 'C', 100, 85],
            [$company->id, 'Empty', 'D', 0, 500],
            [$otherCompany->id, 'Copper Other', 'E', 1000, 1000],
        ] as [$companyId, $name, $code, $quantity, $price]) {
            Item::create([
                'company_id' => $companyId,
                'product_type_id' => $type->id,
                'item_code' => $code,
                'name' => $name,
                'unit' => 'PCS',
                'current_stock' => $quantity,
                'purchase_price' => $price,
                'track_stock' => true,
                'status' => 'active',
            ]);
        }

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('admin.stocks.index'));
        $response->assertOk()->assertViewHas('overallValue', 8500.68)
            ->assertViewHas('overallQty', 100.666)
            ->assertDontSee('Copper Other');
        $response->assertViewHas('items', fn($items) => $items->where('name', 'Copper A')->first()->calculated_stock_value === 0.34);
        $response->assertSee('data-stock-value-paise="34"', false);
        $response->assertSee('data-stock-quantity-milli="333"', false);

        $this->get(route('admin.stocks.index', ['q' => 'Copper']))
            ->assertOk()->assertViewHas('overallValue', 0.68)
            ->assertViewHas('overallQty', 0.666)
            ->assertSee('Filtered Stock Value')->assertDontSee('<b>Relay</b>', false);

        $this->get(route('admin.stocks.index', ['q' => 'missing']))
            ->assertOk()->assertViewHas('overallValue', 0)
            ->assertViewHas('overallQty', 0);
    }
}
