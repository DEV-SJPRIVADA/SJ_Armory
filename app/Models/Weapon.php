<?php

namespace App\Models;

use App\Support\PostCustodyRole;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Weapon extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'internal_code',
        'serial_number',
        'weapon_type',
        'caliber',
        'brand',
        'capacity',
        'ownership_type',
        'ownership_entity',
        'permit_type',
        'permit_number',
        'permit_expires_at',
        'permit_file_id',
        'notes',
        'imprint_month',
        'imprint_received_by',
        'imprint_received_at',
    ];

    protected $casts = [
        'permit_expires_at' => 'date',
    ];

    public function photos()
    {
        return $this->hasMany(WeaponPhoto::class);
    }

    public function documents()
    {
        return $this->hasMany(WeaponDocument::class);
    }

    public function histories()
    {
        return $this->hasMany(WeaponHistory::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function incidents()
    {
        return $this->hasMany(WeaponIncident::class)->orderByDesc('event_at')->orderByDesc('id');
    }

    public function openIncidents()
    {
        return $this->hasMany(WeaponIncident::class)
            ->whereIn('status', [WeaponIncident::STATUS_OPEN, WeaponIncident::STATUS_IN_PROGRESS])
            ->orderByDesc('event_at')
            ->orderByDesc('id');
    }

    public function operationalBlockingIncidents()
    {
        return $this->hasMany(WeaponIncident::class)
            ->operationalBlockers()
            ->orderByDesc('event_at')
            ->orderByDesc('id');
    }

    public function clientAssignments()
    {
        return $this->hasMany(WeaponClientAssignment::class);
    }

    public function activeClientAssignment()
    {
        return $this->hasOne(WeaponClientAssignment::class)->where('is_active', true);
    }

    public function postAssignments()
    {
        return $this->hasMany(WeaponPostAssignment::class);
    }

    public function activePostAssignment()
    {
        return $this->hasOne(WeaponPostAssignment::class)->where('is_active', true);
    }

    public function workerAssignments()
    {
        return $this->hasMany(WeaponWorkerAssignment::class);
    }

    public function activeWorkerAssignment()
    {
        return $this->hasOne(WeaponWorkerAssignment::class)->where('is_active', true);
    }

    public function transfers()
    {
        return $this->hasMany(WeaponTransfer::class);
    }

    /**
     * Transferencia pendiente (relación para eager load en listados).
     */
    public function activePendingTransfer(): HasOne
    {
        return $this->hasOne(WeaponTransfer::class)
            ->where('status', WeaponTransfer::STATUS_PENDING);
    }

    public function pendingTransfer(): ?WeaponTransfer
    {
        if ($this->relationLoaded('activePendingTransfer')) {
            return $this->activePendingTransfer;
        }

        /** @var WeaponTransfer|null $transfer */
        $transfer = $this->transfers()
            ->where('status', WeaponTransfer::STATUS_PENDING)
            ->with(['requestedBy', 'toUser'])
            ->first();

        return $transfer;
    }

    /**
     * Cliente operativo mostrable: asignación activa, o cliente de origen en transferencia pendiente (datos legacy o sin desplegar fix).
     */
    public function operationalDisplayClient(): ?Client
    {
        $fromAssignment = $this->activeClientAssignment?->client;
        if ($fromAssignment) {
            return $fromAssignment;
        }

        return $this->activePendingTransfer?->fromClient;
    }

    /**
     * Responsable operativo mostrable (mismo criterio que operationalDisplayClient).
     */
    public function operationalDisplayResponsible(): ?User
    {
        $fromAssignment = $this->activeClientAssignment?->responsible;
        if ($fromAssignment) {
            return $fromAssignment;
        }

        return $this->activePendingTransfer?->fromUser;
    }

    /**
     * Mensaje para bloquear destino/asignaciones internas mientras hay transferencia pendiente.
     */
    public function pendingTransferBlockMessage(?User $user): ?string
    {
        $pending = $this->pendingTransfer();
        if ($pending === null) {
            return null;
        }
        $pending->loadMissing(['requestedBy', 'toUser']);
        if ($user !== null && $user->isAdmin()) {
            return __('Esta arma está en transferencia pendiente. Enviada por :from; debe aceptarla :to.', [
                'from' => $pending->requestedBy?->name ?? __('—'),
                'to' => $pending->toUser?->name ?? __('—'),
            ]);
        }

        return __('Esta arma tiene una transferencia pendiente de aceptación. No puede modificar su destino ni sus asignaciones hasta que se resuelva.');
    }

    public function permitFile()
    {
        return $this->belongsTo(File::class, 'permit_file_id');
    }

    public function isOperationalForInventory(): bool
    {
        if ($this->relationLoaded('operationalBlockingIncidents')) {
            if ($this->operationalBlockingIncidents->isNotEmpty()) {
                return false;
            }
        } elseif ($this->operationalBlockingIncidents()->exists()) {
            return false;
        }

        $post = $this->relationLoaded('activePostAssignment')
            ? $this->activePostAssignment?->post
            : $this->activePostAssignment()->with('post')->first()?->post;

        if ($post?->isNonOperationalCustody()) {
            return false;
        }

        return true;
    }

    public function scopeOperationalInventory(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('incidents', fn (Builder $incidentQuery) => $incidentQuery->operationalBlockers())
            ->where(function (Builder $outer) {
                $outer
                    ->whereDoesntHave('activePostAssignment')
                    ->orWhereHas('activePostAssignment.post', fn (Builder $postQuery) => $postQuery->operationalCustody());
            });
    }

    public function scopeNonOperationalInventory(Builder $query): Builder
    {
        return $query->where(function (Builder $outer) {
            $outer
                ->whereHas('incidents', fn (Builder $incidentQuery) => $incidentQuery->operationalBlockers())
                ->orWhereHas('activePostAssignment.post', fn (Builder $postQuery) => $postQuery->nonOperationalCustody());
        });
    }

    public function activeCustodyRole(): ?string
    {
        $post = $this->activePostAssignment?->post;

        return $post?->custody_role;
    }

    public function custodyStatusLabel(): ?string
    {
        $role = $this->activeCustodyRole();

        return $role ? PostCustodyRole::label($role) : null;
    }
}
