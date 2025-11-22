<?php

namespace Database\Factories;

use App\Domain\Spending\Models\DailySpendingStat;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailySpendingStatFactory extends Factory
{
    protected $model = DailySpendingStat::class;

    public function definition(): array
    {
        return [
            'date'        => now()->toDateString(),
            'total_spent' => 0,
        ];
    }
}
