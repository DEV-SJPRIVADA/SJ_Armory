<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\File;
use App\Models\IncidentModality;
use App\Models\IncidentType;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponIncident;
use App\Models\WeaponIncidentUpdate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

class WeaponIncidentService
{
    public function create(Weapon $weapon, array $data, User $actor): WeaponIncident
    {
        $type = IncidentType::query()->findOrFail($data['incident_type_id']);
        $modality = $this->resolveModality($type, Arr::get($data, 'incident_modality_id'));
        $attachment = Arr::pull($data, 'attachment');
        $storedPath = null;
        $storedFile = null;
        $incident = null;

        try {
            DB::transaction(function () use (
                $weapon,
                $data,
                $actor,
                $type,
                $modality,
                $attachment,
                &$storedPath,
                &$storedFile,
                &$incident
            ) {
                if ($attachment instanceof UploadedFile) {
                    [$storedPath, $storedFile] = $this->storeAttachment($weapon, $attachment, $actor);
                }

                $status = (string) ($data['status'] ?? WeaponIncident::STATUS_OPEN);
                $eventAt = Arr::get($data, 'event_at', now());

                if (!in_array($status, array_keys(WeaponIncident::initialStatusOptions()), true)) {
                    throw new InvalidArgumentException('El reporte inicial debe abrir el expediente en abierta o en proceso.');
                }

                $incident = WeaponIncident::create([
                    'weapon_id' => $weapon->id,
                    'incident_type_id' => $type->id,
                    'incident_modality_id' => $modality?->id,
                    'status' => $status,
                    'observation' => Arr::get($data, 'observation'),
                    'note' => Arr::get($data, 'note'),
                    'event_at' => $eventAt,
                    'reported_at' => now(),
                    'reported_by' => $actor->id,
                    'source_document_id' => Arr::get($data, 'source_document_id'),
                    'attachment_file_id' => $storedFile?->id,
                    'resolved_at' => in_array($status, [WeaponIncident::STATUS_RESOLVED, WeaponIncident::STATUS_CANCELLED], true) ? now() : null,
                    'resolved_by' => in_array($status, [WeaponIncident::STATUS_RESOLVED, WeaponIncident::STATUS_CANCELLED], true) ? $actor->id : null,
                    'resolution_note' => Arr::get($data, 'resolution_note'),
                    'closure_outcome' => null,
                ]);

                $this->createUpdateRecord($incident, [
                    'event_type' => WeaponIncidentUpdate::EVENT_REPORTED,
                    'note' => $this->normalizeMessage(
                        Arr::get($data, 'note') ?: Arr::get($data, 'observation'),
                        'Novedad registrada en el expediente.'
                    ),
                    'attachment_file_id' => $storedFile?->id,
                    'happened_at' => $eventAt,
                    'status_from' => null,
                    'status_to' => $status,
                    'created_by' => $actor->id,
                ]);

                AuditLog::create([
                    'user_id' => $actor->id,
                    'action' => 'weapon_incident_created',
                    'auditable_type' => Weapon::class,
                    'auditable_id' => $weapon->id,
                    'before' => null,
                    'after' => [
                        'incident_id' => $incident->id,
                        'incident_type_id' => $type->id,
                        'incident_modality_id' => $modality?->id,
                        'status' => $status,
                    ],
                ]);
            });
        } catch (Throwable $e) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $e;
        }

        return $incident->loadMissing($this->incidentRelationships());
    }

    public function addUpdate(WeaponIncident $incident, array $data, User $actor): WeaponIncidentUpdate
    {
        $attachment = Arr::pull($data, 'attachment');
        $storedPath = null;
        $storedFile = null;
        $update = null;

        try {
            DB::transaction(function () use (
                $incident,
                $data,
                $actor,
                $attachment,
                &$storedPath,
                &$storedFile,
                &$update
            ) {
                if ($attachment instanceof UploadedFile) {
                    [$storedPath, $storedFile] = $this->storeAttachment($incident->weapon, $attachment, $actor);
                }

                $statusFrom = $incident->status;
                $statusTo = Arr::get($data, 'status') ?: null;
                $eventType = (string) ($data['event_type'] ?? WeaponIncidentUpdate::EVENT_NOTE);
                $happenedAt = Arr::get($data, 'happened_at', now());

                if (!$incident->isOpen() && $eventType !== WeaponIncidentUpdate::EVENT_REOPEN) {
                    throw new InvalidArgumentException('Reabra el expediente antes de registrar nuevos seguimientos.');
                }

                if (!$storedFile && !$statusTo && trim((string) Arr::get($data, 'note', '')) === '') {
                    throw new InvalidArgumentException('Registre una nota, un adjunto o un cambio de estado para guardar el seguimiento.');
                }

                if ($eventType === WeaponIncidentUpdate::EVENT_REOPEN && !in_array($statusTo, [WeaponIncident::STATUS_OPEN, WeaponIncident::STATUS_IN_PROGRESS], true)) {
                    throw new InvalidArgumentException('La reapertura debe dejar la novedad abierta o en proceso.');
                }

                $update = $this->createUpdateRecord($incident, [
                    'event_type' => $eventType,
                    'note' => $this->normalizeMessage(
                        Arr::get($data, 'note'),
                        $this->defaultUpdateNote($eventType, $statusFrom, $statusTo)
                    ),
                    'attachment_file_id' => $storedFile?->id,
                    'happened_at' => $happenedAt,
                    'status_from' => $statusFrom,
                    'status_to' => $statusTo,
                    'created_by' => $actor->id,
                ]);

                if ($statusTo && $statusTo !== $statusFrom) {
                    $this->applyStatusTransition(
                        $incident,
                        $statusTo,
                        $actor,
                        Arr::get($data, 'note') ?: $update->note,
                        $happenedAt,
                        Arr::get($data, 'closure_outcome')
                    );
                }

                $action = match ($eventType) {
                    WeaponIncidentUpdate::EVENT_REOPEN => 'weapon_incident_reopened',
                    WeaponIncidentUpdate::EVENT_CLOSURE => 'weapon_incident_closed',
                    default => 'weapon_incident_update_added',
                };

                AuditLog::create([
                    'user_id' => $actor->id,
                    'action' => $action,
                    'auditable_type' => Weapon::class,
                    'auditable_id' => $incident->weapon_id,
                    'before' => null,
                    'after' => [
                        'incident_id' => $incident->id,
                        'update_id' => $update->id,
                        'event_type' => $eventType,
                        'status_from' => $statusFrom,
                        'status_to' => $statusTo,
                    ],
                ]);
            });
        } catch (Throwable $e) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $e;
        }

        return $update->loadMissing(['creator', 'attachmentFile']);
    }

    public function close(WeaponIncident $incident, array $data, User $actor): WeaponIncident
    {
        $status = (string) ($data['status'] ?? WeaponIncident::STATUS_RESOLVED);
        $resolutionNote = trim((string) Arr::get($data, 'resolution_note', ''));
        $closureOutcome = Arr::get($data, 'closure_outcome');

        if ($incident->type?->requires_resolution_note && $resolutionNote === '') {
            throw new InvalidArgumentException('Esta novedad exige una nota de cierre para conservar la trazabilidad.');
        }

        if ($status === WeaponIncident::STATUS_RESOLVED) {
            if (! $closureOutcome) {
                throw new InvalidArgumentException('Seleccione el resultado del cierre para definir el impacto operativo.');
            }

            $allowedOutcomes = WeaponIncident::closureOutcomeOptionsForType($incident->type);

            if (! array_key_exists($closureOutcome, $allowedOutcomes)) {
                throw new InvalidArgumentException('El resultado de cierre no aplica para este tipo de novedad.');
            }
        } else {
            $closureOutcome = null;
        }

        $this->addUpdate($incident, [
            'event_type' => WeaponIncidentUpdate::EVENT_CLOSURE,
            'status' => $status,
            'note' => $resolutionNote,
            'closure_outcome' => $closureOutcome,
            'happened_at' => now(),
        ], $actor);

        return $incident->refresh()->loadMissing($this->incidentRelationships());
    }

    public function reopen(WeaponIncident $incident, array $data, User $actor): WeaponIncident
    {
        $status = (string) ($data['status'] ?? WeaponIncident::STATUS_OPEN);

        if (!in_array($status, [WeaponIncident::STATUS_OPEN, WeaponIncident::STATUS_IN_PROGRESS], true)) {
            throw new InvalidArgumentException('La reapertura solo puede dejar la novedad abierta o en proceso.');
        }

        $this->addUpdate($incident, [
            'event_type' => WeaponIncidentUpdate::EVENT_REOPEN,
            'status' => $status,
            'note' => Arr::get($data, 'message'),
            'closure_outcome' => null,
            'happened_at' => Arr::get($data, 'follow_up_at', now()),
        ], $actor);

        return $incident->refresh()->loadMissing($this->incidentRelationships());
    }

    private function resolveModality(IncidentType $type, mixed $modalityId): ?IncidentModality
    {
        if (!$modalityId) {
            if ($type->requires_modality) {
                throw new InvalidArgumentException('Debe seleccionar una modalidad para esta novedad.');
            }

            return null;
        }

        $modality = IncidentModality::query()
            ->where('incident_type_id', $type->id)
            ->find($modalityId);

        if (!$modality) {
            throw new InvalidArgumentException('La modalidad seleccionada no pertenece al tipo de novedad.');
        }

        return $modality;
    }

    private function storeAttachment(Weapon $weapon, UploadedFile $attachment, User $actor): array
    {
        $storedPath = $attachment->store('weapons/' . $weapon->id . '/incidents', 'local');
        $storedFile = File::create([
            'disk' => 'local',
            'path' => $storedPath,
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getClientMimeType(),
            'size' => $attachment->getSize(),
            'checksum' => hash_file('sha256', $attachment->getRealPath()),
            'uploaded_by' => $actor->id,
        ]);

        return [$storedPath, $storedFile];
    }

    private function createUpdateRecord(WeaponIncident $incident, array $attributes): WeaponIncidentUpdate
    {
        return $incident->updates()->create([
            'event_type' => $attributes['event_type'] ?? WeaponIncidentUpdate::EVENT_NOTE,
            'note' => $attributes['note'] ?? null,
            'attachment_file_id' => $attributes['attachment_file_id'] ?? null,
            'happened_at' => $attributes['happened_at'] ?? now(),
            'status_from' => $attributes['status_from'] ?? null,
            'status_to' => $attributes['status_to'] ?? null,
            'created_by' => $attributes['created_by'] ?? null,
        ]);
    }

    private function applyStatusTransition(WeaponIncident $incident, string $statusTo, User $actor, string $resolutionNote, mixed $happenedAt, ?string $closureOutcome = null): void
    {
        $updates = ['status' => $statusTo];

        if (in_array($statusTo, [WeaponIncident::STATUS_RESOLVED, WeaponIncident::STATUS_CANCELLED], true)) {
            $updates['resolved_at'] = $happenedAt;
            $updates['resolved_by'] = $actor->id;
            $updates['resolution_note'] = $resolutionNote;
            $updates['closure_outcome'] = $statusTo === WeaponIncident::STATUS_RESOLVED ? $closureOutcome : null;
            if ($statusTo === WeaponIncident::STATUS_CANCELLED) {
                $updates['closure_outcome'] = null;
            }
        } else {
            $updates['resolved_at'] = null;
            $updates['resolved_by'] = null;
            $updates['resolution_note'] = null;
            $updates['closure_outcome'] = null;
        }

        $incident->update($updates);
    }

    private function defaultUpdateNote(string $eventType, string $statusFrom, ?string $statusTo): string
    {
        return match ($eventType) {
            WeaponIncidentUpdate::EVENT_SUPPORT => 'Se adjunta soporte documental al expediente.',
            WeaponIncidentUpdate::EVENT_RECOVERY => 'Se registra recuperación del arma.',
            WeaponIncidentUpdate::EVENT_REINTEGRATION => 'Se registra reintegración operativa del arma.',
            WeaponIncidentUpdate::EVENT_STATUS => $statusTo && $statusTo !== $statusFrom
                ? 'Cambio de estado registrado en el expediente.'
                : 'Seguimiento de estado registrado.',
            WeaponIncidentUpdate::EVENT_CLOSURE => 'Se cierra la novedad.',
            WeaponIncidentUpdate::EVENT_REOPEN => 'Se reabre la novedad para continuar el seguimiento.',
            default => 'Se registra una nota de seguimiento.',
        };
    }

    private function normalizeMessage(?string $value, string $fallback): string
    {
        $message = trim((string) $value);

        return $message !== '' ? $message : $fallback;
    }

    private function incidentRelationships(): array
    {
        return [
            'type',
            'modality',
            'reporter',
            'attachmentFile',
            'updates.creator',
            'updates.attachmentFile',
            'latestUpdate.creator',
            'latestUpdate.attachmentFile',
        ];
    }
}
