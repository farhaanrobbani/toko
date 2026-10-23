<?php

if (! function_exists('rupiah')) {
    function rupiah(float|int|string|null $value, bool $decimals = false): string
    {
        return 'Rp '.number_format((float) $value, $decimals ? 2 : 0, ',', '.');
    }
}

if (! function_exists('wa_number')) {
    function wa_number(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}

if (! function_exists('qty')) {
    function qty(float|int|string|null $value): string
    {
        $formatted = number_format((float) $value, 3, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',') ?: '0';
    }
}
