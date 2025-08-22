<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiInteraction extends Model
{
    use HasFactory;

    protected $table = 'ai_interactions';

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'metadata',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the session that owns the interaction
     */
    public function session()
    {
        // Session relationship - can be implemented when AiSession model is created
        return null;
    }

    /**
     * Scope to filter by session
     */
    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to filter by role
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get recent interactions
     */
    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
} 