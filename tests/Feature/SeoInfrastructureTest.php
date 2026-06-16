<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Legacy\RobotsController;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    private function useProductionSeoConfig(): void
    {
        config([
            'app.url' => 'https://likehome.md',
            'likehome.site_url' => 'https://likehome.md',
            'likehome.env.PUBLIC_SITE_URL' => 'https://likehome.md',
            'likehome.env.SITE_BASE_PATH' => '',
        ]);
        URL::forceRootUrl('https://likehome.md');
        URL::forceScheme('https');
    }

    #[Test]
    public function robots_txt_declares_sitemap_and_blocks_admin(): void
    {
        $this->useProductionSeoConfig();

        $response = $this->get(action(RobotsController::class));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Allow: /', false);
        $response->assertSee('Disallow: /admin/', false);
        $response->assertSee('Sitemap: https://likehome.md/sitemap.xml', false);
    }

    #[Test]
    public function www_host_redirects_to_canonical_host(): void
    {
        $this->useProductionSeoConfig();

        $response = $this->get('https://www.likehome.md/proprietati', [
            'Host' => 'www.likehome.md',
        ]);

        $response->assertRedirect('https://likehome.md/proprietati');
        $response->assertStatus(301);
    }

    #[Test]
    public function sitemap_xml_is_public_and_lists_urls(): void
    {
        $this->useProductionSeoConfig();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<loc>https://likehome.md</loc>', false);
        $response->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false);
    }
}
