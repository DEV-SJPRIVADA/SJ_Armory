<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeaponIncident extends Model
{
    use HasFactory;
    use Auditable;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CANCELLED = 'cancelled';

    public const OUTCOME_REINTEGRATED = 'reintegrated';
    public const OUTCOME_RECOVERED_PENDING_VALIDATION = 'recovered_pending_validation';
    public const OUTCOME_THEFT_DEFINITIVE = 'theft_definitive';
    public const OUTCOME_LOSS_DEFINITIVE = 'loss_definitive';
    public const OUTCOME_SEIZURE_DEFINITIVE = 'seizure_definitive';
    public const OUTCOME_RETIRED_DEFINITIVE = 'retired_definitive';
    public const OUTCOME_ADMINISTRATIVE_CLOSURE = 'administrative_closure';

    protected $fillable = [
        'weapon_id',
        'incident_type_id',
        'incident_modality_id',
        'status',
        'observation',
        'note',
        'event_at',
        'reported_at',
        'reported_by',
        'source_document_id',
        'attachment_file_id',
        'resolved_at',
        'resolved_by',
        'resolution_note',
        'closure_outcome',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function weapon()
    {
        return $this->belongsTo(Weapon::class);
    }

    public function type()
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id');
    }

    public function modality()
    {
        return $this->belongsTo(IncidentModality::class, 'incident_modality_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function sourceDocument()
    {
        return $this->belongsTo(WeaponDocument::class, 'source_document_id');
    }

    public function attachmentFile()
    {
        return $this->belongsTo(File::class, 'attachment_file_id');
    }

    public function updates()
    {
        return $this->hasMany(WeaponIncidentUpdate::class)
            ->orderByDesc('happened_at')
            ->orderByDesc('id');
    }

    public function latestUpdate()
    {
        return $this->hasOne(WeaponIncidentUpdate::class)->latestOfMany('happened_at');
    }

    public function followUps()
    {
        return $this->hasMany(WeaponIncidentFollowUp::class)
            ->orderByDesc('follow_up_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function latestFollowUp()
    {
        return $this->hasOne(WeaponIncidentFollowUp::class)->latestOfMany('follow_up_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    public function scopeOperationalBlockers(Builder $query): Builder
    {
        $persistentOutcomes = self::persistentClosureOutcomes();

        return $query
            ->whereHas('type', fn (Builder $typeQuery) => $typeQuery->where('blocks_operation', true))
            ->where(function (Builder $statusQuery) use ($persistentOutcomes) {
                $statusQuery->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS])
                    ->orWhere(function (Builder $terminalQuery) use ($persistentOutcomes) {
                        $terminalQuery
                            ->where('status', self::STATUS_RESOLVED)
                            ->where(function (Builder $resolvedQuery) use ($persistentOutcomes) {
                                $resolvedQuery
                                    ->whereIn('closure_outcome', $persistentOutcomes)
                                    ->orWhere(function (Builder $legacyQuery) {
                                        $legacyQuery
                                            ->whereNull('closure_outcome')
                                            ->whereHas('type', fn (Builder $typeQuery) => $typeQuery->where('persists_operational_block', true));
                                    });
                            });
                    });
            });
    }

    public static function closureOutcomeDefinitions(): array
    {
        return [
            self::OUTCOME_REINTEGRATED => [
                'label' => 'Recuperada y reintegrada',
                'blocks_operation' => false,
                'impact_label' => 'Devuelve el arma a operación',
            ],
            self::OUTCOME_RECOVERED_PENDING_VALIDATION => [
                'label' => 'Recuperada pendiente de validación',
                'blocks_operation' => true,
                'impact_label' => 'Mantiene el arma fuera de operación',
            ],
            self::OUTCOME_THEFT_DEFINITIVE => [
                'label' => 'Hurto definitivo',
                'blocks_operation' => true,
                'impact_label' => 'Mantiene el arma fuera de operación de forma definitiva',
            ],
            self::OUTCOME_LOSS_DEFINITIVE => [
                'label' => 'Pérdida definitiva',
                'blocks_operation' => true,
                'impact_label' => 'Mantiene el arma fuera de operación de forma definitiva',
            ],
            self::OUTCOME_SEIZURE_DEFINITIVE => [
                'label' => 'Incautación definitiva',
                'blocks_operation' => true,
                'impact_label' => 'Mantiene el arma fuera de operación de forma definitiva',
            ],
            self::OUTCOME_RETIRED_DEFINITIVE => [
                'label' => 'Dar de baja definitiva',
                'blocks_operation' => true,
                'impact_label' => 'Retira el arma de operación de forma permanente',
            ],
            self::OUTCOME_ADMINISTRATIVE_CLOSURE => [
                'label' => 'Cierre administrativo sin afectación operativa',
                'blocks_operation' => false,
                'impact_label' => 'No bloquea la operación del arma',
            ],
        ];
    }

    public static function closureOutcomeOptionsForType(?IncidentType $type): array
    {
        $definitions = self::closureOutcomeDefinitions();

        if (! $type) {
            return [];
        }

        $allowed = match ($type->code) {
            'hurtada' => [
                self::OUTCOME_REINTEGRATED,
                self::OUTCOME_RECOVERED_PENDING_VALIDATION,
                self::OUTCOME_THEFT_DEFINITIVE,
            ],
            'perdida' => [
                self::OUTCOME_REINTEGRATED,
                self::OUTCOME_RECOVERED_PENDING_VALIDATION,
                self::OUTCOME_LOSS_DEFINITIVE,
            ],
            'incautada' => [
                self::OUTCOME_REINTEGRATED,
                self::OUTCOME_RECOVERED_PENDING_VALIDATION,
                self::OUTCOME_SEIZURE_DEFINITIVE,
            ],
            'dar_de_baja' => [
                self::OUTCOME_RETIRED_DEFINITIVE,
            ],
            default => [
                self::OUTCOME_ADMINISTRATIVE_CLOSURE,
            ],
        };

        return collect($allowed)
            ->mapWithKeys(fn (string $key) => [$key => $definitions[$key]['label']])
            ->all();
    }

    public static function persistentClosureOutcomes(): array
    {
        return collect(self::closureOutcomeDefinitions())
            ->filter(fn (array $definition) => (bool) $definition['blocks_operation'])
            ->keys()
            ->all();
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Abierta',
            self::STATUS_IN_PROGRESS => 'En proceso',
            self::STATUS_RESOLVED => 'Resuelta',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    public static function initialStatusOptions(): array
    {
        return [
            self::STATUS_OPEN => self::statusOptions()[self::STATUS_OPEN],
            self::STATUS_IN_PROGRESS => self::statusOptions()[self::STATUS_IN_PROGRESS],
        ];
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS], true);
    }

    public function blocksOperationalAvailability(): bool
    {
        if (!$this->type?->blocks_operation) {
            return false;
        }

        if ($this->isOpen()) {
            return true;
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return false;
        }

        if ($this->closure_outcome) {
            return (bool) (self::closureOutcomeDefinitions()[$this->closure_outcome]['blocks_operation'] ?? false);
        }

        return (bool) $this->type?->persists_operational_block;
    }

    public function closureOutcomeLabel(): ?string
    {
        if (! $this->closure_outcome) {
            return null;
        }

        return self::closureOutcomeDefinitions()[$this->closure_outcome]['label'] ?? $this->closure_outcome;
    }

    public function closureImpactLabel(): ?string
    {
        if (! $this->closure_outcome) {
            return null;
        }

        return self::closureOutcomeDefinitions()[$this->closure_outcome]['impact_label'] ?? null;
    }

    public function latestActivityAt()
    {
        $latestUpdateAt = $this->latestUpdate?->happened_at;

        if (!$latestUpdateAt) {
            return $this->event_at;
        }

        if (!$this->event_at) {
            return $latestUpdateAt;
        }

        return $latestUpdateAt->greaterThan($this->event_at)
            ? $latestUpdateAt
            : $this->event_at;
    }
}

