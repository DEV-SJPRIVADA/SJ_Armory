@push('styles')
    <style>
        .weapon-import-progress { display: none; gap: 0.75rem; border: 1px solid #dbeafe; border-radius: 0.9rem; background: #eff6ff; padding: 1rem; }
        .weapon-import-progress.is-visible { display: grid; }
        .weapon-import-progress__top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .weapon-import-progress__title { color: #1e3a8a; font-size: 0.95rem; font-weight: 700; }
        .weapon-import-progress__meta { color: #475569; font-size: 0.85rem; font-weight: 600; white-space: nowrap; }
        .weapon-import-progress__bar { width: 100%; height: 0.75rem; overflow: hidden; border-radius: 999px; background: rgba(148, 163, 184, 0.28); }
        .weapon-import-progress__fill { height: 100%; width: 0%; border-radius: inherit; background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%); transition: width 0.25s ease; }
        .weapon-import-progress__fill.is-indeterminate { width: 35%; animation: weapon-import-progress-slide 1.25s ease-in-out infinite; }
        .weapon-import-progress__details { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 0.75rem; color: #475569; font-size: 0.85rem; }
        .weapon-import-execution-panel { display: none; position: fixed; right: 1.5rem; bottom: 1.5rem; z-index: 5500; width: min(28rem, calc(100vw - 2rem)); border: 1px solid #cbd5e1; border-radius: 1rem; background: #ffffff; box-shadow: 0 22px 55px rgba(15, 23, 42, 0.18); padding: 1rem 1rem 0.9rem; }
        .weapon-import-execution-panel.is-visible { display: grid; gap: 0.85rem; }
        .weapon-import-execution-panel__title { color: #0f172a; font-size: 1rem; font-weight: 800; }
        .weapon-import-execution-panel__subtitle { color: #64748b; font-size: 0.86rem; }
        .weapon-import-execution-panel__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; }
        .weapon-import-execution-panel__stat { border-radius: 0.85rem; background: #f8fafc; padding: 0.75rem; }
        .weapon-import-execution-panel__stat-label { color: #64748b; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .weapon-import-execution-panel__stat-value { margin-top: 0.2rem; color: #0f172a; font-size: 1.1rem; font-weight: 800; }
        .weapon-import-execution-panel__message { color: #334155; font-size: 0.86rem; }
        .weapon-import-execution-panel__message.is-error { color: #b91c1c; }
        @keyframes weapon-import-progress-slide { 0% { transform: translateX(-120%); } 100% { transform: translateX(320%); } }
    </style>
@endpush
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Subir armas</h2>
                <p class="text-sm text-gray-500">Carga masiva con validacion previa antes de crear o actualizar armas.</p>
            </div>
            <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'weapon-import-upload')"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                Subir documento
            </button>
        </div>
    </x-slot>

    @php
        $recentBatches = $batches
            ->filter(fn ($batch) => $batch->isExecuted())
            ->reject(fn ($batch) => $batch->id === $selectedBatch?->id)
            ->values();

        $selectedBatchBadge = $selectedBatch?->isExecuted()
            ? ['classes' => 'bg-green-100 text-green-700', 'label' => 'Lote ejecutado']
            : ($selectedBatch?->isProcessing()
                ? ['classes' => 'bg-blue-100 text-blue-700', 'label' => 'Lote en ejecucion']
                : ($selectedBatch?->isFailed()
                    ? ['classes' => 'bg-rose-100 text-rose-700', 'label' => 'Lote con fallo']
                    : ['classes' => 'bg-amber-100 text-amber-700', 'label' => 'Lote pendiente']));
    @endphp

    <div class="py-8"
        data-weapon-import-page
        @if ($selectedBatch)
            data-selected-batch-id="{{ $selectedBatch->id }}"
            data-selected-batch-status="{{ $selectedBatch->status }}"
            data-selected-batch-name="{{ $selectedBatch->source_name }}"
            data-selected-batch-process-url="{{ route('weapon-imports.process', $selectedBatch) }}"
            data-selected-batch-status-url="{{ route('weapon-imports.status', $selectedBatch) }}"
            data-selected-batch-redirect-url="{{ route('weapon-imports.index', ['batch' => $selectedBatch->id]) }}"
        @endif>
        <div class="w-full px-4 pb-20 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('batch'))
                <div class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('batch') }}
                </div>
            @endif

            <div class="grid gap-6 {{ $recentBatches->isNotEmpty() ? 'lg:grid-cols-3' : '' }}">
                @if ($recentBatches->isNotEmpty())
                    <aside class="space-y-6 lg:col-span-1">
                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Lotes ejecutados</h3>
                            <div class="mt-4 space-y-3">
                                @foreach ($recentBatches as $batch)
                                    <a href="{{ route('weapon-imports.index', ['batch' => $batch->id]) }}"
                                        class="block rounded-lg border border-gray-200 bg-white px-4 py-3 transition hover:border-gray-300">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-semibold text-gray-800">{{ $batch->source_name }}</div>
                                                <div class="mt-1 text-xs text-gray-500">{{ $batch->created_at?->format('d/m/Y H:i') }}</div>
                                            </div>
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-[11px] font-semibold text-green-700">
                                                Ejecutado
                                            </span>
                                        </div>
                                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600">
                                            <div>Total: {{ $batch->total_rows }}</div>
                                            <div>Errores: {{ $batch->error_count }}</div>
                                            <div>Crear: {{ $batch->create_count }}</div>
                                            <div>Actualizar: {{ $batch->update_count }}</div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </aside>
                @endif

                <section class="space-y-6 {{ $recentBatches->isNotEmpty() ? 'lg:col-span-2' : '' }}">
                    @if ($selectedBatch)
                        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">{{ $selectedBatch->source_name }}</h3>
                                    <div class="mt-1 text-sm text-gray-500">
                                        Subido por {{ $selectedBatch->uploadedBy?->name ?? 'Sistema' }}
                                        el {{ $selectedBatch->created_at?->format('d/m/Y H:i') }}
                                    </div>
                                    @if ($selectedBatch->isExecuted() && $selectedBatch->executed_at)
                                        <div class="mt-1 text-sm text-gray-500">
                                            Ejecutado por {{ $selectedBatch->executedBy?->name ?? 'Sistema' }}
                                            el {{ $selectedBatch->executed_at->format('d/m/Y H:i') }}
                                        </div>
                                    @elseif ($selectedBatch->isProcessing() && $selectedBatch->started_at)
                                        <div class="mt-1 text-sm text-gray-500">
                                            Iniciado por {{ $selectedBatch->executedBy?->name ?? 'Sistema' }}
                                            el {{ $selectedBatch->started_at->format('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $selectedBatchBadge['classes'] }}">
                                        {{ $selectedBatchBadge['label'] }}
                                    </span>
                                    @if ($selectedBatch->isDraft())
                                        <a href="{{ route('weapon-imports.index', ['batch' => $selectedBatch->id, 'preview' => 1]) }}"
                                            class="inline-flex items-center rounded-md border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">
                                            Revisar lote
                                        </a>
                                        <form method="POST" action="{{ route('weapon-imports.execute', $selectedBatch) }}"
                                            class="weapon-import-execute-form"
                                            data-batch-id="{{ $selectedBatch->id }}"
                                            data-batch-name="{{ $selectedBatch->source_name }}"
                                            data-start-url="{{ route('weapon-imports.start', $selectedBatch) }}"
                                            data-process-url="{{ route('weapon-imports.process', $selectedBatch) }}"
                                            data-status-url="{{ route('weapon-imports.status', $selectedBatch) }}"
                                            data-redirect-url="{{ route('weapon-imports.index', ['batch' => $selectedBatch->id]) }}">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                                                @disabled($selectedBatch->hasErrors())>
                                                Ejecutar
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('weapon-imports.discard', $selectedBatch) }}" class="weapon-import-discard-form">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center rounded-md border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">
                                                Cancelar carga
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            @if ($selectedBatch->isExecuted())
                                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-lg bg-blue-50 px-4 py-3">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">Crear</div>
                                        <div class="mt-1 text-2xl font-semibold text-blue-900">{{ $selectedBatch->create_count }}</div>
                                    </div>
                                    <div class="rounded-lg bg-amber-50 px-4 py-3">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Actualizar</div>
                                        <div class="mt-1 text-2xl font-semibold text-amber-900">{{ $selectedBatch->update_count }}</div>
                                    </div>
                                    <div class="rounded-lg bg-green-50 px-4 py-3">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-green-700">Sin cambios</div>
                                        <div class="mt-1 text-2xl font-semibold text-green-900">{{ $selectedBatch->no_change_count }}</div>
                                    </div>
                                    <div class="rounded-lg bg-rose-50 px-4 py-3">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-rose-700">Errores</div>
                                        <div class="mt-1 text-2xl font-semibold text-rose-900">{{ $selectedBatch->error_count }}</div>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    @include('weapon-imports.partials.rows', ['rows' => $selectedBatch->rows])
                                </div>
                            @elseif ($selectedBatch->isProcessing())
                                <div class="mt-5 rounded-lg border border-blue-100 bg-blue-50 px-4 py-5 text-sm text-blue-800">
                                    El lote se esta ejecutando. Puedes seguir el avance en el panel de progreso.
                                </div>
                            @elseif ($selectedBatch->isFailed())
                                <div class="mt-5 rounded-lg border border-rose-100 bg-rose-50 px-4 py-5 text-sm text-rose-700">
                                    {{ $selectedBatch->last_error ?: 'La ejecucion del lote fallo.' }}
                                </div>
                            @else
                                <div class="mt-5 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-5 text-sm text-gray-600">
                                    Este lote esta pendiente. Usa <strong>Revisar lote</strong> para validar la carga antes de ejecutarla.
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-gray-200 bg-white p-10 text-center text-gray-500 shadow-sm">
                            Sube tu primer archivo para revisar y ejecutar cambios masivos de armas.
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>

    <x-modal name="weapon-import-upload" :show="$errors->has('document')" maxWidth="2xl" focusable>
        <div id="weapon-import-upload-modal" class="p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Subir documento</h3>
                    <p class="mt-1 text-sm text-gray-500">Arrastra un archivo, pegalo desde el portapapeles o selecciona uno de este equipo.</p>
                </div>
                <button type="button" x-data="" x-on:click.prevent="$dispatch('close-modal', 'weapon-import-upload')"
                    class="rounded-md px-2 py-1 text-sm text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                    Cerrar
                </button>
            </div>

            <form id="weapon-import-upload-form" method="POST" action="{{ route('weapon-imports.preview') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf

                <input id="weapon-import-document" type="file" name="document" accept=".xlsx,.csv,.txt" class="hidden" required>

                <label for="weapon-import-document" id="weapon-import-dropzone"
                    class="flex min-h-[15rem] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center transition hover:border-indigo-300 hover:bg-indigo-50/40">
                    <div class="rounded-full bg-white p-4 text-indigo-600 shadow-sm">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M12 16V4" />
                            <path d="M8 8l4-4 4 4" />
                            <path d="M4 16.5v1.5A2 2 0 006 20h12a2 2 0 002-2v-1.5" />
                        </svg>
                    </div>
                    <div class="mt-4 text-base font-semibold text-gray-800">Arrastra el documento aqui</div>
                    <div class="mt-2 text-sm text-gray-500">Tambien puedes pegar el archivo con Ctrl + V o seleccionarlo manualmente.</div>
                    <div class="mt-5 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm">
                        Seleccionar de este equipo
                    </div>
                    <div id="weapon-import-file-name" class="mt-4 text-sm font-medium text-indigo-700"></div>
                </label>

                <div id="weapon-import-upload-error" class="hidden rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

                <div id="weapon-import-upload-progress" class="weapon-import-progress" aria-live="polite">
                    <div class="weapon-import-progress__top">
                        <div id="weapon-import-upload-progress-title" class="weapon-import-progress__title">Subiendo archivo...</div>
                        <div id="weapon-import-upload-progress-meta" class="weapon-import-progress__meta">0%</div>
                    </div>
                    <div class="weapon-import-progress__bar">
                        <div id="weapon-import-upload-progress-fill" class="weapon-import-progress__fill"></div>
                    </div>
                    <div class="weapon-import-progress__details">
                        <span id="weapon-import-upload-progress-detail-left">Esperando archivo</span>
                        <span id="weapon-import-upload-progress-detail-right"></span>
                    </div>
                </div>

                <x-input-error :messages="$errors->get('document')" class="mt-2" />

                <div class="flex items-center justify-end gap-3">
                    <button type="button" x-data="" x-on:click.prevent="$dispatch('close-modal', 'weapon-import-upload')"
                        class="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 hover:text-gray-800">
                        Cancelar
                    </button>
                    <x-primary-button id="weapon-import-submit" disabled>Subir</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>

    @if ($openPreview && ($selectedBatch?->isDraft() || $selectedBatch?->isProcessing()))
        <div id="weapon-import-preview-root" aria-modal="true" role="dialog">
            <div style="position: fixed; left: 0; right: 0; top: 64px; bottom: 0; z-index: 5000; background: rgba(15, 23, 42, 0.55);">
                <div style="height: 100%; width: 100%; padding: 16px; box-sizing: border-box; display: flex; align-items: stretch; justify-content: center;">
                    <div style="width: min(1400px, 100%); height: 100%; background: #ffffff; border-radius: 16px; box-shadow: 0 24px 48px rgba(15, 23, 42, 0.22); display: flex; flex-direction: column; overflow: hidden;">
                        <div style="flex: 0 0 auto; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; background: #ffffff;">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Revision del lote</h3>
                                <p class="mt-1 text-sm text-gray-500">Verifica la accion de cada fila antes de ejecutar los cambios.</p>
                            </div>
                            @if ($selectedBatch->isDraft())
                                <form method="POST" action="{{ route('weapon-imports.discard', $selectedBatch) }}" class="weapon-import-discard-form">
                                    @csrf
                                    <button type="submit"
                                        class="rounded-md px-2 py-1 text-sm text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                                        Cerrar
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('weapon-imports.index', ['batch' => $selectedBatch->id]) }}"
                                    class="rounded-md px-2 py-1 text-sm text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                                    Cerrar
                                </a>
                            @endif
                        </div>

                        <div id="weapon-import-preview-scroll" style="flex: 1 1 auto; min-height: 0; overflow: auto; padding: 16px 24px;">
                            @include('weapon-imports.partials.rows', ['rows' => $selectedBatch->rows])
                        </div>

                        <div style="flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 24px; border-top: 1px solid #e5e7eb; background: #ffffff;">
                            @if ($selectedBatch->isProcessing())
                                <div class="text-sm text-blue-700">
                                    El lote se esta ejecutando. Puedes seguir el avance en el panel de progreso.
                                </div>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('weapon-imports.index', ['batch' => $selectedBatch->id]) }}"
                                        class="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 hover:text-gray-800">
                                        Minimizar
                                    </a>
                                </div>
                            @else
                                <div class="text-sm {{ $selectedBatch->hasErrors() ? 'text-rose-700' : 'text-green-700' }}">
                                    {{ $selectedBatch->hasErrors()
                                        ? 'Hay filas con error. No puedes ejecutar este lote.'
                                        : 'No hay errores. Puedes ejecutar el lote.' }}
                                </div>

                                <div class="flex items-center gap-3">
                                    <form method="POST" action="{{ route('weapon-imports.discard', $selectedBatch) }}" class="weapon-import-discard-form">
                                        @csrf
                                        <button type="submit"
                                            class="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 hover:text-gray-800">
                                            Cancelar
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('weapon-imports.execute', $selectedBatch) }}"
                                        class="weapon-import-execute-form"
                                        data-batch-id="{{ $selectedBatch->id }}"
                                        data-batch-name="{{ $selectedBatch->source_name }}"
                                        data-start-url="{{ route('weapon-imports.start', $selectedBatch) }}"
                                        data-process-url="{{ route('weapon-imports.process', $selectedBatch) }}"
                                        data-status-url="{{ route('weapon-imports.status', $selectedBatch) }}"
                                        data-redirect-url="{{ route('weapon-imports.index', ['batch' => $selectedBatch->id]) }}">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                                            @disabled($selectedBatch->hasErrors())>
                                            Ejecutar
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div id="weapon-import-execution-panel" class="weapon-import-execution-panel" aria-live="polite">
        <div>
            <div id="weapon-import-execution-title" class="weapon-import-execution-panel__title">Ejecutando lote</div>
            <div id="weapon-import-execution-subtitle" class="weapon-import-execution-panel__subtitle">Preparando ejecucion...</div>
        </div>
        <div class="weapon-import-progress is-visible" style="padding: 0; border: none; background: transparent;">
            <div class="weapon-import-progress__top">
                <div class="weapon-import-progress__title">Avance</div>
                <div id="weapon-import-execution-percent" class="weapon-import-progress__meta">0%</div>
            </div>
            <div class="weapon-import-progress__bar">
                <div id="weapon-import-execution-fill" class="weapon-import-progress__fill"></div>
            </div>
            <div class="weapon-import-progress__details">
                <span id="weapon-import-execution-left">0 / 0 filas</span>
                <span id="weapon-import-execution-right">Calculando ETA...</span>
            </div>
        </div>
        <div class="weapon-import-execution-panel__stats">
            <div class="weapon-import-execution-panel__stat">
                <div class="weapon-import-execution-panel__stat-label">Procesadas</div>
                <div id="weapon-import-execution-processed" class="weapon-import-execution-panel__stat-value">0</div>
            </div>
            <div class="weapon-import-execution-panel__stat">
                <div class="weapon-import-execution-panel__stat-label">Correctas</div>
                <div id="weapon-import-execution-successful" class="weapon-import-execution-panel__stat-value">0</div>
            </div>
            <div class="weapon-import-execution-panel__stat">
                <div class="weapon-import-execution-panel__stat-label">Fallidas</div>
                <div id="weapon-import-execution-failed" class="weapon-import-execution-panel__stat-value">0</div>
            </div>
        </div>
        <div id="weapon-import-execution-message" class="weapon-import-execution-panel__message">Esperando inicio...</div>
    </div>
</x-app-layout>

<script>
    (() => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const input = document.getElementById('weapon-import-document');
        const dropzone = document.getElementById('weapon-import-dropzone');
        const fileName = document.getElementById('weapon-import-file-name');
        const submit = document.getElementById('weapon-import-submit');
        const uploadModal = document.getElementById('weapon-import-upload-modal');
        const uploadForm = document.getElementById('weapon-import-upload-form');
        const uploadError = document.getElementById('weapon-import-upload-error');
        const uploadProgress = document.getElementById('weapon-import-upload-progress');
        const uploadProgressTitle = document.getElementById('weapon-import-upload-progress-title');
        const uploadProgressMeta = document.getElementById('weapon-import-upload-progress-meta');
        const uploadProgressFill = document.getElementById('weapon-import-upload-progress-fill');
        const uploadDetailLeft = document.getElementById('weapon-import-upload-progress-detail-left');
        const uploadDetailRight = document.getElementById('weapon-import-upload-progress-detail-right');

        if (!input || !dropzone || !fileName || !submit || !uploadModal || !uploadForm) {
            return;
        }

        const formatDuration = (seconds) => {
            if (!seconds || seconds <= 0) return 'Calculando...';
            const mins = Math.floor(seconds / 60);
            const secs = Math.round(seconds % 60);
            if (mins <= 0) return `${secs}s`;
            if (secs <= 0) return `${mins}m`;
            return `${mins}m ${secs}s`;
        };

        const setUploadError = (message = '') => {
            if (!uploadError) return;
            uploadError.textContent = message;
            uploadError.classList.toggle('hidden', !message);
        };

        const setUploadProgress = ({ visible = false, title = 'Subiendo archivo...', percent = null, left = '', right = '', indeterminate = false }) => {
            uploadProgress?.classList.toggle('is-visible', visible);
            if (!uploadProgressFill || !uploadProgressTitle || !uploadProgressMeta || !uploadDetailLeft || !uploadDetailRight) return;
            uploadProgressTitle.textContent = title;
            uploadProgressMeta.textContent = percent === null ? '...' : `${percent}%`;
            uploadDetailLeft.textContent = left;
            uploadDetailRight.textContent = right;
            uploadProgressFill.classList.toggle('is-indeterminate', indeterminate);
            uploadProgressFill.style.width = percent === null ? '35%' : `${percent}%`;
        };

        const setFile = (file) => {
            if (!file) {
                input.value = '';
                fileName.textContent = '';
                submit.disabled = true;
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            fileName.textContent = file.name;
            submit.disabled = false;
            setUploadError('');
        };

        const parseErrorMessage = (payload) => {
            if (!payload) return 'No se pudo procesar el archivo seleccionado.';
            if (payload.errors) {
                const firstKey = Object.keys(payload.errors)[0];
                if (firstKey && payload.errors[firstKey]?.length) return payload.errors[firstKey][0];
            }
            return payload.message || 'No se pudo procesar el archivo seleccionado.';
        };

        input.addEventListener('change', () => {
            const file = input.files && input.files[0] ? input.files[0] : null;
            setFile(file);
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('border-indigo-400', 'bg-indigo-50');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('border-indigo-400', 'bg-indigo-50');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            const file = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files[0] : null;
            if (file) setFile(file);
        });

        window.addEventListener('paste', (event) => {
            const isVisible = uploadModal.offsetParent !== null;
            if (!isVisible) return;
            const items = event.clipboardData ? Array.from(event.clipboardData.items || []) : [];
            const fileItem = items.find((item) => item.kind === 'file');
            const file = fileItem ? fileItem.getAsFile() : null;
            if (file) {
                event.preventDefault();
                setFile(file);
            }
        });

        uploadForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const file = input.files && input.files[0] ? input.files[0] : null;
            if (!file) return;

            setUploadError('');
            submit.disabled = true;

            const xhr = new XMLHttpRequest();
            const startedAt = Date.now();
            xhr.open('POST', uploadForm.action);
            xhr.responseType = 'json';
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

            xhr.upload.addEventListener('progress', (progressEvent) => {
                if (!progressEvent.lengthComputable) {
                    setUploadProgress({ visible: true, title: 'Subiendo archivo...', percent: null, left: 'Transferencia en curso', right: 'Calculando...', indeterminate: true });
                    return;
                }

                const percent = Math.max(1, Math.min(100, Math.round((progressEvent.loaded / progressEvent.total) * 100)));
                const elapsedSeconds = Math.max(1, (Date.now() - startedAt) / 1000);
                const bytesPerSecond = progressEvent.loaded / elapsedSeconds;
                const remainingSeconds = bytesPerSecond > 0 ? (progressEvent.total - progressEvent.loaded) / bytesPerSecond : 0;
                setUploadProgress({ visible: true, title: 'Subiendo archivo...', percent, left: file.name, right: `Faltan ${formatDuration(remainingSeconds)}`, indeterminate: false });
            });

            xhr.onerror = () => {
                submit.disabled = false;
                setUploadProgress({ visible: false });
                setUploadError('No se pudo subir el archivo. Verifica la conexion e intenta de nuevo.');
            };

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    setUploadProgress({ visible: true, title: 'Validando archivo...', percent: null, left: 'El documento ya fue cargado', right: 'Procesando previsualizacion...', indeterminate: true });
                    const payload = xhr.response || {};
                    window.location.href = payload.redirect_url || uploadForm.action;
                    return;
                }

                submit.disabled = false;
                setUploadProgress({ visible: false });
                setUploadError(parseErrorMessage(xhr.response));
            };

            xhr.send(new FormData(uploadForm));
        });
    })();

    (() => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const page = document.querySelector('[data-weapon-import-page]');
        const executionPanel = document.getElementById('weapon-import-execution-panel');
        const title = document.getElementById('weapon-import-execution-title');
        const subtitle = document.getElementById('weapon-import-execution-subtitle');
        const percent = document.getElementById('weapon-import-execution-percent');
        const fill = document.getElementById('weapon-import-execution-fill');
        const left = document.getElementById('weapon-import-execution-left');
        const right = document.getElementById('weapon-import-execution-right');
        const processed = document.getElementById('weapon-import-execution-processed');
        const successful = document.getElementById('weapon-import-execution-successful');
        const failed = document.getElementById('weapon-import-execution-failed');
        const message = document.getElementById('weapon-import-execution-message');
        const executeForms = Array.from(document.querySelectorAll('.weapon-import-execute-form'));
        const discardButtons = Array.from(document.querySelectorAll('.weapon-import-discard-form button[type="submit"]'));

        if (!executionPanel || !title || !subtitle || !percent || !fill || !left || !right || !processed || !successful || !failed || !message) {
            return;
        }

        const state = {
            isRunning: false,
            processUrl: page?.dataset.selectedBatchProcessUrl || '',
            statusUrl: page?.dataset.selectedBatchStatusUrl || '',
            redirectUrl: page?.dataset.selectedBatchRedirectUrl || '',
            sourceName: page?.dataset.selectedBatchName || 'Lote',
        };

        const formatDuration = (seconds) => {
            if (!seconds || seconds <= 0) return 'Calculando...';
            const mins = Math.floor(seconds / 60);
            const secs = Math.round(seconds % 60);
            if (mins <= 0) return `${secs}s`;
            if (secs <= 0) return `${mins}m`;
            return `${mins}m ${secs}s`;
        };

        const sleep = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

        const setUiLocked = (locked) => {
            executeForms.forEach((form) => {
                const button = form.querySelector('button[type="submit"]');
                if (button) button.disabled = locked;
            });
            discardButtons.forEach((button) => { button.disabled = locked; });
        };

        const renderProgress = (progress) => {
            if (!progress) return;
            executionPanel.classList.add('is-visible');
            title.textContent = 'Ejecutando lote';
            subtitle.textContent = progress.source_name || state.sourceName;
            percent.textContent = `${progress.percentage}%`;
            fill.classList.remove('is-indeterminate');
            fill.style.width = `${progress.percentage}%`;
            left.textContent = `${progress.processed_rows} / ${progress.total_rows} filas`;
            right.textContent = progress.status === 'processing' ? `ETA ${formatDuration(progress.eta_seconds)}` : (progress.status === 'executed' ? 'Completado' : 'Proceso detenido');
            processed.textContent = `${progress.processed_rows}`;
            successful.textContent = `${progress.successful_rows}`;
            failed.textContent = `${progress.failed_rows}`;
            message.classList.toggle('is-error', progress.status === 'failed');
            if (progress.status === 'processing') message.textContent = 'Procesando filas del lote...';
            else if (progress.status === 'executed') message.textContent = 'Carga completada. Redirigiendo...';
            else if (progress.status === 'failed') message.textContent = progress.last_error || 'La ejecucion del lote fallo.';
            else message.textContent = 'Preparando ejecucion...';
        };

        const fetchJson = async (url, options = {}) => {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    ...(options.headers || {}),
                },
                ...options,
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstKey = payload.errors ? Object.keys(payload.errors)[0] : null;
                const text = firstKey && payload.errors[firstKey]?.length ? payload.errors[firstKey][0] : (payload.message || 'No se pudo completar la operacion.');
                throw new Error(text);
            }
            return payload;
        };

        const processLoop = async () => {
            while (state.isRunning) {
                const payload = await fetchJson(state.processUrl, { method: 'POST' });
                renderProgress(payload.progress);
                if (payload.redirect_url) state.redirectUrl = payload.redirect_url;
                if (payload.progress.status === 'executed') {
                    state.isRunning = false;
                    setUiLocked(false);
                    await sleep(700);
                    window.location.href = state.redirectUrl;
                    return;
                }
                if (payload.progress.status === 'failed') {
                    state.isRunning = false;
                    setUiLocked(false);
                    return;
                }
                await sleep(250);
            }
        };

        const startExecution = async (form) => {
            if (state.isRunning) return;
            state.isRunning = true;
            state.processUrl = form.dataset.processUrl;
            state.statusUrl = form.dataset.statusUrl;
            state.redirectUrl = form.dataset.redirectUrl;
            state.sourceName = form.dataset.batchName || 'Lote';
            setUiLocked(true);
            executionPanel.classList.add('is-visible');
            message.classList.remove('is-error');
            message.textContent = 'Preparando ejecucion...';
            fill.classList.add('is-indeterminate');
            fill.style.width = '35%';
            try {
                const payload = await fetchJson(form.dataset.startUrl, { method: 'POST' });
                renderProgress(payload.progress);
                state.processUrl = payload.process_url || state.processUrl;
                state.statusUrl = payload.status_url || state.statusUrl;
                state.redirectUrl = payload.redirect_url || state.redirectUrl;
                await processLoop();
            } catch (error) {
                state.isRunning = false;
                setUiLocked(false);
                executionPanel.classList.add('is-visible');
                fill.classList.remove('is-indeterminate');
                fill.style.width = '0%';
                percent.textContent = '0%';
                message.classList.add('is-error');
                message.textContent = error.message || 'No se pudo iniciar la ejecucion del lote.';
            }
        };

        executeForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                startExecution(form);
            });
        });

        if (page?.dataset.selectedBatchStatus === 'processing' && state.statusUrl && state.processUrl) {
            setUiLocked(true);
            fetchJson(state.statusUrl)
                .then(async (payload) => {
                    renderProgress(payload.progress);
                    state.redirectUrl = payload.redirect_url || state.redirectUrl;
                    if (payload.progress.status === 'processing') {
                        state.isRunning = true;
                        await processLoop();
                    } else if (payload.progress.status === 'failed') {
                        setUiLocked(false);
                    }
                })
                .catch((error) => {
                    setUiLocked(false);
                    executionPanel.classList.add('is-visible');
                    message.classList.add('is-error');
                    message.textContent = error.message || 'No se pudo consultar el progreso del lote.';
                });
        }
    })();

    (() => {
        const root = document.getElementById('weapon-import-preview-root');
        if (!root) return;
        document.body.appendChild(root);
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        const content = document.querySelector('.sj-content');
        if (content) content.style.overflow = 'hidden';
        const scroll = document.getElementById('weapon-import-preview-scroll');
        if (scroll) scroll.scrollTop = 0;
    })();
</script>
