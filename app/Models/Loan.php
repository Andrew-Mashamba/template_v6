<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $table = 'loans';
    protected $guarded = [];
    
    /**
     * The attributes that should have default values.
     *
     * @var array
     */
    protected $attributes = [
        'pay_method' => 'internal_transfer',
    ];

    public function client()
    {
        return $this->belongsTo(ClientsModel::class, 'client_number', 'client_number');
    }
} 