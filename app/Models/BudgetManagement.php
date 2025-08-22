<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetManagement extends Model
{
    use HasFactory;

    protected $table = 'budget_managements';

    protected $fillable = [
        'revenue',
        'expenditure',
        'capital_expenditure',
        'budget_name',
        'start_date',
        'end_date',
        'spent_amount',
        'status',
        'approval_status',
        'notes',
        'expense_account_id',
        'department',
        'currency'
    ];

    protected $casts = [
        'revenue' => 'double',
        'expenditure' => 'double',
        'capital_expenditure' => 'double',
        'spent_amount' => 'double',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'department' => 'integer'
    ];

    /**
     * Get the department that owns the budget
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department');
    }

    /**
     * Get the expense account associated with this budget
     */
    public function expenseAccount()
    {
        return $this->belongsTo(AccountsModel::class, 'expense_account_id');
    }

    /**
     * Get the expenses associated with this budget
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'budget_item_id');
    }

    /**
     * Scope a query to only include pending budgets
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', 'PENDING');
    }

    /**
     * Scope a query to only include approved budgets
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'APPROVED');
    }

    /**
     * Scope a query to only include active budgets
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * Get the total budget amount
     */
    public function getTotalAmountAttribute()
    {
        return ($this->revenue ?? 0) + ($this->capital_expenditure ?? 0);
    }

    /**
     * Get the remaining budget amount
     */
    public function getRemainingAmountAttribute()
    {
        return $this->getTotalAmountAttribute() - ($this->spent_amount ?? 0);
    }

    /**
     * Get the budget utilization percentage
     */
    public function getUtilizationPercentageAttribute()
    {
        $total = $this->getTotalAmountAttribute();
        if ($total <= 0) {
            return 0;
        }
        
        return round((($this->spent_amount ?? 0) / $total) * 100, 2);
    }
} 