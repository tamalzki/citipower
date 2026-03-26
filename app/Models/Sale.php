<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'total_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'note',
        'issued_receipt',
        'poc',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->paid_amount);
    }

    public function getPaymentStatusAttribute(): string
    {
        if ($this->paid_amount <= 0) {
            return 'unpaid';
        }
        if ($this->paid_amount + 0.0001 < (float) $this->total_amount) {
            return 'partial';
        }
        return 'paid';
    }
}