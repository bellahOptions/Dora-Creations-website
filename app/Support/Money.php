<?php

namespace App\Support;

class Money
{
    /**
     * Format a kobo (NGN minor unit) amount for display in NGN, e.g. 1234500 -> "₦12,345.00".
     */
    public static function ngn(int $kobo): string
    {
        return '₦'.number_format($kobo / 100, 2);
    }

    public static function naira(int $kobo): float
    {
        return round($kobo / 100, 2);
    }

    public static function kobo(float $naira): int
    {
        return (int) round($naira * 100);
    }
}
