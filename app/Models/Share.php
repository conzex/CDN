<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'path',
        'type',
        'password_hash',
        'expires_at',
        'max_downloads',
        'download_count',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'max_downloads' => 'integer',
        'download_count' => 'integer',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPasswordProtected(): bool
    {
        return !empty($this->password_hash);
    }

    public function isExhausted(): bool
    {
        return $this->max_downloads !== null && $this->download_count >= $this->max_downloads;
    }

    public function isActive(): bool
    {
        return !$this->isExpired() && !$this->isExhausted();
    }

    public function publicUrl(): string
    {
        return url("/s/{$this->token}");
    }
}
