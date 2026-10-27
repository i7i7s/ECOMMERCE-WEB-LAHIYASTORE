<?php

namespace App\Actions\Credit;

use App\Enums\CreditVerification;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Support\OperationalAlerts;

/**
 * A customer asks to buy on credit. The conversation itself happens on WhatsApp;
 * this marks the account so the admin sees it in the verification list.
 */
class RequestCreditVerification
{
    public function handle(User $customer): User
    {
        if ($customer->role !== UserRole::Customer) {
            throw new BusinessRuleException('Hanya akun pelanggan yang bisa mengajukan kredit.');
        }

        if ($customer->credit_status === CreditVerification::Terverifikasi) {
            throw new BusinessRuleException('Akunmu sudah terverifikasi untuk kredit.');
        }

        if ($customer->credit_status !== CreditVerification::Diajukan) {
            $customer->update(['credit_status' => CreditVerification::Diajukan, 'credit_requested_at' => now()]);

            OperationalAlerts::send(
                'Permintaan verifikasi kredit',
                "{$customer->name} ({$customer->email}) minta verifikasi kredit lewat WhatsApp.",
                route('filament.admin.resources.credit-customers.index'),
            );
        }

        return $customer;
    }
}
