<?php

if (! function_exists('rupiah')) {
    function rupiah(float|int|string|null $value, bool $decimals = false): string
    {
        return 'Rp '.number_format((float) $value, $decimals ? 2 : 0, ',', '.');
    }
}
