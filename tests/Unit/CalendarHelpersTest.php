<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Legacy\LegacyBridge;
use Tests\TestCase;

class CalendarHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        LegacyBridge::boot();
    }

    public function test_blocked_night_detection_accepts_eloquent_iso_dates(): void
    {
        $blocks = [[
            'start_date' => '2026-01-16T00:00:00.000000Z',
            'end_date' => '2026-01-21T00:00:00.000000Z',
            'source' => 'ical_import',
        ]];

        $this->assertTrue(lh_calendar_night_blocked('2026-01-16', $blocks));
        $this->assertTrue(lh_calendar_night_blocked('2026-01-20', $blocks));
        $this->assertFalse(lh_calendar_night_blocked('2026-01-21', $blocks));
        $this->assertSame('REALTY', lh_calendar_blocked_cell_label('2026-01-18', $blocks));
    }

    public function test_booking_night_detection_accepts_eloquent_iso_dates(): void
    {
        $bookings = [[
            'id' => 1,
            'guest_name' => 'Test Guest',
            'check_in' => '2026-05-05T00:00:00.000000Z',
            'check_out' => '2026-05-08T00:00:00.000000Z',
        ]];

        $this->assertNotNull(lh_calendar_booking_for_night('2026-05-06', $bookings));
        $this->assertNull(lh_calendar_booking_for_night('2026-05-08', $bookings));
    }
}
