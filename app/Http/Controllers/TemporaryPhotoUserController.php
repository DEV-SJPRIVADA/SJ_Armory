<?php

namespace App\Http\Controllers;

use App\Models\TemporaryPhotoUser;
use App\Models\User;
use App\Services\RevistaArmasScopeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TemporaryPhotoUserController extends Controller
{
    public function __construct(
        private readonly RevistaArmasScopeService $scopeService,
    ) {
        $this->middleware(['auth', 'revista.staff']);
        $this->authorizeResource(TemporaryPhotoUser::class, 'temporary_photo_user');
    }

    public function index(Request $request): View
    {
        $users = $this->scopeService->temporaryUsersQueryForStaff($request->user())
            ->withCount([
                'grants as active_grants_count' => function ($query) {
                    $query->whereNull('revoked_at')->where('expires_at', '>', now());
                },
            ])
            ->paginate(20);

        $responsibles = $request->user()->isAdmin()
            ? User::query()->where('role', 'RESPONSABLE')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('revista-armas.temporary-users.index', [
            'users' => $users,
            'responsibles' => $responsibles,
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('revista-armas.temporary-users.form', [
            'temporaryPhotoUser' => new TemporaryPhotoUser(),
            'responsibles' => $this->responsibleOptions($request->user()),
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $this->validated($request, $user);

        TemporaryPhotoUser::create([
            'owner_responsible_user_id' => $data['owner_responsible_user_id'],
            'created_by_user_id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('revista-armas.temporary-users.index')
            ->with('status', __('Usuario temporal creado.'));
    }

    public function edit(Request $request, TemporaryPhotoUser $temporaryPhotoUser): View
    {
        return view('revista-armas.temporary-users.form', [
            'temporaryPhotoUser' => $temporaryPhotoUser,
            'responsibles' => $this->responsibleOptions($request->user()),
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    public function update(Request $request, TemporaryPhotoUser $temporaryPhotoUser)
    {
        $user = $request->user();
        $data = $this->validated($request, $user, $temporaryPhotoUser);

        $temporaryPhotoUser->update([
            'owner_responsible_user_id' => $data['owner_responsible_user_id'],
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return redirect()
            ->route('revista-armas.temporary-users.index')
            ->with('status', __('Usuario temporal actualizado.'));
    }

    public function destroy(TemporaryPhotoUser $temporaryPhotoUser)
    {
        $temporaryPhotoUser->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        \App\Models\TemporaryPhotoAccessGrant::query()
            ->where('temporary_photo_user_id', $temporaryPhotoUser->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return redirect()
            ->route('revista-armas.temporary-users.index')
            ->with('status', __('Usuario temporal desactivado. Las fotos en revisión se conservan.'));
    }

    /**
     * @return array{name: string, email: string, owner_responsible_user_id: int}
     */
    private function validated(Request $request, User $actor, ?TemporaryPhotoUser $existing = null): array
    {
        if ($actor->isResponsibleLevelOne()) {
            $request->merge([
                'owner_responsible_user_id' => $actor->id,
            ]);
        }

        $ownerRule = Rule::exists('users', 'id')->where('role', 'RESPONSABLE');

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('temporary_photo_users', 'email')->ignore($existing?->id),
            ],
            'owner_responsible_user_id' => ['required', 'integer', $ownerRule],
        ];

        $data = $request->validate($rules);
        $data['email'] = mb_strtolower(trim($data['email']));

        return $data;
    }

    private function responsibleOptions(User $actor)
    {
        if ($actor->isAdmin()) {
            return User::query()->where('role', 'RESPONSABLE')->orderBy('name')->get(['id', 'name']);
        }

        return collect([$actor]);
    }
}
