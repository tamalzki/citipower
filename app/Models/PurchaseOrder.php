<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'supplier_id',
        'order_date',
        'expected_arrival_date',
        'payment_terms_count',
        'payment_terms_days',
        'status',
        'total_amount',
        'received_at',
        'note',
        'dr_number',
        'arrival_date',
        'arrival_notes',
    ];

    protected $casts = [
        'order_date'            => 'date',
        'expected_arrival_date' => 'date',
        'arrival_date'          => 'date',
        'received_at'           => 'datetime',
        'payment_terms_count'   => 'integer',
        'payment_terms_days'    => 'integer',
        'total_amount'          => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /** Total amount paid against this PO */
    public function getTotalPaidAttribute(): float
    {
        // Use loaded sum if available to avoid extra query
        if ($this->relationLoaded('supplierPayments')) {
            return (float) $this->supplierPayments->sum('amount');
        }
        return (float) $this->supplierPayments()->sum('amount');
    }

    /** Remaining balance */
    public function getPaymentBalanceAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->total_paid);
    }

    /** unpaid | partial | paid */
    public function getPaymentStatusAttribute(): string
    {
        $paid = $this->total_paid;
        $total = (float) $this->total_amount;

        if ($paid <= 0)          return 'unpaid';
        if ($paid >= $total)     return 'paid';
        return 'partial';
    }

    public function getPaymentsMadeCountAttribute(): int
    {
        if ($this->relationLoaded('supplierPayments')) {
            return (int) $this->supplierPayments->count();
        }
        return (int) $this->supplierPayments()->count();
    }

    public function getRemainingTermsAttribute(): int
    {
        $terms = (int) ($this->payment_terms_count ?? 0);
        if ($terms <= 0) {
            return 0;
        }
        return max(0, $terms - $this->payments_made_count);
    }

    public function getSuggestedTermAmountAttribute(): float
    {
        if ($this->payment_balance <= 0) {
            return 0.0;
        }
        if ($this->remaining_terms > 0) {
            return round($this->payment_balance / $this->remaining_terms, 2);
        }
        return (float) $this->payment_balance;
    }
}
