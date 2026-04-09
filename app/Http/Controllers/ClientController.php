<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\AuditLog;
use App\Models\WeaponClientAssignment;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Client::class, 'client');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->input('q', ''));

        if ($user->isResponsible() && !$user->isAdmin()) {
            $query = $user->clients();
        } else {
            $query = Client::query();
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('nit', 'like', '%' . $search . '%')
                    ->orWhere('contact_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%')
                    ->orWhere('department', 'like', '%' . $search . '%');
            });
        }

        $clients = $query
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'results' => view('clients.partials.index_results', compact('clients', 'search'))->render(),
                'countLabel' => $this->clientCountLabel($clients),
            ]);
        }

        return view('clients.index', compact('clients', 'search'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request, GeocodingService $geocodingService)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nit' => ['required', 'string', 'max:50'],
            'legal_representative' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'coords_source' => ['nullable', 'string', 'max:20'],
        ]);

        $useMapCoords = ($data['coords_source'] ?? null) === 'map';
        if ($useMapCoords && !empty($data['latitude']) && !empty($data['longitude'])) {
            $data['latitude'] = (float) $data['latitude'];
            $data['longitude'] = (float) $data['longitude'];
        } elseif (!empty($data['address'])) {
            $coords = $geocodingService->geocode(
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['department'] ?? null,
                $data['neighborhood'] ?? null,
            );
            if ($coords) {
                $data['latitude'] = $coords['lat'];
                $data['longitude'] = $coords['lng'];
            }
        }

        unset($data['coords_source']);

        $client = Client::create($data);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'client_created',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
            'before' => null,
            'after' => $client->only(['name', 'nit', 'city', 'department']),
        ]);

        return redirect()->route('clients.index')->with('status', 'Cliente creado.');
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client, GeocodingService $geocodingService)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nit' => ['required', 'string', 'max:50'],
            'legal_representative' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'coords_source' => ['nullable', 'string', 'max:20'],
        ]);

        $addressChanged = ($data['address'] ?? null) !== $client->address
            || ($data['neighborhood'] ?? null) !== $client->neighborhood
            || ($data['city'] ?? null) !== $client->city
            || ($data['department'] ?? null) !== $client->department;
        $useMapCoords = ($data['coords_source'] ?? null) === 'map';
        if ($useMapCoords && !empty($data['latitude']) && !empty($data['longitude'])) {
            $data['latitude'] = (float) $data['latitude'];
            $data['longitude'] = (float) $data['longitude'];
        } elseif ($addressChanged && !empty($data['address'])) {
            $coords = $geocodingService->geocode(
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['department'] ?? null,
                $data['neighborhood'] ?? null,
            );
            if ($coords) {
                $data['latitude'] = $coords['lat'];
                $data['longitude'] = $coords['lng'];
            }
        }

        $before = $client->only(['name', 'nit', 'city', 'department', 'email', 'contact_name']);
        unset($data['coords_source']);

        $client->update($data);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'client_updated',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
            'before' => $before,
            'after' => $client->only(['name', 'nit', 'city', 'department', 'email', 'contact_name']),
        ]);

        return redirect()->route('clients.index')->with('status', 'Cliente actualizado.');
    }

    public function destroy(Client $client)
    {
        $hasWeapons = WeaponClientAssignment::query()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->exists();

        if ($hasWeapons) {
            return redirect()->route('clients.index')->withErrors([
                'client' => 'No se puede eliminar el cliente porque tiene armas asignadas.',
            ]);
        }

        AuditLog::create([
            'user_id' => request()->user()?->id,
            'action' => 'client_deleted',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
            'before' => $client->only(['name', 'nit', 'city', 'department']),
            'after' => null,
        ]);

        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Cliente eliminado.');
    }

    private function clientCountLabel(LengthAwarePaginator $clients): string
    {
        $total = $clients->total();
        $label = $total === 1 ? 'cliente' : 'clientes';

        return number_format($total) . ' ' . $label;
    }
}
