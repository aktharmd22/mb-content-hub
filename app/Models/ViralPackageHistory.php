<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViralPackageHistory extends Model
{
    protected $table = 'viral_package_history';

    protected $fillable = [
        'deliverable_id',
        'from_stage',
        'to_stage',
        'changed_by',
        'notes',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(ViralPackageDeliverable::class, 'deliverable_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
