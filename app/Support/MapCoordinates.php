<?php

namespace App\Support;

final class MapCoordinates
{
    /**
     * Whether latitude and longitude are set and numeric (map can place a marker).
     */
    public static function isFilled(mixed $latitude, mixed $longitude): bool
    {
        if ($latitude === null || $longitude === null) {
            return false;
        }

        $lat = trim((string) $latitude);
        $lng = trim((string) $longitude);

        if ($lat === '' || $lng === '') {
            return false;
        }

        return is_numeric($lat) && is_numeric($lng);
    }
}
