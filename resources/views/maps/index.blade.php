<x-app-layout>
    <div class="py-8">
        <div class="sj-page-shell sj-page-shell--wide">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4" id="weapons-map-search-shell">
                        <label for="weapons-map-search" class="mb-1 block text-sm font-medium text-gray-700">
                            {{ __('Buscar arma por serie') }}
                        </label>
                        <div class="relative">
                            <input
                                id="weapons-map-search"
                                type="text"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                placeholder="{{ __('Escribe la serie del arma') }}"
                                autocomplete="off"
                            >
                            <div
                                id="weapons-map-search-results"
                                class="absolute left-0 right-0 top-full z-30 mt-1 hidden max-h-64 overflow-auto rounded-md border border-gray-200 bg-white shadow-lg"
                            ></div>
                        </div>
                    </div>
                    <div id="weapons-map" data-endpoint="{{ route('maps.weapons') }}" class="h-[70vh] w-full rounded border border-gray-200"></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@vite('resources/js/map.js')




