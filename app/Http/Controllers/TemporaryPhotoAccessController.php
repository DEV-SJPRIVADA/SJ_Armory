<?php

namespace App\Http\Controllers;

use App\Models\TemporaryPhotoAccessGrant;
use App\Models\TemporaryPhotoUser;
use App\Services\TemporaryPhotoAccessService;
use Illuminate\Http\Request;
use RuntimeException;

class TemporaryPhotoAccessController extends Controller
{
    public function __construct(
        private readonly TemporaryPhotoAccessService $accessService,
    ) {
        $this->middleware(['auth', 'revista.staff']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'temporary_photo_user_id' => ['required', 'integer', 'exists:temporary_photo_users,id'],
            'weapon_ids' => ['required', 'array', 'min:1'],
            'weapon_ids.*' => ['integer', 'distinct', 'exists:weapons,id'],
        ]);

        $temporaryUser = TemporaryPhotoUser::query()->findOrFail($data['temporary_photo_user_id']);
        $this->authorize('view', $temporaryUser);

        try {
            $result = $this->accessService->createGrant(
                $request->user(),
                $temporaryUser,
                $data['weapon_ids'],
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $result['appended']
                    ? __('Armas agregadas al acceso vigente.')
                    : __('Acceso temporal creado.'),
                'login_url' => route('revista-armas.guest.login'),
                'email' => $temporaryUser->email,
                'code' => $result['plain_code'],
                'appended' => $result['appended'],
                'expires_at' => $result['grant']->expires_at->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ]);
        }

        return redirect()
            ->route('revista-armas.index')
            ->with('revista_access_success', [
                'login_url' => route('revista-armas.guest.login'),
                'email' => $temporaryUser->email,
                'code' => $result['plain_code'],
                'appended' => $result['appended'],
                'expires_at' => $result['grant']->expires_at->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'name' => $temporaryUser->name,
            ]);
    }

    public function revoke(Request $request, TemporaryPhotoAccessGrant $grant)
    {
        $grant->loadMissing('temporaryPhotoUser');
        $this->authorize('view', $grant->temporaryPhotoUser);

        $this->accessService->revokeGrant($request->user(), $grant);

        return back()->with('status', __('Acceso temporal revocado.'));
    }
}
