<?php

namespace App\Support;

use App\Models\WeaponDocument;

class WeaponDocumentAlert
{
    public static function forDocument(?WeaponDocument $document): array
    {
        if (!$document) {
            return self::empty();
        }

        if ($document->is_permit || $document->is_renewal) {
            return self::forComplianceDocument($document);
        }

        return self::forManualDocument($document);
    }

    public static function forComplianceDocument(?WeaponDocument $document): array
    {
        if (!$document || !$document->valid_until) {
            return self::empty();
        }

        $days = now()->startOfDay()->diffInDays($document->valid_until, false);

        if ($days <= 0) {
            return [
                'days' => $days,
                'state' => 'Vencido',
                'observation' => self::expiredMessage($days),
                'row_class' => 'bg-red-200',
                'text_class' => 'text-red-800',
                'severity' => 3,
            ];
        }

        if ($days <= 90) {
            return [
                'days' => $days,
                'state' => 'Próximo a vencer',
                'observation' => self::revalidationMessage($days),
                'row_class' => 'bg-orange-200',
                'text_class' => 'text-orange-800',
                'severity' => 2,
            ];
        }

        if ($days <= 120) {
            return [
                'days' => $days,
                'state' => 'Alerta preventiva',
                'observation' => self::revalidationMessage($days),
                'row_class' => 'bg-yellow-200',
                'text_class' => 'text-amber-900',
                'severity' => 1,
            ];
        }

        return [
            'days' => $days,
            'state' => 'Vigente',
            'observation' => '-',
            'row_class' => '',
            'text_class' => 'text-green-700',
            'severity' => 0,
        ];
    }

    public static function forManualDocument(?WeaponDocument $document): array
    {
        if (!$document) {
            return self::empty();
        }

        $status = $document->status ?: '-';
        $observation = $document->observations ?: '-';
        $inProcess = $status === 'En proceso';

        return [
            'days' => null,
            'state' => $status,
            'observation' => $observation,
            'row_class' => $inProcess ? 'bg-red-200' : '',
            'text_class' => $inProcess ? 'text-red-800' : 'text-gray-700',
            'severity' => $inProcess ? 3 : 0,
        ];
    }

    private static function revalidationMessage(int $days): string
    {
        return 'Faltan ' . $days . ' ' . self::pluralize($days, 'día', 'días') . ' para revalidar';
    }

    private static function expiredMessage(int $days): string
    {
        $expiredDays = abs($days);

        return $expiredDays . ' ' . self::pluralize($expiredDays, 'día', 'días') . ' vencido. Fuera de servicio';
    }

    private static function pluralize(int $value, string $singular, string $plural): string
    {
        return $value === 1 ? $singular : $plural;
    }

    private static function empty(): array
    {
        return [
            'days' => null,
            'state' => '-',
            'observation' => '-',
            'row_class' => '',
            'text_class' => 'text-gray-700',
            'severity' => 0,
        ];
    }
}
