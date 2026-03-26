<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'minimum_stock',
    ];

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->minimum_stock;
    }

    public function profitMargin(): float
    {
        if ($this->selling_price <= 0) return 0;
        return (($this->selling_price - $this->purchase_price) / $this->selling_price) * 100;
    }

    public function profitAmount(): float
    {
        return $this->selling_price - $this->purchase_price;
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}