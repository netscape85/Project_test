<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Role constants
    public const ROLE_ADMIN = 'admin';
    public const ROLE_PM = 'pm';
    public const ROLE_ENGINEER = 'engineer';
    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_PM,
        self::ROLE_ENGINEER,
        self::ROLE_VIEWER,
    ];

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is PM (Project Manager)
     */
    public function isPM(): bool
    {
        return $this->role === self::ROLE_PM;
    }

    /**
     * Check if user is Engineer
     */
    public function isEngineer(): bool
    {
        return $this->role === self::ROLE_ENGINEER;
    }

    /**
     * Check if user is Viewer
     */
    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    /**
     * Get all projects created by this user
     */
    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * Get all artifacts owned by this user
     */
    public function ownedArtifacts(): HasMany
    {
        return $this->hasMany(Artifact::class, 'owner_user_id');
    }

    /**
     * Get all audit events performed by this user
     */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'actor_user_id');
    }

    /**
     * Get role display name
     */
    public function getRoleNameAttribute(): string
    {
        return match($this->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_PM => 'Project Manager',
            self::ROLE_ENGINEER => 'Engineer',
            self::ROLE_VIEWER => 'Viewer',
            default => 'Unknown',
        };
    }
}
