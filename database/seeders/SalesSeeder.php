<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Cara pakai:
 *   1. Simpan file CSV di  database/data/Sales_Dataset.csv
 *   2. php artisan migrate
 *   3. php artisan db:seed --class=SalesSeeder
 *
 * Format dataset asli:
 *   - Delimiter titik koma (;)
 *   - Tanggal d/m/Y (contoh 27/06/2023)
 *   - Header: Order ID;Amount;Profit;Quantity;Category;Sub-Category;PaymentMode;
 *             Order Date;CustomerName;State;City;Year-Month
 */
class SalesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/Sales_Dataset.csv');

        if (!is_readable($path)) {
            $this->command?->error("File CSV tidak ditemukan: {$path}");
            return;
        }

        $handle = fopen($path, 'r');

        // Lewati baris header
        fgetcsv($handle, 0, ';');

        DB::table('sales')->truncate();

        $batch = [];
        $total = 0;

        while (($r = fgetcsv($handle, 0, ';')) !== false) {
            if (count($r) < 12) {
                continue; // baris kosong / rusak
            }

            $batch[] = [
                'order_id'      => trim($r[0]),
                'amount'        => (float) $r[1],
                'profit'        => (float) $r[2],
                'quantity'      => (int) $r[3],
                'category'      => trim($r[4]),
                'sub_category'  => trim($r[5]),
                'payment_mode'  => trim($r[6]),
                'order_date'    => Carbon::createFromFormat('d/m/Y', trim($r[7]))->format('Y-m-d'),
                'customer_name' => trim($r[8]),
                'state'         => trim($r[9]),
                'city'          => trim($r[10]),
                'year_month'    => trim($r[11]),
            ];

            if (count($batch) === 500) {
                DB::table('sales')->insert($batch);
                $total += count($batch);
                $batch = [];
            }
        }

        if ($batch) {
            DB::table('sales')->insert($batch);
            $total += count($batch);
        }

        fclose($handle);
        $this->command?->info("Berhasil mengimpor {$total} baris ke tabel sales.");
    }
}
