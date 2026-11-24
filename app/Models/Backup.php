<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $table = 'backups';

    protected $fillable = [
        'type', 'status', 'disk', 'path', 'filename', 'size', 'checksum',
        'created_by', 'restored_by', 'restored_at', 'completed_at', 'error_message', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'metadata' => 'array',
            'restored_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restorer()
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function sizeForHumans(): string
    {
        $size = (float) ($this->size ?? 0);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return number_format($size, $index === 0 ? 0 : 2, ',', '.') . ' ' . $units[$index];
    }
}
