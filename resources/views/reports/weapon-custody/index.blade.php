<x-app-layout>
    <x-slot name="header">
        <div class="sj-incident-header">
            <div class="sj-incident-header__main">
                <p class="sj-incident-header__eyebrow">{{ __('Centro de reportes') }}</p>
                <h2 class="sj-incident-header__title">{{ __('Custodia y taller') }}</h2>
                <p class="sj-incident-header__subtitle">
                    {{ __('Armas ubicadas en armerillo, pendientes de mantenimiento o en armero. No son novedades operativas.') }}
                </p>
            </div>
            <div class="sj-incident-header__side">
                <a href="{{ route('reports.index') }}" class="sj-incident-header__button sj-incident-header__button--ghost">
                    {{ __('Volver a reportes') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="sj-page-shell sj-page-shell--wide space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ($roleLabels as $role => $label)
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</div>
                        <div class="mt-2 text-3xl font-bold text-slate-900 tabular-nums">{{ $counts[$role] ?? 0 }}</div>
                    </div>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="sj-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Estado') }}</th>
                            <th>{{ __('Puesto') }}</th>
                            <th>{{ __('Serie') }}</th>
                            <th>{{ __('Cliente') }}</th>
                            <th>{{ __('Responsable') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-3 py-2">{{ $row['custody_label'] }}</td>
                                <td class="px-3 py-2">{{ $row['post_name'] }}</td>
                                <td class="px-3 py-2 font-medium">{{ $row['weapon']->serial_number ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $row['client_name'] }}</td>
                                <td class="px-3 py-2">{{ $row['responsible_name'] }}</td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('weapons.show', $row['weapon']) }}" class="font-semibold text-[#0b6fb6]">{{ __('Ver arma') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                                    {{ __('No hay armas en puestos de custodia o taller en su alcance.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
