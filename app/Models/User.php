<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'email_notifications_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'                    => 'hashed',
            'is_active'                   => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'last_login_at'               => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSales(): bool
    {
        return $this->role === 'sales';
    }

    public function isTechTeam(): bool
    {
        return $this->role === 'tech_team';
    }

    /** @deprecated Use isTechTeam(). Kept temporarily for legacy callers. */
    public function isTechWriter(): bool
    {
        return $this->isTechTeam();
    }

    /** @deprecated Use isTechTeam(). Kept temporarily for legacy callers. */
    public function isTechLead(): bool
    {
        return $this->isTechTeam();
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles);
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin'     => 'Admin',
            'sales'     => 'Sales',
            'tech_team' => 'Tech team',
            default     => $this->role,
        };
    }

    public function articlesAsSalesRep(): HasMany
    {
        return $this->hasMany(Article::class, 'sales_rep_id');
    }

    public function articlesAsTechWriter(): HasMany
    {
        return $this->hasMany(Article::class, 'tech_writer_id');
    }

    public function articlesAsTechLead(): HasMany
    {
        return $this->hasMany(Article::class, 'tech_lead_id');
    }
}
