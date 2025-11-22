<?php

namespace Database\Factories;

use App\Domain\Invoice\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'amount'     => $this->faker->numberBetween(100, 10000),
            'status'     => 'pending',
            'expires_at' => now()->addHours(2),
            'paid_at'    => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status'  => 'paid',
            'paid_at' => now(),
        ]);
    }
}
