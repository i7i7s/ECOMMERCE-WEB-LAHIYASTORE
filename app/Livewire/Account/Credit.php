<?php

namespace App\Livewire\Account;

use App\Actions\Credit\CloseCreditApplication;
use App\Actions\Credit\RequestCreditVerification;
use App\Enums\CreditApplicationStatus;
use App\Exceptions\BusinessRuleException;
use App\Support\Credit\CreditTerms;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The customer's credit: verification status, credit purchases and their payment schedules.
 */
#[Title('Kredit saya')]
class Credit extends Component
{
    public function requestVerification(RequestCreditVerification $requestVerification): void
    {
        try {
            $requestVerification->handle(auth()->user());
        } catch (BusinessRuleException $exception) {
            $this->addError('credit', $exception->getMessage());
        }
    }

    public function cancel(int $applicationId, CloseCreditApplication $closeCreditApplication): void
    {
        $application = auth()->user()->creditApplications()->findOrFail($applicationId);

        try {
            $closeCreditApplication->cancel($application, auth()->user(), 'Dibatalkan pelanggan');
        } catch (BusinessRuleException $exception) {
            $this->addError('credit', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $user = auth()->user();
        $terms = CreditTerms::current();

        return view('livewire.account.credit', [
            'user' => $user,
            'applications' => $user->creditApplications()->with('installments')->latest('id')->get(),
            'verificationUrl' => $terms->verificationUrl($user),
            'contactUrl' => $terms->contactUrl(),
            'remainingLimit' => $user->credit_limit !== null ? max(0, $user->credit_limit - $user->openCreditFinanced()) : null,
            'submittedCode' => session('credit_submitted'),
            'canCancel' => fn ($application): bool => $application->status === CreditApplicationStatus::Diajukan,
        ]);
    }
}
