<?php

namespace App\Filament\Pages;

use App\Filament\NavigationGroup;
use App\Models\Setting;
use App\Support\Credit\CreditTerms;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class StoreSettings extends Page
{
    protected string $view = 'filament.pages.store-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Pengaturan;

    protected static ?string $title = 'Pengaturan toko';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, array{string, int, int, int}> */
    public const NUMBERS = [
        'eta_min_days' => ['Estimasi tiba minimum (hari)', 14, 1, 365],
        'eta_max_days' => ['Estimasi tiba maksimum (hari)', 21, 1, 365],
        'payment_window_hours' => ['Batas pembayaran (jam)', 24, 1, 720],
        'auto_complete_days' => ['Otomatis selesai setelah dikirim (hari)', 7, 1, 90],
        'complaint_days_after_received' => ['Batas komplain setelah diterima (hari)', 3, 1, 90],
        'complaint_days_after_shipped' => ['Batas komplain setelah dikirim (hari)', 10, 1, 180],
        'proof_unverified_alert_hours' => ['Peringatan bukti belum diverifikasi (jam)', 12, 1, 168],
        'arrived_unshipped_alert_days' => ['Peringatan antrean packing (hari)', 2, 1, 30],
        'confirmation_reminder_business_days' => ['Pengingat konfirmasi (hari kerja)', 2, 1, 30],
        'max_design_upload_mb' => ['Maksimum desain (MB)', 10, 1, 10],
        'max_proof_upload_mb' => ['Maksimum bukti bayar (MB)', 5, 1, 10],
    ];

    /** @var list<int> Tenor choices for credit purchases, in months. */
    public const CREDIT_TENORS = [3, 6, 10, 12, 18, 24];

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->is_active && auth()->user()?->isAdmin());
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $defaults = [
            'store_whatsapp' => '', 'store_email' => '', 'store_tiktok' => '',
            'batch_period' => 'weekly', 'batch_cutoff_day' => 'sunday', 'batch_cutoff_time' => '23:59',
            'phone_sync_frequency' => 'monthly',
            'policy_preorder' => '', 'policy_payment' => '', 'policy_cancellation' => '', 'policy_complaints' => '',
        ];
        foreach (self::NUMBERS as $key => $spec) {
            $defaults[$key] = $spec[1];
        }
        foreach ([...$defaults, ...CreditTerms::DEFAULTS] as $key => $default) {
            $defaults[$key] = Setting::valueOf($key, (string) $default);
        }
        $defaults['credit_tenors'] = array_values(array_filter(explode(',', (string) $defaults['credit_tenors'])));
        $this->form->fill($defaults);
    }

    public function form(Schema $schema): Schema
    {
        $numbers = [];
        foreach (self::NUMBERS as $key => [$label, $default, $min, $max]) {
            $numbers[] = TextInput::make($key)->label($label)->integer()->required()->minValue($min)->maxValue($max);
        }

        return $schema->statePath('data')->components([
            Section::make('Kontak toko')->columns(2)->schema([
                TextInput::make('store_whatsapp')->label('WhatsApp (kode negara + nomor)')->tel()->regex('/^\\+?[0-9]{8,15}$/')->maxLength(16),
                TextInput::make('store_email')->label('Email toko')->email()->maxLength(254),
                TextInput::make('store_tiktok')->label('Link TikTok')->url()->placeholder('https://www.tiktok.com/@lahiyastore')->maxLength(255)
                    ->helperText('Tampil di footer toko. Kosongkan untuk menyembunyikan.'),
            ]),
            Section::make('Jadwal')->description('Zona waktu tetap Asia/Tokyo (JST), mata uang laporan IDR. Jadwal baru berlaku pada batch berikutnya; cutoff batch yang sudah dibuat tidak berubah.')->columns(2)->schema([
                Select::make('batch_period')->label('Periode batch preorder')->options(['monthly' => 'Bulanan (cutoff hari terakhir tiap bulan)', 'weekly' => 'Mingguan'])
                    ->required()->live(),
                Select::make('batch_cutoff_day')->label('Hari cutoff')->visible(fn (Get $get): bool => $get('batch_period') === 'weekly')->options([
                    'monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis',
                    'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu',
                ])->required(),
                TextInput::make('batch_cutoff_time')->label('Jam cutoff (JST)')->required()->rules(['date_format:H:i']),
                Select::make('phone_sync_frequency')->label('Frekuensi sinkronisasi HP')->options(['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'])
                    ->required()->helperText('Menambah tipe HP baru dari daftar bawaan (Tipe HP → Sinkronkan daftar HP). Tidak menghapus atau mengubah tipe buatanmu.'),
            ]),
            Section::make('Batas operasional')->columns(2)
                ->description('Batas upload sampai 10 MB mengikuti kapasitas unggahan saat ini.')->schema($numbers),
            Section::make('Kredit HP')->columns(2)
                ->description('Berlaku untuk pengajuan baru. Kredit yang sudah diajukan tetap memakai syarat saat diajukan. Harga kredit dalam Yen.')
                ->schema([
                    CheckboxList::make('credit_tenors')->label('Tenor yang ditawarkan (bulan)')
                        ->options(array_combine(self::CREDIT_TENORS, array_map(fn (int $months): string => "{$months} bulan", self::CREDIT_TENORS)))
                        ->columns(6)->required()->columnSpanFull(),
                    TextInput::make('credit_min_dp_percent')->label('DP minimal (%)')->integer()->minValue(0)->maxValue(90)->suffix('%')->required(),
                    TextInput::make('credit_margin_percent')->label('Margin flat per bulan (%)')->numeric()->minValue(0)->maxValue(10)->step(0.1)->suffix('%')->required()
                        ->helperText('Dihitung dari jumlah yang dicicil (harga − DP), dikali jumlah bulan.'),
                    TextInput::make('credit_admin_fee')->label('Biaya admin (sekali)')->integer()->minValue(0)->maxValue(100000)->prefix('¥')->required(),
                    TextInput::make('credit_whatsapp')->label('WhatsApp verifikasi kredit')->tel()->regex('/^\\+?[0-9]{8,15}$/')->maxLength(16)
                        ->helperText('Kosongkan untuk memakai WhatsApp toko.'),
                    Textarea::make('credit_verification_message')->label('Pesan otomatis saat pelanggan minta verifikasi')->rows(3)->maxLength(1000)->required()
                        ->helperText('Bisa memakai {nama}, {email} dan {telepon}.')->columnSpanFull(),
                    Textarea::make('credit_terms')->label('Syarat kredit (tampil di halaman produk)')->rows(5)->maxLength(5000)->columnSpanFull(),
                ]),
            Section::make('Teks kebijakan pelanggan')->description('Teks biasa, tanpa HTML. Kosongkan untuk memakai kebijakan bawaan yang mengikuti angka pengaturan.')->schema([
                Textarea::make('policy_preorder')->label('Preorder')->rows(5)->maxLength(10000),
                Textarea::make('policy_payment')->label('Pembayaran')->rows(5)->maxLength(10000),
                Textarea::make('policy_cancellation')->label('Pembatalan')->rows(5)->maxLength(10000),
                Textarea::make('policy_complaints')->label('Komplain, retur dan refund')->rows(7)->maxLength(10000),
            ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $allowed = [
            ...array_keys(self::NUMBERS), 'store_whatsapp', 'store_email', 'store_tiktok', 'batch_period', 'batch_cutoff_day',
            'batch_cutoff_time', 'phone_sync_frequency', 'policy_preorder', 'policy_payment',
            'policy_cancellation', 'policy_complaints', ...array_keys(CreditTerms::DEFAULTS),
        ];
        $data = array_intersect_key($this->form->getState(), array_flip($allowed));
        $tenors = array_map('intval', (array) ($data['credit_tenors'] ?? []));
        sort($tenors);
        $data['credit_tenors'] = implode(',', $tenors);
        foreach ([
            ['eta_min_days', 'eta_max_days'],
            ['complaint_days_after_received', 'complaint_days_after_shipped'],
        ] as [$minimum, $maximum]) {
            if ((int) $data[$minimum] > (int) $data[$maximum]) {
                throw ValidationException::withMessages(['data.'.$maximum => 'Nilai maksimum tidak boleh lebih kecil dari minimum.']);
            }
        }
        DB::transaction(function () use ($data): void {
            $changes = [];
            foreach ($data as $key => $value) {
                $value = (string) ($value ?? '');
                $before = Setting::valueOf($key);
                if ($before !== $value) {
                    $changes[$key] = ['before' => $before, 'after' => $value];
                    Setting::updateOrCreate(['key' => $key], ['value' => $value]);
                }
            }
            if ($changes !== []) {
                DB::table('store_setting_changes')->insert([
                    'user_id' => auth()->id(), 'changes' => json_encode($changes, JSON_THROW_ON_ERROR), 'created_at' => now(),
                ]);
            }
        });
        Notification::make()->title('Pengaturan tersimpan')->success()->send();
    }
}
