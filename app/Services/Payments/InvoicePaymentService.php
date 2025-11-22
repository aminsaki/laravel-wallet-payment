<?php

namespace App\Services\Payments;


use App\Domain\Invoice\Models\Invoice;
use App\Domain\Sending\Models\DailySpendingStat;
use App\Domain\Wallet\Models\Wallet;
use App\Services\Payments\DTOS\InvoicePaymentResult;
use App\Services\Notifications\NotificationServiceInterface;
use ConfirmationServiceInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DomainException;


class InvoicePaymentService
{
    public function __construct(
        private ConfirmationServiceInterface $confirmationService,
        private NotificationServiceInterface $notificationService
    ) {}

    public function pay(Invoice $invoice, int $userId, string $confirmationCode): InvoicePaymentResult
    {
        return DB::transaction(function () use ($invoice, $userId, $confirmationCode) {

            $invoice = Invoice::where('id', $invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $invoice->user_id !== (int) $userId) {
                throw new DomainException('Invoice does not belong to this user.');
            }

            if ($invoice->status !== 'pending') {
                throw new DomainException('Invoice is not payable.');
            }

            if ($invoice->expires_at && $invoice->expires_at->isPast()) {
                throw new DomainException('Invoice has expired.');
            }

            /** @var Wallet $wallet */
            $wallet = Wallet::where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->status !== 'active') {
                throw new DomainException('Wallet is not active.');
            }

            if ($wallet->user?->is_blocked) {
                throw new DomainException('User is blocked.');
            }

            $today = Carbon::today();

            $stat = DailySpendingStat::query()
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            if (! $stat) {
                $stat = DailySpendingStat::create([
                    'date' => $today,
                    'total_spent' => 0,
                ]);
            }

            $dailyLimit = config('payments.daily_limit');

            if ($stat->total_spent + $invoice->amount > $dailyLimit) {
                throw new DomainException('Daily spending limit reached.');
            }


            if ((float) $wallet->balance < (float) $invoice->amount) {
                throw new DomainException('Insufficient wallet balance.');
            }

            $this->confirmationService->assertConfirmed(
                $wallet->user,
                $invoice,
                $confirmationCode
            );

            $wallet->balance = (float) $wallet->balance - (float) $invoice->amount;
            $wallet->save();

            $wallet->transactions()->create([
                'invoice_id' => $invoice->id,
                'type'       => 'debit',
                'amount'     => $invoice->amount,
            ]);

            $invoice->status = 'paid';
            $invoice->paid_at = now();
            $invoice->save();

            $stat->total_spent = (float) $stat->total_spent + (float) $invoice->amount;
            $stat->save();


            $this->notificationService->notifyPaymentSuccess($wallet->user, $invoice);

            return new InvoicePaymentResult(
                success: true,
                invoice: $invoice,
                wallet: $wallet
            );
        });
    }
}
