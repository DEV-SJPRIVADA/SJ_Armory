<section class="sj-weapon-detail-section">
    <h4 class="sj-weapon-detail-section__title">{{ __('Características') }}</h4>
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-weapon-detail-field :label="__('Tipo')">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                {{ $weapon->weapon_type }}
            </span>
        </x-weapon-detail-field>

        <x-weapon-detail-field :label="__('Calibre')">
            {{ $weapon->caliber }}
        </x-weapon-detail-field>

        <x-weapon-detail-field :label="__('Marca')">
            {{ $weapon->brand }}
        </x-weapon-detail-field>

        <x-weapon-detail-field :label="__('Número de serie')">
            {{ $weapon->serial_number }}
        </x-weapon-detail-field>
    </div>
</section>
