<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Forms\Components\FileUpload;

trait ConfiguresPropertyGalleryUpload
{
    protected function makePropertyGalleryUpload(string $name = 'new_images'): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->multiple()
            ->reorderable()
            ->appendFiles()
            ->panelLayout('grid')
            ->imagePreviewHeight('4.5rem')
            ->itemPanelAspectRatio('1:1')
            ->maxFiles(30)
            ->dehydrated(true)
            ->extraAttributes(['class' => 'lh-property-gallery-upload']);
    }
}
