<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Sale::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('order_id', 'like', '%'.$search.'%')
                        ->orWhere('customer_name', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['state'] ?? null, fn ($query, $state) => $query->where('state', $state));

        $sales = $query->orderByDesc('order_date')->orderByDesc('id')->paginate(25)->withQueryString();
        $categories = Sale::query()->distinct()->orderBy('category')->pluck('category');
        $states = Sale::query()->distinct()->orderBy('state')->pluck('state');

        return view('transactions.index', compact('sales', 'categories', 'states', 'filters'));
    }

    public function show(Sale $sale): View
    {
        return view('transactions.show', compact('sale'));
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'profit' => ['required', 'numeric', 'between:-9999999999999.99,9999999999999.99'],
            'quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'category' => ['required', 'string', 'max:255'],
            'sub_category' => ['required', 'string', 'max:255'],
            'payment_mode' => ['required', 'string', 'max:255'],
            'order_date' => ['required', 'date', 'after_or_equal:1000-01-01'],
            'customer_name' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
        ]);

        $data['order_date'] = Carbon::parse($data['order_date'])->toDateString();
        $data['year_month'] = Carbon::parse($data['order_date'])->format('Y-m');
        $sale->update($data);

        return back()->with('status', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $identity = $sale->order_id;
        $sale->delete();

        return back()->with('status', "Transaksi {$identity} berhasil dihapus.");
    }
}
