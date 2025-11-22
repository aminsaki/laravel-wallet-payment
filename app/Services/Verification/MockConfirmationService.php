<?php
namespace App\Services\Verification;

use App\Domain\Invoice\Models\Invoice;
use App\Models\User;

class MockConfirmationService implements ConfirmationServiceInterface
{
    public function assertConfirmed(User $user, Invoice $invoice, string $code): void
    {

        # testing code  123456
        if ($code !== '123456') {
            throw new \DomainException('Invalid confirmation code.');
        }
    }
}
