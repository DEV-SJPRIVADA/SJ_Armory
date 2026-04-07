<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Cargas masivas · {{ $selectedBatch->typeLabel() }}</div>
                <h2 class="mt-1 text-xl font-semibold leading-tight text-gray-800">Detalle del lote</h2>
                <p class="mt-1 text-sm text-gray-500">Revisa el resultado del lote y ejecuta o descarta cambios según el estado actual.</p>
            </div>
            <a href="{{ route('weapon-imports.index') }}" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Volver a lotes</a>
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

    <div class="py-8" data-weapon-import-page data-selected-batch-status="{{ $selectedBatch->status }}" data-selected-batch-name="{{ $selectedBatch->source_name }}" data-selected-batch-process-url="{{ route('weapon-imports.process', $selectedBatch) }}" data-selected-batch-status-url="{{ route('weapon-imports.status', $selectedBatch) }}" data-selected-batch-redirect-url="{{ route('weapon-imports.show', $selectedBatch) }}">
        <div class="w-full px-4 pb-20 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @if ($errors->has('batch'))
                <div class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first('batch') }}</div>
            @endif

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="mb-2 flex flex-wrap gap-2">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $selectedBatch->typeLabel() }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $selectedBatchBadge['classes'] }}">{{ $selectedBatchBadge['label'] }}</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $selectedBatch->source_name }}</h3>
                        <div class="mt-1 text-sm text-gray-500">Subido por {{ $selectedBatch->uploadedBy?->name ?? 'Sistema' }} el {{ $selectedBatch->created_at?->format('d/m/Y H:i') }}</div>
                        @if ($selectedBatch->isExecuted() && $selectedBatch->executed_at)
                            <div class="mt-1 text-sm text-gray-500">Ejecutado por {{ $selectedBatch->executedBy?->name ?? 'Sistema' }} el {{ $selectedBatch->executed_at->format('d/m/Y H:i') }}</div>
                        @elseif ($selectedBatch->isProcessing() && $selectedBatch->started_at)
                            <div class="mt-1 text-sm text-gray-500">Iniciado por {{ $selectedBatch->executedBy?->name ?? 'Sistema' }} el {{ $selectedBatch->started_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>

                    @if ($selectedBatch->isDraft())
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('weapon-imports.show', ['weaponImportBatch' => $selectedBatch->id, 'preview' => 1]) }}" class="inline-flex items-center rounded-md border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">Revisar lote</a>
                            <form method="POST" action="{{ route('weapon-imports.execute', $selectedBatch) }}" class="weapon-import-execute-form" data-batch-name="{{ $selectedBatch->source_name }}" data-start-url="{{ route('weapon-imports.start', $selectedBatch) }}" data-process-url="{{ route('weapon-imports.process', $selectedBatch) }}" data-status-url="{{ route('weapon-imports.status', $selectedBatch) }}" data-redirect-url="{{ route('weapon-imports.show', $selectedBatch) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-gray-300" @disabled($selectedBatch->hasErrors())>Ejecutar</button>
                            </form>
                            <form method="POST" action="{{ route('weapon-imports.discard', $selectedBatch) }}" class="weapon-import-discard-form">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-md border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">Cancelar carga</button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg bg-blue-50 px-4 py-3"><div class="text-xs font-semibold uppercase tracking-wide text-blue-700">Crear</div><div class="mt-1 text-2xl font-semibold text-blue-900">{{ $selectedBatch->create_count }}</div></div>
                    <div class="rounded-lg bg-amber-50 px-4 py-3"><div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Actualizar</div><div class="mt-1 text-2xl font-semibold text-amber-900">{{ $selectedBatch->update_count }}</div></div>
                    <div class="rounded-lg bg-green-50 px-4 py-3"><div class="text-xs font-semibold uppercase tracking-wide text-green-700">Sin cambios</div><div class="mt-1 text-2xl font-semibold text-green-900">{{ $selectedBatch->no_change_count }}</div></div>
                    <div class="rounded-lg bg-rose-50 px-4 py-3"><div class="text-xs font-semibold uppercase tracking-wide text-rose-700">Errores</div><div class="mt-1 text-2xl font-semibold text-rose-900">{{ $selectedBatch->error_count }}</div></div>
                </div>

                @if ($selectedBatch->isProcessing())
                    <div class="mt-5 rounded-lg border border-blue-100 bg-blue-50 px-4 py-5 text-sm text-blue-800">El lote se está ejecutando. Puedes seguir el avance en el panel de progreso.</div>
                @elseif ($selectedBatch->isFailed())
                    <div class="mt-5 rounded-lg border border-rose-100 bg-rose-50 px-4 py-5 text-sm text-rose-700">{{ $selectedBatch->last_error ?: 'La ejecución del lote falló.' }}</div>
                @endif

                @if ($openPreview || $selectedBatch->isExecuted() || $selectedBatch->isFailed() || $selectedBatch->isDraft())
                    <div class="mt-6 overflow-x-auto rounded-lg border border-gray-200 bg-white">
                        @if ($selectedBatch->isClientImport())
                            <table class="min-w-full divide-y divide-gray-200 text-sm min-w-[980px]">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Fila</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Acción</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">NIT./CC</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Razón social</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Representante legal</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Dirección principal</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Ciudad</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Observación</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($selectedBatch->rows as $row)
                                        @php
                                            $raw = $row->raw_payload ?? [];
                                            $normalized = $row->normalized_payload ?? [];
                                        @endphp
                                        <tr class="{{ $row->action === 'error' ? 'bg-rose-50' : ($row->action === 'create' ? 'bg-blue-50' : ($row->action === 'update' ? 'bg-amber-50' : 'bg-green-50')) }}">
                                            <td class="px-3 py-2 font-medium text-gray-800">{{ $row->row_number }}</td>
                                            <td class="px-3 py-2">{{ $row->actionLabel() }}</td>
                                            <td class="px-3 py-2">{{ $normalized['nit'] ?? $raw['nit'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $normalized['name'] ?? $raw['name'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $normalized['legal_representative'] ?? $raw['legal_representative'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $normalized['address'] ?? $raw['address'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $normalized['city'] ?? $raw['city'] ?? '-' }}</td>
                                            <td class="px-3 py-2 text-gray-700">
                                                <div>{{ $row->summary ?: '-' }}</div>
                                                @if (!empty($row->errors))
                                                    <div class="mt-1 text-xs text-rose-700">{{ implode(' ', $row->errors) }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <table class="min-w-full divide-y divide-gray-200 text-sm min-w-[1200px]">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Fila</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Acción</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Tipo</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Marca</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Serie</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Calibre</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Capacidad</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Tipo de permiso</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">No. de permiso</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Fecha de vencimiento</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">Observación</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($selectedBatch->rows as $row)
                                        @php
                                            $raw = $row->raw_payload ?? [];
                                            $normalized = $row->normalized_payload ?? [];
                                            $dateValue = $normalized['permit_expires_at'] ?? null;
                                            $formattedDate = $dateValue ? \Illuminate\Support\Carbon::parse($dateValue)->format('d/m/Y') : ($raw['permit_expires_at'] ?? '');
                                        @endphp
                                        <tr class="{{ $row->action === 'error' ? 'bg-rose-50' : ($row->action === 'create' ? 'bg-blue-50' : ($row->action === 'update' ? 'bg-amber-50' : 'bg-green-50')) }}">
                                            <td class="px-3 py-2 font-medium text-gray-800">{{ $row->row_number }}</td>
                                            <td class="px-3 py-2">{{ $row->actionLabel() }}</td>
                                            <td class="px-3 py-2">{{ $normalized['weapon_type'] ?? $raw['weapon_type'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $normalized['brand'] ?? $raw['brand'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $normalized['serial_number'] ?? $raw['serial_number'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $normalized['caliber'] ?? $raw['caliber'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $normalized['capacity'] ?? $raw['capacity'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $normalized['permit_type'] ?? $raw['permit_type'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $normalized['permit_number'] ?? $raw['permit_number'] ?? '-' }}</td>
                                            <td class="px-3 py-2">{{ $formattedDate ?: '-' }}</td>
                                            <td class="px-3 py-2 text-gray-700">
                                                <div>{{ $row->summary ?: '-' }}</div>
                                                @if (!empty($row->errors))
                                                    <div class="mt-1 text-xs text-rose-700">{{ implode(' ', $row->errors) }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endif
            </div>

            <div id="weapon-import-execution-panel" class="{{ $selectedBatch->isProcessing() ? '' : 'hidden' }} fixed bottom-6 right-6 z-[5500] w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
                <div class="text-base font-semibold text-slate-900">Ejecutando lote</div>
                <div id="weapon-import-execution-subtitle" class="mt-1 text-sm text-slate-500">{{ $selectedBatch->source_name }}</div>
                <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-200"><div id="weapon-import-execution-fill" class="h-full w-0 rounded-full bg-indigo-600"></div></div>
                <div class="mt-3 flex items-center justify-between text-sm text-slate-600"><span id="weapon-import-execution-left">0 / 0 filas</span><span id="weapon-import-execution-right">Calculando ETA...</span></div>
                <div class="mt-4 grid grid-cols-3 gap-3 text-center text-sm">
                    <div class="rounded-xl bg-slate-50 p-3"><div class="text-slate-500">Procesadas</div><div id="weapon-import-execution-processed" class="mt-1 text-lg font-semibold text-slate-900">0</div></div>
                    <div class="rounded-xl bg-slate-50 p-3"><div class="text-slate-500">Correctas</div><div id="weapon-import-execution-successful" class="mt-1 text-lg font-semibold text-slate-900">0</div></div>
                    <div class="rounded-xl bg-slate-50 p-3"><div class="text-slate-500">Fallidas</div><div id="weapon-import-execution-failed" class="mt-1 text-lg font-semibold text-slate-900">0</div></div>
                </div>
                <div id="weapon-import-execution-message" class="mt-4 text-sm text-slate-600">Esperando inicio...</div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const page = document.querySelector('[data-weapon-import-page]');
    const panel = document.getElementById('weapon-import-execution-panel');
    const fill = document.getElementById('weapon-import-execution-fill');
    const left = document.getElementById('weapon-import-execution-left');
    const right = document.getElementById('weapon-import-execution-right');
    const processed = document.getElementById('weapon-import-execution-processed');
    const successful = document.getElementById('weapon-import-execution-successful');
    const failed = document.getElementById('weapon-import-execution-failed');
    const message = document.getElementById('weapon-import-execution-message');
    const forms = Array.from(document.querySelectorAll('.weapon-import-execute-form'));
    const discardButtons = Array.from(document.querySelectorAll('.weapon-import-discard-form button[type="submit"]'));

    if (!page || !panel || !fill || !left || !right || !processed || !successful || !failed || !message) return;

    const state = {
        running: false,
        statusUrl: page.dataset.selectedBatchStatusUrl || '',
        redirectUrl: page.dataset.selectedBatchRedirectUrl || '',
        sourceName: page.dataset.selectedBatchName || 'Lote'
    };

    const formatDuration = (seconds) => {
        if (!seconds || seconds <= 0) return 'Calculando...';
        const mins = Math.floor(seconds / 60);
        const secs = Math.round(seconds % 60);
        if (mins <= 0) return `${secs}s`;
        if (secs <= 0) return `${mins}m`;
        return `${mins}m ${secs}s`;
    };

    const setRunning = (running) => {
        state.running = running;
        panel.classList.toggle('hidden', !running);
        forms.forEach((form) => {
            const button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = running || button.hasAttribute('data-force-disabled');
        });
        discardButtons.forEach((button) => button.disabled = running);
    };

    const renderProgress = (progress) => {
        const total = Number(progress.total_rows || 0);
        const processedRows = Number(progress.processed_rows || 0);
        const successfulRows = Number(progress.successful_rows || 0);
        const failedRows = Number(progress.failed_rows || 0);
        const percentage = total > 0 ? Math.round((processedRows / total) * 100) : 0;

        fill.style.width = `${percentage}%`;
        left.textContent = `${processedRows} / ${total} filas`;
        right.textContent = progress.status === 'processing' ? `ETA ${formatDuration(progress.eta_seconds)}` : (progress.status === 'executed' ? 'Completado' : 'Proceso detenido');
        processed.textContent = processedRows;
        successful.textContent = successfulRows;
        failed.textContent = failedRows;
        message.textContent = progress.status === 'failed'
            ? (progress.last_error || 'La ejecución del lote se detuvo.')
            : (progress.status === 'executed' ? 'Carga completada. Redirigiendo...' : 'El lote se está procesando. Puedes cambiar de pestaña mientras termina.');
    };

    const pollStatus = async () => {
        if (!state.statusUrl) return;

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
                setTimeout(() => { window.location.href = state.redirectUrl; }, 800);
                return;
            }

            if (payload.progress.status === 'processing') {
                window.setTimeout(pollStatus, 1500);
                return;
            }

            setRunning(false);
        } catch {
            window.setTimeout(pollStatus, 2000);
        }
    };

    const startExecution = async (form) => {
        setRunning(true);
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
                setRunning(false);
                return;
            }

            state.statusUrl = payload.status_url || state.statusUrl;
            state.redirectUrl = payload.redirect_url || state.redirectUrl;
            renderProgress(payload.progress);
            pollStatus();
        } catch {
            message.textContent = 'No se pudo iniciar la ejecución del lote.';
            setRunning(false);
        }
    };

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (state.running) return;
            startExecution(form);
        });
    });

    if (page.dataset.selectedBatchStatus === 'processing') {
        setRunning(true);
        pollStatus();
    }
})();
</script>

