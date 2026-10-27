<?php

namespace App\Livewire\Store;

use App\Enums\Currency;
use App\Models\CaseColor;
use App\Models\CaseType;
use App\Models\CustomCaseOption;
use App\Models\PhoneModel;
use App\Models\Setting;
use App\Support\PhoneMockups;
use App\Support\Storefront\Cart;
use App\Support\Storefront\Storefront;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Custom Casing')]
class CustomCase extends Component
{
    use WithFileUploads;

    /** Original design image, uploaded by the Alpine editor as soon as it is picked. */
    public ?TemporaryUploadedFile $designImage = null;

    public string $search = '';

    /** Null when the customer typed a model that is not in the list. */
    #[Locked]
    public ?int $phoneModelId = null;

    #[Locked]
    public string $modelName = '';

    #[Locked]
    public bool $hasPreview = false;

    #[Locked]
    public ?string $phoneModelSlug = null;

    #[Locked]
    public ?float $printWidthMm = null;

    #[Locked]
    public ?float $printHeightMm = null;

    public string $caseType = '';

    public string $color = '';

    public bool $added = false;

    public function mount(): void
    {
        $default = PhoneModel::active()
            ->whereNotNull('mockup_path')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->first();

        if ($default !== null) {
            $this->selectModel($default->id);
        }

        $this->caseType = (string) CaseType::where('is_active', true)->orderBy('sort_order')->value('slug');
        $this->color = (string) CaseColor::where('is_active', true)->orderBy('sort_order')->value('slug');
    }

    /**
     * Active phone models matching the search, models with a preview first.
     *
     * @return Collection<int, PhoneModel>
     */
    #[Computed]
    public function suggestions(): Collection
    {
        $term = trim($this->search);

        if ($term === '') {
            return collect();
        }

        return PhoneModel::active()
            ->where('name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%')
            ->orderByRaw('mockup_path is null')
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->limit(6)
            ->get();
    }

    /**
     * Case types with their price for the selected model. Models with a mockup use their own
     * price per case type (and may lack some types); other models use the case type default.
     *
     * @return Collection<string, array{name: string, price_jpy: int, price_idr: int, available: bool, option_id: int|null}>
     */
    #[Computed]
    public function caseTypes(): Collection
    {
        $options = $this->hasPreview && $this->phoneModelId
            ? CustomCaseOption::where('phone_model_id', $this->phoneModelId)->get()->keyBy('case_type_id')
            : collect();

        return CaseType::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(function (CaseType $type) use ($options): array {
                $option = $options->get($type->id);

                return [$type->slug => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'price_jpy' => $this->hasPreview ? (int) $option?->price_jpy : $type->default_price_jpy,
                    'price_idr' => $this->hasPreview ? (int) $option?->price_idr : $type->default_price_idr,
                    'available' => $this->hasPreview ? (bool) $option?->is_available : true,
                    'option_id' => $option?->id,
                ]];
            });
    }

    /**
     * @return Collection<string, CaseColor>
     */
    #[Computed]
    public function colors(): Collection
    {
        return CaseColor::where('is_active', true)->orderBy('sort_order')->get()->keyBy('slug');
    }

    public function selectModel(int $id): void
    {
        $model = PhoneModel::active()->find($id);

        if ($model === null) {
            return;
        }

        $this->phoneModelId = $model->id;
        $this->phoneModelSlug = $model->slug;
        $this->modelName = $model->name;
        $this->hasPreview = $model->has_preview;

        // The drawn mockup has the phone's real proportions, so the print area follows it.
        $mockup = PhoneMockups::has($model->slug) ? PhoneMockups::for($model->slug) : null;
        $this->printWidthMm = $mockup['width'] ?? $model->print_width_mm;
        $this->printHeightMm = $mockup['height'] ?? $model->print_height_mm;
        $this->afterModelChanged();
    }

    /**
     * Use a phone model typed by the customer; it has no mockup, so no preview.
     */
    public function useManualModel(): void
    {
        $name = trim($this->search);

        if (mb_strlen($name) < 3) {
            $this->addError('search', 'Ketik nama tipe HP minimal 3 huruf.');

            return;
        }

        $this->phoneModelId = null;
        $this->phoneModelSlug = null;
        $this->modelName = mb_substr($name, 0, 80);
        $this->hasPreview = false;
        $this->printWidthMm = null;
        $this->printHeightMm = null;
        $this->afterModelChanged();
    }

    private function afterModelChanged(): void
    {
        $this->search = '';
        $this->added = false;
        unset($this->caseTypes);

        $types = $this->caseTypes;

        if (! ($types->get($this->caseType)['available'] ?? false)) {
            $this->caseType = (string) $types->filter(fn (array $type): bool => $type['available'])->keys()->first();
        }
    }

    /**
     * @param  array<string, mixed>  $design  Editor state sent by the Alpine editor.
     */
    public function addToCart(array $design, Cart $cart): void
    {
        $caseTypes = $this->caseTypes;

        $validated = Validator::make([...$design, 'design_image' => $this->designImage, 'model' => $this->modelName, 'case_type' => $this->caseType, 'color' => $this->color], [
            'design_image' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:'.((int) Setting::valueOf('max_design_upload_mb', '10') * 1024)],
            'model' => ['required'],
            'case_type' => ['required', Rule::in($caseTypes->filter(fn (array $type): bool => $type['available'])->keys())],
            'color' => ['required', Rule::in($this->colors->keys())],
            'file_name' => ['required', 'string', 'max:200'],
            'image_width' => ['required', 'integer', 'min:1'],
            'image_height' => ['required', 'integer', 'min:1'],
            'zoom' => ['required', 'numeric', 'between:1,4'],
            'rotation' => ['required', 'integer', Rule::in([0, 90, 180, 270])],
            'pos_x' => ['nullable', 'numeric', 'between:-5000,5000'],
            'pos_y' => ['nullable', 'numeric', 'between:-5000,5000'],
            'thumbnail' => ['nullable', 'string', 'starts_with:data:image/jpeg;base64,', 'max:200000'],
            'position_note' => ['nullable', 'string', 'max:500'],
            'agree_rights' => ['accepted'],
            'agree_color' => ['accepted'],
            'agree_no_preview' => [Rule::requiredIf(! $this->hasPreview), 'nullable', 'boolean'],
        ], [
            'design_image.required' => 'Gambar belum selesai diunggah.',
            'design_image.mimes' => 'Format gambar harus JPG atau PNG.',
            'design_image.max' => 'Ukuran gambar maksimal '.Setting::valueOf('max_design_upload_mb', '10').' MB.',
            'model.required' => 'Pilih tipe HP dulu.',
            'case_type.in' => 'Jenis case ini belum tersedia untuk tipe HP tersebut.',
            'file_name.required' => 'Upload gambar dulu.',
            'agree_rights.accepted' => 'Centang pernyataan hak atas gambar.',
            'agree_color.accepted' => 'Centang pernyataan perbedaan warna.',
            'position_note.required' => 'Tulis catatan posisi gambar.',
            'agree_no_preview.required' => 'Centang pernyataan tanpa preview.',
        ])->after(function ($validator) use ($design): void {
            if (! $this->hasPreview && ! ($design['agree_no_preview'] ?? false)) {
                $validator->errors()->add('agree_no_preview', 'Centang pernyataan tanpa preview.');
            }
        })->validate();

        $caseType = $caseTypes->get($this->caseType);
        $color = $this->colors->get($this->color);

        $cart->add([
            'type' => 'custom_case',
            'name' => $this->hasPreview ? 'Custom Casing' : 'Custom Casing (perlu konfirmasi)',
            'detail' => "{$this->modelName} · {$caseType['name']} · {$color->name}",
            'price_jpy' => $caseType['price_jpy'],
            'price_idr' => $caseType['price_idr'],
            'has_preview' => $this->hasPreview,
            'needs_confirmation' => ! $this->hasPreview,
            'thumbnail' => $validated['thumbnail'] ?? null,
            'position_note' => $validated['position_note'] ?? null,
            'refs' => [
                'phone_model_id' => $this->phoneModelId,
                'phone_model_text' => $this->modelName,
                'custom_case_option_id' => $this->hasPreview ? $caseType['option_id'] : null,
                'case_type_id' => $caseType['id'],
                'case_color_id' => $color->id,
                'image_width_px' => (int) $validated['image_width'],
                'image_height_px' => (int) $validated['image_height'],
                'scale' => (float) $validated['zoom'],
                'rotation' => (int) $validated['rotation'],
                'pos_x' => (float) ($validated['pos_x'] ?? 0),
                'pos_y' => (float) ($validated['pos_y'] ?? 0),
                'original_path' => $this->designImage->store('design-uploads', 'local'),
            ],
        ]);

        $this->reset('designImage');

        $this->added = true;
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        $storefront = app(Storefront::class);
        $currency = Currency::current();
        $column = $currency === Currency::Jpy ? 'price_jpy' : 'price_idr';

        return view('livewire.store.custom-case', [
            'caseTypeOptions' => $this->caseTypes->map(fn (array $type): array => [...$type, 'price_label' => $type['available'] ? $currency->format($type[$column]) : 'Tidak tersedia']),
            'colorOptions' => $this->colors,
            'batch' => $storefront->openBatch(),
            'etaLabel' => $storefront->etaLabel(),
            'mockup' => match (true) {
                $this->hasPreview => PhoneMockups::for($this->phoneModelSlug, $this->printWidthMm, $this->printHeightMm),
                PhoneMockups::has($this->phoneModelSlug) => PhoneMockups::for($this->phoneModelSlug),
                $this->modelName !== '' => PhoneMockups::approximate($this->modelName),
                default => null,
            },
            'editorConfig' => ['maxUploadMb' => (int) Setting::valueOf('max_design_upload_mb', '10')],
        ]);
    }
}
