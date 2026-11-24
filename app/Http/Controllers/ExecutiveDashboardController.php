<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutiveDashboardController extends Controller
{
    /** Koordinat pusat tiap State (untuk Leaflet, Level 1). */
    private const STATE_COORDS = [
        'California' => [36.7783, -119.4179],
        'Florida' => [27.9944, -81.7603],
        'Illinois' => [40.6331, -89.3985],
        'New York' => [42.9538, -75.5268],
        'Ohio' => [40.4173, -82.9071],
        'Texas' => [31.9686, -99.9018],
    ];

    /** Koordinat tiap City (untuk Leaflet, Level 2). */
    private const CITY_COORDS = [
        'Los Angeles' => [34.0522, -118.2437],
        'San Diego' => [32.7157, -117.1611],
        'San Francisco' => [37.7749, -122.4194],
        'Miami' => [25.7617, -80.1918],
        'Orlando' => [28.5383, -81.3792],
        'Tampa' => [27.9506, -82.4572],
        'Chicago' => [41.8781, -87.6298],
        'Springfield' => [39.7817, -89.6501],
        'Peoria' => [40.6936, -89.5890],
        'Buffalo' => [42.8864, -78.8784],
        'New York City' => [40.7128, -74.0060],
        'Rochester' => [43.1566, -77.6088],
        'Columbus' => [39.9612, -82.9988],
        'Cincinnati' => [39.1031, -84.5120],
        'Cleveland' => [41.4993, -81.6944],
        'Dallas' => [32.7767, -96.7970],
        'Austin' => [30.2672, -97.7431],
        'Houston' => [29.7604, -95.3698],
    ];

    /* ------------------------------------------------------------------
     |  Halaman utama
     * ------------------------------------------------------------------ */
    public function index(): View
    {
        // Opsi filter dibaca dari data, jadi selalu cocok dengan isi tabel.
        $years = Sale::query()
            ->selectRaw('SUBSTR(`year_month`, 1, 4) as y')
            ->distinct()->orderBy('y')->pluck('y');

        $paymentModes = Sale::query()
            ->distinct()->orderBy('payment_mode')->pluck('payment_mode');

        $monthsByYear = Sale::query()
            ->selectRaw('SUBSTR(`year_month`, 1, 4) as year, SUBSTR(`year_month`, 6, 2) as month')
            ->distinct()->orderBy('year')->orderBy('month')->get()
            ->groupBy('year')->map(fn ($rows) => $rows->pluck('month')->values());

        $subCategoriesByCategory = Sale::query()
            ->select(['category', 'sub_category'])->distinct()
            ->orderBy('category')->orderBy('sub_category')->get()
            ->groupBy('category')->map(fn ($rows) => $rows->pluck('sub_category')->values());

        $citiesByState = Sale::query()
            ->select(['state', 'city'])->distinct()
            ->orderBy('state')->orderBy('city')->get()
            ->groupBy('state')->map(fn ($rows) => $rows->pluck('city')->values());

        $categories = Sale::query()->distinct()->orderBy('category')->pluck('category');
        $states = Sale::query()->distinct()->orderBy('state')->pluck('state');

        return view('dashboard', compact(
            'years',
            'paymentModes',
            'monthsByYear',
            'categories',
            'states',
            'subCategoriesByCategory',
            'citiesByState'
        ));
    }

    /* ------------------------------------------------------------------
     |  LEVEL 1 : tahun, kategori, state
     * ------------------------------------------------------------------ */
    public function level1(Request $request): JsonResponse
    {
        $filters = $this->filters($request);
        $base = $this->baseQuery($filters);

        // Tren tahunan sengaja TIDAK memakai filter tahun,
        // supaya garis tren tetap terlihat saat satu tahun dipilih.
        $yearly = $this->baseQuery(['year' => null] + $filters)
            ->selectRaw('SUBSTR(`year_month`, 1, 4) as label, SUM(amount) as amount, SUM(profit) as profit')
            ->groupBy('label')->orderBy('label')->get();

        $byCategory = $this->aggregate(clone $base, 'category');

        $byState = (clone $base)
            ->selectRaw('state as label, SUM(amount) as amount, SUM(profit) as profit, SUM(quantity) as qty')
            ->groupBy('state')->get();

        $insightBase = clone $base;
        $total = $insightBase->selectRaw(
            'COUNT(*) as transactions, COALESCE(SUM(amount), 0) as revenue'
        )->first();
        $topCategoryRow = (clone $base)
            ->selectRaw('category, SUM(amount) as amount')
            ->groupBy('category')->orderByDesc('amount')->first();

        $trend = null;
        $periods = (clone $base)
            ->select('year_month')->distinct()->orderBy('year_month')->pluck('year_month');
        if ($periods->count() >= 2) {
            $currentPeriod = $periods->last();
            $previousPeriod = $periods->slice(-2, 1)->first();
            $currentRevenue = (clone $base)->where('year_month', $currentPeriod)->sum('amount');
            $previousRevenue = (clone $base)->where('year_month', $previousPeriod)->sum('amount');
            $trend = [
                'direction' => $currentRevenue >= $previousRevenue ? 'naik' : 'turun',
                'percent' => $previousRevenue > 0
                    ? round(abs($currentRevenue - $previousRevenue) / $previousRevenue * 100, 1)
                    : ($currentRevenue > 0 ? 100.0 : 0.0),
            ];
        }
        $insight = [
            'totalRevenue' => (float) $total->revenue,
            'transactions' => (int) $total->transactions,
            'trend' => $trend,
            'topCategory' => $topCategoryRow ? (string) $topCategoryRow->category : null,
            'topCategoryRevenue' => $topCategoryRow ? (float) $topCategoryRow->amount : 0.0,
        ];

        return response()->json([
            'level' => 1,
            'kpi' => $this->kpi(clone $base),
            'trend' => $this->trendPayload($yearly),
            'bar' => $this->barPayload($byCategory),
            'map' => $this->mapPayload($byState, self::STATE_COORDS),
            'insight' => $insight,
            'selected' => null,
        ]);
    }

    /* ------------------------------------------------------------------
     |  LEVEL 2 : bulan, sub-kategori, city (untuk 1 kategori terpilih)
     * ------------------------------------------------------------------ */
    public function level2(Request $request): JsonResponse
    {
        $request->validate(['category' => 'required|string|max:100']);

        $filters = $this->filters($request);
        $base = $this->baseQuery($filters)->where('category', (string) $request->input('category'));

        $monthly = (clone $base)
            ->selectRaw('`year_month` as label, SUM(amount) as amount, SUM(profit) as profit')
            ->groupBy('year_month')->orderBy('year_month')->get();

        $bySub = $this->aggregate(clone $base, 'sub_category');

        $byCity = (clone $base)
            ->selectRaw('city as label, SUM(amount) as amount, SUM(profit) as profit, SUM(quantity) as qty')
            ->groupBy('city')->get();

        return response()->json([
            'level' => 2,
            'kpi' => $this->kpi(clone $base),
            'trend' => $this->trendPayload($monthly),
            'bar' => $this->barPayload($bySub),
            'map' => $this->mapPayload($byCity, self::CITY_COORDS),
            'selected' => (string) $request->input('category'),
            'insight' => [
                'totalRevenue' => (float) (clone $base)->sum('amount'),
                'transactions' => (int) (clone $base)->count(),
                'trend' => null,
                'topCategory' => (string) $request->input('category'),
                'topCategoryRevenue' => (float) (clone $base)->sum('amount'),
            ],
        ]);
    }

    /* ------------------------------------------------------------------
     |  LEVEL 1 (klik state di peta) : rincian city dalam satu state
     * ------------------------------------------------------------------ */
    public function cities(Request $request): JsonResponse
    {
        $request->validate(['state' => 'required|string|max:100']);

        $filters = $this->filters($request);
        $stateName = (string) $request->input('state');

        $rows = $this->baseQuery($filters)
            ->where('state', $stateName)
            ->selectRaw('city as label, SUM(amount) as amount, SUM(profit) as profit, SUM(quantity) as qty')
            ->groupBy('city')->orderByDesc('amount')->get();

        return response()->json([
            'state' => $stateName,
            'kpi' => $this->kpi($this->baseQuery($filters)->where('state', $stateName)),
            'cities' => $this->mapPayload($rows, self::CITY_COORDS),
        ]);
    }

    /* ------------------------------------------------------------------
     |  LEVEL 1 (klik titik tahun di line chart) : tren per bulan di tahun itu
     * ------------------------------------------------------------------ */
    public function months(Request $request): JsonResponse
    {
        $request->validate(['year' => ['required', 'regex:/^\d{4}$/']]);

        $year = (string) $request->input('year');

        // Hanya filter Payment mode yang dipakai; tahun ditentukan oleh titik yang diklik.
        $rows = $this->baseQuery(['year' => null] + $this->filters($request))
            ->where('year_month', 'like', $year.'-%')
            ->selectRaw('`year_month` as label, SUM(amount) as amount, SUM(profit) as profit')
            ->groupBy('year_month')->orderBy('year_month')->get();

        return response()->json([
            'year' => $year,
            'trend' => $this->trendPayload($rows),
        ]);
    }

    public function whatIf(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'regex:/^\d{4}$/'],
            'month' => ['nullable', 'regex:/^(0[1-9]|1[0-2])$/'],
            'category' => ['nullable', 'string', 'max:100'],
            'sub_category' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'metric' => ['required', 'in:amount,profit,quantity'],
            'change' => ['required', 'numeric', 'between:-100,1000'],
        ]);

        $change = (float) $validated['change'];
        $query = Sale::query()
            ->when($validated['year'] ?? null, fn ($q, $year) => $q->where('year_month', 'like', $year.'-%'))
            ->when($validated['month'] ?? null, fn ($q, $month) => $q->where('year_month', 'like', '%-'.$month))
            ->when($validated['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($validated['sub_category'] ?? null, fn ($q, $subCategory) => $q->where('sub_category', $subCategory))
            ->when($validated['state'] ?? null, fn ($q, $state) => $q->where('state', $state))
            ->when($validated['city'] ?? null, fn ($q, $city) => $q->where('city', $city));

        $row = $query->selectRaw(
            'COALESCE(SUM(amount), 0) as amount, COALESCE(SUM(profit), 0) as profit, COALESCE(SUM(quantity), 0) as quantity'
        )->first();

        $actual = [
            'amount' => (float) $row->amount,
            'profit' => (float) $row->profit,
            'quantity' => (int) $row->quantity,
        ];
        $projected = $actual;
        $factor = 1 + ($change / 100);

        if ($validated['metric'] === 'amount') {
            $projected['amount'] = round($projected['amount'] * $factor, 2);
            $projected['profit'] = round($projected['profit'] * $factor, 2);
        } elseif ($validated['metric'] === 'quantity') {
            $projected['quantity'] = (int) round($projected['quantity'] * $factor);
        } else {
            $projected[$validated['metric']] = round($projected[$validated['metric']] * $factor, 2);
        }

        return response()->json([
            'actual' => $actual,
            'projected' => $projected,
            'metric' => $validated['metric'],
            'change' => $change,
        ]);
    }

    /* ------------------------------------------------------------------
     |  Helper
     * ------------------------------------------------------------------ */

    /** Baca & validasi filter dari query string. */
    private function filters(Request $request): array
    {
        $year = $request->input('year');
        $pay = $request->input('payment_mode');

        return [
            'year' => ($year && preg_match('/^\d{4}$/', $year)) ? $year : null,
            'payment_mode' => ($pay && $pay !== 'all') ? $pay : null,
        ];
    }

    /** Query dasar dengan filter Tahun & Payment Mode. */
    private function baseQuery(array $filters): Builder
    {
        return Sale::query()
            ->when($filters['year'] ?? null, fn ($q, $y) => $q->where('year_month', 'like', $y.'-%'))
            ->when($filters['payment_mode'] ?? null, fn ($q, $p) => $q->where('payment_mode', $p));
    }

    /** 4 KPI utama. Profit margin = profit / pendapatan * 100. */
    private function kpi(Builder $query): array
    {
        $row = $query->selectRaw(
            'COALESCE(SUM(amount),0) as revenue, COALESCE(SUM(profit),0) as profit, COALESCE(SUM(quantity),0) as qty'
        )->first();

        $revenue = (float) $row->revenue;
        $profit = (float) $row->profit;

        return [
            'revenue' => $revenue,
            'profit' => $profit,
            'qty' => (int) $row->qty,
            'margin' => $revenue > 0 ? round($profit / $revenue * 100, 1) : 0.0,
        ];
    }

    /** Jumlahkan amount & quantity per kolom (kategori / sub-kategori). */
    private function aggregate(Builder $query, string $column)
    {
        return $query
            ->selectRaw("{$column} as label, SUM(amount) as amount, SUM(quantity) as qty")
            ->groupBy($column)->orderByDesc('amount')->get();
    }

    private function trendPayload($rows): array
    {
        return [
            'labels' => $rows->pluck('label')->values(),
            'sales' => $rows->map(fn ($r) => (float) $r->amount)->values(),
            'profit' => $rows->map(fn ($r) => (float) $r->profit)->values(),
        ];
    }

    private function barPayload($rows): array
    {
        return [
            'labels' => $rows->pluck('label')->values(),
            'amount' => $rows->map(fn ($r) => (float) $r->amount)->values(),
            'qty' => $rows->map(fn ($r) => (int) $r->qty)->values(),
        ];
    }

    /** Gabungkan hasil agregasi dengan koordinat untuk marker Leaflet. */
    private function mapPayload($rows, array $coords): array
    {
        return $rows->filter(fn ($r) => isset($coords[$r->label]))
            ->map(fn ($r) => [
                'name' => $r->label,
                'lat' => $coords[$r->label][0],
                'lng' => $coords[$r->label][1],
                'amount' => (float) $r->amount,
                'profit' => (float) $r->profit,
                'qty' => (int) $r->qty,
            ])->values()->all();
    }
}
