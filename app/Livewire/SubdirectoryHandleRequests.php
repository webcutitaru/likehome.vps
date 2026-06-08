<?php

namespace App\Livewire;

use Livewire\Mechanisms\HandleRequests\HandleRequests as BaseHandleRequests;

class SubdirectoryHandleRequests extends BaseHandleRequests
{
    public function getUpdateUri(): string
    {
        $uri = parent::getUpdateUri();
        $basePath = $this->subdirectoryBasePath();

        if ($basePath !== '' && ! str_starts_with($uri, $basePath)) {
            return $basePath.$uri;
        }

        return $uri;
    }

    private function subdirectoryBasePath(): string
    {
        $basePath = parse_url(rtrim((string) config('app.url'), '/'), PHP_URL_PATH) ?: '';

        return ($basePath === '' || $basePath === '/') ? '' : rtrim($basePath, '/');
    }
}
