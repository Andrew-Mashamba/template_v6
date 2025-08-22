<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dividend extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'year' => 'integer',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime'
    ];

    /**
     * Get the member that owns the dividend.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the share that owns the dividend.
     */
    public function share()
    {
        return $this->belongsTo(Share::class);
    }

    /**
     * Scope a query to only include pending dividends.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include paid dividends.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include dividends for a specific year.
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Get the total dividends for a specific year.
     */
    public static function getTotalForYear($year)
    {
        return static::where('year', $year)
            ->where('status', 'paid')
            ->sum('dividend_amount');
    }

    /**
     * Get the average dividend rate for a specific year.
     */
    public static function getAverageRateForYear($year)
    {
        return static::where('year', $year)
            ->where('status', 'paid')
            ->avg('dividend_rate');
    }
} 