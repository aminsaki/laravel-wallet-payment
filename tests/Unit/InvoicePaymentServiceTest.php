<?php

namespace Tests\Unit;

use App\Domain\Invoice\Models\Invoice;
use App\Domain\Wallet\Models\Wallet;
use App\Models\User;
use App\Services\Payments\DTOS\InvoicePaymentResult;
use App\Services\Payments\InvoicePaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use DomainException;

class InvoicePaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoicePaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(InvoicePaymentService::class);
    }

    /** @test */
    public function it_pays_invoice_successfully()
    {
        Carbon::setTestNow('2025-01-01 10:00:00');

        $user = User::factory()->create([
            'is_blocked' => false,
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 5000,
            'status' => 'active',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'amount' => 1000,
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        config()->set('payments.daily_limit', 10000);

        $result = $this->service->pay($invoice, $user->id, '123456');

        $this->assertInstanceOf(InvoicePaymentResult::class, $result);
        $this->assertEquals('paid', $result->invoice->status);
        $this->assertEquals(4000, (float)$result->wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'invoice_id' => $invoice->id,
            'type' => 'debit',
            'amount' => 1000,
        ]);

        $this->assertDatabaseHas('daily_spending_stats', [
            'date' => Carbon::today()->toDateString(),
            'total_spent' => 1000,
        ]);
    }

    /** @test */
    public function it_fails_when_balance_is_insufficient()
    {
        $user = User::factory()->create([
            'is_blocked' => false,
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 500,
            'status' => 'active',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'amount' => 1000,
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        config()->set('payments.daily_limit', 10000);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Insufficient wallet balance.');

        $this->service->pay($invoice, $user->id, '123456');

        $this->assertEquals(500, (float)$wallet->fresh()->balance);
    }

    public function it_cannot_pay_same_invoice_twice()
    {
        $user = User::factory()->create([
            'is_blocked' => false,
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 5000,
            'status' => 'active',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'amount' => 1000,
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        config()->set('payments.daily_limit', 10000);

        $this->service->pay($invoice, $user->id, '123456');

        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertEquals(4000, (float)$wallet->fresh()->balance);


        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invoice is not payable.');

        $this->service->pay($invoice->fresh(), $user->id, '123456');

        $this->assertEquals(4000, (float)$wallet->fresh()->balance);
    }

}
