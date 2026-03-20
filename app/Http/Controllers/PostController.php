<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\AuditLog;
use App\Models\Post;
use App\Services\GeocodingService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            $action = $request->route()?->getActionMethod();

            if ($action === 'index') {
                if (!$user?->isAdmin() && !$user?->isResponsible() && !$user?->isAuditor()) {
                    abort(403);
                }

                return $next($request);
            }

            if (!$user?->isAdmin()) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Post::with('client');
        $user = $request->user();
        $search = trim((string) $request->input('q', ''));
        $clientId = $request->integer('client_id');

        if ($user?->isResponsible() && !$user?->isAdmin()) {
            $clientIds = $user->clients()->pluck('clients.id');
            $query->whereIn('client_id', $clientIds);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $posts = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        if ($user?->isResponsible() && !$user?->isAdmin()) {
            $clients = $user->clients()->orderBy('name')->get();
        } else {
            $clients = Client::orderBy('name')->get();
        }

        return view('posts.index', compact('posts', 'clients', 'search', 'clientId'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();

        return view('posts.create', compact('clients'));
    }

    public function store(Request $request, GeocodingService $geocodingService)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'coords_source' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $client = Client::find($data['client_id']);
        $useMapCoords = ($data['coords_source'] ?? null) === 'map';
        if ($useMapCoords && !empty($data['latitude']) && !empty($data['longitude'])) {
            $data['latitude'] = (float) $data['latitude'];
            $data['longitude'] = (float) $data['longitude'];
        } elseif (!empty($data['address'])) {
            $coords = $geocodingService->geocode(
                $data['address'] ?? null,
                $data['city'] ?? ($client?->city),
                $data['department'] ?? ($client?->department),
            );
            if ($coords) {
                $data['latitude'] = $coords['lat'];
                $data['longitude'] = $coords['lng'];
            }
        }

        unset($data['coords_source']);

        $post = Post::create($data);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'post_created',
            'auditable_type' => Post::class,
            'auditable_id' => $post->id,
            'before' => null,
            'after' => $post->only(['client_id', 'name', 'address', 'city', 'department']),
        ]);

        return redirect()->route('posts.index')->with('status', 'Puesto creado.');
    }

    public function edit(Post $post)
    {
        $clients = Client::orderBy('name')->get();

        return view('posts.edit', compact('post', 'clients'));
    }

    public function update(Request $request, Post $post, GeocodingService $geocodingService)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'coords_source' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $addressChanged = ($data['address'] ?? null) !== $post->address
            || ($data['city'] ?? null) !== $post->city
            || ($data['department'] ?? null) !== $post->department
            || ($data['client_id'] ?? null) !== $post->client_id;
        $useMapCoords = ($data['coords_source'] ?? null) === 'map';
        if ($useMapCoords && !empty($data['latitude']) && !empty($data['longitude'])) {
            $data['latitude'] = (float) $data['latitude'];
            $data['longitude'] = (float) $data['longitude'];
        } elseif ($addressChanged && !empty($data['address'])) {
            $client = Client::find($data['client_id']);
            $coords = $geocodingService->geocode(
                $data['address'] ?? null,
                $data['city'] ?? ($client?->city),
                $data['department'] ?? ($client?->department),
            );
            if ($coords) {
                $data['latitude'] = $coords['lat'];
                $data['longitude'] = $coords['lng'];
            }
        }

        $before = $post->only(['client_id', 'name', 'address', 'city', 'department', 'notes']);
        unset($data['coords_source']);

        $post->update($data);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'post_updated',
            'auditable_type' => Post::class,
            'auditable_id' => $post->id,
            'before' => $before,
            'after' => $post->only(['client_id', 'name', 'address', 'city', 'department', 'notes']),
        ]);

        return redirect()->route('posts.index')->with('status', 'Puesto actualizado.');
    }

    public function destroy(Post $post)
    {
        AuditLog::create([
            'user_id' => request()->user()?->id,
            'action' => 'post_deleted',
            'auditable_type' => Post::class,
            'auditable_id' => $post->id,
            'before' => $post->only(['client_id', 'name']),
            'after' => null,
        ]);

        $post->delete();

        return redirect()->route('posts.index')->with('status', 'Puesto eliminado.');
    }
}

