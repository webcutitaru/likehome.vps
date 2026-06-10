<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    #[Test]
    public function legacy_properties_php_redirects_to_clean_url(): void
    {
        $response = $this->get('/properties.php');

        $response->assertRedirect(route('ro.properties.index', absolute: false));
    }

    #[Test]
    public function legacy_admin_edit_property_php_redirects_to_filament_edit_page(): void
    {
        $response = $this->get('/admin/edit-property.php?id=42');

        $response->assertRedirect('/admin/edit-property/42');
        $response->assertStatus(301);
    }

    #[Test]
    public function legacy_admin_edit_property_php_without_id_redirects_to_admin_dashboard(): void
    {
        $response = $this->get('/admin/edit-property.php');

        $response->assertRedirect('/admin');
        $response->assertStatus(301);
    }
}
