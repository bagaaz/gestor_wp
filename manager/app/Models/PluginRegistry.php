<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PluginRegistry extends Model
{
    protected $table = 'plugin_registry';

    protected $fillable = [
        'name',
        'slug',
        'source',
        'file_path',
        'description',
    ];
}
