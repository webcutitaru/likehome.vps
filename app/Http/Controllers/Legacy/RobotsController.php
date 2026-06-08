<?php

declare(strict_types=1);

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Legacy\LegacyBridge;
use Illuminate\Http\Response;

final class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        LegacyBridge::boot();

        $prefix = defined('SITE_BASE_PATH') ? (string) SITE_BASE_PATH : '';
        if ($prefix !== '' && isset($prefix[0]) && $prefix[0] !== '/') {
            $prefix = '/'.$prefix;
        }
        $prefix = $prefix === '/' ? '' : $prefix;
        $adminPath = ($prefix === '' ? '' : rtrim($prefix, '/')).'/admin/';

        $body = "User-agent: *\n"
            ."Allow: /\n"
            .'Disallow: '.$adminPath."\n\n"
            .'Sitemap: '.lh_absolute_url('sitemap.xml')."\n";

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
