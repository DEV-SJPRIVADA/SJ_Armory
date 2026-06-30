<?php

namespace Tests\Unit;

use App\Services\Formats\MonthlyWeaponReviewQueryService;
use Tests\TestCase;

class MonthlyWeaponReviewQueryServiceTest extends TestCase
{
    public function test_page_count_returns_zero_for_empty_result_sets(): void
    {
        $service = new MonthlyWeaponReviewQueryService;

        $this->assertSame(0, $service->pageCount(0));
        $this->assertSame(1, $service->pageCount(1));
        $this->assertSame(1, $service->pageCount(20));
        $this->assertSame(2, $service->pageCount(21));
    }
}
