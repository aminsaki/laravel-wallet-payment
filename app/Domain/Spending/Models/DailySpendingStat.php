<?php
namespace App\Domain\Sending\Models;
use Illuminate\Database\Eloquent\Model;

class DailySpendingStat extends Model
{
    public $timestamps = false;

    protected $fillable = ['date', 'total_spent'];

    protected $casts = [
        'date' => 'date',
    ];
}
