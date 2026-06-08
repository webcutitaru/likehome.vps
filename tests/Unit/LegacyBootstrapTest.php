<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Legacy\LegacyBridge;
use App\Services\PropertySaveService;
use Tests\TestCase;

class LegacyBootstrapTest extends TestCase
{
    public function test_legacy_bootstrap_and_property_save_includes_do_not_redeclare_helpers(): void
    {
        LegacyBridge::boot();
        $this->assertTrue(function_exists('lh_property_image_url'));

        $service = app(PropertySaveService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('loadLegacyIncludes');
        $method->setAccessible(true);
        $method->invoke($service);

        LegacyBridge::boot();

        $this->assertTrue(function_exists('lh_edit_property_save_from_post'));
        $this->assertTrue(function_exists('lh_store_property_image'));
    }

    public function test_csp_is_skipped_for_admin_and_livewire_paths(): void
    {
        LegacyBridge::boot();
        $this->assertTrue(function_exists('lh_should_skip_csp'));

        $this->get('/admin');
        $this->assertTrue(lh_should_skip_csp());

        $this->get('/livewire/update');
        $this->assertTrue(lh_should_skip_csp());

        $this->get('/');
        $this->assertFalse(lh_should_skip_csp());
    }
}
