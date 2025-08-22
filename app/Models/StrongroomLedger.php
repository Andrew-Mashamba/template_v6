<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrongroomLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'type',
        'amount',
        'balance',
        'reference',
        'status',
        'authorized_by',
        'last_audit_date',
        'last_audit_by',
        'security_level',
        'location',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'last_audit_date' => 'datetime',
    ];

    /**
     * Get the user who performed the last audit
     */
    public function lastAuditedBy()
    {
        return $this->belongsTo(User::class, 'last_audit_by');
    }

    /**
     * Get the user who authorized this entry
     */
    public function authorized_by()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 2);
    }

    /**
     * Check if audit is overdue (more than 30 days)
     */
    public function isAuditOverdue(): bool
    {
        if (!$this->last_audit_date) {
            return true;
        }
        
        return $this->last_audit_date->diffInDays(now()) > 30;
    }

    /**
     * Get days since last audit
     */
    public function getDaysSinceAuditAttribute(): int
    {
        if (!$this->last_audit_date) {
            return 999; // Indicates never audited
        }
        
        return $this->last_audit_date->diffInDays(now());
    }

    /**
     * Get audit status color for UI
     */
    public function getAuditStatusColorAttribute(): string
    {
        $days = $this->days_since_audit;
        
        if ($days > 30) {
            return 'red'; // Overdue
        } elseif ($days > 20) {
            return 'yellow'; // Warning
        } else {
            return 'green'; // Good
        }
    }
}
