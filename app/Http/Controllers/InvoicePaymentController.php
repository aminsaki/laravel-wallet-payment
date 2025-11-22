<?php

namespace App\Http\Controllers;

use App\Domain\Invoice\Models\Invoice;
use App\Http\Requests\PayInvoiceRequest;
use App\Services\Payments\InvoicePaymentService;
use App\Services\Notifications\NotificationServiceInterface;
use DomainException;
use Illuminate\Http\JsonResponse;
use Throwable;

class InvoicePaymentController extends Controller
{
    public function __construct(
        private InvoicePaymentService $invoicePaymentService,
        private NotificationServiceInterface $notificationService
    ) {}

    public function pay(PayInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();

        try {
            $result = $this->invoicePaymentService->pay(
                $invoice,
                $user->id,
                $request->input('confirmation_code')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice' => [
                        'id'       => $result->invoice->id,
                        'status'   => $result->invoice->status,
                        'amount'   => $result->invoice->amount,
                        'paid_at'  => $result->invoice->paid_at,
                    ],
                    'wallet' => [
                        'balance' => $result->wallet->balance,
                    ],
                ],
            ], 200);

        } catch (DomainException $e) {

            $this->notificationService->notifyPaymentFailed($user, $invoice, $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {

            $this->notificationService->notifyPaymentFailed($user, $invoice, 'Unexpected error');

            report($e);

            return response()->json([
                'success' => false,
                'error'   => 'Internal error',
            ], 500);
        }
    }
}
