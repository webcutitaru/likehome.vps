<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BlockedDate;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IcalExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ical_export_returns_calendar_for_valid_token(): void
    {
        $token = 'a5a04341e5a249b4230983597f34ec82';
        $property = Property::query()->create([
            'title' => 'Export Test Apartment',
            'lot_id' => 'EXP-001',
            'slug' => 'export-test-apartment',
            'price' => 1000,
            'location' => 'Chișinău',
            'description' => 'Test',
            'city' => 'Chișinău',
            'district' => 'Centru',
            'address' => 'Str. Test 1',
            'description_long' => 'Long',
            'property_type' => 'Apartament',
            'rooms' => 2,
            'sleep_capacity' => 4,
            'area_sqm' => 50,
            'floor' => 1,
            'min_stay' => 1,
            'amenities' => '[]',
            'ical_export_token' => $token,
            'image_name' => 'default.jpg',
            'is_active' => true,
        ]);

        BlockedDate::query()->create([
            'property_id' => $property->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'source' => 'manual_block',
            'external_event_id' => 'block-1',
            'notes' => 'Test block',
        ]);

        $response = $this->get('/ical/'.$token.'.ics');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $response->assertSee('BEGIN:VCALENDAR', false);
        $response->assertSee('BEGIN:VEVENT', false);
        $response->assertSee('Manual Block', false);
    }

    public function test_ical_export_returns_404_for_unknown_token(): void
    {
        $response = $this->get('/ical/unknown-token-1234567890abcdef.ics');

        $response->assertNotFound();
    }

    public function test_legacy_export_php_redirects_to_ics_url(): void
    {
        $token = 'legacy-export-token-abc';

        Property::query()->create([
            'title' => 'Legacy Export Apartment',
            'lot_id' => 'LEG-001',
            'slug' => 'legacy-export-apartment',
            'price' => 1000,
            'location' => 'Chișinău',
            'description' => 'Test',
            'city' => 'Chișinău',
            'district' => 'Centru',
            'address' => 'Str. Test 2',
            'description_long' => 'Long',
            'property_type' => 'Apartament',
            'rooms' => 2,
            'sleep_capacity' => 4,
            'area_sqm' => 50,
            'floor' => 1,
            'min_stay' => 1,
            'amenities' => '[]',
            'ical_export_token' => $token,
            'image_name' => 'default.jpg',
            'is_active' => true,
        ]);

        $response = $this->get('/ical/export.php?token='.$token);

        $response->assertRedirect('/ical/'.$token.'.ics');
        $response->assertStatus(301);
    }
}
