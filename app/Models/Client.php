<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'company',
        'contact_email',
        'contact_phone',
        'notes',
        'created_by',
    ];

    /** Prioritised display label — company name first, falling back to the contact name. */
    public function displayName(): string
    {
        return $this->company ?: $this->name;
    }

    /** The contact person's name, shown as a secondary line only when a company exists. */
    public function secondaryName(): ?string
    {
        return $this->company ? $this->name : null;
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function viralPackages(): HasMany
    {
        return $this->hasMany(ViralPackage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
