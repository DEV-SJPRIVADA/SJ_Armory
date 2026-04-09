<x-app-layout>
    <x-slot name="header">
        <div class="sj-section-header">
            <div class="sj-section-header__main">
                <h2 class="sj-section-header__title">{{ __('Historial por arma') }}</h2>
            </div>

            <div class="sj-section-header__actions">
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $activeIncident = $incidents->first(fn ($item) => in_array($item->status, [
            App\Models\WeaponIncident::STATUS_OPEN,
            App\Models\WeaponIncident::STATUS_IN_PROGRESS,
        ], true));
    @endphp

    <div class="py-8" data-incident-module>
        <div class="sj-page-shell sj-page-shell--wide space-y-6">
            <section class="sj-report-hero">
                <div class="sj-report-hero__copy">
                    <p class="sj-report-hero__eyebrow">{{ __('Consulta puntual') }}</p>
                    <h1 class="sj-report-hero__title">{{ __('Historial consolidado del arma') }}</h1>
                    <p class="sj-report-hero__subtitle">
                        {{ __('Busca el arma por cliente, marca o serie y revisa su trazabilidad operativa sin duplicar información.') }}
                    </p>
                </div>

                <div class="sj-report-hero__stats">
                    <form method="GET" class="w-full space-y-3">
                        <div class="sj-weapon-picker" data-weapon-picker data-search-url="{{ route('reports.weapon-incidents.weapons.search') }}">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="history_weapon_search">{{ __('Arma') }}</label>
                            <input type="hidden" name="weapon_id" value="{{ $weapon?->id }}" data-weapon-picker-value required>
                            <input
                                id="history_weapon_search"
                                type="text"
                                value="{{ $selectedWeapon['summary'] ?? '' }}"
                                class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm"
                                placeholder="{{ __('Buscar por cliente, marca o serie...') }}"
                                autocomplete="off"
                                spellcheck="false"
                                data-weapon-picker-input
                            >
                            <div class="sj-weapon-picker__selected {{ $selectedWeapon ? '' : 'hidden' }}" data-weapon-picker-selected>
                                <div>
                                    <strong data-weapon-picker-selected-summary>{{ $selectedWeapon['summary'] ?? '' }}</strong>
                                    <span data-weapon-picker-selected-meta>
                                        {{ $selectedWeapon ? ($selectedWeapon['client'] . ' / vence ' . $selectedWeapon['permit_expires_label']) : '' }}
                                    </span>
                                </div>
                                <button type="button" class="sj-weapon-picker__clear" data-weapon-picker-clear>{{ __('Cambiar') }}</button>
                            </div>
                            <div class="sj-weapon-picker__menu hidden" data-weapon-picker-menu>
                                <div class="sj-weapon-picker__head">
                                    <span>{{ __('Cliente') }}</span>
                                    <span>{{ __('Marca') }}</span>
                                    <span>{{ __('Serie') }}</span>
                                    <span>{{ __('Vence') }}</span>
                                </div>
                                <div class="sj-weapon-picker__results" data-weapon-picker-results></div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-2">
                            @if ($weapon)
                                <a href="{{ route('reports.history') }}" class="sj-incident-header__button sj-incident-header__button--ghost">
                                    {{ __('Limpiar') }}
                                </a>
                            @endif
                            <button type="submit" class="sj-incident-header__button sj-incident-header__button--accent">
                                {{ __('Ver historial') }}
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            @if ($weapon)
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="sj-report-card">
                        <div class="sj-report-card__eyebrow">{{ __('Arma') }}</div>
                        <h3 class="sj-report-card__title">{{ $weapon->internal_code }}</h3>
                        <p class="sj-report-card__subtitle">{{ $weapon->brand }} / {{ $weapon->weapon_type }} / {{ $weapon->serial_number }}</p>
                    </article>

                    <article class="sj-report-card">
                        <div class="sj-report-card__eyebrow">{{ __('Destino actual') }}</div>
                        <h3 class="sj-report-card__title">{{ $weapon->activeClientAssignment?->client?->name ?? __('Sin destino') }}</h3>
                        <p class="sj-report-card__subtitle">
                            {{ $weapon->activeClientAssignment?->responsible?->name ? __('Responsable: ') . $weapon->activeClientAssignment->responsible->name : __('Sin responsable activo.') }}
                        </p>
                    </article>

                    <article class="sj-report-card">
                        <div class="sj-report-card__eyebrow">{{ __('Permiso') }}</div>
                        <h3 class="sj-report-card__title">{{ $weapon->permit_expires_at?->format('Y-m-d') ?? __('Sin vencimiento') }}</h3>
                        <p class="sj-report-card__subtitle">
                            {{ $weapon->permit_number ? __('Permiso ') . $weapon->permit_number : __('Sin número de permiso registrado.') }}
                        </p>
                    </article>

                    <article class="sj-report-card">
                        <div class="sj-report-card__eyebrow">{{ __('Novedad activa') }}</div>
                        <h3 class="sj-report-card__title">{{ $activeIncident?->type?->name ?? __('Sin novedad activa') }}</h3>
                        <p class="sj-report-card__subtitle">
                            {{ $activeIncident ? (App\Models\WeaponIncident::statusOptions()[$activeIncident->status] ?? $activeIncident->status) : __('Sin bloqueos o seguimientos abiertos.') }}
                        </p>
                    </article>
                </section>

                <section class="sj-panel">
                    <div class="sj-panel__head">
                        <div>
                            <div class="sj-form-section__title">{{ __('Novedades') }}</div>
                            <h3 class="sj-panel__title">{{ __('Trazabilidad de novedades') }}</h3>
                        </div>
                        <span class="sj-report-console__badge">{{ $incidents->count() }} {{ __('registros') }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Fecha') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Tipo') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Modalidad') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Estado') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Resumen') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Nota') }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Adjunto') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($incidents as $incident)
                                    @php
                                        $tone = match ($incident->status) {
                                            App\Models\WeaponIncident::STATUS_OPEN => 'danger',
                                            App\Models\WeaponIncident::STATUS_IN_PROGRESS => 'warning',
                                            App\Models\WeaponIncident::STATUS_RESOLVED => 'ok',
                                            App\Models\WeaponIncident::STATUS_CANCELLED => 'neutral',
                                            default => 'notice',
                                        };
                                        $noteTrail = collect([
                                            $incident->note,
                                            $incident->latestUpdate?->note,
                                            $incident->resolution_note,
                                        ])->filter()->unique()->implode(' · ');
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $incident->event_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $incident->type?->name ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $incident->modality?->name ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            <span class="weapon-incident-status">
                                                <span class="weapon-incident-status__dot weapon-incident-status__dot--{{ $tone }}"></span>
                                                <span>{{ App\Models\WeaponIncident::statusOptions()[$incident->status] ?? $incident->status }}</span>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">{{ $incident->observation ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $noteTrail ?: '-' }}</td>
                                        <td class="px-3 py-2">
                                            @if ($incident->attachmentFile)
                                                <a href="{{ route('weapon-incidents.attachment', $incident) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $incident->attachmentFile->original_name ?? __('Descargar') }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-6 text-center text-gray-500">
                                            {{ __('Sin novedades registradas para esta arma.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-2">
                    <div class="sj-panel">
                        <div class="sj-panel__head">
                            <div>
                                <div class="sj-form-section__title">{{ __('Asignaciones') }}</div>
                                <h3 class="sj-panel__title">{{ __('Historial de asignaciones') }}</h3>
                            </div>
                            <span class="sj-report-console__badge">{{ $assignments->count() }} {{ __('movimientos') }}</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Responsable') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Inicio') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Fin') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse ($assignments as $assignment)
                                        <tr>
                                            <td class="px-3 py-2">{{ $assignment->responsible?->name ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $assignment->client?->name ?? '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $assignment->start_at?->format('Y-m-d') ?? '-' }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $assignment->end_at?->format('Y-m-d') ?? __('Activa') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-3 py-6 text-center text-gray-500">
                                                {{ __('Sin asignaciones registradas.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="sj-panel">
                        <div class="sj-panel__head">
                            <div>
                                <div class="sj-form-section__title">{{ __('Documentos') }}</div>
                                <h3 class="sj-panel__title">{{ __('Soportes asociados') }}</h3>
                            </div>
                            <span class="sj-report-console__badge">{{ $documents->count() }} {{ __('archivos') }}</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Documento') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Vence') }}</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Observaciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse ($documents as $document)
                                        <tr>
                                            <td class="px-3 py-2">
                                                <div>{{ $document->file?->original_name ?? __('Documento') }}</div>
                                                <div class="text-xs text-gray-500">{{ $document->file?->mime_type ?? '-' }}</div>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $document->valid_until?->format('Y-m-d') ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $document->observations ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-3 py-6 text-center text-gray-500">
                                                {{ __('Sin documentos registrados para esta arma.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @else
                <section class="sj-panel">
                    <div class="p-2 text-center text-slate-600">
                        {{ __('Selecciona un arma desde el buscador para revisar su historial operativo, novedades y soportes.') }}
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
