@props(['targetBody'])

@php
    $alertTableColumns = [
        ['key' => 'cliente', 'label' => __('Cliente')],
        ['key' => 'tipo', 'label' => __('Tipo')],
        ['key' => 'serie', 'label' => __('Serie')],
        ['key' => 'vence', 'label' => __('Vence')],
        ['key' => 'estado', 'label' => __('Estado')],
        ['key' => 'observacion', 'label' => __('Observación')],
    ];
@endphp

<thead class="alerts-table-head">
    <tr>
        @foreach ($alertTableColumns as $column)
            <th class="alerts-col-filter-th" scope="col">
                <div class="alerts-col-filter">
                    <span class="alerts-col-filter__label">{{ $column['label'] }}</span>
                    <button
                        type="button"
                        class="alerts-col-filter__trigger"
                        data-col-filter-trigger
                        data-col-filter="{{ $column['key'] }}"
                        data-target-body="{{ $targetBody }}"
                        aria-expanded="false"
                        aria-haspopup="dialog"
                        aria-controls="alerts-column-filter-popover"
                        aria-label="{{ __('Filtrar :column', ['column' => $column['label']]) }}"
                    >
                        <svg class="alerts-col-filter__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.24 4.5a.75.75 0 01-1.08 0l-4.24-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </th>
        @endforeach
    </tr>
</thead>
