<?php

namespace App\Services;

use App\Models\User;
use App\Models\Weapon;
use App\Support\PostCustodyRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WeaponCustodyReportService
{
    /**
     * @return array{
     *     rows: Collection<int, array{weapon: Weapon, custody_label: string, post_name: string, responsible_name: string, client_name: string}>,
     *     counts: array<string, int>
     * }
     */
    public function dashboard(User $user): array
    {
        $weapons = $this->baseQuery($user)
            ->with([
                'activeClientAssignment.client',
                'activeClientAssignment.responsible',
                'activePostAssignment.post',
            ])
            ->get()
            ->filter(fn (Weapon $weapon) => filled($weapon->activeCustodyRole()));

        $counts = [
            PostCustodyRole::ARMERILLO => 0,
            PostCustodyRole::ARMERILLO_PARA_MANTENIMIENTO => 0,
            PostCustodyRole::ARMERO => 0,
        ];

        $rows = $weapons->map(function (Weapon $weapon) use (&$counts) {
            $role = (string) $weapon->activeCustodyRole();
            $counts[$role] = ($counts[$role] ?? 0) + 1;

            return [
                'weapon' => $weapon,
                'custody_label' => $weapon->custodyStatusLabel() ?? '—',
                'post_name' => $weapon->activePostAssignment?->post?->name ?? '—',
                'responsible_name' => $weapon->activeClientAssignment?->responsible?->name ?? '—',
                'client_name' => $weapon->activeClientAssignment?->client?->name ?? '—',
            ];
        })->sortBy(fn (array $row) => $row['custody_label'] . $row['weapon']->serial_number)->values();

        return [
            'rows' => $rows,
            'counts' => $counts,
        ];
    }

    private function baseQuery(User $user): Builder
    {
        $query = Weapon::query()
            ->whereHas('activePostAssignment.post', function (Builder $postQuery) {
                $postQuery->whereIn('custody_role', PostCustodyRole::all());
            });

        if ($user->isResponsible() && ! $user->isAdmin()) {
            $query->whereHas('activeClientAssignment', function (Builder $assignmentQuery) use ($user) {
                $assignmentQuery->where('responsible_user_id', $user->id);
            });
        }

        return $query;
    }
}
