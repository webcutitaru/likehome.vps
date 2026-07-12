<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use App\Services\PropertyDeleteService;
use App\Services\PropertySaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PropertyAdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin Features',
            'email' => 'admin-features@likehome.md',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_property_delete_service_removes_record(): void
    {
        $property = Property::query()->create([
            'title' => 'Delete Me',
            'lot_id' => 'DEL-1',
            'slug' => 'delete-me',
            'price' => 500,
            'location' => 'Chișinău',
            'description' => 'x',
            'city' => 'Chișinău',
            'description_long' => 'x',
            'property_type' => 'Apartament',
            'rooms' => 1,
            'sleep_capacity' => 2,
            'min_stay' => 1,
            'amenities' => '[]',
            'image_name' => 'default.jpg',
            'is_active' => true,
        ]);

        $result = app(PropertyDeleteService::class)->delete($property, $this->admin->id);

        $this->assertTrue($result['ok'], (string) ($result['error'] ?? 'unknown error'));
        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
    }

    public function test_gallery_order_normalization_preserves_sequence(): void
    {
        $ordered = ['c.webp', 'a.webp', 'b.webp'];

        $service = app(PropertySaveService::class);
        $post = $service->toLegacyPost([
            'existing_images' => $ordered,
            'amenities' => [],
            'pricing_periods' => [],
            'stay_discounts_global' => [],
        ]);

        $this->assertSame($ordered, array_values($post['existing_images']));
    }

    public function test_add_property_page_loads_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/properties/create');

        $response->assertOk();
        $response->assertSee('Creează proprietatea', false);
        $response->assertSee('reordona', false);
    }

    public function test_property_save_preserves_gallery_when_only_ical_link_changes(): void
    {
        $property = Property::query()->create([
            'title' => 'Gallery Preserve',
            'lot_id' => 'GAL-1',
            'slug' => 'gallery-preserve',
            'price' => 800,
            'location' => 'Chișinău',
            'description' => 'Short',
            'city' => 'Chișinău',
            'district' => 'Centru',
            'address' => 'Str. Test 1',
            'description_long' => 'Long description',
            'property_type' => 'Apartament',
            'rooms' => 2,
            'sleep_capacity' => 4,
            'min_stay' => 1,
            'amenities' => '[]',
            'image_name' => 'alpha.webp,beta.webp',
            'ical_import_link' => '',
            'is_active' => true,
        ]);

        $service = app(PropertySaveService::class);
        $formData = $service->loadFormData($property);
        $formData['existing_images'] = ['alpha.webp', 'beta.webp'];
        $formData['ical_import_link'] = 'https://example.com/calendar.ics';

        $result = $service->save($property, $formData, []);

        $this->assertTrue($result['ok'] ?? false, (string) ($result['error'] ?? 'save failed'));
        $property->refresh();
        $this->assertSame('alpha.webp,beta.webp', (string) $property->image_name);
        $this->assertSame('https://example.com/calendar.ics', (string) $property->ical_import_link);
    }
}
