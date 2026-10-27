<?php

namespace App\Filament\Widgets;

use App\Enums\CashFlow;
use App\Enums\Currency;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class FinanceOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Keuangan';

    public static function canView(): bool
    {
        return (bool) (auth()->user()?->is_active && auth()->user()?->isAdmin());
    }

    public function boot(): void
    {
        abort_unless(static::canView(), 403);
    }

    protected function getStats(): array
    {
        if (! static::canView()) {
            return [];
        }

        $stats = [];
        foreach (Currency::cases() as $currency) {
            $income = CashTransaction::where('currency', $currency)->where('type', CashFlow::Masuk)
                ->whereNull('voided_at')->whereNull('transfer_group')->whereDate('transaction_date', now('Asia/Tokyo')->toDateString())->sum('amount');
            $stats[] = Stat::make('Pemasukan hari ini · '.$currency->value, $currency->format((int) $income));
        }
        foreach (CashAccount::where('is_active', true)->orderBy('id')
            ->withSum(['cashTransactions as incoming' => fn (Builder $query) => $query->notVoided()->where('type', CashFlow::Masuk)], 'amount')
            ->withSum(['cashTransactions as outgoing' => fn (Builder $query) => $query->notVoided()->where('type', CashFlow::Keluar)], 'amount')->get() as $account) {
            $stats[] = Stat::make($account->name, $account->currency->format((int) $account->incoming - (int) $account->outgoing))->url(route('filament.admin.resources.cash-accounts.index'));
        }

        return $stats;
    }
}
