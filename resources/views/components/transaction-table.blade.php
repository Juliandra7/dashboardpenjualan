@props(['sales', 'highlightId' => null])

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <h2 class="font-semibold">Daftar Transaksi</h2>
        <p class="text-sm text-slate-500">{{ number_format($sales->total(), 0, ',', '.') }} data ditemukan</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Order ID</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Pelanggan</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                    <th class="px-4 py-3 text-right">Profit</th>
                    <th class="px-4 py-3 text-right">Qty</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($sales as $sale)
                    <tr id="sale-{{ $sale->id }}" class="hover:bg-slate-50 {{ (int) $highlightId === $sale->id ? 'bg-emerald-50 ring-1 ring-inset ring-emerald-200' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $sale->order_id }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $sale->order_date->format('d/m/Y') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $sale->customer_name }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $sale->category }} <span class="text-slate-400">/ {{ $sale->sub_category }}</span></td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ \App\Support\Currency::format($sale->amount) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ \App\Support\Currency::format($sale->profit) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ number_format($sale->quantity, 0, '.', ',') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button type="button" onclick="document.getElementById('edit-sale-{{ $sale->id }}').showModal()" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-medium hover:bg-slate-50" title="Edit {{ $sale->order_id }}">Edit</button>
                            <form method="POST" action="{{ route('transactions.destroy', $sale) }}" class="inline"
                                  onsubmit="return confirm('Yakin ingin menghapus transaksi {{ addslashes($sale->order_id) }} milik {{ addslashes($sale->customer_name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ml-1 rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50" title="Hapus {{ $sale->order_id }}">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">Belum ada data transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($sales->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">{{ $sales->links() }}</div>
    @endif
</section>

@foreach ($sales as $sale)
    <dialog id="edit-sale-{{ $sale->id }}" class="w-[min(640px,calc(100%-2rem))] rounded-2xl border border-slate-200 p-0 shadow-xl">
        <form method="POST" action="{{ route('transactions.update', $sale) }}" class="p-5">
            @csrf
            @method('PUT')
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Edit Transaksi {{ $sale->order_id }}</h2>
                <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border px-3 py-1 text-sm">Tutup</button>
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'order_id' => ['Order ID', 'text'], 'amount' => ['Pendapatan (USD)', 'number'],
                    'profit' => ['Keuntungan (USD)', 'number'], 'quantity' => ['Quantity', 'number'],
                    'category' => ['Category', 'text'], 'sub_category' => ['Sub-Category', 'text'],
                    'payment_mode' => ['Payment Mode', 'text'], 'order_date' => ['Tanggal Transaksi', 'date'],
                    'customer_name' => ['Nama Pelanggan', 'text'], 'state' => ['State / Wilayah', 'text'],
                    'city' => ['City', 'text'],
                ] as $field => [$label, $type])
                    <label class="text-sm font-medium" for="edit-{{ $sale->id }}-{{ $field }}">
                        {{ $label }}
                        <input id="edit-{{ $sale->id }}-{{ $field }}" name="{{ $field }}" type="{{ $type }}"
                               value="{{ $field === 'order_date' ? $sale->order_date->format('Y-m-d') : $sale->{$field} }}"
                               @if ($type === 'number') step="{{ $field === 'quantity' ? '1' : '0.01' }}" @endif
                               required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                @endforeach
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border border-slate-300 px-4 py-2">Batal</button>
                <button type="submit" class="rounded-lg bg-blue-900 px-4 py-2 font-semibold text-white">Simpan</button>
            </div>
        </form>
    </dialog>
@endforeach
