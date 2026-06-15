<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\PropertyTranslation;
use App\Services\TextTranslationService;
use Illuminate\Console\Command;

class TranslatePropertyDescriptionsCommand extends Command
{
    protected $signature = 'properties:translate-descriptions
                            {--locale=* : Target locales (en, ru). Default: both}
                            {--property= : Only this property ID}
                            {--force : Re-translate even if a translation already exists}
                            {--dry-run : Show what would be translated without saving}';

    protected $description = 'Auto-translate property description_long (RO) into EN/RU and store in property_translations';

    /** @var list<string> */
    private array $targetLocales = ['en', 'ru'];

    public function handle(TextTranslationService $translator): int
    {
        $locales = $this->option('locale');
        if (is_array($locales) && $locales !== []) {
            $this->targetLocales = array_values(array_intersect(['en', 'ru'], $locales));
        }

        if ($this->targetLocales === []) {
            $this->error('No valid target locales. Use --locale=en and/or --locale=ru');

            return self::FAILURE;
        }

        $query = Property::query()
            ->where('is_active', true)
            ->whereRaw("TRIM(COALESCE(description_long, '')) <> ''")
            ->orderBy('id');

        if ($this->option('property')) {
            $query->where('id', (int) $this->option('property'));
        }

        $properties = $query->get();
        if ($properties->isEmpty()) {
            $this->warn('No active properties with description_long found.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($properties as $property) {
            $source = trim((string) $property->description_long);
            if ($source === '') {
                continue;
            }

            foreach ($this->targetLocales as $locale) {
                $existing = PropertyTranslation::query()
                    ->where('property_id', $property->id)
                    ->where('locale', $locale)
                    ->first();

                if ($existing !== null && ! $force && trim((string) $existing->description_long) !== '') {
                    ++$skipped;
                    $this->line(sprintf('  skip #%d [%s] — translation exists', $property->id, $locale));

                    continue;
                }

                $this->info(sprintf('Translating #%d [%s] %s', $property->id, $locale, $property->title));

                if ($dryRun) {
                    ++$created;

                    continue;
                }

                try {
                    $translated = $translator->translate($source, $locale, 'ro');
                    $title = (string) $property->title;
                    $slug = (string) ($property->slug ?: $title);
                    $descShort = mb_substr($translated, 0, 220);

                    PropertyTranslation::query()->updateOrCreate(
                        [
                            'property_id' => $property->id,
                            'locale' => $locale,
                        ],
                        [
                            'title' => $title,
                            'slug' => $slug,
                            'description' => $descShort,
                            'description_long' => $translated,
                        ]
                    );

                    ++$created;
                } catch (\Throwable $e) {
                    ++$failed;
                    $this->error(sprintf('  failed #%d [%s]: %s', $property->id, $locale, $e->getMessage()));
                }
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. translated=%d skipped=%d failed=%d%s',
            $created,
            $skipped,
            $failed,
            $dryRun ? ' (dry-run)' : ''
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
