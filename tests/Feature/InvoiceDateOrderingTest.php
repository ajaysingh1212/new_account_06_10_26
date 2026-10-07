<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InvoiceDateOrderingTest extends TestCase
{
    use RefreshDatabase;

    public static function invoiceListings(): array
    {
        return [
            'sales' => [SalesInvoice::class, 'admin.sales.index', 'invoices', 'sale_type'],
            'purchases' => [PurchaseBill::class, 'admin.purchases.index', 'bills', 'purchase_type'],
        ];
    }

    #[DataProvider('invoiceListings')]
    public function test_listing_orders_by_invoice_date_including_backdated_entries(string $model, string $route, string $viewKey, string $typeField): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Date Ordering Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);

        // Create November last to simulate a backdated entry after December and January.
        foreach (['DECEMBER' => '2026-12-20', 'JANUARY' => '2027-01-05', 'DECEMBER-EARLY' => '2026-12-01', 'NOVEMBER' => '2026-11-30'] as $number => $date) {
            $invoice = $model::create([
                'company_id' => $company->id,
                'invoice_no' => $number,
                'billing_date' => $date,
                $typeField => 'cash',
                'status' => 'posted',
                'created_by' => $user->id,
            ]);
            $invoice->forceFill(['created_at' => '2027-02-'.str_pad((string) $invoice->id, 2, '0', STR_PAD_LEFT).' 12:00:00'])->save();
        }

        $this->actingAs($user)
            ->get(route($route))
            ->assertOk()
            ->assertViewHas($viewKey, fn ($rows) => $rows->pluck('invoice_no')->all() === ['JANUARY', 'DECEMBER', 'DECEMBER-EARLY', 'NOVEMBER'])
            ->assertSeeInOrder(['05 Jan 2027', '20 Dec 2026', '01 Dec 2026', '30 Nov 2026'])
            ->assertSee('data-order="2027-01-05"', false)
            ->assertSee("order:[[1, 'desc']]", false);
    }
}
