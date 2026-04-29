@forelse ($workers as $worker)
    <tr>
        <td class="px-3 py-2">{{ $worker->name }}</td>
        <td class="px-3 py-2">{{ $worker->document ?? '-' }}</td>
        <td class="px-3 py-2">{{ $roles[$worker->role] ?? $worker->role }}</td>
        <td class="px-3 py-2">{{ $worker->client?->name }}</td>
        <td class="px-3 py-2">{{ $worker->responsible?->name }}</td>
        <td class="px-3 py-2">
            @if ($worker->isArchived())
                <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ __('Archivado') }}</span>
            @else
                <span class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-800">{{ __('Activo') }}</span>
            @endif
        </td>
        <td class="px-3 py-2 text-right space-x-2">
            @can('view', $worker)
                <button
                    type="button"
                    class="text-gray-700 hover:text-gray-900"
                    @click="openHistory(@js($worker->name), '{{ route('workers.histories', $worker) }}')"
                >
                    {{ __('Historial') }}
                </button>
            @endcan
            @can('update', $worker)
                <a href="{{ route('workers.edit', $worker) }}" class="text-indigo-600 hover:text-indigo-900">
                    {{ __('Editar') }}
                </a>
            @endcan
            @can('delete', $worker)
                <form action="{{ route('workers.destroy', $worker) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-amber-700 hover:text-amber-900" onclick="return confirm(@js(__('¿Archivar este trabajador? Las armas asignadas aquí quedarán sin ubicación interna activa.')))">
                        {{ __('Archivar') }}
                    </button>
                </form>
            @endcan
            @can('restore', $worker)
                <form action="{{ route('workers.restore', $worker) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-green-700 hover:text-green-900" onclick="return confirm(@js(__('¿Reactivar este trabajador?')))">
                        {{ __('Reactivar') }}
                    </button>
                </form>
            @endcan
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-3 py-6 text-center text-gray-500">
            {{ __('No hay trabajadores registrados.') }}
        </td>
    </tr>
@endforelse
