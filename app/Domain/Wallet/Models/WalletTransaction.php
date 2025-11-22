<?php
namespace App\Domain\Wallet\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'date',
        'total_spent',
    ];

    protected $casts = [
        'date' => 'date',
        'total_spent' => 'decimal:2',
    ];
}
