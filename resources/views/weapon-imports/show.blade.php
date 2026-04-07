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
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Subir armas</div>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Detalle del lote</h2>
                <p class="mt-1 text-sm text-gray-500">Revisa el resultado del lote y ejecuta o descarta cambios según el estado actual.</p>
            </div>
            <a href="{{ route('weapon-imports.index') }}"
                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                Volver a lotes
            </a>
        </div>
    </x-slot>

    @php
        $selectedBatchBadge = $selectedBatch->isExecuted()
            ? ['classes' => 'bg-green-100 text-green-700', 'label' => 'Lote ejecutado']
            : ($selectedBatch->isProcessing()
                ? ['classes' => 'bg-blue-100 text-blue-700', 'label' => 'Lote en ejecución']
                : ($selectedBatch->isFailed()
                    ? ['classes' => 'bg-rose-100 text-rose-700', 'label' => 'Lote con fallo']
                    : ['classes' => 'bg-amber-100 text-amber-700', 'label' => 'Lote pendiente']));
    @endphp

    <div class="py-8"
        data-weapon-import-page
        data-selected-batch-id="{{ $selectedBatch->id }}"
        data-selected-batch-status="{{ $selectedBatch->status }}"
        data-selected-batch-name="{{ $selectedBatch->source_name }}"
        data-selected-batch-process-url="{{ route('weapon-imports.process', $selectedBatch) }}"
        data-selected-batch-status-url="{{ route('weapon-imports.status', $selectedBatch) }}"
        data-selected-batch-redirect-url="{{ route('weapon-imports.show', $selectedBatch) }}">
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

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $selectedBatch->source_name }}</h3>
                        <div class="mt-1 text-sm text-gray-500">
                            Subido por {{ $selectedBatch->uploadedBy?->name ?? 'Sistema' }} el {{ $selectedBatch->created_at?->format('d/m/Y H:i') }}
                        </div>
                        @if ($selectedBatch->isExecuted() && $selectedBatch->executed_at)
                            <div class="mt-1 text-sm text-gray-500">
                                Ejecutado por {{ $selectedBatch->executedBy?->name ?? 'Sistema' }} el {{ $selectedBatch->executed_at->format('d/m/Y H:i') }}
                            </div>
                        @elseif ($selectedBatch->isProcessing() && $selectedBatch->started_at)
                            <div class="mt-1 text-sm text-gray-500">
                                Iniciado por {{ $selectedBatch->executedBy?->name ?? 'Sistema' }} el {{ $selectedBatch->started_at->format('d/m/Y H:i') }}
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $selectedBatchBadge['classes'] }}">
                            {{ $selectedBatchBadge['label'] }}
                        </span>
                        @if ($selectedBatch->isDraft())
                            <a href="{{ route('weapon-imports.show', ['weaponImportBatch' => $selectedBatch->id, 'preview' => 1]) }}"
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
                                data-redirect-url="{{ route('weapon-imports.show', $selectedBatch) }}">
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
                        El lote se está ejecutando. Puedes seguir el avance en el panel de progreso.
                    </div>
                @elseif ($selectedBatch->isFailed())
                    <div class="mt-5 rounded-lg border border-rose-100 bg-rose-50 px-4 py-5 text-sm text-rose-700">
                        {{ $selectedBatch->last_error ?: 'La ejecución del lote falló.' }}
                    </div>
                @else
                    <div class="mt-5 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-5 text-sm text-gray-600">
                        Este lote está pendiente. Usa <strong>Revisar lote</strong> para validar la carga antes de ejecutarla.
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($openPreview && ($selectedBatch->isDraft() || $selectedBatch->isProcessing()))
        <div id="weapon-import-preview-root" aria-modal="true" role="dialog">
            <div style="position: fixed; left: 0; right: 0; top: 64px; bottom: 0; z-index: 5000; background: rgba(15, 23, 42, 0.55);">
                <div style="height: 100%; width: 100%; padding: 16px; box-sizing: border-box; display: flex; align-items: stretch; justify-content: center;">
                    <div style="width: min(1400px, 100%); height: 100%; background: #ffffff; border-radius: 16px; box-shadow: 0 24px 48px rgba(15, 23, 42, 0.22); display: flex; flex-direction: column; overflow: hidden;">
                        <div style="flex: 0 0 auto; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; background: #ffffff;">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Revisión del lote</h3>
                                <p class="mt-1 text-sm text-gray-500">Verifica la acción de cada fila antes de ejecutar los cambios.</p>
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
                                <a href="{{ route('weapon-imports.show', $selectedBatch) }}"
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
                                    El lote se está ejecutando. Puedes seguir el avance en el panel de progreso.
                                </div>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('weapon-imports.show', $selectedBatch) }}"
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
                                        data-redirect-url="{{ route('weapon-imports.show', $selectedBatch) }}">
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
            <div id="weapon-import-execution-subtitle" class="weapon-import-execution-panel__subtitle">Preparando ejecución...</div>
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

        const setRunningState = (running) => {
            state.isRunning = running;
            executionPanel.classList.toggle('is-visible', running);
            executeForms.forEach((form) => {
                const button = form.querySelector('button[type="submit"]');
                if (!button) return;
                button.disabled = running || button.hasAttribute('data-force-disabled');
            });
            discardButtons.forEach((button) => {
                button.disabled = running;
            });
        };

        const renderProgress = (progress) => {
            const total = Number(progress.total_rows || 0);
            const processedRows = Number(progress.processed_rows || 0);
            const successfulRows = Number(progress.successful_rows || 0);
            const failedRows = Number(progress.failed_rows || 0);
            const percentage = total > 0 ? Math.round((processedRows / total) * 100) : 0;

            title.textContent = progress.status === 'executed' ? 'Lote ejecutado' : (progress.status === 'failed' ? 'Ejecución detenida' : 'Ejecutando lote');
            subtitle.textContent = state.sourceName;
            percent.textContent = `${percentage}%`;
            fill.style.width = `${percentage}%`;
            left.textContent = `${processedRows} / ${total} filas`;
            right.textContent = progress.status === 'processing' ? `ETA ${formatDuration(progress.eta_seconds)}` : (progress.status === 'executed' ? 'Completado' : 'Proceso detenido');
            processed.textContent = processedRows;
            successful.textContent = successfulRows;
            failed.textContent = failedRows;
            if (progress.status === 'failed') message.textContent = progress.last_error || 'La ejecución del lote se detuvo.';
            else if (progress.status === 'executed') message.textContent = 'Carga completada. Redirigiendo...';
            else message.textContent = 'El lote se está procesando. Puedes cambiar de pestaña mientras termina.';
            message.classList.toggle('is-error', progress.status === 'failed');
        };

        const pollStatus = async () => {
            if (!state.statusUrl || !state.processUrl) return;

            try {
                const response = await fetch(state.statusUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                if (!response.ok) return;
                const payload = await response.json();
                renderProgress(payload.progress);
                if (payload.redirect_url) state.redirectUrl = payload.redirect_url;
                if (payload.progress.status === 'executed') {
                    setTimeout(() => {
                        window.location.href = state.redirectUrl;
                    }, 800);
                    return;
                }
                if (payload.progress.status === 'processing') {
                    window.setTimeout(pollStatus, 1500);
                    return;
                }
                setRunningState(false);
            } catch {
                window.setTimeout(pollStatus, 2000);
            }
        };

        const startExecution = async (form) => {
            state.processUrl = form.dataset.processUrl;
            state.statusUrl = form.dataset.statusUrl;
            state.redirectUrl = form.dataset.redirectUrl;
            state.sourceName = form.dataset.batchName || 'Lote';

            setRunningState(true);
            renderProgress({
                status: 'processing',
                total_rows: 0,
                processed_rows: 0,
                successful_rows: 0,
                failed_rows: 0,
                eta_seconds: 0,
            });

            try {
                const response = await fetch(form.dataset.startUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const payload = await response.json();
                if (!response.ok) {
                    message.textContent = payload.message || 'No se pudo iniciar la ejecución del lote.';
                    message.classList.add('is-error');
                    setRunningState(false);
                    return;
                }
                state.statusUrl = payload.status_url || state.statusUrl;
                state.processUrl = payload.process_url || state.processUrl;
                state.redirectUrl = payload.redirect_url || state.redirectUrl;
                renderProgress(payload.progress);
                pollStatus();
            } catch {
                message.textContent = 'No se pudo iniciar la ejecución del lote.';
                message.classList.add('is-error');
                setRunningState(false);
            }
        };

        executeForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (state.isRunning) return;
                startExecution(form);
            });
        });

        if (page?.dataset.selectedBatchStatus === 'processing' && state.statusUrl && state.processUrl) {
            setRunningState(true);
            fetch(state.statusUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
            })
                .then((response) => response.json())
                .then((payload) => {
                    state.redirectUrl = payload.redirect_url || state.redirectUrl;
                    renderProgress(payload.progress);
                    pollStatus();
                })
                .catch(() => {
                    renderProgress({
                        status: 'processing',
                        total_rows: 0,
                        processed_rows: 0,
                        successful_rows: 0,
                        failed_rows: 0,
                        eta_seconds: 0,
                    });
                    pollStatus();
                });
        }
    })();

    (() => {
        const root = document.getElementById('weapon-import-preview-root');
        if (!root) return;

        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        const content = document.querySelector('.sj-page-main');
        if (content) content.style.overflow = 'hidden';
        const scroll = document.getElementById('weapon-import-preview-scroll');
        if (scroll) scroll.scrollTop = 0;
    })();
</script>

<script>
    (() => {
        const page = document.querySelector('[data-weapon-import-page]');
        const executionPanel = document.getElementById('weapon-import-execution-panel');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        if (!page || !executionPanel) return;
        if (page.dataset.executionBridgeReady === '1') return;

        page.dataset.executionBridgeReady = '1';

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
        const executeButtons = Array.from(document.querySelectorAll('.weapon-import-execute-form button[type="submit"]'));
        const discardButtons = Array.from(document.querySelectorAll('.weapon-import-discard-form button[type="submit"]'));

        if (!title || !subtitle || !percent || !fill || !left || !right || !processed || !successful || !failed || !message) return;

        const state = {
            active: false,
            processUrl: page.dataset.selectedBatchProcessUrl || '',
            redirectUrl: page.dataset.selectedBatchRedirectUrl || '',
            sourceName: page.dataset.selectedBatchName || 'Lote',
            timer: null,
        };

        const setBusy = (busy) => {
            state.active = busy;
            executionPanel.classList.toggle('is-visible', busy);

            executeButtons.forEach((button) => {
                button.disabled = busy || button.hasAttribute('data-force-disabled');
            });

            discardButtons.forEach((button) => {
                button.disabled = busy;
            });

            if (!busy && state.timer) {
                window.clearTimeout(state.timer);
                state.timer = null;
            }
        };

        const formatDuration = (seconds) => {
            if (!seconds || seconds <= 0) return 'Calculando...';

            const mins = Math.floor(seconds / 60);
            const secs = Math.round(seconds % 60);

            if (mins <= 0) return `${secs}s`;
            if (secs <= 0) return `${mins}m`;

            return `${mins}m ${secs}s`;
        };

        const renderProgress = (progress) => {
            const totalRows = Number(progress.total_rows || 0);
            const processedRows = Number(progress.processed_rows || 0);
            const successfulRows = Number(progress.successful_rows || 0);
            const failedRows = Number(progress.failed_rows || 0);
            const percentage = totalRows > 0 ? Math.round((processedRows / totalRows) * 100) : 0;

            title.textContent = progress.status === 'executed' ? 'Lote ejecutado' : (progress.status === 'failed' ? 'Ejecución detenida' : 'Ejecutando lote');
            subtitle.textContent = state.sourceName;
            percent.textContent = `${percentage}%`;
            fill.style.width = `${percentage}%`;
            left.textContent = `${processedRows} / ${totalRows} filas`;
            right.textContent = progress.status === 'processing'
                ? `ETA ${formatDuration(progress.eta_seconds)}`
                : (progress.status === 'executed' ? 'Completado' : 'Proceso detenido');
            processed.textContent = processedRows;
            successful.textContent = successfulRows;
            failed.textContent = failedRows;
            message.classList.toggle('is-error', progress.status === 'failed');

            if (progress.status === 'failed') {
                message.textContent = progress.last_error || 'La ejecución del lote se detuvo.';
            } else if (progress.status === 'executed') {
                message.textContent = 'Carga completada. Redirigiendo...';
            } else {
                message.textContent = 'El lote se está procesando. Puedes cambiar de pestaña mientras termina.';
            }
        };

        const extractErrorMessage = (payload, fallback) => {
            if (!payload || typeof payload !== 'object') return fallback;
            if (typeof payload.message === 'string' && payload.message.trim() !== '') return payload.message;

            const batchErrors = payload.errors?.batch;
            if (Array.isArray(batchErrors) && batchErrors.length > 0) return String(batchErrors[0]);

            return fallback;
        };

        const redirectToBatch = () => {
            window.setTimeout(() => {
                window.location.href = state.redirectUrl;
            }, 800);
        };

        const scheduleNextChunk = (delay = 250) => {
            if (!state.active) return;

            if (state.timer) {
                window.clearTimeout(state.timer);
            }

            state.timer = window.setTimeout(() => {
                processChunk();
            }, delay);
        };

        const processChunk = async () => {
            if (!state.active || !state.processUrl) return;

            try {
                const response = await fetch(state.processUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const payload = await response.json();

                if (!response.ok) {
                    message.textContent = extractErrorMessage(payload, 'No se pudo continuar la ejecución del lote.');
                    message.classList.add('is-error');
                    setBusy(false);
                    return;
                }

                if (payload.redirect_url) {
                    state.redirectUrl = payload.redirect_url;
                }

                renderProgress(payload.progress);

                if (payload.progress.status === 'executed') {
                    redirectToBatch();
                    return;
                }

                if (payload.progress.status === 'failed') {
                    setBusy(false);
                    return;
                }

                scheduleNextChunk(250);
            } catch {
                message.textContent = 'No se pudo continuar la ejecución del lote.';
                message.classList.add('is-error');
                setBusy(false);
            }
        };

        const startExecution = async (form) => {
            state.processUrl = form.dataset.processUrl || state.processUrl;
            state.redirectUrl = form.dataset.redirectUrl || state.redirectUrl;
            state.sourceName = form.dataset.batchName || state.sourceName;

            setBusy(true);
            renderProgress({
                status: 'processing',
                total_rows: 0,
                processed_rows: 0,
                successful_rows: 0,
                failed_rows: 0,
                eta_seconds: 0,
            });

            try {
                const response = await fetch(form.dataset.startUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const payload = await response.json();

                if (!response.ok) {
                    message.textContent = extractErrorMessage(payload, 'No se pudo iniciar la ejecución del lote.');
                    message.classList.add('is-error');
                    setBusy(false);
                    return;
                }

                if (payload.process_url) state.processUrl = payload.process_url;
                if (payload.redirect_url) state.redirectUrl = payload.redirect_url;

                renderProgress(payload.progress);
                scheduleNextChunk(150);
            } catch {
                message.textContent = 'No se pudo iniciar la ejecución del lote.';
                message.classList.add('is-error');
                setBusy(false);
            }
        };

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('.weapon-import-execute-form');
            if (!form) return;

            event.preventDefault();
            event.stopImmediatePropagation();

            if (state.active) return;

            startExecution(form);
        }, true);

        if (page.dataset.selectedBatchStatus === 'processing' && state.processUrl) {
            setBusy(true);
            scheduleNextChunk(150);
        }
    })();
</script>
