@php
    $focusedIncidentId = (string) old('focus_incident_id', request('focus_incident'));
    $isFocusedIncident = $focusedIncidentId === (string) $incident->id;
    $isOpenIncident = $incident->isOpen();
    $statusTone = match ($incident->status) {
        App\Models\WeaponIncident::STATUS_OPEN => 'danger',
        App\Models\WeaponIncident::STATUS_IN_PROGRESS => 'warning',
        App\Models\WeaponIncident::STATUS_RESOLVED => 'ok',
        App\Models\WeaponIncident::STATUS_CANCELLED => 'neutral',
        default => 'notice',
    };
    $operationalLabel = $incident->blocksOperationalAvailability()
        ? __('Fuera de operación')
        : ($incident->type?->persists_operational_block ? __('Bloqueo persistente') : __('Operativa'));
    $operationalTone = $incident->blocksOperationalAvailability()
        ? 'danger'
        : ($incident->type?->persists_operational_block ? 'neutral' : 'ok');
    $defaultUpdateTime = $isFocusedIncident
        ? old('happened_at', now()->format('Y-m-d\TH:i'))
        : now()->format('Y-m-d\TH:i');
    $defaultReopenTime = $isFocusedIncident
        ? old('follow_up_at', now()->format('Y-m-d\TH:i'))
        : now()->format('Y-m-d\TH:i');
    $closureOutcomeOptions = App\Models\WeaponIncident::closureOutcomeOptionsForType($incident->type);
    $selectedClosureStatus = $isFocusedIncident
        ? old('status', App\Models\WeaponIncident::STATUS_RESOLVED)
        : App\Models\WeaponIncident::STATUS_RESOLVED;
    $selectedClosureOutcome = $isFocusedIncident
        ? old('closure_outcome', $incident->closure_outcome)
        : $incident->closure_outcome;
@endphp

<div
    id="incident-case-{{ $incident->id }}"
    class="sj-incident-modal hidden"
    aria-hidden="true"
    data-modal-key="incident-case-{{ $incident->id }}"
>
    <button type="button" class="sj-incident-modal__backdrop" data-close-modal aria-label="{{ __('Cerrar') }}"></button>

    <div class="sj-incident-modal__wrap">
        <section class="sj-incident-modal__panel" role="dialog" aria-modal="true" aria-labelledby="incident-case-title-{{ $incident->id }}">
            <div class="sj-incident-modal__header">
                <div>
                    <p class="sj-incident-modal__eyebrow">{{ __('Expediente') }}</p>
                    <h3 id="incident-case-title-{{ $incident->id }}" class="sj-incident-modal__title">
                        {{ $incident->type?->name ?? __('Novedad') }} / {{ $incident->weapon?->internal_code ?? '-' }}
                    </h3>
                    <p class="sj-incident-modal__subtitle">
                        {{ $incident->weapon?->serial_number ?? '-' }} · {{ $incident->weapon?->activeClientAssignment?->client?->name ?? __('Sin destino') }}
                    </p>
                </div>

                <button type="button" class="sj-incident-modal__close" data-close-modal>
                    {{ __('Cerrar') }}
                </button>
            </div>

            <div class="sj-incident-modal__body space-y-5">
                @if ($isFocusedIncident && $errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <div class="font-semibold">{{ __('No fue posible actualizar el expediente.') }}</div>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="sj-case-sheet">
                    <div class="sj-case-sheet__summary">
                        <article class="sj-case-pill sj-case-pill--{{ $statusTone }}">
                            <span class="sj-case-pill__label">{{ __('Estado actual') }}</span>
                            <strong class="sj-case-pill__value">{{ $statusOptions[$incident->status] ?? $incident->status }}</strong>
                        </article>
                        <article class="sj-case-pill sj-case-pill--{{ $operationalTone }}">
                            <span class="sj-case-pill__label">{{ __('Impacto operativo') }}</span>
                            <strong class="sj-case-pill__value">{{ $operationalLabel }}</strong>
                        </article>
                        <article class="sj-case-pill sj-case-pill--notice">
                            <span class="sj-case-pill__label">{{ __('Último movimiento') }}</span>
                            <strong class="sj-case-pill__value">{{ $incident->latestUpdate?->eventTypeLabel() ?? __('Reporte inicial') }}</strong>
                            <span class="sj-case-pill__meta">
                                {{ $incident->latestActivityAt()?->format('Y-m-d H:i') ?? '-' }}
                            </span>
                        </article>
                        <article class="sj-case-pill sj-case-pill--neutral">
                            <span class="sj-case-pill__label">{{ __('Observación base') }}</span>
                            <strong class="sj-case-pill__value">{{ $incident->observation ?: __('Sin resumen') }}</strong>
                            <span class="sj-case-pill__meta">{{ $incident->modality?->name ?? __('Sin modalidad') }}</span>
                        </article>
                        @if ($incident->closureOutcomeLabel())
                            <article class="sj-case-pill sj-case-pill--neutral">
                                <span class="sj-case-pill__label">{{ __('Resultado de cierre') }}</span>
                                <strong class="sj-case-pill__value">{{ $incident->closureOutcomeLabel() }}</strong>
                                <span class="sj-case-pill__meta">{{ $incident->closureImpactLabel() }}</span>
                            </article>
                        @endif
                    </div>

                    <div class="sj-case-sheet__layout">
                        <section class="sj-case-card">
                            <div class="sj-case-card__head">
                                <div>
                                    <div class="sj-form-section__title">{{ __('Trazabilidad') }}</div>
                                    <h4 class="sj-case-card__title">{{ __('Actividad del expediente') }}</h4>
                                </div>
                            </div>

                            <div class="sj-case-timeline">
                                @foreach ($incident->updates as $update)
                                    @php
                                        $eventTone = match ($update->event_type) {
                                            App\Models\WeaponIncidentUpdate::EVENT_REPORTED => 'notice',
                                            App\Models\WeaponIncidentUpdate::EVENT_RECOVERY => 'warning',
                                            App\Models\WeaponIncidentUpdate::EVENT_REINTEGRATION => 'ok',
                                            App\Models\WeaponIncidentUpdate::EVENT_CLOSURE => 'neutral',
                                            App\Models\WeaponIncidentUpdate::EVENT_REOPEN => 'danger',
                                            default => 'slate',
                                        };
                                    @endphp
                                    <article class="sj-case-timeline__item">
                                        <div class="sj-case-timeline__rail">
                                            <span class="sj-case-timeline__dot sj-case-timeline__dot--{{ $eventTone }}"></span>
                                        </div>
                                        <div class="sj-case-timeline__body">
                                            <div class="sj-case-timeline__top">
                                                <div>
                                                    <strong class="sj-case-timeline__title">{{ $update->eventTypeLabel() }}</strong>
                                                    @if ($update->status_to && $update->status_to !== $update->status_from)
                                                        <div class="sj-case-timeline__meta">
                                                            {{ $statusOptions[$update->status_from] ?? __('Sin estado') }}
                                                            <span>&rarr;</span>
                                                            {{ $statusOptions[$update->status_to] ?? $update->status_to }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <span class="sj-case-timeline__date">{{ $update->happened_at?->format('Y-m-d H:i') ?? '-' }}</span>
                                            </div>
                                            <p class="sj-case-timeline__note">{{ $update->note ?: __('Sin nota adicional.') }}</p>
                                            <div class="sj-case-timeline__meta">
                                                <span>{{ $update->creator?->name ?? __('Sistema') }}</span>
                                                @if ($update->attachmentFile)
                                                    <a href="{{ route('weapon-incidents.updates.attachment', [$incident, $update]) }}" class="text-indigo-600 hover:text-indigo-900">
                                                        {{ __('Descargar adjunto') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>

                        <section class="sj-case-card">
                            <div class="sj-case-card__head">
                                <div>
                                    <div class="sj-form-section__title">{{ __('Gestión') }}</div>
                                    <h4 class="sj-case-card__title">{{ __('Acciones del caso') }}</h4>
                                </div>
                            </div>

                            <div class="space-y-4">
                                @if ($incident->type?->blocks_operation)
                                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                        @if ($incident->type?->persists_operational_block)
                                            {{ __('Esta novedad mantiene el arma fuera de operación incluso al cerrar el expediente, salvo que se cancele el caso.') }}
                                        @else
                                            {{ __('Mientras el caso siga abierto o en proceso, el arma permanece fuera de operación. Si el arma fue recuperada, registra el seguimiento y luego cierra como resuelta.') }}
                                        @endif
                                    </div>
                                @endif

                                @can('update', $incident)
                                    @if ($isOpenIncident)
                                        <form
                                            method="POST"
                                            action="{{ route('weapon-incidents.updates.store', $incident) }}"
                                            enctype="multipart/form-data"
                                            class="sj-case-form"
                                        >
                                            @csrf
                                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                            <input type="hidden" name="focus_incident_id" value="{{ $incident->id }}">

                                            <div class="sj-form-grid sj-form-grid--two">
                                                <div>
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_update_type_{{ $incident->id }}">{{ __('Tipo de seguimiento') }}</label>
                                                    <select id="incident_update_type_{{ $incident->id }}" name="event_type" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm" required>
                                                        <option value="">{{ __('Seleccione') }}</option>
                                                        @foreach (App\Models\WeaponIncidentUpdate::manualEventTypeOptions() as $value => $label)
                                                            <option value="{{ $value }}" @selected($isFocusedIncident && old('event_type') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_update_happened_at_{{ $incident->id }}">{{ __('Fecha y hora') }}</label>
                                                    <input id="incident_update_happened_at_{{ $incident->id }}" name="happened_at" type="datetime-local" value="{{ $defaultUpdateTime }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm" required>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_update_status_{{ $incident->id }}">{{ __('Estado posterior') }}</label>
                                                    <select id="incident_update_status_{{ $incident->id }}" name="status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm">
                                                        <option value="">{{ __('Conservar estado actual') }}</option>
                                                        @foreach (App\Models\WeaponIncident::initialStatusOptions() as $value => $label)
                                                            <option value="{{ $value }}" @selected($isFocusedIncident && old('status') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_update_attachment_{{ $incident->id }}">{{ __('Adjunto') }}</label>
                                                    <input id="incident_update_attachment_{{ $incident->id }}" name="attachment" type="file" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm" accept=".pdf,.doc,.docx,image/jpeg,image/png,image/webp">
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_update_note_{{ $incident->id }}">{{ __('Nota de seguimiento') }}</label>
                                                <textarea id="incident_update_note_{{ $incident->id }}" name="note" rows="4" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm">{{ $isFocusedIncident ? old('note') : '' }}</textarea>
                                            </div>

                                            <div class="sj-case-form__footer">
                                                <span class="text-xs text-slate-500">{{ __('Usa recuperación cuando el arma aparezca. Si vuelve a control, cierra luego el expediente como resuelto.') }}</span>
                                                <button type="submit" class="sj-incident-header__button sj-incident-header__button--primary">
                                                    {{ __('Guardar seguimiento') }}
                                                </button>
                                            </div>
                                        </form>

                                        @can('close', $incident)
                                            <form method="POST" action="{{ route('weapon-incidents.close', $incident) }}" class="sj-case-form sj-case-form--secondary">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                                <input type="hidden" name="focus_incident_id" value="{{ $incident->id }}">

                                                <div class="sj-form-grid sj-form-grid--two">
                                                    <div>
                                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_close_status_{{ $incident->id }}">{{ __('Estado final') }}</label>
                                                        <select
                                                            id="incident_close_status_{{ $incident->id }}"
                                                            name="status"
                                                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm"
                                                            data-close-status-select
                                                            data-target-outcome="incident_close_outcome_wrap_{{ $incident->id }}"
                                                            required
                                                        >
                                                            <option value="{{ App\Models\WeaponIncident::STATUS_RESOLVED }}" @selected($selectedClosureStatus === App\Models\WeaponIncident::STATUS_RESOLVED)>{{ __('Cerrado') }}</option>
                                                            <option value="{{ App\Models\WeaponIncident::STATUS_CANCELLED }}" @selected($selectedClosureStatus === App\Models\WeaponIncident::STATUS_CANCELLED)>{{ __('Cancelado') }}</option>
                                                        </select>
                                                    </div>

                                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                                        {{ __('Cancelado solo debe usarse para reportes errados, duplicados o anulados. Si el caso fue real, ciérralo como cerrado y define el resultado del cierre.') }}
                                                    </div>
                                                </div>

                                                <div
                                                    id="incident_close_outcome_wrap_{{ $incident->id }}"
                                                    data-close-outcome-wrap
                                                    class="{{ $selectedClosureStatus === App\Models\WeaponIncident::STATUS_CANCELLED ? 'hidden ' : '' }}mt-4"
                                                >
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_close_outcome_{{ $incident->id }}">{{ __('Resultado del cierre') }}</label>
                                                    <select
                                                        id="incident_close_outcome_{{ $incident->id }}"
                                                        name="closure_outcome"
                                                        class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm"
                                                        data-close-outcome-select
                                                        {{ $selectedClosureStatus !== App\Models\WeaponIncident::STATUS_CANCELLED ? 'required' : '' }}
                                                    >
                                                        <option value="">{{ __('Seleccione') }}</option>
                                                        @foreach ($closureOutcomeOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected($selectedClosureOutcome === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <p class="mt-2 text-xs text-slate-500">
                                                        {{ __('Este resultado define si el arma vuelve a operación o permanece fuera de servicio después del cierre.') }}
                                                    </p>
                                                </div>

                                                <div class="mt-4">
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_resolution_note_{{ $incident->id }}">{{ __('Nota de cierre') }}</label>
                                                    <textarea id="incident_resolution_note_{{ $incident->id }}" name="resolution_note" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm">{{ $isFocusedIncident ? old('resolution_note') : '' }}</textarea>
                                                </div>

                                                <div class="sj-case-form__footer">
                                                    <span class="text-xs text-slate-500">{{ __('Si el arma ya fue localizada y quedó bajo control, primero registra la recuperación y después cierra el expediente.') }}</span>
                                                    <button type="submit" class="sj-incident-header__button sj-incident-header__button--accent">
                                                        {{ __('Cerrar expediente') }}
                                                    </button>
                                                </div>
                                            </form>
                                        @endcan
                                    @else
                                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                            <div class="font-semibold">{{ __('Expediente cerrado') }}</div>
                                            <div class="mt-1">
                                                {{ $incident->resolution_note ?: __('No se dejó nota final de cierre.') }}
                                            </div>
                                            @if ($incident->closureOutcomeLabel())
                                                <div class="mt-2 font-semibold">{{ $incident->closureOutcomeLabel() }}</div>
                                                <div class="mt-1">{{ $incident->closureImpactLabel() }}</div>
                                            @endif
                                        </div>

                                        <form method="POST" action="{{ route('weapon-incidents.reopen', $incident) }}" class="sj-case-form">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                            <input type="hidden" name="focus_incident_id" value="{{ $incident->id }}">

                                            <div class="sj-form-grid sj-form-grid--two">
                                                <div>
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_reopen_status_{{ $incident->id }}">{{ __('Reabrir en') }}</label>
                                                    <select id="incident_reopen_status_{{ $incident->id }}" name="status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm" required>
                                                        @foreach (App\Models\WeaponIncident::initialStatusOptions() as $value => $label)
                                                            <option value="{{ $value }}" @selected(!$isFocusedIncident ? $value === App\Models\WeaponIncident::STATUS_OPEN : old('status', App\Models\WeaponIncident::STATUS_OPEN) === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_reopen_follow_up_at_{{ $incident->id }}">{{ __('Fecha y hora') }}</label>
                                                    <input id="incident_reopen_follow_up_at_{{ $incident->id }}" name="follow_up_at" type="datetime-local" value="{{ $defaultReopenTime }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm">
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="incident_reopen_message_{{ $incident->id }}">{{ __('Motivo de reapertura') }}</label>
                                                <textarea id="incident_reopen_message_{{ $incident->id }}" name="message" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm">{{ $isFocusedIncident ? old('message') : '' }}</textarea>
                                            </div>

                                            <div class="sj-case-form__footer">
                                                <span class="text-xs text-slate-500">{{ __('Reabre el caso si necesitas registrar nuevos hitos, soportes o una validación posterior.') }}</span>
                                                <button type="submit" class="sj-incident-header__button sj-incident-header__button--primary">
                                                    {{ __('Reabrir expediente') }}
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                @else
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                        {{ __('Este usuario solo tiene acceso de consulta sobre el expediente.') }}
                                    </div>
                                @endcan
                            </div>
                        </section>
                    </div>
                </section>
            </div>
        </section>
    </div>
</div>

