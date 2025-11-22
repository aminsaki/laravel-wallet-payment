<?php
namespace App\Domain\Http\Controllers;
use App\Domain\Invoice\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Services\Notifications\NotificationServiceInterface;
use App\Services\Payments\InvoicePaymentService;
use PayInvoiceRequest;

class InvoicePaymentController extends Controller
{
    public function __construct(
        private InvoicePaymentService $invoicePaymentService
    ) {}

    public function pay(PayInvoiceRequest $request, Invoice $invoice)
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
                'invoice' => [
                    'id'        => $result->invoice->id,
                    'status'    => $result->invoice->status,
                    'paid_at'   => $result->invoice->paid_at,
                    'amount'    => $result->invoice->amount,
                ],
                'wallet' => [
                    'balance' => $result->wallet->balance,
                ],
            ]);
        } catch (\DomainException $e) {
            app(NotificationServiceInterface::class)
                ->notifyPaymentFailed($user, $invoice, $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            app(NotificationServiceInterface::class)
                ->notifyPaymentFailed($user, $invoice, 'Unexpected error');

            return response()->json([
                'success' => false,
                'error'   => 'Internal error',
            ], 500);
        }
    }
}
