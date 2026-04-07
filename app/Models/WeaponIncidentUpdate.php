<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeaponIncidentUpdate extends Model
{
    use HasFactory;
    use Auditable;

    public const EVENT_REPORTED = 'reported';
    public const EVENT_NOTE = 'note';
    public const EVENT_SUPPORT = 'support';
    public const EVENT_RECOVERY = 'recovery';
    public const EVENT_REINTEGRATION = 'reintegration';
    public const EVENT_STATUS = 'status_change';
    public const EVENT_CLOSURE = 'closure';
    public const EVENT_REOPEN = 'reopen';

    protected $fillable = [
        'weapon_incident_id',
        'event_type',
        'note',
        'attachment_file_id',
        'happened_at',
        'status_from',
        'status_to',
        'created_by',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
    ];

    public function incident()
    {
        return $this->belongsTo(WeaponIncident::class, 'weapon_incident_id');
    }

    public function attachmentFile()
    {
        return $this->belongsTo(File::class, 'attachment_file_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function eventTypeOptions(): array
    {
        return [
            self::EVENT_REPORTED => 'Reporte inicial',
            self::EVENT_NOTE => 'Seguimiento',
            self::EVENT_SUPPORT => 'Soporte documental',
            self::EVENT_RECOVERY => 'Recuperación',
            self::EVENT_REINTEGRATION => 'Reintegración operativa',
            self::EVENT_STATUS => 'Cambio de estado',
            self::EVENT_CLOSURE => 'Cierre',
            self::EVENT_REOPEN => 'Reapertura',
        ];
    }

    public static function manualEventTypeOptions(): array
    {
        return collect(self::eventTypeOptions())
            ->except([
                self::EVENT_REPORTED,
                self::EVENT_CLOSURE,
                self::EVENT_REOPEN,
            ])
            ->all();
    }

    public function eventTypeLabel(): string
    {
        return self::eventTypeOptions()[$this->event_type] ?? $this->event_type;
    }
}

