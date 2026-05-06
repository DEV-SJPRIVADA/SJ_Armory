<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    use HasFactory;

    public const ROLE_ESCOLTA = 'ESCOLTA';
    public const ROLE_SUPERVISOR = 'SUPERVISOR';
    public const ROLE_GUARDA = 'GUARDA';
    public const ROLE_MOTORIZADO = 'MOTORIZADO';
    public const ROLE_GUARDA_INFRAESTRUCTURA = 'GUARDA_INFRAESTRUCTURA';

    /**
     * Valores guardados en workers.role y etiquetas para formularios y listados.
     *
     * @return array<string, string>
     */
    public static function roleLabels(): array
    {
        return [
            self::ROLE_ESCOLTA => 'Escolta',
            self::ROLE_SUPERVISOR => 'Supervisor',
            self::ROLE_GUARDA => 'Guarda',
            self::ROLE_MOTORIZADO => 'Motorizado',
            self::ROLE_GUARDA_INFRAESTRUCTURA => 'Guarda de infraestructura',
        ];
    }

    protected $fillable = [
        'client_id',
        'name',
        'document',
        'role',
        'responsible_user_id',
        'notes',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function assignments()
    {
        return $this->hasMany(WeaponWorkerAssignment::class);
    }

    public function histories()
    {
        return $this->hasMany(WorkerHistory::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }
}
