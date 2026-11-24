@extends('layouts.app')

@section('title', 'ETL | Analisis Penjualan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-emerald-800">Extract · Transform · Load</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Pengelolaan Data Transaksi</h1>
            <p class="mt-2 text-slate-600">Tambah transaksi manual atau ganti seluruh data melalui file Excel.</p>
        </div>
        <a href="{{ route('transactions.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">Data Transaksi</a>
    </div>

    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <p class="font-semibold">Data belum dapat diproses:</p>
            <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($importResult)
        <section class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-5" role="status">
            <h2 class="font-semibold text-emerald-900">
                {{ $importResult['mode'] === 'replace' ? 'Data berhasil diganti' : 'Data berhasil ditambahkan' }}
            </h2>
            <p class="mt-1 text-sm text-emerald-800">{{ number_format($importResult['imported'], 0, '.', ',') }} baris berhasil diimpor.</p>
            @if (($importResult['invalid'] ?? 0) > 0)
                <p class="mt-1 text-sm text-amber-800">{{ number_format($importResult['invalid'], 0, '.', ',') }} baris tidak valid dilewati.</p>
            @endif
        </section>
    @endif

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-lg font-semibold">Input Data Manual</h2>
        <form method="POST" action="{{ route('etl.manual') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
            @csrf
            @foreach ([
                ['order_id', 'Order ID', 'text'], ['amount', 'Pendapatan (USD)', 'number'],
                ['profit', 'Keuntungan (USD)', 'number'], ['quantity', 'Quantity', 'number'],
                ['category', 'Category', 'select'], ['sub_category', 'Sub-Category', 'select'],
                ['payment_mode', 'Payment Mode', 'select'], ['order_date', 'Tanggal Transaksi', 'date'],
                ['customer_name', 'Nama Pelanggan', 'text'], ['state', 'State / Wilayah', 'select'],
                ['city', 'City', 'select'],
            ] as [$name, $label, $type])
                <label class="block text-sm font-medium text-slate-700" for="{{ $name }}">
                    {{ $label }}
                    @if ($type === 'select')
                        <select id="{{ $name }}" name="{{ $name }}" required
                                @if (in_array($name, ['sub_category', 'city'], true)) disabled @endif
                                class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100">
                            <option value="">Pilih {{ $label }}</option>
                            @if ($name === 'sub_category')
                                @foreach ($subCategoriesByCategory[old('category', '')] ?? [] as $option)
                                    <option value="{{ $option }}" @selected(old('sub_category') === $option)>{{ $option }}</option>
                                @endforeach
                            @elseif ($name === 'city')
                                @foreach ($citiesByState[old('state', '')] ?? [] as $option)
                                    <option value="{{ $option }}" @selected(old('city') === $option)>{{ $option }}</option>
                                @endforeach
                            @else
                                @foreach ($selectOptions[$name] as $option)
                                    <option value="{{ $option }}" @selected(old($name) === $option)>{{ $option }}</option>
                                @endforeach
                            @endif
                        </select>
                    @else
                        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
                               @if ($type === 'number') step="{{ $name === 'quantity' ? '1' : '0.01' }}" @endif
                               value="{{ old($name) }}" required
                               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100">
                    @endif
                </label>
            @endforeach
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-lg bg-blue-900 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Simpan Transaksi</button>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const subCategoriesByCategory = @json($subCategoriesByCategory);
            const categorySelect = document.getElementById('category');
            const subCategorySelect = document.getElementById('sub_category');
            const previousSubCategory = @json(old('sub_category', ''));
            const citiesByState = @json($citiesByState);
            const stateSelect = document.getElementById('state');
            const citySelect = document.getElementById('city');
            const previousCity = @json(old('city', ''));

            const updateSubCategories = (selectedSubCategory = '') => {
                const subCategories = subCategoriesByCategory[categorySelect.value] || [];
                subCategorySelect.replaceChildren(new Option('Pilih Sub-Category', ''));

                subCategories.forEach((subCategory) => {
                    subCategorySelect.add(new Option(subCategory, subCategory));
                });
                subCategorySelect.disabled = subCategories.length === 0;
                subCategorySelect.value = subCategories.includes(selectedSubCategory) ? selectedSubCategory : '';
            };

            const updateCities = (selectedCity = '') => {
                const cities = citiesByState[stateSelect.value] || [];
                citySelect.replaceChildren(new Option('Pilih City', ''));

                cities.forEach((city) => {
                    citySelect.add(new Option(city, city));
                });
                citySelect.disabled = cities.length === 0;
                citySelect.value = cities.includes(selectedCity) ? selectedCity : '';
            };

            categorySelect.addEventListener('change', () => updateSubCategories());
            stateSelect.addEventListener('change', () => updateCities());
            updateSubCategories(previousSubCategory);
            updateCities(previousCity);
        })();
    </script>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-lg font-semibold">Upload Excel</h2>
        <p class="mt-1 text-sm text-slate-500">Unggah .xlsx atau .xls maksimal 5 MB, lalu pilih cara memproses datanya.</p>
        <form method="POST" action="{{ route('etl.csv.preview') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <label for="import-mode" class="block text-sm font-medium text-slate-700">
                Cara impor
                <select id="import-mode" name="mode" required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                    <option value="append" @selected(($preview['mode'] ?? old('mode', 'append')) === 'append')>Tambah data (pertahankan data lama)</option>
                    <option value="replace" @selected(($preview['mode'] ?? old('mode')) === 'replace')>Ganti seluruh data</option>
                </select>
            </label>
            <label for="excel-file" class="block flex-1 text-sm font-medium text-slate-700">
                File Excel
                <input id="excel-file" name="file" type="file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required
                       class="mt-1.5 block w-full rounded-lg border border-slate-300 text-sm file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-2.5">
            </label>
            <button type="submit" class="rounded-lg bg-emerald-800 px-5 py-2.5 font-semibold text-white hover:bg-emerald-700">Upload Excel</button>
        </form>
    </section>

    @if ($preview)
        <section class="mt-6 rounded-2xl border border-blue-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold">Preview &amp; Konfirmasi</h2>
            <p class="mt-1 text-sm text-slate-600">{{ number_format($preview['total'], 0, '.', ',') }} baris valid siap digunakan.</p>
            <p class="mt-1 text-sm font-medium text-slate-700">
                @if ($preview['mode'] === 'replace')
                    Data lama akan diganti seluruhnya.
                @else
                    Data dari Excel akan ditambahkan dan data lama tetap dipertahankan.
                @endif
            </p>
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full text-left text-xs">
                    <thead class="bg-slate-50"><tr>
                        @foreach (['order_id' => 'Order ID', 'amount' => 'Amount', 'profit' => 'Profit', 'quantity' => 'Qty', 'category' => 'Category', 'order_date' => 'Tanggal'] as $field => $label)
                            <th class="px-3 py-2">{{ $label }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($preview['sample'] as $row)
                            <tr>
                                @foreach (['order_id', 'amount', 'profit', 'quantity', 'category', 'order_date'] as $field)
                                    <td class="whitespace-nowrap px-3 py-2">
                                        @if (in_array($field, ['amount', 'profit'])) {{ \App\Support\Currency::format($row[$field]) }}
                                        @else {{ $row[$field] }} @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('etl.csv.import') }}" class="mt-4"
                  onsubmit="return confirm('{{ $preview['mode'] === 'replace' ? 'Data lama akan diganti seluruhnya dengan '.$preview['total'].' baris data baru. Tindakan ini tidak dapat dibatalkan. Lanjutkan?' : $preview['total'].' baris data akan ditambahkan. Lanjutkan?' }}')">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <button type="submit" class="rounded-lg bg-blue-900 px-5 py-2.5 font-semibold text-white">
                    {{ $preview['mode'] === 'replace' ? 'Ya, Ganti Data' : 'Ya, Tambah Data' }}
                </button>
                <a href="{{ route('etl.index') }}" class="ml-2 rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium">Batal</a>
            </form>
        </section>
    @endif

    <div class="mt-6">
        <x-transaction-table :sales="$sales" :highlight-id="$highlightId" />
    </div>
@endsection
