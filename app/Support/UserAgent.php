<?php

namespace App\Support;

final class UserAgent
{
    public static function label(
        ?string $userAgent,
    ): string {
        if ($userAgent === null) {
            return 'Unbekanntes Gerät';
        }

        $browser = match (true) {
            str_contains(
                $userAgent,
                'Edg/'
            ) => 'Microsoft Edge',

            str_contains(
                $userAgent,
                'Chrome/'
            ) => 'Chrome',

            str_contains(
                $userAgent,
                'Firefox/'
            ) => 'Firefox',

            str_contains(
                $userAgent,
                'Safari/'
            ) => 'Safari',

            default => 'Unbekannter Browser',
        };

        $platform = match (true) {
            str_contains(
                $userAgent,
                'Windows'
            ) => 'Windows',

            str_contains(
                $userAgent,
                'Macintosh'
            ) => 'macOS',

            str_contains(
                $userAgent,
                'Android'
            ) => 'Android',

            str_contains(
                $userAgent,
                'iPhone'
            ) => 'iPhone',

            str_contains(
                $userAgent,
                'iPad'
            ) => 'iPad',

            str_contains(
                $userAgent,
                'Linux'
            ) => 'Linux',

            default => 'Unbekanntes System',
        };

        return $browser.' auf '.$platform;
    }
}
