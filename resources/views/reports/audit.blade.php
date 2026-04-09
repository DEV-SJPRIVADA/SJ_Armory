<x-app-layout>
    <x-slot name="header">
        <div class="sj-section-header">
            <div class="sj-section-header__main">
                <h2 class="sj-section-header__title">
                    {{ __('Auditoría reciente') }}
                </h2>
            </div>
            <div class="sj-section-header__actions">
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <label class="text-sm text-gray-600">{{ __('Rango') }}</label>
                    <select name="days" class="rounded-md border-gray-300 text-sm">
                        <option value="30" @selected($days === 30)>{{ __('Últimos 30 días') }}</option>
                        <option value="90" @selected($days === 90)>{{ __('Últimos 90 días') }}</option>
                    </select>
                    <label class="text-sm text-gray-600">{{ __('Módulo') }}</label>
                    <select name="module" class="rounded-md border-gray-300 text-sm">
                        @foreach ($modules as $value => $label)
                            <option value="{{ $value }}" @selected($module === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="text-xs text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1 rounded">
                        {{ __('Filtrar') }}
                    </button>
                </form>
                <a href="{{ route('reports.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="sj-page-shell sj-page-shell--wide">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Fecha') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Usuario') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Acción') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Entidad') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="px-3 py-2">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-3 py-2">{{ $log->user?->name ?? '-' }}</td>
                                    <td class="px-3 py-2">
                                        {{ $actionLabels[$log->action] ?? ucfirst(str_replace('_', ' ', $log->action)) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        @php
                                            $entityLabel = $entityLabels[$log->auditable_type] ?? class_basename($log->auditable_type);
                                            $entityName = null;
                                            if ($log->auditable) {
                                                if ($log->auditable instanceof \App\Models\User) {
                                                    $entityName = $log->auditable->name;
                                                } elseif ($log->auditable instanceof \App\Models\Client) {
                                                    $entityName = $log->auditable->name;
                                                } elseif ($log->auditable instanceof \App\Models\Weapon) {
                                                    $entityName = $log->auditable->internal_code ?? ('#' . $log->auditable->id);
                                                } elseif ($log->auditable instanceof \App\Models\WeaponClientAssignment) {
                                                    $entityName = $log->auditable->client?->name;
                                                } elseif ($log->auditable instanceof \App\Models\WeaponTransfer) {
                                                    $transferWeapon = $log->auditable->loadMissing('weapon', 'fromUser', 'toUser');
                                                    $weaponCode = $transferWeapon->weapon?->internal_code ?? ('#' . $transferWeapon->weapon_id);
                                                    $fromName = $transferWeapon->fromUser?->name;
                                                    $toName = $transferWeapon->toUser?->name;
                                                    $transferDate = $log->auditable->requested_at?->format('Y-m-d H:i');
                                                    $entityName = __('Arma') . ' ' . $weaponCode;
                                                    if ($fromName || $toName) {
                                                        $entityName .= ' (' . ($fromName ?? '-') . ' -> ' . ($toName ?? '-') . ')';
                                                    }
                                                    if ($transferDate) {
                                                        $entityName .= ' - ' . $transferDate;
                                                    }
                                                } elseif ($log->auditable instanceof \App\Models\Post) {
                                                    $entityName = $log->auditable->name;
                                                } elseif ($log->auditable instanceof \App\Models\Worker) {
                                                    $entityName = $log->auditable->name;
                                                }
                                            }
                                        @endphp
                                        {{ $entityLabel }}
                                        @if ($entityName)
                                            - {{ $entityName }}
                                        @else
                                            #{{ $log->auditable_id }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-gray-500">
                                        {{ __('Sin auditoría en el rango.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
