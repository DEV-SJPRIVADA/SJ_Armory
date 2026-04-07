<?php

namespace App\Http\Controllers;

use App\Models\IncidentModality;
use App\Models\IncidentType;
use App\Models\Weapon;
use App\Models\WeaponIncident;
use App\Models\WeaponIncidentUpdate;
use App\Services\WeaponIncidentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class WeaponIncidentController extends Controller
{
    public function __construct(private readonly WeaponIncidentService $service)
    {
    }

    public function store(Request $request)
    {
        $this->authorize('create', WeaponIncident::class);

        $data = $request->validate([
            'weapon_id' => ['required', 'exists:weapons,id'],
            'incident_type_id' => ['required', 'exists:incident_types,id'],
            'incident_modality_id' => ['nullable', 'exists:incident_modalities,id'],
            'status' => ['nullable', Rule::in(array_keys(WeaponIncident::initialStatusOptions()))],
            'observation' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'event_at' => ['required', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'],
            'redirect_to' => ['nullable', 'url'],
        ]);

        $weapon = Weapon::query()->findOrFail($data['weapon_id']);
        $type = IncidentType::query()->findOrFail($data['incident_type_id']);

        if ($type->requires_modality && empty($data['incident_modality_id'])) {
            return back()->withErrors(['incident_modality_id' => 'Seleccione la modalidad de la novedad.'])->withInput();
        }

        if (!empty($data['incident_modality_id'])) {
            $modalityBelongs = IncidentModality::query()
                ->where('incident_type_id', $type->id)
                ->whereKey($data['incident_modality_id'])
                ->exists();

            if (!$modalityBelongs) {
                return back()->withErrors(['incident_modality_id' => 'La modalidad no pertenece al tipo seleccionado.'])->withInput();
            }
        }

        if (empty($data['status'])) {
            $data['status'] = $type->default_status ?: WeaponIncident::STATUS_OPEN;
        }

        try {
            $incident = $this->service->create($weapon, $data, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['incident_type_id' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->to($request->input('redirect_to') ?: route('reports.weapon-incidents.index'))
            ->with('status', 'Novedad registrada para el arma ' . ($incident->weapon->internal_code ?? $incident->weapon->serial_number) . '.');
    }

    public function storeUpdate(Request $request, WeaponIncident $weaponIncident)
    {
        $this->authorize('update', $weaponIncident);

        $data = $request->validate([
            'event_type' => ['required', Rule::in(array_keys(WeaponIncidentUpdate::manualEventTypeOptions()))],
            'status' => ['nullable', Rule::in(array_keys(WeaponIncident::initialStatusOptions()))],
            'note' => ['nullable', 'string'],
            'happened_at' => ['required', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'],
            'redirect_to' => ['nullable', 'url'],
        ]);

        try {
            $this->service->addUpdate($weaponIncident, $data, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['incident_update' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->to($request->input('redirect_to') ?: route('reports.weapon-incidents.index'))
            ->with('status', 'Seguimiento registrado para la novedad.');
    }

    public function close(Request $request, WeaponIncident $weaponIncident)
    {
        $this->authorize('close', $weaponIncident);

        $data = $request->validate([
            'status' => ['required', Rule::in([WeaponIncident::STATUS_RESOLVED, WeaponIncident::STATUS_CANCELLED])],
            'closure_outcome' => ['nullable', 'string', 'max:100'],
            'resolution_note' => ['nullable', 'string'],
            'redirect_to' => ['nullable', 'url'],
        ]);

        if ($data['status'] === WeaponIncident::STATUS_RESOLVED && empty($data['closure_outcome'])) {
            return back()->withErrors(['closure_outcome' => 'Seleccione el resultado del cierre para definir el impacto operativo.'])->withInput();
        }

        try {
            $this->service->close($weaponIncident, $data, $request->user());
        } catch (InvalidArgumentException $e) {
            $field = str_contains(mb_strtolower($e->getMessage()), 'resultado') ? 'closure_outcome' : 'resolution_note';

            return back()->withErrors([$field => $e->getMessage()])->withInput();
        }

        return redirect()
            ->to($request->input('redirect_to') ?: route('reports.weapon-incidents.index'))
            ->with('status', 'Novedad actualizada.');
    }

    public function reopen(Request $request, WeaponIncident $weaponIncident)
    {
        $this->authorize('update', $weaponIncident);

        $data = $request->validate([
            'status' => ['nullable', Rule::in([WeaponIncident::STATUS_OPEN, WeaponIncident::STATUS_IN_PROGRESS])],
            'message' => ['nullable', 'string'],
            'follow_up_at' => ['nullable', 'date'],
            'redirect_to' => ['nullable', 'url'],
        ]);

        try {
            $this->service->reopen($weaponIncident, $data, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->to($request->input('redirect_to') ?: route('reports.weapon-incidents.index'))
            ->with('status', 'Novedad reabierta para seguimiento.');
    }

    public function downloadAttachment(WeaponIncident $weaponIncident)
    {
        $this->authorize('downloadAttachment', $weaponIncident);

        $file = $weaponIncident->attachmentFile;

        if (!$file || !Storage::disk($file->disk)->exists($file->path)) {
            abort(404);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function downloadUpdateAttachment(WeaponIncident $weaponIncident, WeaponIncidentUpdate $weaponIncidentUpdate)
    {
        $this->authorize('downloadUpdateAttachment', $weaponIncident);

        if ($weaponIncidentUpdate->weapon_incident_id !== $weaponIncident->id) {
            abort(404);
        }

        $file = $weaponIncidentUpdate->attachmentFile;

        if (!$file || !Storage::disk($file->disk)->exists($file->path)) {
            abort(404);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
