@forelse ($weapons as $weapon)
    @php
        $listStatus = \App\Support\WeaponListStatusResolver::for($weapon);
        $statusText = $listStatus['text'];
        $statusClass = $listStatus['text_class'];
        $incidentTone = $listStatus['tone'];
        $rowClass = $listStatus['row_class'];
        $internalAssignment = $weapon->activeWorkerAssignment ?? $weapon->activePostAssignment;
        $imprintChecked = $weapon->imprint_month === now()->format('Y-m');
        $canToggleImprint = auth()->user()?->isAdmin();
        $destinationLabel = '-';
        if ($weapon->activeWorkerAssignment) {
            $destinationLabel = $weapon->activeWorkerAssignment->worker?->name ?? '-';
        } elseif ($weapon->activePostAssignment) {
            $destinationLabel = $weapon->activePostAssignment->post?->name ?? '-';
        }
    @endphp
    <tr
        class="weapon-row {{ $rowClass }} cursor-pointer transition-colors hover:bg-blue-50/70"
        data-weapon-id="{{ $weapon->id }}"
        data-show-url="{{ route('weapons.show', $weapon) }}"
        data-edit-url="{{ route('weapons.edit', $weapon) }}"
        data-can-edit="{{ auth()->user()?->can('update', $weapon) ? '1' : '0' }}"
        data-export-client="{{ $weapon->operationalDisplayClient()?->name ?? __('Sin destino') }}"
        data-export-type="{{ $weapon->weapon_type }}"
        data-export-brand="{{ $weapon->brand }}"
        data-export-serial="{{ $weapon->serial_number }}"
        data-export-caliber="{{ $weapon->caliber }}"
        data-export-permit-type="{{ $weapon->permit_type ? \Illuminate\Support\Str::ucfirst($weapon->permit_type) : '-' }}"
        data-export-permit-number="{{ $weapon->permit_number ?? '-' }}"
        data-export-expires-at="{{ $weapon->permit_expires_at?->format('Y-m-d') ?? '-' }}"
        data-col-cliente="{{ $weapon->operationalDisplayClient()?->name ?? __('Sin destino') }}"
        data-col-tipo="{{ $weapon->weapon_type }}"
        data-col-marca="{{ $weapon->brand }}"
        data-col-serie="{{ $weapon->serial_number }}"
        data-col-calibre="{{ $weapon->caliber }}"
        data-col-capacidad="{{ $weapon->capacity ?? '-' }}"
        data-col-tipo_permiso="{{ $weapon->permit_type ? \Illuminate\Support\Str::ucfirst($weapon->permit_type) : '-' }}"
        data-col-numero_permiso="{{ $weapon->permit_number ?? '-' }}"
        data-col-vence="{{ $weapon->permit_expires_at?->format('Y-m-d') ?? '-' }}"
        data-col-estado="{{ $statusText }}"
        data-col-municion="{{ $internalAssignment?->ammo_count ?? '-' }}"
        data-col-proveedor="{{ $internalAssignment?->provider_count ?? '-' }}"
        data-col-responsable="{{ $weapon->operationalDisplayResponsible()?->name ?? '-' }}"
        data-col-destino="{{ $destinationLabel }}"
        data-col-cedula="{{ $weapon->activeWorkerAssignment?->worker?->document ?? '-' }}"
    >
        <td class="px-3 py-2 text-center whitespace-nowrap" data-searchable="false">
            <input
                type="checkbox"
                class="weapon-export-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                value="{{ $weapon->id }}"
                aria-label="{{ __('Seleccionar arma :serial', ['serial' => $weapon->serial_number]) }}"
            >
        </td>
        <td class="px-3 py-2 min-w-[200px] whitespace-nowrap">{{ $weapon->operationalDisplayClient()?->name ?? __('Sin destino') }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->weapon_type }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->brand }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->serial_number }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->caliber }}</td>
        <td class="px-3 py-2 whitespace-nowrap text-center">{{ $weapon->capacity ?? '-' }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->permit_type ? \Illuminate\Support\Str::ucfirst($weapon->permit_type) : '-' }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->permit_number ?? '-' }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->permit_expires_at?->format('Y-m-d') ?? '-' }}</td>
        <td class="px-3 py-2 whitespace-nowrap">
            <span class="weapon-incident-status">
                <span class="weapon-incident-status__dot weapon-incident-status__dot--{{ $incidentTone }}" title="{{ $statusText }}"></span>
                <span class="{{ $statusClass }}">{{ $statusText }}</span>
            </span>
        </td>
        <td class="px-3 py-2 whitespace-nowrap text-center">
            {{ $internalAssignment?->ammo_count ?? '-' }}
        </td>
        <td class="px-3 py-2 whitespace-nowrap text-center">
            {{ $internalAssignment?->provider_count ?? '-' }}
        </td>
        <td class="px-3 py-2 min-w-[200px] whitespace-nowrap">{{ $weapon->operationalDisplayResponsible()?->name ?? '-' }}</td>
        <td class="px-3 py-2 min-w-[220px] whitespace-nowrap">{{ $destinationLabel }}</td>
        <td class="px-3 py-2 whitespace-nowrap">
            {{ $weapon->activeWorkerAssignment?->worker?->document ?? '-' }}
        </td>
        <td class="px-3 py-2 text-center whitespace-nowrap" data-searchable="false">
            <form method="POST" action="{{ route('weapons.imprints.toggle', $weapon) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="received" value="0">
                <input type="checkbox" name="received" value="1" class="imprint-checkbox"
                    @checked($imprintChecked) @disabled(!$canToggleImprint)>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="16" class="px-3 py-6 text-center text-gray-500">
            {{ __('No hay armas registradas.') }}
        </td>
    </tr>
@endforelse
