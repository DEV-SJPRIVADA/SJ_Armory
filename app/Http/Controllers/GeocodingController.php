<?php

namespace App\Http\Controllers;

use App\Services\GeocodingService;
use Illuminate\Http\Request;

class GeocodingController extends Controller
{
    public function search(Request $request, GeocodingService $geocodingService)
    {
        $data = $request->validate([
            'address' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $geocodingService->geocode(
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['department'] ?? null,
            $data['neighborhood'] ?? null,
        );

        if (!$result) {
            return response()->json([
                'error' => 'geocode_failed',
            ], 422);
        }

        return response()->json($result);
    }

    public function reverse(Request $request, GeocodingService $geocodingService)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $result = $geocodingService->reverseGeocode((float) $data['lat'], (float) $data['lng']);

        if (!$result) {
            return response()->json([
                'error' => 'reverse_geocode_failed',
            ], 422);
        }

        return response()->json($result);
    }
}

