<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    protected $fillable = ['site_id', 'path', 'size', 'type', 'notes'];

    protected $casts = ['size' => 'integer'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
