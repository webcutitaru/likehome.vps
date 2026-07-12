<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PropertyGalleryRepairService;
use Illuminate\Console\Command;

class RepairPropertyGalleryCommand extends Command
{
    protected $signature = 'properties:repair-gallery
                            {propertyId? : Repair a single property by ID}
                            {--dry-run : Show CSV without updating the database}
                            {--mirror-legacy : Copy missing files into app/Legacy/uploads/properties/{id}/}';

    protected $description = 'Rebuild properties.image_name from gallery files on disk when DB is empty';

    public function handle(PropertyGalleryRepairService $service): int
    {
        $propertyIdArg = $this->argument('propertyId');
        $onlyId = $propertyIdArg !== null && $propertyIdArg !== ''
            ? (int) $propertyIdArg
            : null;

        if ($onlyId !== null && $onlyId <= 0) {
            $this->error('Invalid property ID.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $mirrorLegacy = (bool) $this->option('mirror-legacy');

        if ($onlyId !== null) {
            $propertyIds = [$onlyId];
        } else {
            $propertyIds = $service->findDesyncedPropertyIds();
        }

        if ($propertyIds === []) {
            $this->info('No desynced properties found (empty image_name with files on disk).');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d propert%s...',
            $dryRun ? 'Dry-run for' : 'Repairing',
            count($propertyIds),
            count($propertyIds) === 1 ? 'y' : 'ies'
        ));

        $failures = 0;

        foreach ($propertyIds as $propertyId) {
            $imageNames = $service->discoverGalleryBasenames($propertyId);

            if ($imageNames === []) {
                $this->warn("property {$propertyId}: no gallery files found on disk");
                ++$failures;

                continue;
            }

            $result = $service->repair($propertyId, $dryRun, $mirrorLegacy);

            $this->line(sprintf(
                'property %d: %d images%s%s',
                $propertyId,
                count($imageNames),
                $dryRun ? ' (dry-run)' : ($result['updated'] ? ' updated' : ''),
                $mirrorLegacy ? ', mirrored '.$result['mirrored'].' file(s)' : ''
            ));

            if ($this->output->isVerbose()) {
                $this->line($result['csv']);
            }
        }

        if ($dryRun) {
            $this->comment('Dry-run complete — no database changes were made.');
        }

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
