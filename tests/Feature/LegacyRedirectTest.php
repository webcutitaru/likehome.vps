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
}
