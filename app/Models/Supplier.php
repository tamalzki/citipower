<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_suppliers')
                    ->withPivot('cost_price')
                    ->withTimestamps();
    }

    public function deliveries()
    {
        return $this->hasMany(SupplierDelivery::class);
    }

    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function getOutstandingBalanceAttribute(): float
    {
        $totalDelivered = (float) $this->deliveries()->sum('amount');
        $totalPaid      = (float) $this->supplierPayments()->sum('amount');
        return max(0, $totalDelivered - $totalPaid);
    }
}
