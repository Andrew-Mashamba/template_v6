<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PPE extends Model
{
    use HasFactory;
    
    protected $table = 'ppes';
    
    protected $fillable = [
        'name', 'category', 'purchase_price', 'purchase_date', 'salvage_value',
        'useful_life', 'quantity', 'initial_value', 'depreciation_rate',
        'accumulated_depreciation', 'depreciation_for_year', 'depreciation_for_month',
        'closing_value', 'status', 'location', 'notes', 'account_number',
        // Additional costs for proper capitalization
        'legal_fees', 'registration_fees', 'renovation_costs', 'transportation_costs',
        'installation_costs', 'other_costs',
        // Payment method and related accounts
        'payment_method', 'payment_account_number', 'payable_account_number',
        // Accounting transaction tracking
        'accounting_transaction_id', 'accounting_entry_created',
        // Additional useful fields
        'supplier_name', 'invoice_number', 'invoice_date', 'additional_notes',
        // Disposal tracking fields
        'disposal_date', 'disposal_method', 'disposal_proceeds', 'disposal_notes',
        // Approval workflow fields
        'disposal_approval_status', 'disposal_approved_by', 'disposal_approved_at', 'disposal_rejection_reason'
    ];
    
    protected $casts = [
        'purchase_price' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'initial_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'depreciation_for_year' => 'decimal:2',
        'depreciation_for_month' => 'decimal:2',
        'closing_value' => 'decimal:2',
        // Additional costs
        'legal_fees' => 'decimal:2',
        'registration_fees' => 'decimal:2',
        'renovation_costs' => 'decimal:2',
        'transportation_costs' => 'decimal:2',
        'installation_costs' => 'decimal:2',
        'other_costs' => 'decimal:2',
        // Disposal fields
        'disposal_proceeds' => 'decimal:2',
        // Dates
        'purchase_date' => 'date',
        'invoice_date' => 'date',
        'disposal_date' => 'date',
        'disposal_approved_at' => 'datetime',
        // Integers
        'useful_life' => 'integer',
        'quantity' => 'integer',
        'disposal_approved_by' => 'integer',
        // Booleans
        'accounting_entry_created' => 'boolean'
    ];

    /**
     * Calculate the total capitalized cost including all additional costs
     */
    public function getTotalCapitalizedCostAttribute()
    {
        return $this->purchase_price + 
               $this->legal_fees + 
               $this->registration_fees + 
               $this->renovation_costs + 
               $this->transportation_costs + 
               $this->installation_costs + 
               $this->other_costs;
    }

    /**
     * Get the accounting entry description based on payment method
     */
    public function getAccountingEntryDescriptionAttribute()
    {
        $description = "PPE Purchase: {$this->name}";
        
        if ($this->payment_method === 'cash') {
            return $description . " (Cash Payment)";
        } elseif ($this->payment_method === 'credit') {
            return $description . " (Credit Purchase)";
        } elseif ($this->payment_method === 'loan') {
            return $description . " (Loan Financed)";
        } elseif ($this->payment_method === 'lease') {
            return $description . " (Lease Agreement)";
        }
        
        return $description;
    }

    /**
     * Check if accounting entry needs to be created
     */
    public function needsAccountingEntry()
    {
        return !$this->accounting_entry_created && $this->total_capitalized_cost > 0;
    }

    /**
     * Get the disposal approval record
     */
    public function disposalApproval()
    {
        return $this->hasOne(\App\Models\approvals::class, 'process_id', 'id')
                    ->where('process_code', 'ASSET_DISP');
    }

    /**
     * Get the user who approved the disposal
     */
    public function disposalApprovedBy()
    {
        return $this->belongsTo(User::class, 'disposal_approved_by');
    }

    /**
     * Check if disposal is pending approval
     */
    public function isDisposalPendingApproval()
    {
        return $this->status === 'pending_disposal' && $this->disposal_approval_status === 'pending';
    }

    /**
     * Check if disposal is approved for disposal
     */
    public function isApprovedForDisposal()
    {
        return $this->status === 'approved_for_disposal';
    }

    /**
     * Check if disposal is approved
     */
    public function isDisposalApproved()
    {
        return $this->disposal_approval_status === 'approved';
    }

    /**
     * Check if disposal is rejected
     */
    public function isDisposalRejected()
    {
        return $this->disposal_approval_status === 'rejected';
    }

    /**
     * Calculate gain or loss on disposal
     */
    public function getDisposalGainLossAttribute()
    {
        if (!$this->disposal_proceeds || !$this->closing_value) {
            return 0;
        }
        
        return $this->disposal_proceeds - $this->closing_value;
    }

    /**
     * Check if disposal resulted in a gain
     */
    public function hasDisposalGain()
    {
        return $this->disposal_gain_loss > 0;
    }

    /**
     * Check if disposal resulted in a loss
     */
    public function hasDisposalLoss()
    {
        return $this->disposal_gain_loss < 0;
    }

    /**
     * Get disposal method display name
     */
    public function getDisposalMethodDisplayAttribute()
    {
        $methods = [
            'sold' => 'Sold',
            'scrapped' => 'Scrapped',
            'donated' => 'Donated',
            'lost' => 'Lost',
            'stolen' => 'Stolen',
            'other' => 'Other'
        ];
        
        return $methods[$this->disposal_method] ?? $this->disposal_method;
    }
}
