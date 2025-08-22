<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountsModel extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'accounts';

    public function client()
    {
        return $this->belongsTo(ClientsModel::class, 'client_number', 'client_number');
    }

    public function shareProduct()
    {
        return $this->belongsTo(sub_products::class, 'product_number', 'sub_product_id');
    }
}