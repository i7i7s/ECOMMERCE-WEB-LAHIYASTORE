<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\CreditApplicationStatus;
use App\Enums\CreditVerification;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'is_active', 'credit_status', 'credit_requested_at', 'credit_verified_at', 'credit_verified_by', 'credit_limit', 'credit_note'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'credit_status' => 'none',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'credit_status' => CreditVerification::class,
            'credit_requested_at' => 'datetime',
            'credit_verified_at' => 'datetime',
            'credit_limit' => 'integer',
        ];
    }

    /**
     * Only active admin and staff accounts may sign in to the admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && in_array($this->role, [UserRole::Admin, UserRole::Staff], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<CreditApplication, $this>
     */
    public function creditApplications(): HasMany
    {
        return $this->hasMany(CreditApplication::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creditVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'credit_verified_by');
    }

    public function canBuyOnCredit(): bool
    {
        return $this->is_active && $this->credit_status === CreditVerification::Terverifikasi;
    }

    /**
     * Yen still financed by open credit purchases (cash price minus down payment). Rupiah credits
     * are converted with the JPY→IDR rate kept when they were submitted.
     */
    public function openCreditFinanced(): int
    {
        return (int) ceil((float) $this->creditApplications()
            ->whereIn('status', CreditApplicationStatus::open())
            ->selectRaw("coalesce(sum(case when currency = 'IDR' and exchange_rate > 0 then (cash_price - down_payment) / exchange_rate else cash_price - down_payment end), 0) as financed")
            ->value('financed'));
    }
}
