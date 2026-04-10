<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $fillable = [
        'name',
        'url',
        'db_name',
        'wp_version',
        'php_version',
        'admin_user',
        'admin_email',
        'status',
        'disk_usage',
        'db_size',
        'notes',
        'plugins',
        'themes',
    ];

    protected $casts = [
        'plugins' => 'array',
        'themes' => 'array',
        'disk_usage' => 'integer',
        'db_size' => 'integer',
    ];

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function getFormattedDiskUsageAttribute(): string
    {
        return $this->formatBytes($this->disk_usage ?? 0);
    }

    public function getFormattedDbSizeAttribute(): string
    {
        return $this->formatBytes($this->db_size ?? 0);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
