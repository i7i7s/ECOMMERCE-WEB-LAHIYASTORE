<?php

namespace App\Actions\PhoneModels;

use App\Enums\PhoneModelSource;
use App\Models\PhoneBrand;
use App\Models\PhoneModel;
use App\Support\PhoneCatalog;
use App\Support\PhoneMockups;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Adds and updates phone models from the bundled catalog (CUST-12). Never deletes, and never
 * touches models the admin created by hand (same slug), so mockups and prices stay as they are.
 */
class SyncPhoneModels
{
    public function __construct(private EnableBuiltInPreview $enableBuiltInPreview) {}

    /**
     * @param  array<string, list<string>>|null  $catalog  Brand name => model names; defaults to PhoneCatalog::MODELS.
     * @return array{created: int, updated: int}
     */
    public function handle(?array $catalog = null): array
    {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($catalog, &$created, &$updated): void {
            $brandOrder = 0;

            foreach ($catalog ?? PhoneCatalog::MODELS as $brandName => $models) {
                $brand = PhoneBrand::firstOrCreate(['name' => $brandName], ['sort_order' => $brandOrder]);
                $brandOrder += 10;

                foreach ($models as $position => $name) {
                    $slug = Str::slug(str_replace('+', ' plus', $name));
                    $externalId = "katalog:{$slug}";
                    $model = PhoneModel::query()->where('external_id', $externalId)->orWhere('slug', $slug)->first();

                    if ($model === null) {
                        PhoneModel::create([
                            'phone_brand_id' => $brand->id,
                            'name' => $name,
                            'slug' => $slug,
                            'source' => PhoneModelSource::Katalog,
                            'external_id' => $externalId,
                            'sort_order' => 1000 + $position,
                            'synced_at' => now(),
                        ]);
                        $created++;

                        continue;
                    }

                    if ($model->source !== PhoneModelSource::Katalog) {
                        continue;
                    }

                    $model->fill(['phone_brand_id' => $brand->id, 'name' => $name]);

                    if ($model->isDirty()) {
                        $updated++;
                    }

                    $model->synced_at = now();
                    $model->save();
                }
            }
        });

        // Models with a drawn mockup (e.g. iPhone 18 Pro) get the 2D preview right away.
        PhoneModel::query()
            ->whereIn('slug', PhoneMockups::slugs())
            ->where(fn ($query) => $query->whereNull('mockup_path')->orWhereNull('print_width_mm')->orWhereNull('print_height_mm')->orWhereDoesntHave('customCaseOptions'))
            ->each(fn (PhoneModel $model): bool => $this->enableBuiltInPreview->handle($model));

        return ['created' => $created, 'updated' => $updated];
    }
}
