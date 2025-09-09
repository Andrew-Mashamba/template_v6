<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $casts = [
        'payment_date' => 'datetime',
        'last_payment_date' => 'datetime',
        'expense_month' => 'date',
        'amount' => 'decimal:2',
        'actual_spent' => 'decimal:2',
        'last_payment_amount' => 'decimal:2',
        'budget_utilization_percentage' => 'decimal:2'
    ];

    protected $fillable = [
        'account_id',
        'budget_item_id',
        'budget_allocation_id',
        'amount',
        'description',
        'payment_type',
        'user_id',
        'status',
        'approval_id',
        'retirement_receipt_path',
        'monthly_budget_amount',
        'monthly_spent_amount',
        'budget_utilization_percentage',
        'budget_status',
        'budget_resolution',
        'budget_notes',
        'expense_month',
        // Payment fields
        'payment_date',
        'payment_transaction_id',
        'payment_method',
        'payment_reference',
        'paid_by_user_id',
        'actual_spent',
        'last_payment_date',
        'last_payment_amount'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approval()
    {
        return $this->belongsTo(Approvals::class);
    }

    public function budgetItem()
    {
        return $this->belongsTo(BudgetManagement::class, 'budget_item_id');
    }
    
    public function budgetAllocation()
    {
        return $this->belongsTo(BudgetAllocation::class, 'budget_allocation_id');
    }

    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(Transaction::class, 'payment_transaction_id');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING_APPROVAL');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['PENDING_APPROVAL', 'APPROVED']);
    }
} 