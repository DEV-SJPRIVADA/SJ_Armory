<div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-200">
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-5">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                    <div class="bg-green-100 p-2 rounded-lg">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    {{ __('Documentos') }}
                </h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Los documentos manuales soportan la operación interna. Para hurtos, pérdidas o bajas usa el módulo de Novedades.') }}</p>
            </div>
            
            @can('update', $weapon)
                <div class="text-sm text-gray-600">
                    <div class="font-medium text-gray-900">{{ __('Documentos cargados:') }} {{ $weapon->documents->count() }}</div>
                </div>
            @endcan
        </div>
    </div>
    
    <div class="p-6">

            @can('update', $weapon)
                @php
                    $statusOptions = [
                        'Sin novedad',
                        'En proceso',
                    ];
                @endphp
                <form method="POST" action="{{ route('weapons.documents.store', $weapon) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <label class="inline-flex cursor-pointer items-center rounded border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:bg-gray-50">
                        <span class="mr-2">{{ __('Seleccionar archivo') }}</span>
                        <span class="text-xs text-gray-500" data-document-file-name>{{ __('Ningún archivo') }}</span>
                        <input type="file" name="document" required class="hidden" accept=".pdf,.doc,.docx,image/jpeg,image/png,image/webp" data-document-file-input>
                    </label>
                    <input type="date" name="valid_until" class="rounded-md border-gray-300 text-sm" placeholder="{{ __('Vence') }}">
                    <select name="status" class="rounded-md border-gray-300 text-sm" required>
                        <option value="">{{ __('Estado') }}</option>
                        @foreach ($statusOptions as $option)
                            <option value="{{ $option }}" @selected(old('status') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <select name="observations" class="rounded-md border-gray-300 text-sm" required>
                        <option value="">{{ __('Observaciones') }}</option>
                        <option value="En Armerillo" @selected(old('observations') === 'En Armerillo')>{{ __('En Armerillo') }}</option>
                        <option value="En Mantenimiento" @selected(old('observations') === 'En Mantenimiento')>{{ __('En Mantenimiento') }}</option>
                        <option value="Para Mantenimiento" @selected(old('observations') === 'Para Mantenimiento')>{{ __('Para Mantenimiento') }}</option>
                    </select>
                    <x-primary-button class="text-xs">
                        {{ __('Subir') }}
                    </x-primary-button>
                </form>
            @endcan
        </div>

        @if ($errors->has('document'))
            <div class="mt-2 text-sm text-red-600">{{ $errors->first('document') }}</div>
        @endif

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Documento') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Fecha de vencimiento') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Tipo de permiso') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Estado') }}</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">{{ __('Observaciones') }}</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-600">{{ __('Descargar') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($weapon->documents as $document)
                        @php($alert = \App\Support\WeaponDocumentAlert::forDocument($document))
                        @php($fileType = $document->file?->original_name ? strtoupper(pathinfo($document->file->original_name, PATHINFO_EXTENSION)) : ($document->file?->mime_type ?? '-'))
                        <tr class="{{ $alert['row_class'] }}">
                            <td class="px-3 py-2">
                                <div class="font-medium">{{ $document->document_name ?? __('Documento') }}</div>
                                <div class="text-xs text-gray-500">
                                    @if ($document->document_number)
                                        {{ __('Número:') }} {{ $document->document_number }} |
                                    @endif
                                    {{ __('Tipo:') }} {{ $fileType ?: '-' }}
                                </div>
                            </td>
                            <td class="px-3 py-2">{{ optional($document->valid_until)->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-3 py-2">
                                @if ($document->permit_kind === 'porte')
                                    {{ __('Porte') }}
                                @elseif ($document->permit_kind === 'tenencia')
                                    {{ __('Tenencia') }}
                                @else
                                    {{ __('No aplica') }}
                                @endif
                            </td>
                            <td class="px-3 py-2 {{ $alert['text_class'] }}">{{ $alert['state'] }}</td>
                            <td class="px-3 py-2 {{ $alert['text_class'] }}">{{ $alert['observation'] }}</td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <a href="{{ route('weapons.documents.download', [$weapon, $document]) }}" class="text-indigo-600 hover:text-indigo-900">
                                    {{ __('Descargar') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                                {{ __('Sin documentos cargados.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (() => {
        const input = document.querySelector('[data-document-file-input]');
        const name = document.querySelector('[data-document-file-name]');
        if (!input || !name) return;

        input.addEventListener('change', () => {
            name.textContent = input.files?.[0]?.name || 'Ningún archivo';
        });
    })();
</script>