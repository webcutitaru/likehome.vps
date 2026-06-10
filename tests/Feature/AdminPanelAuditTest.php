<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Legacy\LegacyBridge;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyPricingPeriod;
use App\Models\User;
use App\Services\PropertySaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPanelAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin Audit',
            'email' => 'admin-audit@likehome.md',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_all_admin_sections_render_expected_content(): void
    {
        $property = $this->createProperty();
        $booking = $this->createBooking($property);

        $pages = [
            ['/admin', ['Salutare', 'Proprietăți']],
            ['/admin/property-calendar', ['Calendar rezervări și prețuri', 'calCalendarRoot']],
            ['/admin/bookings', ['Rezervări']],
            ['/admin/bookings/'.$booking->id, ['Rezervare', (string) $booking->id]],
            ['/admin/properties', ['Proprietăți']],
            ['/admin/properties/create', ['Adaugă locuință nouă', 'Creează proprietatea']],
            ['/admin/discount-coupons', ['Cupoane']],
            ['/admin/discount-coupons/create', ['Cod (unic)']],
            ['/admin/users', ['Utilizatori']],
            ['/admin/users/create', ['Confirmă parola']],
            ['/admin/admin-activity-logs', ['Jurnal activitate']],
            ['/admin/edit-property/'.$property->id, ['Editează:', 'Actualizează proprietatea']],
        ];

        foreach ($pages as [$url, $needles]) {
            $response = $this->actingAs($this->admin)->get($url);
            $response->assertOk("Failed: {$url}");
            foreach ($needles as $needle) {
                $response->assertSee($needle, false);
            }
        }
    }

    public function test_calendar_renders_blocked_cells_in_view_range(): void
    {
        $property = $this->createProperty();

        BlockedDate::query()->create([
            'property_id' => $property->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-15',
            'source' => 'manual_block',
            'external_event_id' => 'manual-test-1',
            'notes' => 'Test block',
        ]);

        $response = $this->actingAs($this->admin)->get(
            '/admin/property-calendar?from=2026-06-01&days=30'
        );

        $response->assertOk();
        $response->assertSee('cal-cell-blocked', false);
        $response->assertSee('Blocat', false);
    }

    public function test_calendar_has_sticky_header_structure(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/property-calendar');

        $response->assertOk();
        $html = (string) $response->getContent();
        $this->assertMatchesRegularExpression(
            '/id="calVertScroll"[^>]*>\s*<div class="cal-sticky-header[^"]*sticky top-0/s',
            $html,
            'Sticky header must be the first child inside #calVertScroll'
        );
    }

    public function test_calendar_renders_coupon_indicator_on_booking_cell(): void
    {
        $property = $this->createProperty();

        Booking::query()->create([
            'property_id' => $property->id,
            'guest_name' => 'Coupon Guest',
            'guest_phone' => '+37360000001',
            'guest_email' => 'coupon@example.com',
            'check_in' => '2026-06-10',
            'check_out' => '2026-06-13',
            'guests' => 2,
            'total_price' => 3000,
            'coupon_code' => 'SUMMER10',
            'coupon_discount_amount' => 300,
            'status' => 'confirmed',
            'locale' => 'ro',
        ]);

        $response = $this->actingAs($this->admin)->get(
            '/admin/property-calendar?from=2026-06-01&days=30'
        );

        $response->assertOk();
        $response->assertSee('cal-cell-booking', false);
        $response->assertSee('Coupon Guest', false);
        $response->assertSee('aria-hidden="true">%</span>', false);
    }

    public function test_calendar_renders_calendar_special_price_marker(): void
    {
        $property = $this->createProperty(['min_stay' => 2]);

        PropertyPricingPeriod::query()->create([
            'property_id' => $property->id,
            'date_start' => '2026-06-10',
            'date_end' => '2026-06-20',
            'price' => 900,
            'price_weekend' => 1000,
            'label' => 'Preț special (calendar)',
            'min_stay' => 3,
        ]);

        $response = $this->actingAs($this->admin)->get(
            '/admin/property-calendar?from=2026-06-01&days=30'
        );

        $response->assertOk();
        $html = (string) $response->getContent();
        $this->assertStringContainsString('whitespace-nowrap">*3</span>', $html);
        $this->assertStringContainsString('font-extrabold text-slate-900 leading-tight">900</span>', $html);
        $this->assertStringContainsString('sejur min. 3 nop', $html);
    }

    public function test_property_editor_loads_correct_ical_export_url(): void
    {
        $property = $this->createProperty(['ical_export_token' => 'export-token-abc']);

        $data = app(PropertySaveService::class)->loadFormData($property);

        $this->assertStringContainsString('/ical/export-token-abc.ics', (string) ($data['ical_export_url'] ?? ''));
        $this->assertStringNotContainsString('/api/ical/export', (string) ($data['ical_export_url'] ?? ''));
    }

    public function test_admin_post_routes_accept_dual_csrf_tokens(): void
    {
        LegacyBridge::boot();
        $property = $this->createProperty();

        $this->actingAs($this->admin)->get('/admin/property-calendar');
        $legacyToken = 'legacy-audit-csrf-token';
        session(['_lh_csrf_token' => $legacyToken]);
        $laravelToken = session()->token();

        $calendarPost = $this->post('/admin/calendar-action', [
            '_token' => $laravelToken,
            'csrf_token' => $legacyToken,
            'calendar_action' => 'special_price',
            'property_id' => $property->id,
            'range_start' => '2099-01-01',
            'range_end_exclusive' => '2099-01-03',
            'price' => '500',
            'redirect_from' => '2099-01-01',
            'redirect_days' => 30,
        ]);
        $calendarPost->assertRedirect();
        $calendarPost->assertSessionHasNoErrors();

        $booking = $this->createBooking($property);
        $bookingPost = $this->post('/admin/booking-action', [
            '_token' => $laravelToken,
            'csrf_token' => $legacyToken,
            'action' => 'cancel',
            'booking_id' => $booking->id,
            'return_page' => 'bookings',
        ]);
        $bookingPost->assertRedirect();
        $bookingPost->assertSessionHasNoErrors();
    }

    public function test_admin_pages_do_not_send_strict_csp(): void
    {
        $routes = [
            '/admin',
            '/admin/property-calendar',
            '/admin/edit-property/'.$this->createProperty()->id,
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route);
            $response->assertOk();
            $response->assertHeaderMissing('Content-Security-Policy');
        }
    }

    private function createProperty(array $overrides = []): Property
    {
        return Property::query()->create(array_merge([
            'title' => 'Audit Apartment',
            'lot_id' => 'AUD-001',
            'slug' => 'audit-apartment',
            'price' => 1200,
            'location' => 'Chișinău',
            'description' => 'Test',
            'city' => 'Chișinău',
            'district' => 'Centru',
            'address' => 'Str. Audit 1',
            'description_long' => 'Long description',
            'property_type' => 'Apartament',
            'rooms' => 2,
            'sleep_capacity' => 4,
            'area_sqm' => 50,
            'floor' => 3,
            'min_stay' => 1,
            'amenities' => '[]',
            'image_name' => 'default.jpg',
            'is_active' => true,
        ], $overrides));
    }

    private function createBooking(Property $property): Booking
    {
        return Booking::query()->create([
            'property_id' => $property->id,
            'guest_name' => 'Audit Guest',
            'guest_phone' => '+37360000000',
            'guest_email' => 'guest@example.com',
            'check_in' => '2026-07-01',
            'check_out' => '2026-07-05',
            'guests' => 2,
            'total_price' => 4000,
            'status' => 'confirmed',
            'locale' => 'ro',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
        ]);
    }
}
