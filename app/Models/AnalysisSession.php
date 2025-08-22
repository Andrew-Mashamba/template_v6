<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalysisSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_name',
        'account_number',
        'statement_period',
        'bank',
        'currency',
        'opening_balance',
        'closing_balance',
        'total_transactions',
        'status',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'total_transactions' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function bankTransactions()
    {
        return $this->hasMany(BankTransaction::class, 'session_id');
    }

    public function getReconciliationSummaryAttribute()
    {
        $total = $this->bankTransactions()->count();
        $reconciled = $this->bankTransactions()->where('reconciliation_status', 'reconciled')->count();
        $matched = $this->bankTransactions()->where('reconciliation_status', 'matched')->count();
        $unreconciled = $this->bankTransactions()->where('reconciliation_status', 'unreconciled')->count();

        return [
            'total' => $total,
            'reconciled' => $reconciled,
            'matched' => $matched,
            'unreconciled' => $unreconciled,
            'reconciliation_rate' => $total > 0 ? round((($reconciled + $matched) / $total) * 100, 2) : 0
        ];
    }
} 