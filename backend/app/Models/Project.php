<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'client_name',
        'status',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created the project
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all artifacts for this project
     */
    public function artifacts(): HasMany
    {
        return $this->hasMany(Artifact::class);
    }

    /**
     * Get all modules for this project
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    /**
     * Get all audit events for this project
     */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'entity_id')
            ->where('entity_type', 'project');
    }

    /**
     * Check if project can be edited based on status
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'discovery', 'execution']);
    }

    /**
     * Check if project can be archived
     */
    public function isArchivable(): bool
    {
        return $this->status === 'delivered';
    }
}
