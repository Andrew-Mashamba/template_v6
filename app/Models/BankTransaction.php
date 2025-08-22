<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'transaction_date',
        'value_date',
        'reference_number',
        'narration',
        'withdrawal_amount',
        'deposit_amount',
        'balance',
        'matched_transaction_id',
        'reconciliation_status',
        'match_confidence',
        'reconciliation_notes',
        'reconciled_at',
        'reconciled_by',
        'branch',
        'transaction_type',
        'raw_data'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'value_date' => 'date',
        'withdrawal_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'match_confidence' => 'decimal:2',
        'reconciled_at' => 'datetime',
        'raw_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function analysisSession()
    {
        return $this->belongsTo(AnalysisSession::class, 'session_id');
    }

    public function matchedTransaction()
    {
        return $this->belongsTo(Transaction::class, 'matched_transaction_id');
    }

    public function getAmountAttribute()
    {
        return $this->deposit_amount > 0 ? $this->deposit_amount : $this->withdrawal_amount;
    }

    public function getTransactionTypeAttribute()
    {
        return $this->deposit_amount > 0 ? 'credit' : 'debit';
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('reconciliation_status', 'unreconciled');
    }

    public function scopeMatched($query)
    {
        return $query->where('reconciliation_status', 'matched');
    }

    public function scopeReconciled($query)
    {
        return $query->where('reconciliation_status', 'reconciled');
    }

    public function markAsMatched($transactionId, $confidence = 100, $notes = null)
    {
        $this->update([
            'matched_transaction_id' => $transactionId,
            'reconciliation_status' => 'matched',
            'match_confidence' => $confidence,
            'reconciliation_notes' => $notes,
            'reconciled_at' => now(),
            'reconciled_by' => auth()->id()
        ]);
    }

    public function markAsReconciled($notes = null)
    {
        $this->update([
            'reconciliation_status' => 'reconciled',
            'reconciliation_notes' => $notes,
            'reconciled_at' => now(),
            'reconciled_by' => auth()->id()
        ]);
    }
} 