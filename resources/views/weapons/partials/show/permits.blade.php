@php
    $expiryDate = $weapon->permit_expires_at?->format('Y-m-d');
    $isExpired = $weapon->permit_expires_at && $weapon->permit_expires_at->isPast();
@endphp

<section class="sj-weapon-detail-section">
    <h4 class="sj-weapon-detail-section__title">{{ __('Permisos') }}</h4>
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-weapon-detail-field :label="__('Tipo de permiso')">
            @if ($weapon->permit_type === 'porte')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                    {{ __('Porte') }}
                </span>
            @elseif ($weapon->permit_type === 'tenencia')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                    {{ __('Tenencia') }}
                </span>
            @else
                <span class="text-gray-500">-</span>
            @endif
        </x-weapon-detail-field>

        <x-weapon-detail-field :label="__('Número de permiso')">
            {{ $weapon->permit_number ?: '-' }}
        </x-weapon-detail-field>

        <x-weapon-detail-field :label="__('Fecha de vencimiento')">
            {{ $expiryDate ?: '-' }}
        </x-weapon-detail-field>

        <x-weapon-detail-field :label="__('Estado')">
            @if ($expiryDate)
                @if ($isExpired)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                        {{ __('Vencido') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                        {{ __('Vigente') }}
                    </span>
                @endif
            @else
                <span class="text-gray-500">-</span>
            @endif
        </x-weapon-detail-field>
    </div>
</section>
