<?php
namespace App\Services\Notifications;
use App\Domain\Invoice\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MockNotificationService implements NotificationServiceInterface
{
    public function notifyPaymentSuccess(User $user, Invoice $invoice): void
    {
        Log::info("Payment succeeded", [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);
    }

    public function notifyPaymentFailed(User $user, Invoice $invoice, string $reason): void
    {
        Log::warning("Payment failed", [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'reason' => $reason,
        ]);
    }
}

