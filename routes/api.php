<?php

use App\Http\Controllers\InvoicePaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/invoices/{invoice}/pay', [InvoicePaymentController::class, 'pay']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/invoices/{invoice}/pay', [InvoicePaymentController::class, 'pay']);
});

