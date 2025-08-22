<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Saving extends Model
{
    protected $table = 'savings';
    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(ClientsModel::class, 'client_number', 'client_number');
    }
} 