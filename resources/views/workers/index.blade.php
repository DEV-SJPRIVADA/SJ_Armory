<x-app-layout>
    <x-slot name="header">
        <div class="sj-section-header">
            <div class="sj-section-header__main">
                <h2 class="sj-section-header__title">{{ __('Trabajadores') }}</h2>
            </div>

            <div class="sj-section-header__actions">
                @can('create', App\Models\Worker::class)
                    <a href="{{ route('workers.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                        {{ __('Nuevo trabajador') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="sj-page-shell sj-page-shell--wide">
            @if (session('status'))
                <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('workers.index') }}" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                        <div>
                            <label class="text-sm text-gray-600">{{ __('Buscar') }}</label>
                            <input type="text" name="q" value="{{ $search }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm" placeholder="{{ __('Nombre') }}">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">{{ __('Cliente') }}</label>
                            <select name="client_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">{{ __('Todos') }}</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected($clientId == $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">{{ __('Rol') }}</label>
                            <select name="role" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">{{ __('Todos') }}</option>
                                @foreach ($roles as $value => $label)
                                    <option value="{{ $value }}" @selected($role == $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">{{ __('Responsable') }}</label>
                            <select name="responsible_user_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">{{ __('Todos') }}</option>
                                @foreach ($responsibles as $responsible)
                                    <option value="{{ $responsible->id }}" @selected($responsibleId == $responsible->id)>{{ $responsible->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end gap-2 md:col-span-4">
                            <button type="submit" class="text-sm text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-2 rounded">
                                {{ __('Filtrar') }}
                            </button>
                            <a href="{{ route('workers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Limpiar') }}
                            </a>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Nombre') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cédula') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Rol') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Responsable') }}</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($workers as $worker)
                                <tr>
                                    <td class="px-3 py-2">{{ $worker->name }}</td>
                                    <td class="px-3 py-2">{{ $worker->document ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $worker->role }}</td>
                                    <td class="px-3 py-2">{{ $worker->client?->name }}</td>
                                    <td class="px-3 py-2">{{ $worker->responsible?->name }}</td>
                                    <td class="px-3 py-2 text-right space-x-2">
                                        @can('update', $worker)
                                            <a href="{{ route('workers.edit', $worker) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ __('Editar') }}
                                            </a>
                                        @endcan
                                        @can('delete', $worker)
                                            <form action="{{ route('workers.destroy', $worker) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm(@js(__('¿Eliminar trabajador?')))">
                                                    {{ __('Eliminar') }}
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                                        {{ __('No hay trabajadores registrados.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <div class="mt-4">
                        {{ $workers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>




