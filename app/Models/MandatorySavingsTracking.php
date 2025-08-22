<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class MandatorySavingsTracking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mandatory_savings_tracking';

    protected $fillable = [
        'client_number',
        'account_number',
        'year',
        'month',
        'required_amount',
        'paid_amount',
        'balance',
        'status',
        'due_date',
        'paid_date',
        'months_in_arrears',
        'total_arrears',
        'notes'
    ];

    protected $casts = [
        'required_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'total_arrears' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'months_in_arrears' => 'integer'
    ];

    /**
     * Get the client/member for this tracking record.
     */
    public function client()
    {
        return $this->belongsTo(ClientsModel::class, 'client_number', 'client_number');
    }

    /**
     * Get the account for this tracking record.
     */
    public function account()
    {
        return $this->belongsTo(AccountsModel::class, 'account_number', 'account_number');
    }

    /**
     * Get notifications for this tracking record.
     */
    public function notifications()
    {
        return $this->hasMany(MandatorySavingsNotification::class, 'client_number', 'client_number')
            ->where('year', $this->year)
            ->where('month', $this->month);
    }

    /**
     * Scope to get records for a specific period.
     */
    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Scope to get unpaid records.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['UNPAID', 'PARTIAL', 'OVERDUE']);
    }

    /**
     * Scope to get overdue records.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'OVERDUE');
    }

    /**
     * Scope to get records due within a date range.
     */
    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * Get the period as a formatted string.
     */
    public function getPeriodAttribute()
    {
        return Carbon::createFromDate($this->year, $this->month, 1)->format('F Y');
    }

    /**
     * Check if the payment is overdue.
     */
    public function isOverdue()
    {
        return $this->status === 'OVERDUE' || 
               ($this->due_date->isPast() && $this->balance > 0);
    }

    /**
     * Get the number of days overdue.
     */
    public function getDaysOverdueAttribute()
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return $this->due_date->diffInDays(now());
    }

    /**
     * Update the payment status based on current data.
     */
    public function updateStatus()
    {
        if ($this->paid_amount >= $this->required_amount) {
            $this->status = 'PAID';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'PARTIAL';
        } elseif ($this->due_date->isPast()) {
            $this->status = 'OVERDUE';
        } else {
            $this->status = 'UNPAID';
        }

        $this->balance = $this->required_amount - $this->paid_amount;
        $this->save();
    }

    /**
     * Record a payment for this tracking record.
     */
    public function recordPayment($amount, $paymentDate = null)
    {
        $this->paid_amount += $amount;
        $this->paid_date = $paymentDate ?? now();
        $this->updateStatus();
    }

    /**
     * Calculate arrears for this member.
     */
    public static function calculateArrears($clientNumber, $asOfDate = null)
    {
        $asOfDate = $asOfDate ?? now();
        
        $unpaidRecords = self::where('client_number', $clientNumber)
            ->where('due_date', '<=', $asOfDate)
            ->where('balance', '>', 0)
            ->get();

        $totalArrears = $unpaidRecords->sum('balance');
        $monthsInArrears = $unpaidRecords->count();

        return [
            'total_arrears' => $totalArrears,
            'months_in_arrears' => $monthsInArrears,
            'unpaid_records' => $unpaidRecords
        ];
    }
} 