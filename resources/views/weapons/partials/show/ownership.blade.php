@php
    $ownershipType = $ownershipTypes[$weapon->ownership_type] ?? $weapon->ownership_type;
    $ownershipColor = match ($weapon->ownership_type) {
        'propia' => 'bg-green-100 text-green-800 border-green-200',
        'arrendada' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'comodato' => 'bg-purple-100 text-purple-800 border-purple-200',
        default => 'bg-gray-100 text-gray-800 border-gray-200',
    };
@endphp

<section class="sj-weapon-detail-section">
    <h4 class="sj-weapon-detail-section__title">{{ __('Propiedad') }}</h4>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <x-weapon-detail-field :label="__('Tipo de propiedad')">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border {{ $ownershipColor }}">
                {{ $ownershipType }}
            </span>
        </x-weapon-detail-field>

        <x-weapon-detail-field :label="__('Entidad de propiedad')">
            {{ $weapon->ownership_entity ?: '-' }}
        </x-weapon-detail-field>

        <x-weapon-detail-field :label="__('Responsable')">
            {{ $weapon->activeClientAssignment?->responsible?->name ?? '-' }}
        </x-weapon-detail-field>
    </div>
</section>
