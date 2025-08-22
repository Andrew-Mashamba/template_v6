<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    use HasFactory;

    protected $table = 'approvals';

    protected $fillable = [
        'institution',
        'process_name',
        'process_description',
        'approval_process_description',
        'process_code',
        'process_id',
        'approver_id',
        'approval_status',
        'process_status',
        'user_id',
        'team_id',
        'edit_package',
        'checker_level',
        'first_checker_id',
        'second_checker_id',
        'first_checker_status',
        'second_checker_status'
    ];

    protected $casts = [
        'edit_package' => 'array',
        'checker_level' => 'integer'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function firstChecker()
    {
        return $this->belongsTo(User::class, 'first_checker_id');
    }

    public function secondChecker()
    {
        return $this->belongsTo(User::class, 'second_checker_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function processConfig()
    {
        return $this->belongsTo(ProcessCodeConfig::class, 'process_code', 'process_code');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('process_status', 'PENDING');
    }

    public function scopeApproved($query)
    {
        return $query->where('process_status', 'APPROVED');
    }

    public function scopeRejected($query)
    {
        return $query->where('process_status', 'REJECTED');
    }

    // Helper Methods
    public function isPending()
    {
        return $this->process_status === 'PENDING';
    }

    public function isApproved()
    {
        return $this->process_status === 'APPROVED';
    }

    public function isRejected()
    {
        return $this->process_status === 'REJECTED';
    }

    public function getCurrentLevel()
    {
        return $this->checker_level;
    }

    public function getNextLevel()
    {
        $config = $this->processConfig;
        if (!$config) return null;

        if ($this->checker_level === 1 && $config->requires_second_checker) {
            return 2;
        } elseif ($this->checker_level === 2 && $config->requires_approver) {
            return 3;
        }

        return null;
    }
} 