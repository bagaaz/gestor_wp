<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['site_id', 'action', 'description', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
