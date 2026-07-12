<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Property;
use App\Services\PropertyGalleryRepairService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PropertyGalleryRepairServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovers_main_webp_files_sorted_by_mtime(): void
    {
        $property = Property::query()->create($this->propertyAttributes());

        $dir = public_path('uploads/properties/'.$property->id);
        mkdir($dir, 0755, true);

        $older = $dir.'/older.webp';
        $newer = $dir.'/newer.webp';
        file_put_contents($older, 'old');
        file_put_contents($newer, 'new');
        touch($older, 1000);
        touch($newer, 2000);
        file_put_contents($dir.'/older_thumb.webp', 'thumb');
        file_put_contents($dir.'/skip.jpg', 'jpg');

        $service = app(PropertyGalleryRepairService::class);
        $names = $service->discoverGalleryBasenames((int) $property->id);

        $this->assertSame(['older.webp', 'newer.webp'], $names);
    }

    public function test_repair_updates_image_name_from_disk(): void
    {
        $property = Property::query()->create(array_merge($this->propertyAttributes(), [
            'image_name' => '',
        ]));

        $dir = public_path('uploads/properties/'.$property->id);
        mkdir($dir, 0755, true);
        file_put_contents($dir.'/one.webp', '1');
        file_put_contents($dir.'/two.webp', '2');

        $service = app(PropertyGalleryRepairService::class);
        $result = $service->repair((int) $property->id);

        $this->assertTrue($result['updated']);
        $this->assertSame('one.webp,two.webp', $result['csv']);
        $this->assertSame('one.webp,two.webp', (string) DB::table('properties')->where('id', $property->id)->value('image_name'));
    }

    public function test_dry_run_does_not_update_database(): void
    {
        $property = Property::query()->create(array_merge($this->propertyAttributes(), [
            'image_name' => '',
        ]));

        $dir = public_path('uploads/properties/'.$property->id);
        mkdir($dir, 0755, true);
        file_put_contents($dir.'/only.webp', '1');

        $service = app(PropertyGalleryRepairService::class);
        $result = $service->repair((int) $property->id, dryRun: true);

        $this->assertFalse($result['updated']);
        $this->assertSame('', (string) DB::table('properties')->where('id', $property->id)->value('image_name'));
    }

    public function test_command_dry_run_lists_desynced_property(): void
    {
        $property = Property::query()->create(array_merge($this->propertyAttributes(), [
            'image_name' => '',
        ]));

        $dir = public_path('uploads/properties/'.$property->id);
        mkdir($dir, 0755, true);
        file_put_contents($dir.'/cmd.webp', '1');

        Artisan::call('properties:repair-gallery', [
            '--dry-run' => true,
            '-v' => true,
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('property '.$property->id, $output);
        $this->assertStringContainsString('cmd.webp', $output);
        $this->assertSame('', (string) DB::table('properties')->where('id', $property->id)->value('image_name'));
    }

    protected function tearDown(): void
    {
        $dirs = glob(public_path('uploads/properties/*')) ?: [];
        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            foreach (glob($dir.'/*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($dir);
        }

        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyAttributes(): array
    {
        return [
            'title' => 'Repair Test',
            'lot_id' => 'REP-1',
            'slug' => 'repair-test-'.uniqid(),
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
        ];
    }
}
