<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">{{ __('Documentos') }}</h3>
            @can('update', $weapon)
                @php
                    $statusOptions = [
                        'Sin novedad',
                        'En proceso',
                    ];
                @endphp
                <form method="POST" action="{{ route('weapons.documents.store', $weapon) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <label class="inline-flex items-center rounded border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer">
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
                        <option value="Hurtada" @selected(old('observations') === 'Hurtada')>{{ __('Hurtada') }}</option>
                        <option value="Perdida" @selected(old('observations') === 'Perdida')>{{ __('Perdida') }}</option>
                        <option value="Dar de Baja" @selected(old('observations') === 'Dar de Baja')>{{ __('Dar de Baja') }}</option>
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




