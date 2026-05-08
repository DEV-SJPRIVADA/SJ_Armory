<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermitAuthenticatedTemplate extends Model
{
    public const KIND_PORTE = 'porte';

    public const KIND_TENENCIA = 'tenencia';

    protected $fillable = [
        'permit_kind',
        'file_id',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
