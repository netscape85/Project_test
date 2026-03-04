<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'project_id',
        'domain',
        'name',
        'status',
        'objective',
        'inputs',
        'data_structure',
        'logic_rules',
        'outputs',
        'responsibility',
        'failure_scenarios',
        'audit_trail_requirements',
        'dependencies',
        'version_note',
    ];

    protected $casts = [
        'inputs' => 'array',
        'outputs' => 'array',
        'dependencies' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the project this module belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get audit events for this module
     */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'entity_id')
            ->where('entity_type', 'module');
    }

    /**
     * Check if module can be edited
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if module can be validated
     * 
     * A module can only move to validated if:
     * - objective is not empty
     * - inputs has at least 1 item
     * - outputs has at least 1 item
     * - responsibility is not empty
     */
    public function isValidatable(): bool
    {
        return $this->status === 'draft' && $this->hasRequiredFields();
    }

    /**
     * Check if module has all required fields filled
     * Per requirements:
     * - objective is not empty
     * - inputs has at least 1 item
     * - outputs has at least 1 item
     * - responsibility is not empty
     */
    public function hasRequiredFields(): bool
    {
        $hasObjective = !empty($this->objective);
        $hasInputs = !empty($this->inputs) && is_array($this->inputs) && count($this->inputs) >= 1;
        $hasOutputs = !empty($this->outputs) && is_array($this->outputs) && count($this->outputs) >= 1;
        $hasResponsibility = !empty($this->responsibility);

        return $hasObjective && $hasInputs && $hasOutputs && $hasResponsibility;
    }
}
