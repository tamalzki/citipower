<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierDelivery extends Model
{
    protected $fillable = [
        'supplier_id',
        'purchase_order_id',
        'dr_number',
        'delivery_date',
        'amount',
        'notes',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'amount'        => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
