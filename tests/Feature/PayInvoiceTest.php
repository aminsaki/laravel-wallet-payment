<?php

namespace Tests\Feature;

use App\Domain\Invoice\Models\Invoice;
use App\Domain\Wallet\Models\Wallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class PayInvoiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_pay_invoice_through_api()
    {
        Carbon::setTestNow('2025-01-01 10:00:00');

        $user = User::factory()->create([
            'is_blocked' => false,
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 5000,
            'status'  => 'active',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id'    => $user->id,
            'amount'     => 1000,
            'status'     => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        config()->set('payments.daily_limit', 10000);

        $response = $this->actingAs($user)
            ->postJson("/api/invoices/{$invoice->id}/pay", [
                'confirmation_code' => '123456',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'invoice' => [
                        'id'     => $invoice->id,
                        'status' => 'paid',
                    ],
                ],
            ]);

        $this->assertEquals(4000, (float) $wallet->fresh()->balance);
    }

    /** @test */
    public function it_returns_error_for_invalid_confirmation_code()
    {
        $user = User::factory()->create([
            'is_blocked' => false,
        ]);

        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 5000,
            'status'  => 'active',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id'    => $user->id,
            'amount'     => 1000,
            'status'     => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        config()->set('payments.daily_limit', 10000);

        $response = $this->actingAs($user)
            ->postJson("/api/invoices/{$invoice->id}/pay", [
                'confirmation_code' => '999999',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error'   => 'Invalid confirmation code.',
            ]);
    }
}
