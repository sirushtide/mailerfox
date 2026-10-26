<?php

if (!function_exists('maskApiKey')) {
    function maskApiKey(string $key, ?int $visiblePrefix = null): string
    {
        $length = mb_strlen($key);
        $maskLength = 10;

        if ($length <= $maskLength) {
            return str_repeat('*', $length);
        }

        $visible = $visiblePrefix ?? ($length - $maskLength);
        $visible = max(0, min($visible, $length - $maskLength));

        return mb_substr($key, 0, $visible) . str_repeat('*', $maskLength);
    }
}