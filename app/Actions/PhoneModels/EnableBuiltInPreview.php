<?php

namespace App\Actions\PhoneModels;

use App\Models\CaseType;
use App\Models\PhoneModel;
use App\Support\PhoneMockups;

/**
 * Turns on the 2D preview for a phone model that has a drawn mockup in PhoneMockups:
 * print size from the real phone, and a custom case price per case type (the case type
 * default, adjustable in the admin). Models with their own mockup or prices keep them.
 */
class EnableBuiltInPreview
{
    public function handle(PhoneModel $model): bool
    {
        if (! PhoneMockups::has($model->slug)) {
            return false;
        }

        $mockup = PhoneMockups::for($model->slug);

        $model->fill([
            'mockup_path' => $model->mockup_path ?? "mockups/{$model->slug}.png",
            'print_width_mm' => $model->print_width_mm ?? $mockup['width'],
            'print_height_mm' => $model->print_height_mm ?? $mockup['height'],
        ])->save();

        foreach (CaseType::where('is_active', true)->get() as $caseType) {
            $model->customCaseOptions()->firstOrCreate(['case_type_id' => $caseType->id], [
                'price_jpy' => $caseType->default_price_jpy,
                'price_idr' => $caseType->default_price_idr,
                'cost_price_idr' => $caseType->default_cost_price_idr,
            ]);
        }

        return true;
    }
}
