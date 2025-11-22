<?php
namespace App\Services\Verification;
use App\Domain\Invoice\Models\Invoice;
use App\Models\User;

interface ConfirmationServiceInterface
{
    /**
     * @param User $user
     * @param Invoice $invoice
     * @param string $code
     * @return void
     */
    public function assertConfirmed(User $user, Invoice $invoice, string $code): void;
}
