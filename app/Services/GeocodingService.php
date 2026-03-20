<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class GeocodingService
{
    public function geocode(string $address, ?string $city = null): ?array
    {
        $query = trim($address . ($city ? ', ' . $city : '') . ', Colombia');
        if ($query === '') {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => config('services.nominatim.user_agent'),
            ])->timeout(5)->connectTimeout(3)->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
                'q' => $query,
                'countrycodes' => 'co',
                'limit' => 1,
            ]);
        } catch (Throwable $exception) {
            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $result = $response->json()[0] ?? null;
        if (!$result || !isset($result['lat'], $result['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $result['lat'],
            'lng' => (float) $result['lon'],
        ];
    }

    public function reverseGeocode(float $lat, float $lng): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('services.nominatim.user_agent'),
            ])->timeout(5)->connectTimeout(3)->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'jsonv2',
                'lat' => $lat,
                'lon' => $lng,
                'addressdetails' => 1,
            ]);
        } catch (Throwable $exception) {
            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        return $response->json();
    }
}

