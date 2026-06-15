<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\View;

trait ConfiguresPropertyGalleryUpload
{
    protected function propertyGalleryUploadScripts(): View
    {
        return View::make('filament.pages.partials.property-gallery-upload-scripts');
    }

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
            ->maxFiles(100)
            ->maxSize(12_288)
            ->maxParallelUploads(4)
            ->dehydrated(true)
            ->extraAttributes(['class' => 'lh-property-gallery-upload']);
    }
}
