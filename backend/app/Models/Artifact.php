<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artifact extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'status',
        'owner_user_id',
        'content_json',
        'completed_at',
    ];

    protected $casts = [
        'content_json' => 'array',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Artifact types as constants
    public const TYPE_STRATEGIC_ALIGNMENT = 'strategic_alignment';
    public const TYPE_BIG_PICTURE = 'big_picture';
    public const TYPE_DOMAIN_BREAKDOWN = 'domain_breakdown';
    public const TYPE_MODULE_MATRIX = 'module_matrix';
    public const TYPE_MODULE_ENGINEERING = 'module_engineering';
    public const TYPE_SYSTEM_ARCHITECTURE = 'system_architecture';
    public const TYPE_PHASE_SCOPE = 'phase_scope';

    public const TYPES = [
        self::TYPE_STRATEGIC_ALIGNMENT,
        self::TYPE_BIG_PICTURE,
        self::TYPE_DOMAIN_BREAKDOWN,
        self::TYPE_MODULE_MATRIX,
        self::TYPE_MODULE_ENGINEERING,
        self::TYPE_SYSTEM_ARCHITECTURE,
        self::TYPE_PHASE_SCOPE,
    ];

    // Status constants
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_DONE = 'done';

    /**
     * Get the project this artifact belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the owner user of this artifact
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Get audit events for this artifact
     */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'entity_id')
            ->where('entity_type', 'artifact');
    }

    /**
     * Check if artifact can be edited
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_NOT_STARTED, self::STATUS_IN_PROGRESS, self::STATUS_BLOCKED]);
    }

    /**
     * Check if artifact can be completed
     */
    public function isCompletable(): bool
    {
        return $this->status !== self::STATUS_DONE && !empty($this->content_json);
    }

    /**
     * Mark artifact as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_DONE,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get the display name for the artifact type
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            self::TYPE_STRATEGIC_ALIGNMENT => 'Strategic Alignment',
            self::TYPE_BIG_PICTURE => 'Big Picture',
            self::TYPE_DOMAIN_BREAKDOWN => 'Domain Breakdown',
            self::TYPE_MODULE_MATRIX => 'Module Matrix',
            self::TYPE_MODULE_ENGINEERING => 'Module Engineering',
            self::TYPE_SYSTEM_ARCHITECTURE => 'System Architecture',
            self::TYPE_PHASE_SCOPE => 'Phase Scope',
            default => $this->type,
        };
    }
}
