<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SalesExcelImporter
{
    private const HEADERS = [
        'order_id' => ['orderid', 'order id', 'order_id'],
        'amount' => ['amount'],
        'profit' => ['profit'],
        'quantity' => ['quantity', 'qty'],
        'category' => ['category'],
        'sub_category' => ['subcategory', 'sub category', 'sub_category'],
        'payment_mode' => ['paymentmode', 'payment mode', 'payment_mode'],
        'order_date' => ['orderdate', 'order date', 'order_date'],
        'customer_name' => ['customername', 'customer name', 'customer_name'],
        'state' => ['state'],
        'city' => ['city'],
        'year_month' => ['yearmonth', 'year month', 'year-month', 'year_month'],
    ];

    public function process(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $values = $sheet->rangeToArray("A1:{$highestColumn}{$highestRow}", null, true, false);

        if (count($values) < 2) {
            throw new InvalidArgumentException('File Excel hanya berisi header dan tidak memiliki data.');
        }

        $headerMap = $this->headerMap($values[0]);
        $rows = [];
        $errors = [];

        for ($rowNumber = 2; $rowNumber <= count($values); $rowNumber++) {
            $raw = $values[$rowNumber - 1];
            if ($this->rowIsEmpty($raw)) {
                continue;
            }

            $rowErrors = [];
            $row = [];
            foreach (self::HEADERS as $field => $_) {
                $row[$field] = trim((string) ($raw[$headerMap[$field]] ?? ''));
            }

            foreach (['order_id', 'category', 'sub_category', 'payment_mode', 'customer_name', 'state', 'city'] as $field) {
                if ($row[$field] === '') {
                    $rowErrors[] = "{$field} wajib diisi";
                } elseif (strlen($row[$field]) > 255) {
                    $rowErrors[] = "{$field} maksimal 255 karakter";
                }
            }

            $amount = $this->number($row['amount']);
            $profit = $this->number($row['profit']);
            if ($amount === null) {
                $rowErrors[] = 'amount harus berupa angka';
            } elseif ($amount < 0 || $amount > 9999999999999.99) {
                $rowErrors[] = 'amount tidak valid atau berada di luar batas';
            }
            if ($profit === null) {
                $rowErrors[] = 'profit harus berupa angka';
            } elseif (abs($profit) > 9999999999999.99) {
                $rowErrors[] = 'profit berada di luar batas';
            }

            if (! preg_match('/^\d+$/', $row['quantity'])) {
                $rowErrors[] = 'quantity harus berupa bilangan bulat non-negatif';
                $quantity = null;
            } else {
                $quantity = (int) $row['quantity'];
                if ($quantity > 2147483647) {
                    $rowErrors[] = 'quantity berada di luar batas';
                }
            }

            $date = $this->date($row['order_date'], $rowNumber, $sheet);
            if ($date === null) {
                $rowErrors[] = 'order_date harus berupa tanggal valid';
            }

            if ($date !== null && ! preg_match('/^\d{4}-\d{2}$/', $row['year_month'])) {
                $rowErrors[] = 'year_month harus berformat YYYY-MM';
            } elseif ($date !== null && $date->format('Y-m') !== $row['year_month']) {
                $rowErrors[] = 'year_month harus sesuai dengan order_date';
            }

            if ($rowErrors !== []) {
                $errors[] = ['row' => $rowNumber, 'message' => implode('; ', $rowErrors)];
                continue;
            }

            $rows[] = [
                'order_id' => $row['order_id'],
                'amount' => round($amount, 2),
                'profit' => round($profit, 2),
                'quantity' => $quantity,
                'category' => $row['category'],
                'sub_category' => $row['sub_category'],
                'payment_mode' => $row['payment_mode'],
                'order_date' => $date->toDateString(),
                'customer_name' => $row['customer_name'],
                'state' => $row['state'],
                'city' => $row['city'],
                'year_month' => $row['year_month'],
            ];
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(
                'Ditemukan '.count($errors).' baris tidak valid: '.
                implode(' | ', array_map(
                    fn ($error) => "Baris {$error['row']}: {$error['message']}",
                    array_slice($errors, 0, 10)
                ))
            );
        }

        if ($rows === []) {
            throw new InvalidArgumentException('File Excel tidak memiliki baris data valid.');
        }

        return [
            'rows' => $rows,
            'total' => count($rows),
            'sample' => array_slice($rows, 0, 5),
        ];
    }

    private function headerMap(array $headers): array
    {
        $normalizedHeaders = [];
        foreach ($headers as $index => $header) {
            $normalizedHeaders[$this->normalizeHeader((string) $header)] = $index;
        }

        $map = [];
        $missing = [];
        foreach (self::HEADERS as $field => $aliases) {
            $index = null;
            foreach ($aliases as $alias) {
                $normalizedAlias = $this->normalizeHeader($alias);
                if (array_key_exists($normalizedAlias, $normalizedHeaders)) {
                    $index = $normalizedHeaders[$normalizedAlias];
                    break;
                }
            }
            if ($index === null) {
                $missing[] = $field;
            } else {
                $map[$field] = $index;
            }
        }

        if ($missing !== []) {
            throw new InvalidArgumentException('Kolom berikut tidak ditemukan: '.implode(', ', $missing).'.');
        }

        return $map;
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $header), '_'));
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function number(string $value): ?float
    {
        $value = trim(str_replace(',', '', $value));
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }
        $number = (float) $value;
        return is_finite($number) ? $number : null;
    }

    private function date(string $value, int $rowNumber, $sheet): ?Carbon
    {
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                $timestamp = Date::excelToTimestamp((float) $value);
                return Carbon::createFromTimestampUTC($timestamp);
            } catch (\Throwable) {
                return null;
            }
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            $date = Carbon::createFromFormat('!'.$format, $value);
            if ($date !== false) {
                return $date;
            }
        }
        return null;
    }
}
