<?php

if (! function_exists('rupiah')) {
    function rupiah(float|int|string|null $value, bool $decimals = false): string
    {
        return 'Rp '.number_format((float) $value, $decimals ? 2 : 0, ',', '.');
    }
}

if (! function_exists('qty')) {
    function qty(float|int|string|null $value): string
    {
        $formatted = number_format((float) $value, 3, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',') ?: '0';
    }
}
