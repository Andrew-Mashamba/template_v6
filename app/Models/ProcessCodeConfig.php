<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessCodeConfig extends Model
{
    protected $fillable = [
        'process_code',
        'process_name',
        'description',
        'requires_first_checker',
        'requires_second_checker',
        'requires_approver',
        'first_checker_roles',
        'second_checker_roles',
        'approver_roles',
        'min_amount',
        'max_amount',
        'is_active'
    ];

    protected $casts = [
        'first_checker_roles' => 'array',
        'second_checker_roles' => 'array',
        'approver_roles' => 'array',
        'requires_first_checker' => 'boolean',
        'requires_second_checker' => 'boolean',
        'requires_approver' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function getApproversForAmount($amount = null)
    {
        $config = $this->where('is_active', true);

        if ($amount !== null) {
            $config = $config->where(function($query) use ($amount) {
                $query->whereNull('min_amount')
                    ->orWhere('min_amount', '<=', $amount);
            })
            ->where(function($query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            });
        }

        return $config->first();
    }

    public function canUserApprove($user, $level = 1)
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if user is an admin
        if ($user->isAdmin()) {
            return true;
        }

        // Get the roles for the current level
        $roles = match($level) {
            1 => $this->first_checker_roles,
            2 => $this->second_checker_roles,
            3 => $this->approver_roles,
            default => null
        };
        
        if (empty($roles)) {
            return false;
        }

        // Check if user has any of the required roles
        return $user->roles()->whereIn('roles.id', $roles)->exists();
    }

    public function requiresSecondChecker($amount = null)
    {
        if (!$this->requires_second_checker) {
            return false;
        }

        if ($this->min_amount === null) {
            return true;
        }

        return $amount >= $this->min_amount;
    }

    // Helper method to get role names for display
    public function getRoleNames($roleIds)
    {
        return Role::whereIn('id', $roleIds)->pluck('name')->toArray();
    }

    public function firstCheckerRoles()
    {
        return $this->belongsToMany(Role::class, null, 'first_checker_roles');
    }

    public function secondCheckerRoles()
    {
        return $this->belongsToMany(Role::class, null, 'second_checker_roles');
    }

    public function approverRoles()
    {
        return $this->belongsToMany(Role::class, null, 'approver_roles');
    }

    public function getFirstCheckerRoleNamesAttribute()
    {
        if (empty($this->first_checker_roles)) {
            return [];
        }
        return Role::whereIn('id', $this->first_checker_roles)->pluck('name')->toArray();
    }

    public function getSecondCheckerRoleNamesAttribute()
    {
        if (empty($this->second_checker_roles)) {
            return [];
        }
        return Role::whereIn('id', $this->second_checker_roles)->pluck('name')->toArray();
    }

    public function getApproverRoleNamesAttribute()
    {
        if (empty($this->approver_roles)) {
            return [];
        }
        return Role::whereIn('id', $this->approver_roles)->pluck('name')->toArray();
    }
} 