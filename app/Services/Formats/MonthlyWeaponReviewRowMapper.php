<?php

namespace App\Services\Formats;

use App\Models\Weapon;
use Carbon\Carbon;

class MonthlyWeaponReviewRowMapper
{
    /**
     * @return list<string>
     */
    public function map(Weapon $weapon, int $rowNumber, ?Carbon $reviewDate = null): array
    {
        $reviewDate ??= now();
        $internalAssignment = $weapon->activeWorkerAssignment ?? $weapon->activePostAssignment;
        $postName = (string) ($weapon->activePostAssignment?->post?->name ?? '');
        $responsibleName = (string) ($weapon->operationalDisplayResponsible()?->name ?? '');
        $workerDocument = (string) ($weapon->activeWorkerAssignment?->worker?->document ?? '');

        return [
            (string) $rowNumber,
            $postName,
            $reviewDate->format('y-m-d'),
            $responsibleName,
            $workerDocument,
            (string) $weapon->weapon_type,
            (string) $weapon->serial_number,
            '',
            (string) $weapon->caliber,
            $internalAssignment?->ammo_count !== null ? (string) $internalAssignment->ammo_count : '',
            $internalAssignment?->provider_count !== null ? (string) $internalAssignment->provider_count : '',
            (string) ($weapon->permit_number ?? ''),
            $weapon->permit_expires_at?->format('d/m/Y') ?? '',
            '',
            '',
            '',
            $weapon->imprint_month ? 'Recibida' : '',
        ];
    }

}
