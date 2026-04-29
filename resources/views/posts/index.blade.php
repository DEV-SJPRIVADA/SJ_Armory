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
        <div
            class="sj-page-shell sj-page-shell--wide"
            x-data="{
                historyOpen: false,
                historyTitle: '',
                historyLoading: false,
                historyEntries: [],
                async openHistory(title, url) {
                    this.historyTitle = title;
                    this.historyOpen = true;
                    this.historyLoading = true;
                    this.historyEntries = [];
                    try {
                        const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '' }});
                        const d = await r.json();
                        this.historyEntries = d.entries || [];
                    } catch (e) {
                        this.historyEntries = [];
                    } finally {
                        this.historyLoading = false;
                    }
                }
            }"
        >
            @if (session('status'))
                <div class="mb-4 rounded bg-green-50 p-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('restore'))
                <div class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first('restore') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- Siempre en una fila horizontal; scroll horizontal si el ancho no alcanza --}}
                    <form method="GET" action="{{ route('posts.index') }}" class="mb-4 flex flex-nowrap items-end gap-3 sm:gap-4 overflow-x-auto pb-1">
                        <div class="min-w-[11rem] sm:min-w-[13rem] w-44 sm:w-52 shrink-0">
                            <label class="block text-sm text-gray-600 whitespace-nowrap">{{ __('Buscar') }}</label>
                            <input type="text" name="q" value="{{ $search }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm" placeholder="{{ __('Nombre o dirección') }}">
                        </div>
                        <div class="min-w-[10rem] sm:min-w-[12rem] w-40 sm:w-48 shrink-0">
                            <label class="block text-sm text-gray-600 whitespace-nowrap">{{ __('Cliente') }}</label>
                            <select name="client_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">{{ __('Todos') }}</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected($clientId == $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-[9.5rem] w-36 sm:w-44 shrink-0">
                            <label class="block text-sm text-gray-600 whitespace-nowrap">{{ __('Estado') }}</label>
                            <select name="archive" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="active" @selected($archiveFilter === 'active')>{{ __('Solo activos') }}</option>
                                <option value="archived" @selected($archiveFilter === 'archived')>{{ __('Solo archivados') }}</option>
                                <option value="all" @selected($archiveFilter === 'all')>{{ __('Todos') }}</option>
                            </select>
                        </div>
                        <div class="flex shrink-0 items-center gap-2 sm:gap-3 pb-0.5">
                            <button type="submit" class="text-sm text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-2 rounded whitespace-nowrap">
                                {{ __('Filtrar') }}
                            </button>
                            <a href="{{ route('posts.index') }}" class="text-sm text-gray-600 hover:text-gray-900 whitespace-nowrap">
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
                                <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Estado') }}</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($posts as $post)
                                <tr>
                                    <td class="px-3 py-2">{{ $post->name }}</td>
                                    <td class="px-3 py-2">{{ $post->client?->name }}</td>
                                    <td class="px-3 py-2">{{ $post->address }}</td>
                                    <td class="px-3 py-2">
                                        @if ($post->isArchived())
                                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ __('Archivado') }}</span>
                                        @else
                                            <span class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-800">{{ __('Activo') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right space-x-2">
                                        @can('view', $post)
                                            <button
                                                type="button"
                                                class="text-gray-700 hover:text-gray-900"
                                                @click="openHistory(@js($post->name), '{{ route('posts.histories', $post) }}')"
                                            >
                                                {{ __('Historial') }}
                                            </button>
                                        @endcan
                                        @can('update', $post)
                                            <a href="{{ route('posts.edit', $post) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ __('Editar') }}
                                            </a>
                                        @endcan
                                        @can('delete', $post)
                                            <form action="{{ route('posts.destroy', $post) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-amber-700 hover:text-amber-900" onclick="return confirm(@js(__('¿Archivar este puesto? Las armas asignadas aquí quedarán sin ubicación interna activa.')))">
                                                    {{ __('Archivar') }}
                                                </button>
                                            </form>
                                        @endcan
                                        @can('restore', $post)
                                            <form action="{{ route('posts.restore', $post) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-green-700 hover:text-green-900" onclick="return confirm(@js(__('¿Reactivar este puesto?')))">
                                                    {{ __('Reactivar') }}
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-gray-500">
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

                    <div
                        x-show="historyOpen"
                        x-cloak
                        class="fixed inset-0 z-[4000] flex items-center justify-center bg-black/50 p-4"
                        @keydown.escape.window="historyOpen = false"
                    >
                        <div class="max-h-[85vh] w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl" @click.outside="historyOpen = false">
                            <div class="flex items-center justify-between border-b px-4 py-3">
                                <h3 class="text-base font-semibold text-gray-900">{{ __('Historial') }}: <span x-text="historyTitle"></span></h3>
                                <button type="button" class="text-2xl leading-none text-gray-500 hover:text-gray-800" @click="historyOpen = false">&times;</button>
                            </div>
                            <div class="max-h-[calc(85vh-4rem)] overflow-y-auto p-4 text-sm">
                                <template x-if="historyLoading">
                                    <p class="text-gray-500">{{ __('Cargando…') }}</p>
                                </template>
                                <template x-if="!historyLoading && historyEntries.length === 0">
                                    <p class="text-gray-500">{{ __('No hay entradas en el historial.') }}</p>
                                </template>
                                <ul class="space-y-4" x-show="!historyLoading && historyEntries.length">
                                    <template x-for="e in historyEntries" :key="e.id">
                                        <li class="rounded border border-gray-100 bg-gray-50 p-3">
                                            <div class="text-xs text-gray-500">
                                                <span x-text="e.at"></span>
                                                <span x-show="e.user"> · <span x-text="e.user"></span></span>
                                            </div>
                                            <pre class="mt-2 whitespace-pre-wrap font-sans text-gray-800 text-sm" x-text="e.body"></pre>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
