<?php

namespace App\Support;

final class PostCustodyRole
{
    public const ARMERILLO = 'armerillo';

    public const ARMERILLO_PARA_MANTENIMIENTO = 'armerillo_para_mantenimiento';

    public const ARMERO = 'armero';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ARMERILLO,
            self::ARMERILLO_PARA_MANTENIMIENTO,
            self::ARMERO,
        ];
    }

    /**
     * @return list<string>
     */
    public static function nonOperational(): array
    {
        return [
            self::ARMERILLO_PARA_MANTENIMIENTO,
            self::ARMERO,
        ];
    }

    public static function label(string $role): string
    {
        return match ($role) {
            self::ARMERILLO => __('Armerillo'),
            self::ARMERILLO_PARA_MANTENIMIENTO => __('Armerillo — Para mantenimiento'),
            self::ARMERO => __('Armero / taller'),
            default => $role,
        };
    }
}
