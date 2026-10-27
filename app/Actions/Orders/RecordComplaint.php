<?php

namespace App\Actions\Orders;

use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderComplaint;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecordComplaint
{
    /** @param array<string, mixed> $data */
    public function handle(Order $order, User $by, array $data): OrderComplaint
    {
        abort_unless($by->is_active && in_array($by->role, [UserRole::Admin, UserRole::Staff], true), 403);
        $data = Validator::make($data, [
            'type' => ['required', Rule::enum(ComplaintType::class)],
            'description' => ['required', 'string', 'max:5000'],
            'received_at' => ['required', 'date_format:Y-m-d'],
            'evidence_path' => ['required', 'string', 'max:255'],
            'photo_path' => ['required', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($order, $data): OrderComplaint {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($order->status, [OrderStatus::Dikirim, OrderStatus::Selesai], true) || $order->shipped_at === null) {
                throw new BusinessRuleException('Komplain hanya untuk order yang sudah dikirim.');
            }

            $received = CarbonImmutable::parse($data['received_at'], 'Asia/Tokyo');
            $today = CarbonImmutable::now('Asia/Tokyo')->startOfDay();
            $shipped = $order->shipped_at->copy()->timezone('Asia/Tokyo')->startOfDay();
            if ($received->lt($shipped) || $received->gt($today)) {
                throw new BusinessRuleException('Tanggal diterima harus antara tanggal pengiriman dan hari ini.');
            }
            if ($today->gt($received->addDays((int) Setting::valueOf('complaint_days_after_received', '3')))
                || $today->gt($shipped->addDays((int) Setting::valueOf('complaint_days_after_shipped', '10')))) {
                throw new BusinessRuleException('Batas waktu pengajuan komplain sudah lewat.');
            }
            if ($order->complaints()->where('status', ComplaintStatus::Terbuka)->exists()) {
                throw new BusinessRuleException('Order ini masih memiliki komplain terbuka.');
            }
            foreach (['evidence_path', 'photo_path'] as $field) {
                if (! str_starts_with($data[$field], 'complaints/') || ! Storage::disk('local')->exists($data[$field])) {
                    throw new BusinessRuleException('Video unboxing dan foto harus diupload terlebih dahulu.');
                }
            }

            return $order->complaints()->create([...$data, 'status' => ComplaintStatus::Terbuka]);
        });
    }
}
