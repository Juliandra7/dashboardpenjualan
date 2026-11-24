<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatIfAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_dashboard_renders_the_what_if_controls(): void
    {
        $this->get(route('dashboard.analysis'))
            ->assertOk()
            ->assertSee('What-If Analysis')
            ->assertSee('State / Wilayah')
            ->assertSee('border-blue-200 bg-blue-50')
            ->assertSee('border-emerald-200 bg-emerald-50')
            ->assertSee('border-amber-200 bg-amber-50')
            ->assertSee('border-violet-200 bg-violet-50');
    }

    public function test_dashboard_pages_and_data_endpoints_aggregate_sales(): void
    {
        $this->createSale();

        $this->get(route('dashboard.index'))->assertOk();
        $this->get(route('dashboard.analysis'))->assertOk();

        $this->getJson(route('dashboard.level1'))
            ->assertOk()
            ->assertJsonPath('trend.labels.0', '2025');

        $this->getJson(route('dashboard.level2', ['category' => 'Furniture']))
            ->assertOk()
            ->assertJsonPath('trend.labels.0', '2025-01');

        $this->getJson(route('dashboard.months', ['year' => '2025']))
            ->assertOk()
            ->assertJsonPath('trend.labels.0', '2025-01');
    }

    public function test_amount_scenario_applies_filters_and_projects_profit_with_revenue(): void
    {
        $this->createSale();
        $this->createSale([
            'order_id' => 'other-order',
            'amount' => 5000,
            'profit' => 1000,
            'year_month' => '2024-02',
            'order_date' => '2024-02-01',
        ]);

        $this->getJson(route('dashboard.what-if', [
            'year' => '2025',
            'month' => '01',
            'category' => 'Furniture',
            'sub_category' => 'Chairs',
            'state' => 'California',
            'city' => 'Los Angeles',
            'metric' => 'amount',
            'change' => 10,
        ]))
            ->assertOk()
            ->assertJsonPath('actual.amount', 1000)
            ->assertJsonPath('actual.profit', 250)
            ->assertJsonPath('actual.quantity', 2)
            ->assertJsonPath('projected.amount', 1100)
            ->assertJsonPath('projected.profit', 275)
            ->assertJsonPath('projected.quantity', 2);
    }

    public function test_profit_and_quantity_scenarios_only_change_the_selected_metric(): void
    {
        $this->createSale();

        $this->getJson(route('dashboard.what-if', ['metric' => 'profit', 'change' => 20]))
            ->assertOk()
            ->assertJsonPath('projected.amount', 1000)
            ->assertJsonPath('projected.profit', 300)
            ->assertJsonPath('projected.quantity', 2);

        $this->getJson(route('dashboard.what-if', ['metric' => 'quantity', 'change' => 50]))
            ->assertOk()
            ->assertJsonPath('projected.amount', 1000)
            ->assertJsonPath('projected.profit', 250)
            ->assertJsonPath('projected.quantity', 3);
    }

    public function test_change_outside_supported_range_is_rejected(): void
    {
        $this->getJson(route('dashboard.what-if', ['metric' => 'amount', 'change' => -101]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('change');
    }

    private function createSale(array $overrides = []): Sale
    {
        return Sale::create(array_merge([
            'order_id' => 'sample-order',
            'amount' => 1000,
            'profit' => 250,
            'quantity' => 2,
            'category' => 'Furniture',
            'sub_category' => 'Chairs',
            'payment_mode' => 'UPI',
            'order_date' => '2025-01-15',
            'customer_name' => 'Sample Customer',
            'state' => 'California',
            'city' => 'Los Angeles',
            'year_month' => '2025-01',
        ], $overrides));
    }
}
