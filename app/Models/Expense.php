<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'budget_item_id',
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
        'expense_month'
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

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING_APPROVAL');
    }
} 