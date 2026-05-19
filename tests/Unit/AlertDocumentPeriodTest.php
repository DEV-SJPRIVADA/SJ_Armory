<?php

namespace Tests\Unit;

use App\Support\AlertDocumentPeriod;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AlertDocumentPeriodTest extends TestCase
{
    public function test_download_base_name_uses_single_selected_month(): void
    {
        Carbon::setTestNow('2026-05-19 12:00:00');

        $name = AlertDocumentPeriod::downloadBaseName(['2025-05']);

        $this->assertSame('Revalidacion_mayo_2025', $name);
    }

    public function test_download_base_name_uses_closest_month_when_multiple_selected(): void
    {
        Carbon::setTestNow('2026-05-19 12:00:00');

        $name = AlertDocumentPeriod::downloadBaseName(['2024-03', '2025-05', '2026-01']);

        $this->assertSame('Revalidacion_enero_2026', $name);
    }

    public function test_download_base_name_uses_current_month_when_none_selected(): void
    {
        Carbon::setTestNow('2026-05-19 12:00:00');

        $name = AlertDocumentPeriod::downloadBaseName([]);

        $this->assertSame('Revalidacion_mayo_2026', $name);
    }

    public function test_normalize_list_dedupes_and_sorts(): void
    {
        $months = AlertDocumentPeriod::normalizeList(['2026-01', '2025-05', '2025-05', 'invalid']);

        $this->assertSame(['2025-05', '2026-01'], $months);
    }
}
