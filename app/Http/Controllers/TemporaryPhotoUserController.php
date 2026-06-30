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
                'authorizedResponsibles as authorized_responsibles_count',
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

        $temporaryPhotoUser = TemporaryPhotoUser::create([
            'owner_responsible_user_id' => $data['owner_responsible_user_id'],
            'created_by_user_id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'is_shared' => $data['is_shared'],
            'is_active' => true,
        ]);

        if ($user->isAdmin()) {
            $this->syncAuthorizedResponsibles($temporaryPhotoUser, $data['authorized_responsible_ids'] ?? [], $user);
        }

        return redirect()
            ->route('revista-armas.temporary-users.index')
            ->with('status', __('Usuario temporal creado.'));
    }

    public function edit(Request $request, TemporaryPhotoUser $temporaryPhotoUser): View
    {
        $temporaryPhotoUser->load('authorizedResponsibles:id,name');

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
            'is_shared' => $data['is_shared'],
        ]);

        if ($user->isAdmin()) {
            if ($data['is_shared']) {
                $this->syncAuthorizedResponsibles(
                    $temporaryPhotoUser,
                    $data['authorized_responsible_ids'] ?? [],
                    $user,
                );
            } else {
                $temporaryPhotoUser->authorizedResponsibles()->detach();
            }
        }

        return redirect()
            ->route('revista-armas.temporary-users.index')
            ->with('status', __('Usuario temporal actualizado.'));
    }

    public function destroy(TemporaryPhotoUser $temporaryPhotoUser)
    {
        $temporaryPhotoUser->update([
            'is_active' => false,
            'is_shared' => false,
            'deactivated_at' => now(),
        ]);

        $temporaryPhotoUser->authorizedResponsibles()->detach();

        \App\Models\TemporaryPhotoAccessGrant::query()
            ->where('temporary_photo_user_id', $temporaryPhotoUser->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return redirect()
            ->route('revista-armas.temporary-users.index')
            ->with('status', __('Usuario temporal desactivado. Las fotos en revisión se conservan.'));
    }

    /**
     * @return array{
     *     name: string,
     *     email: string,
     *     owner_responsible_user_id: int,
     *     is_shared: bool,
     *     authorized_responsible_ids?: list<int>
     * }
     */
    private function validated(Request $request, User $actor, ?TemporaryPhotoUser $existing = null): array
    {
        if ($actor->isResponsibleLevelOne()) {
            $request->merge([
                'owner_responsible_user_id' => $actor->id,
                'is_shared' => false,
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
            'is_shared' => ['boolean'],
        ];

        if ($actor->isAdmin()) {
            $rules['authorized_responsible_ids'] = ['nullable', 'array'];
            $rules['authorized_responsible_ids.*'] = [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where('role', 'RESPONSABLE'),
            ];
        }

        $data = $request->validate($rules);
        $data['email'] = mb_strtolower(trim($data['email']));
        $data['is_shared'] = $actor->isAdmin() && $request->boolean('is_shared');

        if ($data['is_shared']) {
            $authorizedIds = collect($data['authorized_responsible_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0 && $id !== (int) $data['owner_responsible_user_id'])
                ->unique()
                ->values()
                ->all();

            if ($authorizedIds === []) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'authorized_responsible_ids' => [__('Seleccione al menos un responsable autorizado además del dueño.')],
                ]);
            }

            $data['authorized_responsible_ids'] = $authorizedIds;
        } else {
            $data['authorized_responsible_ids'] = [];
        }

        return $data;
    }

    /**
     * @param  list<int>  $responsibleIds
     */
    private function syncAuthorizedResponsibles(TemporaryPhotoUser $temporaryPhotoUser, array $responsibleIds, User $assignedBy): void
    {
        $payload = collect($responsibleIds)
            ->mapWithKeys(fn (int $id) => [
                $id => ['assigned_by_user_id' => $assignedBy->id],
            ])
            ->all();

        $temporaryPhotoUser->authorizedResponsibles()->sync($payload);
    }

    private function responsibleOptions(User $actor)
    {
        if ($actor->isAdmin()) {
            return User::query()->where('role', 'RESPONSABLE')->orderBy('name')->get(['id', 'name']);
        }

        return collect([$actor]);
    }
}
