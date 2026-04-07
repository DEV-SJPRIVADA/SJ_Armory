<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeaponImportRow extends Model
{
    use HasFactory;

    public const ACTION_ERROR = 'error';
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_NO_CHANGE = 'no_change';
    public const EXECUTION_PENDING = 'pending';
    public const EXECUTION_PROCESSING = 'processing';
    public const EXECUTION_COMPLETED = 'completed';
    public const EXECUTION_FAILED = 'failed';

    public const ACTION_LABELS = [
        self::ACTION_CREATE => 'Crear',
        self::ACTION_UPDATE => 'Actualizar',
        self::ACTION_NO_CHANGE => 'Sin cambios',
        self::ACTION_ERROR => 'Error',
    ];

    protected $fillable = [
        'batch_id',
        'weapon_id',
        'client_id',
        'row_number',
        'action',
        'execution_status',
        'processed_at',
        'execution_error',
        'summary',
        'raw_payload',
        'normalized_payload',
        'before_payload',
        'after_payload',
        'errors',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
        'before_payload' => 'array',
        'after_payload' => 'array',
        'errors' => 'array',
        'processed_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(WeaponImportBatch::class, 'batch_id');
    }

    public function weapon()
    {
        return $this->belongsTo(Weapon::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? ucfirst((string) $this->action);
    }
}
