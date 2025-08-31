<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class LoansModel extends Model
{
    use HasFactory;
    
    protected $table = 'loans';
    
    protected $guarded = [];

    protected $casts = [
        'principle' => 'decimal:2',
        'interest' => 'decimal:2',
        'business_inventory' => 'decimal:2',
        'cash_at_hand' => 'decimal:2',
        'daily_sales' => 'decimal:2',
        'cost_of_goods_sold' => 'decimal:2',
        'available_funds' => 'decimal:2',
        'operating_expenses' => 'decimal:2',
        'monthly_taxes' => 'decimal:2',
        'other_expenses' => 'decimal:2',
        'collateral_value' => 'decimal:2',
        'tenure' => 'integer',
        'principle_amount' => 'decimal:2',
        'days_in_arrears' => 'integer',
        'total_days_in_arrears' => 'integer',
        'arrears_in_amount' => 'decimal:2',
        'future_interest' => 'decimal:2',
        'total_principle' => 'decimal:2',
        'approved_loan_value' => 'decimal:2',
        'approved_term' => 'integer',
        'amount_to_be_credited' => 'decimal:2',
        'disbursement_date' => 'datetime',
        'transaction_processed_at' => 'datetime',
        'transaction_metadata' => 'array',
        'has_exceptions' => 'boolean',
        'exceptions_cleared_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanSubProduct::class, 'loan_sub_product', 'product_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientsModel::class, 'client_number', 'client_number');
    }

    public function clientName(): BelongsTo
    {
        return $this->belongsTo(ClientsModel::class, 'client_number', 'client_number');
    }

    public function loanBranch(): BelongsTo
    {
        return $this->belongsTo(BranchesModel::class, 'branch_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(loans_schedules::class, 'loan_id', 'loan_id');
    }

    public function loanAccount()
    {
        return $this->belongsTo(AccountsModel::class, 'loan_account_number', 'account_number');
    }

    /**
     * Get the maximum days in arrears for this loan
     */
    public function getMaxDaysInArrearsAttribute()
    {
        return $this->schedules()->max('days_in_arrears') ?? 0;
    }

    /**
     * Get the total amount in arrears for this loan
     */
    public function getTotalAmountInArrearsAttribute()
    {
        return $this->schedules()->sum('amount_in_arrears') ?? 0;
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LoanApproval::class, 'loan_id', 'id');
    }

    public function collateral(): HasMany
    {
        return $this->hasMany(LoanCollateral::class, 'loan_id', 'id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(LoanAuditLog::class, 'loan_id', 'id');
    }

    public function settledLoans(): HasMany
    {
        return $this->hasMany(SettledLoan::class, 'loan_id', 'id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'REJECTED');
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByClient($query, $clientNumber)
    {
        return $query->where('client_number', $clientNumber);
    }

    public function scopeByLoanType($query, $loanType)
    {
        return $query->where('loan_type_2', $loanType);
    }

    // Business Logic Methods
    public function getMonthlyPaymentAttribute()
    {
        if ($this->principle <= 0 || $this->interest <= 0 || $this->tenure <= 0) {
            return 0;
        }

        $monthlyRate = $this->interest / 12 / 100;
        $numerator = $this->principle * $monthlyRate * pow(1 + $monthlyRate, $this->tenure);
        $denominator = pow(1 + $monthlyRate, $this->tenure) - 1;

        return $denominator > 0 ? $numerator / $denominator : 0;
    }

    public function getTotalInterestAttribute()
    {
        return ($this->monthly_payment * $this->tenure) - $this->principle;
    }

    public function getTotalAmountAttribute()
    {
        return $this->principle + $this->total_interest;
    }

    public function getDaysInArrearsAttribute()
    {
        if ($this->disbursement_date && $this->status === 'ACTIVE') {
            $lastPaymentDate = $this->schedules()
                ->where('completion_status', 'COMPLETED')
                ->max('installment_date');
            
            if ($lastPaymentDate) {
                return Carbon::now()->diffInDays($lastPaymentDate);
            }
        }
        return 0;
    }

    public function getAffordabilityRatioAttribute()
    {
        if ($this->available_funds <= 0) {
            return 0;
        }
        return ($this->monthly_payment / $this->available_funds) * 100;
    }

    public function getCollateralCoverageRatioAttribute()
    {
        if ($this->collateral_value <= 0 || $this->principle <= 0) {
            return 0;
        }
        return ($this->collateral_value / $this->principle) * 100;
    }

    public function isOverdue(): bool
    {
        return $this->days_in_arrears > 0;
    }

    public function isAffordable(): bool
    {
        return $this->affordability_ratio <= 70; // 70% threshold
    }

    public function hasAdequateCollateral(): bool
    {
        return $this->collateral_coverage_ratio >= 120; // 120% coverage
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'PENDING' && 
               $this->isAffordable() && 
               $this->hasAdequateCollateral();
    }

    public function getRiskLevelAttribute(): string
    {
        $riskScore = 0;
        
        // Income risk
        if ($this->affordability_ratio > 50) $riskScore += 2;
        if ($this->affordability_ratio > 70) $riskScore += 3;
        
        // Collateral risk
        if ($this->collateral_coverage_ratio < 100) $riskScore += 3;
        if ($this->collateral_coverage_ratio < 120) $riskScore += 1;
        
        // Business risk
        if ($this->business_age < 1) $riskScore += 2;
        if ($this->business_age < 2) $riskScore += 1;
        
        // Credit history risk
        if ($this->days_in_arrears > 30) $riskScore += 3;
        if ($this->days_in_arrears > 90) $riskScore += 2;

        if ($riskScore >= 8) return 'HIGH';
        if ($riskScore >= 5) return 'MEDIUM';
        return 'LOW';
    }

    // Exception handling methods
    public function markAsHavingExceptions(string $trackingId = null): void
    {
        $this->update([
            'has_exceptions' => true,
            'exception_tracking_id' => $trackingId ?? 'EXC_' . $this->id . '_' . time(),
            'status' => 'PENDING-WITH-EXCEPTIONS'
        ]);
    }

    public function clearExceptions(int $clearedBy = null): void
    {
        $this->update([
            // Keep has_exceptions as true for historical tracking - it indicates loan originally had exceptions
            // 'has_exceptions' => false, // DON'T CHANGE THIS - it's for tracking original state
            'exceptions_cleared_at' => now(),
            'exceptions_cleared_by' => $clearedBy ?? auth()->id(),
            'status' => 'PENDING'
        ]);
    }

    public function hasInitialExceptions(): bool
    {
        return $this->has_exceptions || !empty($this->exception_tracking_id);
    }
    
    public function areExceptionsCleared(): bool
    {
        return $this->has_exceptions && !empty($this->exceptions_cleared_at);
    }
    
    public function hasActiveExceptions(): bool
    {
        // Has exceptions but not yet cleared
        return $this->has_exceptions && empty($this->exceptions_cleared_at);
    }

    public function getExceptionTrackingId(): ?string
    {
        return $this->exception_tracking_id;
    }

    public function scopeWithExceptions($query)
    {
        return $query->where('has_exceptions', true);
    }

    public function scopeWithoutExceptions($query)
    {
        return $query->where('has_exceptions', false);
    }
}
