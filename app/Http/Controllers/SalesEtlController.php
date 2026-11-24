<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\SalesCsvImporter;
use App\Services\SalesExcelImporter;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class SalesEtlController extends Controller
{
    public function index(Request $request): View
    {
        $previews = $request->session()->get('etl_previews', []);
        foreach ($previews as $token => $preview) {
            if (now()->timestamp - $preview['created_at'] > 7200) {
                Storage::disk('local')->delete($preview['path']);
                unset($previews[$token]);
            }
        }
        $request->session()->put('etl_previews', $previews);

        $subCategoriesByCategory = Sale::query()
            ->select(['category', 'sub_category'])->distinct()
            ->orderBy('category')->orderBy('sub_category')->get()
            ->groupBy('category')->map(fn ($rows) => $rows->pluck('sub_category')->values());

        $citiesByState = Sale::query()
            ->select(['state', 'city'])->distinct()
            ->orderBy('state')->orderBy('city')->get()
            ->groupBy('state')->map(fn ($rows) => $rows->pluck('city')->values());

        return view('etl.index', [
            'preview' => session('csv_preview'),
            'importResult' => session('csv_import_result'),
            'highlightId' => session('highlight_sale_id'),
            'subCategoriesByCategory' => $subCategoriesByCategory,
            'citiesByState' => $citiesByState,
            'selectOptions' => [
                'category' => Sale::query()->distinct()->orderBy('category')->pluck('category'),
                'payment_mode' => Sale::query()->distinct()->orderBy('payment_mode')->pluck('payment_mode'),
                'state' => Sale::query()->distinct()->orderBy('state')->pluck('state'),
            ],
            'sales' => Sale::query()->orderByDesc('order_date')->orderByDesc('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        $date = Carbon::parse($data['order_date']);
        $data['order_date'] = $date->toDateString();
        $data['year_month'] = $date->format('Y-m');
        $sale = Sale::query()->create($data);

        return redirect()->route('etl.index')
            ->with('status', 'Transaksi berhasil disimpan.')
            ->with('highlight_sale_id', $sale->id);
    }

    public function preview(Request $request, SalesExcelImporter $excelImporter, SalesCsvImporter $csvImporter): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:5120'],
            'mode' => ['required', 'in:append,replace'],
        ], [
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            throw ValidationException::withMessages(['file' => 'File harus berformat Excel (.xlsx atau .xls).']);
        }
        $storedExtension = $extension === 'csv' ? 'csv' : 'xlsx';
        $path = $file->storeAs(
            'etl-previews/'.$request->user()->getAuthIdentifier(),
            Str::uuid().'.'.$storedExtension,
            'local'
        );

        if ($path === false) {
            throw ValidationException::withMessages(['file' => 'File Excel gagal disimpan untuk proses preview.']);
        }

        try {
            $report = $extension === 'csv'
                ? $csvImporter->process(Storage::disk('local')->path($path))
                : $excelImporter->process(Storage::disk('local')->path($path));
        } catch (InvalidArgumentException $exception) {
            Storage::disk('local')->delete($path);
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        } catch (\RuntimeException $exception) {
            Storage::disk('local')->delete($path);
            throw ValidationException::withMessages(['file' => 'File Excel gagal dibaca. Pastikan file tidak rusak dan coba unggah ulang.']);
        }

        $token = (string) Str::uuid();
        $previews = $request->session()->get('etl_previews', []);
        foreach ($previews as $oldToken => $oldPreview) {
            if (now()->timestamp - $oldPreview['created_at'] > 7200) {
                Storage::disk('local')->delete($oldPreview['path']);
                unset($previews[$oldToken]);
            }
        }
        $previews[$token] = [
            'path' => $path,
            'created_at' => now()->timestamp,
            'mode' => $validated['mode'],
            'report' => $report,
        ];
        $request->session()->put('etl_previews', $previews);

        return redirect()->route('etl.index')->with('csv_preview', $report + [
            'token' => $token,
            'mode' => $validated['mode'],
        ]);
    }

    public function import(Request $request, SalesCsvImporter $csvImporter): RedirectResponse
    {
        $validated = $request->validate(['token' => ['required', 'uuid']]);
        $previews = $request->session()->get('etl_previews', []);
        $preview = $previews[$validated['token']] ?? null;

        if (
            ! $preview ||
            now()->timestamp - $preview['created_at'] > 7200 ||
            ! Storage::disk('local')->exists($preview['path'])
        ) {
            if ($preview) {
                Storage::disk('local')->delete($preview['path']);
            }
            unset($previews[$validated['token']]);
            $request->session()->put('etl_previews', $previews);

            return redirect()->route('etl.index')->withErrors([
                'file' => 'Preview Excel sudah kedaluwarsa. Silakan unggah ulang file.',
            ]);
        }

        if (! in_array($preview['mode'] ?? null, ['append', 'replace'], true)) {
            return redirect()->route('etl.index')->withErrors([
                'file' => 'Pilihan proses pada preview tidak valid. Silakan unggah ulang file Excel.',
            ]);
        }

        $imported = 0;
        $invalid = $preview['report']['invalid'] ?? 0;
        $errors = $preview['report']['errors'] ?? [];
        try {
            DB::transaction(function () use ($preview, $csvImporter, &$imported) {
                $rows = $preview['report']['rows'] ?? null;
                if ($rows === null) {
                    $rows = [];
                    $csvImporter->process(Storage::disk('local')->path($preview['path']), function (array $row) use (&$rows) {
                        $rows[] = $row;
                    });
                }
                if ($preview['mode'] === 'replace') {
                    Sale::query()->delete();
                }
                if ($rows !== []) {
                    Sale::query()->insert($rows);
                }
                $imported = count($rows);
            });
        } catch (QueryException $exception) {
            Log::error('Excel transaction import failed.', [
                'user_id' => $request->user()->getAuthIdentifier(),
                'sql_state' => $exception->getCode(),
            ]);

            return redirect()->route('etl.index')->withErrors([
                'file' => 'Penyimpanan gagal. Tidak ada baris yang disimpan; silakan periksa data lalu coba lagi.',
            ])->with('csv_preview', $preview['report'] + [
                'token' => $validated['token'],
                'mode' => $preview['mode'],
            ]);
        }

        Storage::disk('local')->delete($preview['path']);
        unset($previews[$validated['token']]);
        $request->session()->put('etl_previews', $previews);

        return redirect()->route('etl.index')->with('csv_import_result', [
            'imported' => $imported,
            'invalid' => $invalid,
            'errors' => $errors,
            'mode' => $preview['mode'],
        ]);
    }
}
