@push('styles')
    <style>
        .mass-import-progress { display: none; gap: 0.75rem; border: 1px solid #dbeafe; border-radius: 0.9rem; background: #eff6ff; padding: 1rem; }
        .mass-import-progress.is-visible { display: grid; }
        .mass-import-progress__top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .mass-import-progress__title { color: #1e3a8a; font-size: 0.95rem; font-weight: 700; }
        .mass-import-progress__meta { color: #475569; font-size: 0.85rem; font-weight: 600; white-space: nowrap; }
        .mass-import-progress__bar { width: 100%; height: 0.75rem; overflow: hidden; border-radius: 999px; background: rgba(148, 163, 184, 0.28); }
        .mass-import-progress__fill { height: 100%; width: 0%; border-radius: inherit; background: linear-gradient(90deg, #2563eb 0%, #0ea5e9 100%); transition: width 0.25s ease; }
        .mass-import-progress__fill.is-indeterminate { width: 35%; animation: mass-import-progress-slide 1.25s ease-in-out infinite; }
        .mass-import-progress__details { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 0.75rem; color: #475569; font-size: 0.85rem; }

        @keyframes mass-import-progress-slide {
            0% { transform: translateX(-120%); }
            100% { transform: translateX(320%); }
        }
    </style>
@endpush

@push('scripts')
<script>
(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const input = document.getElementById('mass-import-document');
    const typeInput = document.getElementById('mass-import-type');
    const title = document.getElementById('mass-import-title');
    const copy = document.getElementById('mass-import-copy');
    const dropzoneTitle = document.getElementById('mass-import-dropzone-title');
    const dropzone = document.getElementById('mass-import-dropzone');
    const fileName = document.getElementById('mass-import-file-name');
    const submit = document.getElementById('mass-import-submit');
    const uploadModal = document.getElementById('mass-import-upload-modal');
    const uploadForm = document.getElementById('mass-import-upload-form');
    const uploadError = document.getElementById('mass-import-upload-error');
    const uploadProgress = document.getElementById('mass-import-upload-progress');
    const uploadProgressTitle = document.getElementById('mass-import-upload-progress-title');
    const uploadProgressMeta = document.getElementById('mass-import-upload-progress-meta');
    const uploadProgressFill = document.getElementById('mass-import-upload-progress-fill');
    const uploadDetailLeft = document.getElementById('mass-import-upload-progress-detail-left');
    const uploadDetailRight = document.getElementById('mass-import-upload-progress-detail-right');
    const clientFormat = document.getElementById('mass-import-client-format');
    const triggers = Array.from(document.querySelectorAll('[data-import-trigger]'));

    if (!input || !typeInput || !title || !copy || !dropzoneTitle || !dropzone || !fileName || !submit || !uploadModal || !uploadForm) {
        return;
    }

    const typeConfig = {
        weapon: {
            title: 'Subir documento de armas',
            copy: 'Carga masiva con validacion previa antes de crear o actualizar armas.',
            dropzone: 'Arrastra el documento aqui',
        },
        client: {
            title: 'Subir documento de clientes',
            copy: 'Carga el archivo base de clientes para crear o actualizar el registro minimo.',
            dropzone: 'Arrastra el documento aqui',
        },
    };

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

    const setUploadProgress = ({ visible = false, title: progressTitle = 'Subiendo archivo...', percent = null, left = '', right = '', indeterminate = false }) => {
        uploadProgress?.classList.toggle('is-visible', visible);
        if (!uploadProgressFill || !uploadProgressTitle || !uploadProgressMeta || !uploadDetailLeft || !uploadDetailRight) return;
        uploadProgressTitle.textContent = progressTitle;
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

    const applyType = (type) => {
        const normalizedType = Object.prototype.hasOwnProperty.call(typeConfig, type) ? type : 'weapon';
        const config = typeConfig[normalizedType];
        typeInput.value = normalizedType;
        title.textContent = config.title;
        copy.textContent = config.copy;
        dropzoneTitle.textContent = config.dropzone;
        clientFormat?.classList.toggle('hidden', normalizedType !== 'client');
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            applyType(trigger.dataset.importType || 'weapon');
            setFile(null);
            setUploadError('');
            setUploadProgress({ visible: false });
        });
    });

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
        if (file) {
            setFile(file);
        }
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
                setUploadProgress({ visible: true, percent: null, left: 'Transferencia en curso', right: 'Calculando...', indeterminate: true });
                return;
            }

            const progress = Math.max(1, Math.min(100, Math.round((progressEvent.loaded / progressEvent.total) * 100)));
            const elapsedSeconds = Math.max(1, (Date.now() - startedAt) / 1000);
            const bytesPerSecond = progressEvent.loaded / elapsedSeconds;
            const remainingSeconds = bytesPerSecond > 0 ? (progressEvent.total - progressEvent.loaded) / bytesPerSecond : 0;
            setUploadProgress({ visible: true, percent: progress, left: file.name, right: `Faltan ${formatDuration(remainingSeconds)}` });
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

    applyType(typeInput.value || 'weapon');
})();
</script>
@endpush

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Centro de cargas masivas</h2>
                <p class="mt-1 text-sm text-gray-500">Carga y procesa informacion en lote</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row lg:justify-end">
                <button type="button" data-import-trigger data-import-type="weapon" x-data="" x-on:click.prevent="$dispatch('open-modal', 'mass-import-upload')" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    Subir armas
                </button>
                <button type="button" data-import-trigger data-import-type="client" x-data="" x-on:click.prevent="$dispatch('open-modal', 'mass-import-upload')" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Subir clientes
                </button>
            </div>
        </div>
    </x-slot>

    @php
        $selectedType = old('type', \App\Models\WeaponImportBatch::TYPE_WEAPON);
    @endphp

    <div class="py-8">
        <div class="w-full px-4 pb-20 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @if ($errors->has('batch'))
                <div class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first('batch') }}</div>
            @endif

            <section>
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Historial</div>
                        <h3 class="text-lg font-semibold text-gray-800">Lotes ejecutados</h3>
                    </div>
                    <div class="text-sm text-gray-500">{{ $batches->count() }} lote(s)</div>
                </div>

                @if ($batches->isNotEmpty())
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($batches as $batch)
                            <a href="{{ route('weapon-imports.show', $batch) }}" class="block rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-base font-semibold text-gray-800">{{ $batch->source_name }}</div>
                                        <div class="mt-1 text-sm text-gray-500">{{ $batch->created_at?->format('d/m/Y H:i') }}</div>
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Ejecutado</span>
                                        <span class="rounded-full {{ $batch->isClientImport() ? 'bg-slate-100 text-slate-700' : 'bg-indigo-100 text-indigo-700' }} px-2.5 py-1 text-xs font-semibold">{{ $batch->typeLabel() }}</span>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-3 text-sm text-gray-600">
                                    <div><div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total</div><div class="mt-1 font-semibold text-gray-800">{{ $batch->total_rows }}</div></div>
                                    <div><div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Errores</div><div class="mt-1 font-semibold text-gray-800">{{ $batch->error_count }}</div></div>
                                    <div><div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Crear</div><div class="mt-1 font-semibold text-blue-700">{{ $batch->create_count }}</div></div>
                                    <div><div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Actualizar</div><div class="mt-1 font-semibold text-amber-700">{{ $batch->update_count }}</div></div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center text-gray-500 shadow-sm">Aun no hay lotes ejecutados. Sube tu primer archivo para revisar y ejecutar cambios masivos.</div>
                @endif
            </section>
        </div>
    </div>

    <x-modal name="mass-import-upload" :show="$errors->has('document')" maxWidth="2xl" focusable>
        <div id="mass-import-upload-modal" class="p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="mass-import-title" class="text-lg font-semibold text-gray-800">Subir documento de armas</h3>
                    <p id="mass-import-copy" class="mt-1 text-sm text-gray-500">Carga masiva con validacion previa antes de crear o actualizar armas.</p>
                </div>
                <button type="button" x-data="" x-on:click.prevent="$dispatch('close-modal', 'mass-import-upload')" class="rounded-md px-2 py-1 text-sm text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                    Cerrar
                </button>
            </div>

            <form id="mass-import-upload-form" method="POST" action="{{ route('weapon-imports.preview') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf
                <input id="mass-import-type" type="hidden" name="type" value="{{ $selectedType }}">
                <input id="mass-import-document" type="file" name="document" accept=".xlsx,.csv,.txt" class="hidden" required>

                <label for="mass-import-document" id="mass-import-dropzone" class="flex min-h-[15rem] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center transition hover:border-indigo-300 hover:bg-indigo-50/40">
                    <div class="rounded-full bg-white p-4 text-indigo-600 shadow-sm">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M12 16V4" />
                            <path d="M8 8l4-4 4 4" />
                            <path d="M4 16.5v1.5A2 2 0 006 20h12a2 2 0 002-2v-1.5" />
                        </svg>
                    </div>
                    <div id="mass-import-dropzone-title" class="mt-4 text-base font-semibold text-gray-800">Arrastra el documento aqui</div>
                    <div class="mt-2 text-sm text-gray-500">Tambien puedes pegar el archivo con Ctrl + V o seleccionarlo manualmente.</div>
                    <div class="mt-5 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm">
                        Seleccionar de este equipo
                    </div>
                    <div id="mass-import-file-name" class="mt-4 text-sm font-medium text-indigo-700"></div>
                </label>

                <div id="mass-import-upload-error" class="hidden rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

                <div id="mass-import-upload-progress" class="mass-import-progress" aria-live="polite">
                    <div class="mass-import-progress__top">
                        <div id="mass-import-upload-progress-title" class="mass-import-progress__title">Subiendo archivo...</div>
                        <div id="mass-import-upload-progress-meta" class="mass-import-progress__meta">0%</div>
                    </div>
                    <div class="mass-import-progress__bar">
                        <div id="mass-import-upload-progress-fill" class="mass-import-progress__fill"></div>
                    </div>
                    <div class="mass-import-progress__details">
                        <span id="mass-import-upload-progress-detail-left">Esperando archivo</span>
                        <span id="mass-import-upload-progress-detail-right"></span>
                    </div>
                </div>

                <x-input-error :messages="$errors->get('document')" class="mt-2" />

                <div id="mass-import-client-format" class="hidden rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <div class="font-semibold text-slate-800">Formato minimo para clientes</div>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        <span>NIT./CC</span>
                        <span>RAZON SOCIAL</span>
                        <span>NOMBRE REP. LEGAL</span>
                        <span>DIRECCION PRINCIPAL</span>
                        <span>CIUDAD</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" x-data="" x-on:click.prevent="$dispatch('close-modal', 'mass-import-upload')" class="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 hover:text-gray-800">
                        Cancelar
                    </button>
                    <x-primary-button id="mass-import-submit" disabled>Subir</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>
