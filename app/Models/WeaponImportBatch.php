<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeaponImportBatch extends Model
{
    use HasFactory;

    public const TYPE_WEAPON = 'weapon';
    public const TYPE_CLIENT = 'client';

    protected $fillable = [
        'file_id',
        'uploaded_by',
        'executed_by',
        'status',
        'type',
        'source_name',
        'total_rows',
        'create_count',
        'update_count',
        'no_change_count',
        'error_count',
        'executed_at',
        'started_at',
        'finished_at',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'last_error',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function executedBy()
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function rows()
    {
        return $this->hasMany(WeaponImportRow::class, 'batch_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isExecuted(): bool
    {
        return $this->status === 'executed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function hasErrors(): bool
    {
        return (int) $this->error_count > 0;
    }

    public function isWeaponImport(): bool
    {
        return ($this->type ?: self::TYPE_WEAPON) === self::TYPE_WEAPON;
    }

    public function isClientImport(): bool
    {
        return $this->type === self::TYPE_CLIENT;
    }

    public function typeLabel(): string
    {
        return match ($this->type ?: self::TYPE_WEAPON) {
            self::TYPE_CLIENT => 'Clientes',
            default => 'Armas',
        };
    }

    public function progressPercentage(): int
    {
        if ((int) $this->total_rows <= 0) {
            return 0;
        }

        return (int) min(100, floor(((int) $this->processed_rows / (int) $this->total_rows) * 100));
    }
}
