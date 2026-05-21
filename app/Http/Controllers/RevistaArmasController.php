<?php

namespace App\Http\Controllers;

use App\Models\TemporaryPhotoUser;
use App\Models\Weapon;
use App\Support\RevistaWeaponPhotoSlots;
use App\Services\RevistaArmasScopeService;
use App\Services\TemporaryPhotoAccessService;
use App\Services\WeaponPhotoStagingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevistaArmasController extends Controller
{
    public function __construct(
        private readonly RevistaArmasScopeService $scopeService,
        private readonly WeaponPhotoStagingService $stagingService,
        private readonly TemporaryPhotoAccessService $accessService,
    ) {
        $this->middleware(['auth', 'revista.staff']);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $temporaryPhotoUserId = $request->integer('temporary_photo_user_id') ?: null;

        $temporaryUsers = $this->scopeService->temporaryUsersQueryForStaff($user)
            ->active()
            ->orderBy('name')
            ->get();

        $activeGrantMissing = false;
        $weaponsQuery = $this->scopeService->weaponsQueryForStaff($user);

        if ($temporaryPhotoUserId) {
            $temporaryUser = $temporaryUsers->firstWhere('id', $temporaryPhotoUserId);

            if ($temporaryUser) {
                $grant = $this->accessService->activeGrantFor($temporaryUser);

                if ($grant) {
                    $weaponIds = $this->accessService->grantWeaponIds($grant);
                    $weaponsQuery->whereIn('weapons.id', $weaponIds);
                } else {
                    $activeGrantMissing = true;
                    $weaponsQuery->whereRaw('0 = 1');
                }
            }
        }

        $weapons = $weaponsQuery->get();

        $rows = $weapons->map(function (Weapon $weapon) use ($temporaryPhotoUserId, $temporaryUsers) {
            $completions = [];

            if ($temporaryPhotoUserId) {
                $tempUser = $temporaryUsers->firstWhere('id', $temporaryPhotoUserId);
                if ($tempUser) {
                    $completions[$temporaryPhotoUserId] = $this->stagingService->isStagingComplete($tempUser, $weapon);
                }
            } else {
                foreach ($temporaryUsers as $tempUser) {
                    $completions[$tempUser->id] = $this->stagingService->isStagingComplete($tempUser, $weapon);
                }
            }

            return [
                'weapon' => $weapon,
                'completions' => $completions,
            ];
        });

        return view('revista-armas.index', [
            'rows' => $rows,
            'temporaryUsers' => $temporaryUsers,
            'selectedTemporaryUserId' => $temporaryPhotoUserId,
            'activeGrantMissing' => $activeGrantMissing,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function review(Request $request, Weapon $weapon, TemporaryPhotoUser $temporaryPhotoUser)
    {
        $user = $request->user();
        $this->authorize('view', $temporaryPhotoUser);

        if (! $this->scopeService->canStaffManageWeapon($user, $weapon)) {
            abort(403);
        }

        $staging = $this->stagingService->stagingByDescription($temporaryPhotoUser, $weapon);
        $requiredCount = RevistaWeaponPhotoSlots::requiredCount();
        $uploadedCount = collect($staging)->filter(fn ($row) => $row->file !== null)->count();
        $pendingCount = max(0, $requiredCount - $uploadedCount);

        return response()->json([
            'weapon' => [
                'id' => $weapon->id,
                'serial_number' => $weapon->serial_number,
            ],
            'temporary_user' => [
                'id' => $temporaryPhotoUser->id,
                'name' => $temporaryPhotoUser->name,
            ],
            'slots' => collect(RevistaWeaponPhotoSlots::DESCRIPTIONS)->map(function ($label, $description) use ($staging) {
                $row = $staging[$description] ?? null;
                $url = $row?->file
                    ? \Illuminate\Support\Facades\Storage::disk($row->file->disk)->url($row->file->path)
                    : null;

                return [
                    'description' => $description,
                    'label' => $label,
                    'url' => $url,
                ];
            })->values(),
            'required_count' => $requiredCount,
            'uploaded_count' => $uploadedCount,
            'pending_count' => $pendingCount,
            'is_complete' => $pendingCount === 0,
        ]);
    }
}
