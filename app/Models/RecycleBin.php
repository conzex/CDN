<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecycleBin extends Model
{
    use HasFactory;

    protected $table = 'recycle_bin';

    protected $fillable = [
        'original_path',
        'trashed_path',
        'type',
        'size',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'size' => 'integer',
    ];
}
