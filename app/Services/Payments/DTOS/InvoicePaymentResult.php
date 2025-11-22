<?php

namespace App\Services\Payments\DTOS;


use App\Domain\Invoice\Models\Invoice;
use App\Domain\Wallet\Models\Wallet;

class InvoicePaymentResult
{
   public function __construct(
        public bool $success,
        public Invoice $invoice,
        public Wallet $wallet,
    ) {}
}
