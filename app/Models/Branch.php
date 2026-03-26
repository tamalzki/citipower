<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'code'];

    public function stockTransfersFrom()
    {
        return $this->hasMany(StockTransfer::class, 'from_branch_id');
    }

    public function stockTransfersTo()
    {
        return $this->hasMany(StockTransfer::class, 'to_branch_id');
    }
}
