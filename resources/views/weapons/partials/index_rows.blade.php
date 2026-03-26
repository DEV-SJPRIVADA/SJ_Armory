@forelse ($weapons as $weapon)
    @php
        $renewalDocument = $weapon->documents->firstWhere('is_renewal', true) ?? $weapon->documents->firstWhere('is_permit', true);
        $renewalAlert = \App\Support\WeaponDocumentAlert::forComplianceDocument($renewalDocument);
        $manualInProcess = $weapon->documents
            ->filter(fn ($doc) => !($doc->is_permit || $doc->is_renewal))
            ->first(fn ($doc) => ($doc->status ?? '') === 'En proceso');
        $internalAssignment = $weapon->activePostAssignment ?? $weapon->activeWorkerAssignment;
        $imprintChecked = $weapon->imprint_month === now()->format('Y-m');
        $canToggleImprint = auth()->user()?->isAdmin();
        $rowClass = $manualInProcess ? 'bg-red-100' : ($renewalAlert['row_class'] ?? '');
        $statusText = $manualInProcess
            ? trim(($manualInProcess->document_name ?: 'Documento') . ': ' . ($manualInProcess->observations ?: 'En proceso'))
            : ($renewalAlert['observation'] !== '-' ? $renewalAlert['observation'] : ($weapon->activeClientAssignment ? __('Asignada') : __('Sin destino')));
        $statusClass = $manualInProcess ? 'text-red-700' : ($renewalAlert['text_class'] ?? '');
        $destinationLabel = '-';
        if ($weapon->activePostAssignment) {
            $destinationLabel = $weapon->activePostAssignment->post?->name ?? '-';
        } elseif ($weapon->activeWorkerAssignment) {
            $destinationLabel = $weapon->activeWorkerAssignment->worker?->name ?? '-';
        }
    @endphp
    <tr
        class="weapon-row {{ $rowClass }} cursor-pointer transition-colors hover:bg-blue-50/70"
        data-weapon-id="{{ $weapon->id }}"
        data-show-url="{{ route('weapons.show', $weapon) }}"
        data-edit-url="{{ route('weapons.edit', $weapon) }}"
        data-delete-url="{{ route('weapons.destroy', $weapon) }}"
        data-can-edit="{{ auth()->user()?->can('update', $weapon) ? '1' : '0' }}"
        data-can-delete="{{ auth()->user()?->can('delete', $weapon) ? '1' : '0' }}"
    >
        <td class="px-3 py-2 text-center whitespace-nowrap" data-searchable="false">
            <input
                type="checkbox"
                class="weapon-export-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                value="{{ $weapon->id }}"
                aria-label="{{ __('Seleccionar arma :serial', ['serial' => $weapon->serial_number]) }}"
            >
        </td>
        <td class="px-3 py-2 min-w-[200px] whitespace-nowrap">{{ $weapon->activeClientAssignment?->client?->name ?? __('Sin destino') }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->weapon_type }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->brand }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->serial_number }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->caliber }}</td>
        <td class="px-3 py-2 whitespace-nowrap text-center">{{ $weapon->capacity ?? '-' }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->permit_type ? \Illuminate\Support\Str::ucfirst($weapon->permit_type) : '-' }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->permit_number ?? '-' }}</td>
        <td class="px-3 py-2 whitespace-nowrap">{{ $weapon->permit_expires_at?->format('Y-m-d') ?? '-' }}</td>
        <td class="px-3 py-2 whitespace-nowrap">
            <span class="{{ $statusClass }}">{{ $statusText }}</span>
        </td>
        <td class="px-3 py-2 whitespace-nowrap text-center">
            {{ $internalAssignment?->ammo_count ?? '-' }}
        </td>
        <td class="px-3 py-2 whitespace-nowrap text-center">
            {{ $internalAssignment?->provider_count ?? '-' }}
        </td>
        <td class="px-3 py-2 min-w-[200px] whitespace-nowrap">{{ $weapon->activeClientAssignment?->responsible?->name ?? '-' }}</td>
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



