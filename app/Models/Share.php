<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'shares';

    protected $casts = [
        'paid_up_shares' => 'integer',
        'price_per_share' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function dividends()
    {
        return $this->hasMany(Dividend::class);
    }
}
