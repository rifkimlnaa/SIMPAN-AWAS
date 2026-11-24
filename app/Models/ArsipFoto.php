<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu foto bukti milik sebuah arsip (disimpan privat di disk "local"). */
class ArsipFoto extends Model
{
    protected $fillable = ['arsip_id', 'path', 'nama_asli', 'mime', 'ukuran', 'urutan'];

    public function arsip(): BelongsTo
    {
        return $this->belongsTo(Arsip::class);
    }
}
