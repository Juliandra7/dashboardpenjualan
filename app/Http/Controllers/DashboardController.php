<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $summary = Sale::query()
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(amount), 0) as revenue, COALESCE(SUM(profit), 0) as profit')
            ->first();

        $monthlySales = Sale::query()
            ->selectRaw('year_month as period, SUM(amount) as amount')
            ->groupBy('year_month')
            ->orderByDesc('year_month')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();
        $topCategories = Sale::query()
            ->selectRaw('category, SUM(amount) as amount')
            ->groupBy('category')
            ->orderByDesc('amount')
            ->limit(5)
            ->get();

        return view('dashboard.home', compact('summary', 'monthlySales', 'topCategories'));
    }
}
