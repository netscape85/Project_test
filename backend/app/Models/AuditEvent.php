<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends Model
{
    protected $fillable = [
        'actor_user_id',
        'entity_type',
        'entity_id',
        'action',
        'before_json',
        'after_json',
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Action constants
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_STATUS_CHANGED = 'status_changed';
    public const ACTION_VALIDATED = 'validated';
    public const ACTION_COMPLETED = 'completed';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_RESTORED = 'restored';

    // Entity types
    public const ENTITY_PROJECT = 'project';
    public const ENTITY_ARTIFACT = 'artifact';
    public const ENTITY_MODULE = 'module';

    /**
     * Get the user who performed the action
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Get the related entity (polymorphic)
     */
    public function entity()
    {
        return match($this->entity_type) {
            self::ENTITY_PROJECT => $this->belongsTo(Project::class, 'entity_id'),
            self::ENTITY_ARTIFACT => $this->belongsTo(Artifact::class, 'entity_id'),
            self::ENTITY_MODULE => $this->belongsTo(Module::class, 'entity_id'),
            default => null,
        };
    }

    /**
     * Create an audit event for a model change
     */
    public static function logChange(
        int $actorUserId,
        string $entityType,
        int $entityId,
        string $action,
        ?array $before = null,
        ?array $after = null
    ): self {
        return self::create([
            'actor_user_id' => $actorUserId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'before_json' => $before,
            'after_json' => $after,
        ]);
    }
}
