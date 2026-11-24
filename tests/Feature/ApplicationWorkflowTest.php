<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_public_and_application_pages_require_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Pahami performa penjualan dengan lebih mudah.')
            ->assertSee('Masuk ke Aplikasi');
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Analisis Penjualan')
            ->assertSee('Masuk')
            ->assertSee('data-theme-toggle', false);

        $this->get(route('dashboard.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard.analysis'))->assertRedirect(route('login'));
        $this->get(route('etl.index'))->assertRedirect(route('login'));
        $this->get(route('transactions.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard.what-if'))->assertRedirect(route('login'));
    }

    public function test_theme_toggle_is_available_across_public_and_authenticated_pages(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false);

        $this->actingAs(User::factory()->create());

        foreach ([
            route('dashboard.index'),
            route('dashboard.analysis'),
            route('etl.index'),
            route('transactions.index'),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('data-theme-toggle', false);
        }
    }

    public function test_user_can_sign_in_with_username_and_sign_out(): void
    {
        $user = User::factory()->create(['name' => 'Sales Analyst']);

        $this->post(route('login.store'), ['identifier' => $user->name, 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login.store'), ['identifier' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_is_reported_without_authenticating(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), ['identifier' => '', 'password' => ''])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['identifier', 'password']);

        $this->from(route('login'))
            ->post(route('login.store'), ['identifier' => 'unknown@example.com', 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_etl_manual_categorical_fields_use_values_from_existing_sales(): void
    {
        $this->actingAs(User::factory()->create());
        $this->createSale('ETL-OPTIONS-1', 'Furniture');
        Sale::query()->create([
            'order_id' => 'ETL-OPTIONS-2',
            'amount' => 1200,
            'profit' => 300,
            'quantity' => 2,
            'category' => 'Technology',
            'sub_category' => 'Phones',
            'payment_mode' => 'Debit Card',
            'order_date' => '2025-01-15',
            'customer_name' => 'Sample Customer',
            'state' => 'New York',
            'city' => 'New York City',
            'year_month' => '2025-01',
        ]);

        $this->get(route('etl.index'))
            ->assertOk()
            ->assertSee('<select id="import-mode" name="mode"', false)
            ->assertSee('<select id="category"', false)
            ->assertSee('<select id="sub_category"', false)
            ->assertSee('<select id="payment_mode"', false)
            ->assertSee('<select id="state"', false)
            ->assertSee('<select id="city"', false)
            ->assertSee('value="Furniture"', false)
            ->assertSee('value="Phones"', false)
            ->assertSee('value="Debit Card"', false)
            ->assertSee('value="New York"', false)
            ->assertSee('value="New York City"', false)
            ->assertSee('const subCategoriesByCategory =', false)
            ->assertSee('"Furniture":["General"]', false)
            ->assertSee('"Technology":["Phones"]', false)
            ->assertSee('"California":["Los Angeles"]', false)
            ->assertSee('"New York":["New York City"]', false);
    }

    public function test_manual_entry_is_saved_and_can_be_viewed_in_transaction_detail(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('etl.manual'), [
                'order_id' => 'MANUAL-1',
                'amount' => 1250,
                'profit' => 300,
                'quantity' => 3,
                'category' => 'Furniture',
                'sub_category' => 'Chairs',
                'payment_mode' => 'UPI',
                'order_date' => '2025-03-15',
                'customer_name' => 'Test Customer',
                'state' => 'California',
                'city' => 'Los Angeles',
            ])
            ->assertRedirect(route('etl.index'))
            ->assertSessionHas('status');

        $sale = Sale::query()->where('order_id', 'MANUAL-1')->firstOrFail();
        $this->assertSame('2025-03', $sale->year_month);

        $this->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Tren Pendapatan Bulanan')
            ->assertSee('border-blue-200 bg-blue-50')
            ->assertSee('border-emerald-200 bg-emerald-50')
            ->assertSee('border-violet-200 bg-violet-50')
            ->assertSee('Furniture')
            ->assertSee('$1,250.00');

        $this->get(route('transactions.show', $sale))
            ->assertOk()
            ->assertSee('Test Customer')
            ->assertSee('MANUAL-1');
    }

    public function test_csv_preview_imports_valid_rows_and_reports_invalid_rows(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createSale('EXISTING-1', 'Technology');

        $csv = implode("\n", [
            'Order ID;Amount;Profit;Quantity;Category;Sub-Category;PaymentMode;Order Date;CustomerName;State;City;Year-Month',
            'CSV-VALID;1000;250;2;Furniture;Chairs;UPI;15/01/2025;Valid Customer;California;Los Angeles;2025-01',
            'CSV-INVALID;1000;250;-1;Furniture;Chairs;UPI;15/01/2025;Invalid Customer;California;Los Angeles;2025-01',
        ]);

        $response = $this->post(route('etl.csv.preview'), [
            'file' => UploadedFile::fake()->createWithContent('sales.csv', $csv),
            'mode' => 'replace',
        ])->assertRedirect(route('etl.index'));

        $preview = $response->getSession()->get('csv_preview');
        $this->get(route('etl.index'))
            ->assertOk()
            ->assertSee('Data lama akan diganti seluruhnya')
            ->assertSee('Ya, Ganti Data');
        $this->assertSame(1, $preview['valid']);
        $this->assertSame(1, $preview['invalid']);

        $this->post(route('etl.csv.import'), ['token' => $preview['token']])
            ->assertRedirect(route('etl.index'));

        $this->assertDatabaseHas('sales', ['order_id' => 'CSV-VALID', 'year_month' => '2025-01']);
        $this->assertDatabaseMissing('sales', ['order_id' => 'CSV-INVALID']);
        $this->assertDatabaseMissing('sales', ['order_id' => 'EXISTING-1']);
        $this->get(route('etl.index'))
            ->assertOk()
            ->assertSee('1 baris berhasil diimpor')
            ->assertSee('Data berhasil diganti')
            ->assertSee('1 baris tidak valid dilewati');
    }

    public function test_csv_append_import_keeps_existing_sales(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $this->createSale('EXISTING-APPEND', 'Technology');

        $csv = implode("\n", [
            'Order ID;Amount;Profit;Quantity;Category;Sub-Category;PaymentMode;Order Date;CustomerName;State;City;Year-Month',
            'CSV-APPEND;1000;250;2;Furniture;Chairs;UPI;15/01/2025;Append Customer;California;Los Angeles;2025-01',
        ]);
        $response = $this->post(route('etl.csv.preview'), [
            'file' => UploadedFile::fake()->createWithContent('sales.csv', $csv),
            'mode' => 'append',
        ])->assertRedirect(route('etl.index'));
        $preview = $response->getSession()->get('csv_preview');

        $this->get(route('etl.index'))
            ->assertOk()
            ->assertSee('Data dari Excel akan ditambahkan')
            ->assertSee('Ya, Tambah Data');
        $this->assertSame('append', $preview['mode']);
        $this->post(route('etl.csv.import'), ['token' => $preview['token']])
            ->assertRedirect(route('etl.index'));

        $this->assertDatabaseHas('sales', ['order_id' => 'EXISTING-APPEND']);
        $this->assertDatabaseHas('sales', ['order_id' => 'CSV-APPEND']);
        $this->get(route('etl.index'))
            ->assertOk()
            ->assertSee('Data berhasil ditambahkan');
    }

    public function test_csv_import_accepts_comma_delimiter_and_iso_dates(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());

        $csv = implode("\n", [
            'Order ID,Amount,Profit,Quantity,Category,Sub-Category,PaymentMode,Order Date,CustomerName,State,City,Year-Month',
            'CSV-COMMA,1000,250,2,Furniture,Chairs,UPI,2025-01-15,Comma Customer,California,Los Angeles,2025-01',
        ]);
        $response = $this->post(route('etl.csv.preview'), [
            'file' => UploadedFile::fake()->createWithContent('sales.csv', $csv),
            'mode' => 'append',
        ])->assertRedirect(route('etl.index'));
        $preview = $response->getSession()->get('csv_preview');

        $this->assertSame(1, $preview['valid']);
        $this->post(route('etl.csv.import'), ['token' => $preview['token']])->assertRedirect(route('etl.index'));
        $this->assertDatabaseHas('sales', ['order_id' => 'CSV-COMMA', 'order_date' => '2025-01-15']);
    }

    public function test_csv_preview_rejects_rows_with_a_different_number_of_columns(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());

        $csv = implode("\n", [
            'Order ID;Amount;Profit;Quantity;Category;Sub-Category;PaymentMode;Order Date;CustomerName;State;City;Year-Month',
            'CSV-EXTRA;1000;250;2;Furniture;Chairs;UPI;15/01/2025;Extra Customer;California;Los Angeles;2025-01;unexpected',
        ]);
        $response = $this->post(route('etl.csv.preview'), [
            'file' => UploadedFile::fake()->createWithContent('sales.csv', $csv),
            'mode' => 'append',
        ])->assertRedirect(route('etl.index'));
        $preview = $response->getSession()->get('csv_preview');

        $this->assertSame(0, $preview['valid']);
        $this->assertSame(1, $preview['invalid']);
        $this->assertStringContainsString('Jumlah kolom', $preview['errors'][0]['message']);
    }

    public function test_transaction_search_and_category_filter_are_applied(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createSale('MATCH-1', 'Furniture');
        $this->createSale('OTHER-1', 'Technology');

        $this->get(route('transactions.index', ['search' => 'MATCH', 'category' => 'Furniture']))
            ->assertOk()
            ->assertSee('MATCH-1')
            ->assertDontSee('OTHER-1');
    }

    private function createSale(string $orderId, string $category): Sale
    {
        return Sale::query()->create([
            'order_id' => $orderId,
            'amount' => 1200,
            'profit' => 300,
            'quantity' => 2,
            'category' => $category,
            'sub_category' => 'General',
            'payment_mode' => 'UPI',
            'order_date' => '2025-01-15',
            'customer_name' => 'Sample Customer',
            'state' => 'California',
            'city' => 'Los Angeles',
            'year_month' => '2025-01',
        ]);
    }
}
