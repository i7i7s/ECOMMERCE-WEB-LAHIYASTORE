<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\ConfirmOrderAvailability;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Payments\RecordManualPayment;
use App\Enums\Currency;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\BusinessAction;
use App\Models\CashAccount;
use App\Models\PhoneModel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'Order manual (WA/TikTok)';

    protected static bool $canCreateAnother = false;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();

        return BusinessAction::run(fn () => DB::transaction(function () use ($data, $user): Model {
            $order = app(PlaceOrder::class)->handle(
                customer: [
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'postal_code' => $data['postal_code'],
                    'prefecture' => $data['prefecture'],
                    'city' => $data['city'],
                    'address_line1' => $data['address_line1'],
                    'address_line2' => $data['address_line2'] ?? null,
                ],
                items: collect($data['lines'])->map(fn (array $line): array => $this->cartItem($line)),
                currency: Currency::from($data['currency']),
                source: OrderSource::from($data['source']),
                createdBy: $user,
            );

            // Availability of a model without mockup is checked with the vendor during the chat.
            if ($order->status === OrderStatus::MenungguKonfirmasi) {
                app(ConfirmOrderAvailability::class)->handle($order, $user);
            }

            if ($data['already_paid'] ?? false) {
                app(RecordManualPayment::class)->handle($order, CashAccount::findOrFail($data['cash_account_id']), $data['payment_proof'], $user);
            }

            return $order->refresh();
        }), 'Order dibuat');
    }

    /**
     * Converts a form line into the cart item shape PlaceOrder expects.
     *
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function cartItem(array $line): array
    {
        if ($line['type'] === 'product') {
            return ['type' => 'product', 'name' => 'Produk', 'qty' => (int) $line['qty'], 'refs' => ['product_variant_id' => (int) $line['product_variant_id']]];
        }

        $phoneModel = filled($line['phone_model_id'] ?? null) ? PhoneModel::find($line['phone_model_id']) : null;
        [$width, $height] = $this->imageSize($line['design'] ?? null);

        return [
            'type' => 'custom_case',
            'detail' => 'Custom casing '.($phoneModel?->name ?? $line['phone_model_text'] ?? ''),
            'qty' => (int) $line['qty'],
            'position_note' => $line['position_note'] ?? null,
            'refs' => [
                'phone_model_id' => $phoneModel?->id,
                'phone_model_text' => $phoneModel?->name ?? $line['phone_model_text'],
                'case_type_id' => (int) $line['case_type_id'],
                'case_color_id' => (int) $line['case_color_id'],
                'original_path' => $line['design'] ?? null,
                'image_width_px' => $width,
                'image_height_px' => $height,
            ],
        ];
    }

    /**
     * @return array{int|null, int|null}
     */
    private function imageSize(?string $path): array
    {
        $disk = Storage::disk(PlaceOrder::DESIGN_DISK);

        if ($path === null || ! $disk->exists($path)) {
            return [null, null];
        }

        $size = @getimagesize($disk->path($path));

        return $size ? [$size[0], $size[1]] : [null, null];
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): null
    {
        return null;
    }
}
