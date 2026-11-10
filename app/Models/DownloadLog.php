<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLog extends Model
{
    protected $table = 'download_logs';

    public $timestamps = false;

    protected $fillable = ['arsip_id', 'user_id', 'arsip_label', 'ip_address', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function arsip(): BelongsTo
    {
        return $this->belongsTo(Arsip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
