<?php
 namespace App\Services\Notifications;
 use App\Domain\Invoice\Models\Invoice;
 use App\Models\User;

 interface NotificationServiceInterface
 {
     public function notifyPaymentSuccess(User $user, Invoice $invoice): void;
     public function notifyPaymentFailed(User $user, Invoice $invoice, string $reason): void;
 }
