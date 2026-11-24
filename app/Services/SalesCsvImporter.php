<?php

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class SalesCsvImporter
{
    private const HEADERS = [
        'order_id' => ['order_id', 'order id'],
        'amount' => ['amount'],
        'profit' => ['profit'],
        'quantity' => ['quantity', 'qty'],
        'category' => ['category'],
        'sub_category' => ['sub_category', 'sub category'],
        'payment_mode' => ['paymentmode', 'payment_mode', 'payment mode'],
        'order_date' => ['order_date', 'order date'],
        'customer_name' => ['customername', 'customer_name', 'customer name'],
        'state' => ['state'],
        'city' => ['city'],
        'year_month' => ['year_month', 'year-month', 'year month'],
    ];

    /**
     * Validate every row and optionally stream valid rows to a callback.
     *
     * @return array{total:int,valid:int,invalid:int,sample:list<array<string,mixed>>,errors:list<array{row:int,message:string}>}
     */
    public function process(string $path, ?callable $onValid = null): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                throw new InvalidArgumentException('File CSV kosong.');
            }

            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter, '"', '');

            if ($headers === false) {
                throw new InvalidArgumentException('Header CSV tidak dapat dibaca.');
            }

            $indices = $this->headerIndices($headers);
            $result = ['total' => 0, 'valid' => 0, 'invalid' => 0, 'sample' => [], 'errors' => []];
            $line = 1;

            while (($values = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if ($values === [null] || (count($values) === 1 && trim((string) $values[0]) === '')) {
                    continue;
                }

                $result['total']++;
                if (count($values) !== count($headers)) {
                    $result['invalid']++;
                    if (count($result['errors']) < 50) {
                        $result['errors'][] = ['row' => $line, 'message' => 'Jumlah kolom tidak sesuai dengan header CSV.'];
                    }

                    continue;
                }

                $row = [];
                foreach ($indices as $field => $index) {
                    $row[$field] = trim((string) ($values[$index] ?? ''));
                }

                [$normalized, $errors] = $this->normalize($row);
                if ($errors !== []) {
                    $result['invalid']++;
                    if (count($result['errors']) < 50) {
                        $result['errors'][] = ['row' => $line, 'message' => implode(' ', $errors)];
                    }

                    continue;
                }

                $result['valid']++;
                if (count($result['sample']) < 10) {
                    $result['sample'][] = $normalized;
                }
                if ($onValid !== null) {
                    $onValid($normalized);
                }
            }

            if ($result['total'] === 0) {
                throw new InvalidArgumentException('File CSV hanya berisi header dan tidak memiliki data.');
            }

            return $result;
        } finally {
            fclose($handle);
        }
    }

    private function headerIndices(array $headers): array
    {
        $normalizedHeaders = [];
        foreach ($headers as $index => $header) {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
            $normalized = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $header), '_'));
            $normalizedHeaders[$normalized] = $index;
        }

        $indices = [];
        $missing = [];
        foreach (self::HEADERS as $field => $aliases) {
            $index = null;
            foreach ($aliases as $alias) {
                $normalizedAlias = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $alias), '_'));
                if (array_key_exists($normalizedAlias, $normalizedHeaders)) {
                    $index = $normalizedHeaders[$normalizedAlias];
                    break;
                }
            }

            if ($index === null) {
                $missing[] = $field;
            } else {
                $indices[$field] = $index;
            }
        }

        if ($missing !== []) {
            throw new InvalidArgumentException('Header CSV tidak lengkap. Kolom wajib: '.implode(', ', $missing).'.');
        }

        return $indices;
    }

    private function normalize(array $row): array
    {
        $errors = [];
        foreach (['order_id', 'category', 'sub_category', 'payment_mode', 'customer_name', 'state', 'city'] as $field) {
            if ($row[$field] === '') {
                $errors[] = "{$field} wajib diisi.";
            } elseif (strlen($row[$field]) > 255) {
                $errors[] = "{$field} maksimal 255 karakter.";
            }
        }

        $amount = $this->number($row['amount'], 'amount', $errors);
        $profit = $this->number($row['profit'], 'profit', $errors);
        if ($amount !== null && $amount < 0) {
            $errors[] = 'amount tidak boleh negatif.';
        } elseif ($amount !== null && $amount > 9999999999999.99) {
            $errors[] = 'amount melebihi batas kolom database.';
        }
        if ($profit !== null && abs($profit) > 9999999999999.99) {
            $errors[] = 'profit melebihi batas kolom database.';
        }

        if (! preg_match('/^\d+$/', $row['quantity'])) {
            $errors[] = 'quantity harus berupa bilangan bulat non-negatif.';
            $quantity = null;
        } else {
            $quantity = filter_var($row['quantity'], FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity > 2147483647) {
                $errors[] = 'quantity berada di luar batas yang didukung.';
                $quantity = null;
            }
        }

        $date = $this->date($row['order_date']);
        if ($date === null) {
            $errors[] = 'order_date harus berformat d/m/YYYY atau YYYY-mm-dd.';
        } elseif ((int) $date->format('Y') < 1000) {
            $errors[] = 'order_date harus berada pada tahun 1000 atau setelahnya.';
        } elseif (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $row['year_month'])) {
            $errors[] = 'year_month harus berformat YYYY-MM.';
        } elseif ($date->format('Y-m') !== $row['year_month']) {
            $errors[] = 'year_month harus sesuai dengan order_date.';
        }

        if ($errors !== []) {
            return [[], $errors];
        }

        return [[
            'order_id' => $row['order_id'],
            'amount' => $amount,
            'profit' => $profit,
            'quantity' => $quantity,
            'category' => $row['category'],
            'sub_category' => $row['sub_category'],
            'payment_mode' => $row['payment_mode'],
            'order_date' => $date->format('Y-m-d'),
            'customer_name' => $row['customer_name'],
            'state' => $row['state'],
            'city' => $row['city'],
            'year_month' => $row['year_month'],
        ], []];
    }

    private function number(string $value, string $field, array &$errors): ?float
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            $errors[] = "{$field} harus berupa angka.";

            return null;
        }

        return (float) $value;
    }

    private function date(string $value): ?DateTimeImmutable
    {
        foreach (['!d/m/Y', '!Y-m-d'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date;
            }
        }

        return null;
    }
}
