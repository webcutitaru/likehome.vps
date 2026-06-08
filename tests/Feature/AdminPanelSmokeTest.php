<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin Test',
            'email' => 'admin-test@likehome.md',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_pages_load_without_server_error(): void
    {
        $property = Property::query()->create([
            'title' => 'Test Apartment',
            'lot_id' => 'T-001',
            'slug' => 'test-apartment',
            'price' => 1000,
            'location' => 'Chișinău',
            'description' => 'Test',
            'city' => 'Chișinău',
            'district' => 'Centru',
            'address' => 'Str. Test 1',
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
        ]);

        $routes = [
            '/admin',
            '/admin/property-calendar',
            '/admin/bookings',
            '/admin/properties',
            '/admin/properties/create',
            '/admin/discount-coupons',
            '/admin/discount-coupons/create',
            '/admin/users',
            '/admin/admin-activity-logs',
            '/admin/edit-property/'.$property->id,
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route);
            $response->assertOk("Failed loading {$route}: ".$response->getStatusCode());
        }

        $calendar = $this->actingAs($this->admin)->get('/admin/property-calendar');
        $calendar->assertSee('Calendar rezervări și prețuri', false);
        $calendar->assertSee('calCalendarRoot', false);

        $edit = $this->actingAs($this->admin)->get('/admin/edit-property/'.$property->id);
        $edit->assertSee('Actualizează proprietatea', false);
        $edit->assertSee('Editează:', false);
    }

    public function test_admin_skips_strict_csp_so_livewire_can_eval(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();
        $response->assertHeaderMissing('Content-Security-Policy');
    }
}
