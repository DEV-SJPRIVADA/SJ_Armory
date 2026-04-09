<x-app-layout>
    <x-slot name="header">
        <div class="sj-section-header">
            <div class="sj-section-header__main">
                <h2 class="sj-section-header__title">{{ __('Puestos') }}</h2>
            </div>

            <div class="sj-section-header__actions">
                @can('create', App\Models\Post::class)
                    <a href="{{ route('posts.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                        {{ __('Nuevo puesto') }}
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
                    <form method="GET" action="{{ route('posts.index') }}" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <label class="text-sm text-gray-600">{{ __('Buscar') }}</label>
                            <input type="text" name="q" value="{{ $search }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm" placeholder="{{ __('Nombre o dirección') }}">
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
                        <div class="flex items-end gap-2">
                            <button type="submit" class="text-sm text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-2 rounded">
                                {{ __('Filtrar') }}
                            </button>
                            <a href="{{ route('posts.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Limpiar') }}
                            </a>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Puesto') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Cliente') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Dirección') }}</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($posts as $post)
                                <tr>
                                    <td class="px-3 py-2">{{ $post->name }}</td>
                                    <td class="px-3 py-2">{{ $post->client?->name }}</td>
                                    <td class="px-3 py-2">{{ $post->address }}</td>
                                    <td class="px-3 py-2 text-right space-x-2">
                                        @can('update', $post)
                                            <a href="{{ route('posts.edit', $post) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ __('Editar') }}
                                            </a>
                                        @endcan
                                        @can('delete', $post)
                                            <form action="{{ route('posts.destroy', $post) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm(@js(__('¿Eliminar puesto?')))">
                                                    {{ __('Eliminar') }}
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-gray-500">
                                        {{ __('No hay puestos registrados.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <div class="mt-4">
                        {{ $posts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
