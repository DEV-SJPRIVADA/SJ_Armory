<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AlertDocumentPeriod
{
    public const MONTH_PATTERN = '/^\d{4}-\d{2}$/';

    /**
     * @return list<string>
     */
    public static function normalizeFromRequest(Request $request): array
    {
        $raw = $request->input('months', []);

        if (! is_array($raw)) {
            $raw = [$raw];
        }

        $legacy = trim((string) $request->input('month', ''));
        if ($legacy !== '' && preg_match(self::MONTH_PATTERN, $legacy) === 1) {
            $raw[] = $legacy;
        }

        return self::normalizeList($raw);
    }

    /**
     * @param  array<int, mixed>  $values
     * @return list<string>
     */
    public static function normalizeList(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn (string $value) => preg_match(self::MONTH_PATTERN, $value) === 1)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $months
     */
    public static function applyMonthFilter(Builder $query, array $months, string $column = 'valid_until'): void
    {
        if ($months === []) {
            return;
        }

        $query->where(function (Builder $outer) use ($months, $column) {
            foreach ($months as $month) {
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $outer->orWhereBetween($column, [$start, $end]);
            }
        });
    }

    /**
     * @param  list<string>  $months
     */
    public static function label(array $months): string
    {
        if ($months === []) {
            return __('Todos los meses');
        }

        if (count($months) === 1) {
            return Carbon::createFromFormat('Y-m', $months[0])
                ->locale(app()->getLocale())
                ->translatedFormat('F \d\e Y');
        }

        return collect($months)
            ->map(fn (string $month) => Carbon::createFromFormat('Y-m', $month)
                ->locale(app()->getLocale())
                ->translatedFormat('M Y'))
            ->implode(', ');
    }

    /**
     * @param  list<string>  $months
     */
    public static function closestToNow(array $months): Carbon
    {
        $reference = now();

        if ($months === []) {
            return $reference->copy()->startOfMonth();
        }

        $candidates = collect($months)
            ->map(fn (string $month) => Carbon::createFromFormat('Y-m', $month)->startOfMonth());

        $minDistance = $candidates
            ->map(fn (Carbon $date) => abs($date->diffInDays($reference, false)))
            ->min();

        return $candidates
            ->filter(fn (Carbon $date) => abs($date->diffInDays($reference, false)) === $minDistance)
            ->sortByDesc(fn (Carbon $date) => $date->timestamp)
            ->first();
    }

    /**
     * @param  list<string>  $months
     */
    public static function downloadBaseName(array $months): string
    {
        $period = self::closestToNow($months);
        $monthName = Str::lower(Str::ascii($period->locale('es')->translatedFormat('F')));

        return 'Revalidacion_' . $monthName . '_' . $period->format('Y');
    }

    public static function sanitizeDownloadBaseName(string $baseName): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9_\-]+/', '_', trim($baseName)) ?? '';

        return $sanitized !== '' ? $sanitized : 'Revalidacion';
    }

}
