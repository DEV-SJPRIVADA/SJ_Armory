@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
    <style>
        .sj-paste-hint-zone { position: relative; }
        .sj-paste-proxy {
            position: absolute;
            inset: 0;
            z-index: 20;
            background: transparent;
            border: 0;
            color: transparent;
            caret-color: transparent;
            opacity: 0;
            font-size: 1px;
            line-height: 1;
            padding: 0;
            margin: 0;
            outline: none;
            user-select: none;
            -webkit-user-select: none;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .sj-paste-proxy::selection { background: transparent; }
        .sj-pa-surface { transition: border-color 0.15s ease, background-color 0.15s ease; }
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
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
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

(() => {
    const modalRoot = document.getElementById('permit-authenticated-upload-modal');
    if (!modalRoot) {
        return;
    }

    const kinds = ['porte', 'tenencia'];
    const roots = {};

    kinds.forEach((kind) => {
        roots[kind] = {
            kind,
            input: document.getElementById(`pa-input-${kind}`),
            img: document.getElementById(`pa-img-${kind}`),
            empty: document.getElementById(`pa-placeholder-${kind}`),
            btn: document.getElementById(`pa-btn-${kind}`),
            surface: document.querySelector(`[data-pa-surface="${kind}"]`),
            zone: document.querySelector(`[data-pa-zone="${kind}"]`),
            pasteProxy: document.getElementById(`pa-paste-proxy-${kind}`),
            _objUrl: null,
        };
    });

    let hoverKind = null;
    let pendingTarget = null;
    let editorCropper = null;
    let editorObjectUrl = null;

    const editorModal = document.getElementById('permit-auth-editor-modal');
    const editorImage = document.getElementById('permit-auth-editor-image');
    const editorClose = document.getElementById('permit-auth-editor-close');
    const editorCancel = document.getElementById('permit-auth-editor-cancel');
    const editorApply = document.getElementById('permit-auth-editor-apply');
    const editorRotateLeft = document.getElementById('permit-auth-editor-rotate-left');
    const editorRotateRight = document.getElementById('permit-auth-editor-rotate-right');
    const editorFineInput = document.getElementById('permit-auth-editor-rotate-fine');
    const editorFineValue = document.getElementById('permit-auth-editor-rotate-value');
    const editorReset = document.getElementById('permit-auth-editor-rotate-reset');

    let editorFineRotation = 0;

    const isPermitAuthModalVisible = () => {
        let el = modalRoot;
        while (el) {
            const st = el.style?.display;
            if (st === 'none') {
                return false;
            }
            const cs = window.getComputedStyle(el);
            if (cs.display === 'none' || cs.visibility === 'hidden' || cs.opacity === '0') {
                return false;
            }
            el = el.parentElement;
        }
        return true;
    };

    const syncFineUi = () => {
        if (editorFineInput) {
            editorFineInput.value = editorFineRotation.toString();
        }
        if (editorFineValue) {
            editorFineValue.textContent = `${editorFineRotation.toFixed(1)}°`;
        }
    };

    const closeEditor = () => {
        if (editorCropper) {
            editorCropper.destroy();
            editorCropper = null;
        }
        if (editorObjectUrl) {
            URL.revokeObjectURL(editorObjectUrl);
            editorObjectUrl = null;
        }
        if (editorImage) {
            editorImage.removeAttribute('src');
        }
        editorFineRotation = 0;
        syncFineUi();
        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'permit-auth-editor' }));
    };

    const assignFileToZone = (cfg, file) => {
        if (!cfg?.input || !file || !file.type.startsWith('image/')) {
            return;
        }
        if (cfg._objUrl) {
            URL.revokeObjectURL(cfg._objUrl);
        }
        cfg._objUrl = URL.createObjectURL(file);
        const dt = new DataTransfer();
        dt.items.add(file);
        cfg.input.files = dt.files;
        if (cfg.img) {
            cfg.img.src = cfg._objUrl;
            cfg.img.classList.remove('hidden');
        }
        if (cfg.empty) {
            cfg.empty.classList.add('hidden');
        }
        if (cfg.btn) {
            cfg.btn.disabled = false;
        }
    };

    const openEditor = (cfg, file) => {
        if (!cfg || !file || !editorModal || !editorImage) {
            return;
        }
        if (!file.type.startsWith('image/')) {
            return;
        }
        pendingTarget = cfg;
        if (editorObjectUrl) {
            URL.revokeObjectURL(editorObjectUrl);
        }
        editorObjectUrl = URL.createObjectURL(file);
        editorImage.src = editorObjectUrl;
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'permit-auth-editor' }));
        editorFineRotation = 0;
        syncFineUi();

        if (editorCropper) {
            editorCropper.destroy();
        }

        editorCropper = new Cropper(editorImage, {
            viewMode: 0,
            autoCropArea: 1,
            toggleDragModeOnDblclick: false,
            responsive: true,
        });
    };

    kinds.forEach((kind) => {
        const cfg = roots[kind];
        if (!cfg.zone || !cfg.input || !cfg.surface) {
            return;
        }

        cfg.zone.addEventListener('mouseenter', () => {
            hoverKind = kind;
        });
        cfg.zone.addEventListener('mouseleave', () => {
            if (hoverKind === kind) {
                hoverKind = null;
            }
        });

        cfg.input.addEventListener('change', () => {
            const file = cfg.input.files && cfg.input.files[0] ? cfg.input.files[0] : null;
            if (file) {
                openEditor(cfg, file);
            }
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            cfg.surface.addEventListener(eventName, (event) => {
                event.preventDefault();
                cfg.surface.classList.add('border-indigo-400', 'bg-indigo-50');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
            cfg.surface.addEventListener(eventName, (event) => {
                event.preventDefault();
                cfg.surface.classList.remove('border-indigo-400', 'bg-indigo-50');
            });
        });

        cfg.surface.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];
            if (file) {
                openEditor(cfg, file);
            }
        });

        if (cfg.pasteProxy) {
            cfg.zone.addEventListener('mouseenter', () => {
                hoverKind = kind;
            });
            cfg.zone.addEventListener('mouseleave', () => {
                if (hoverKind === kind) {
                    hoverKind = null;
                }
            });
            cfg.pasteProxy.addEventListener('focus', () => {
                hoverKind = kind;
            });
            cfg.pasteProxy.addEventListener('mousedown', (event) => {
                hoverKind = kind;
                if (event.button === 0) {
                    event.preventDefault();
                    cfg.input.click();
                }
            });
            cfg.pasteProxy.addEventListener('paste', (event) => {
                const items = Array.from(event.clipboardData?.items || []);
                const imageItem = items.find((item) => item.kind === 'file' && item.type.startsWith('image/'));
                const file = imageItem ? imageItem.getAsFile() : null;
                if (!file) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                openEditor(cfg, file);
                cfg.pasteProxy.textContent = '';
            });
        }
    });

    window.addEventListener('paste', (event) => {
        if (!isPermitAuthModalVisible() || !hoverKind || !roots[hoverKind]) {
            return;
        }
        const items = Array.from(event.clipboardData?.items || []);
        const imageItem = items.find((item) => item.kind === 'file' && item.type.startsWith('image/'));
        const file = imageItem ? imageItem.getAsFile() : null;
        if (!file) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        openEditor(roots[hoverKind], file);
    });

    editorClose?.addEventListener('click', closeEditor);
    editorCancel?.addEventListener('click', closeEditor);
    editorApply?.addEventListener('click', () => {
        if (!editorCropper || !pendingTarget) {
            closeEditor();
            return;
        }

        editorCropper.getCroppedCanvas().toBlob((blob) => {
            if (!blob) {
                closeEditor();
                return;
            }
            const file = new File([blob], `permit_authenticated_${pendingTarget.kind}.jpg`, { type: blob.type || 'image/jpeg' });
            assignFileToZone(pendingTarget, file);
            closeEditor();
        }, 'image/jpeg', 0.92);
    });

    editorRotateLeft?.addEventListener('click', () => {
        if (!editorCropper) return;
        if (editorFineRotation !== 0) {
            editorCropper.rotate(-editorFineRotation);
            editorFineRotation = 0;
            syncFineUi();
        }
        editorCropper.rotate(-90);
    });

    editorRotateRight?.addEventListener('click', () => {
        if (!editorCropper) return;
        if (editorFineRotation !== 0) {
            editorCropper.rotate(-editorFineRotation);
            editorFineRotation = 0;
            syncFineUi();
        }
        editorCropper.rotate(90);
    });

    editorFineInput?.addEventListener('input', () => {
        if (!editorCropper) return;
        const next = Number.parseFloat(editorFineInput.value || '0') || 0;
        const diff = next - editorFineRotation;
        editorFineRotation = next;
        if (diff !== 0) {
            editorCropper.rotate(diff);
        }
        syncFineUi();
    });

    editorReset?.addEventListener('click', () => {
        if (editorCropper) {
            editorCropper.reset();
        }
        editorFineRotation = 0;
        syncFineUi();
    });
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
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap lg:justify-end">
                <a href="{{ route('weapon-imports.templates.weapon') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    {{ __('Descargar formato armas') }}
                </a>
                <a href="{{ route('weapon-imports.templates.client') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    {{ __('Descargar formato clientes') }}
                </a>
                <button type="button" data-import-trigger data-import-type="weapon" x-data="" x-on:click.prevent="$dispatch('open-modal', 'mass-import-upload')" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    {{ __('Subir armas') }}
                </button>
                <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'permit-authenticated-upload')" class="inline-flex items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-900 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-100">
                    {{ __('Cargar autenticación') }}
                </button>
                <button type="button" data-import-trigger data-import-type="client" x-data="" x-on:click.prevent="$dispatch('open-modal', 'mass-import-upload')" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    {{ __('Subir clientes') }}
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

            @php($permitAuthTemplates = $permitAuthTemplates ?? collect())

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

    <x-modal name="permit-authenticated-upload" :show="$errors->has('photo')" maxWidth="5xl" focusable>
        <div id="permit-authenticated-upload-modal" class="p-6">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Permiso autenticado global') }}</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ __('Dos referencias para todo el sistema (porte y tenencia). Se muestran en las fichas según el tipo de permiso del arma. Arrastra, pega con el puntero sobre la tarjeta o haz clic para elegir archivo.') }}</p>
                </div>
                <button type="button" x-data="" x-on:click.prevent="$dispatch('close-modal', 'permit-authenticated-upload')" class="shrink-0 rounded-md px-2 py-1 text-sm text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                    {{ __('Cerrar') }}
                </button>
            </div>

            @if ($errors->has('photo'))
                <div class="mt-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first('photo') }}</div>
            @endif

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                @foreach (['porte' => __('Porte'), 'tenencia' => __('Tenencia')] as $kind => $label)
                    @php($tpl = $permitAuthTemplates->get($kind))
                    @php($hasFile = (bool) ($tpl?->file))
                    <div class="rounded-xl border border-gray-200 bg-gradient-to-b from-slate-50/80 to-white p-4 shadow-sm sj-paste-hint-zone">
                        <form id="pa-form-{{ $kind }}" method="POST" action="{{ route('weapon-imports.permit-authenticated.update', $kind) }}" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-800">{{ $label }}</span>
                                @if ($hasFile && $tpl?->updated_at)
                                    <span class="text-xs text-gray-500">{{ __('Actualizado :fecha', ['fecha' => $tpl->updated_at->format('d/m/Y H:i')]) }}</span>
                                @endif
                            </div>

                            <div class="sj-paste-hint-zone rounded-lg border border-gray-200 bg-white p-2" data-pa-zone="{{ $kind }}" tabindex="0">
                                <div id="pa-paste-proxy-{{ $kind }}" class="sj-paste-proxy" contenteditable="true" spellcheck="false"></div>
                                <label for="pa-input-{{ $kind }}" class="block cursor-pointer">
                                    <input id="pa-input-{{ $kind }}" name="photo" type="file" accept="image/*" class="hidden">
                                    <div data-pa-surface="{{ $kind }}" class="sj-pa-surface relative flex h-44 w-full items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 transition hover:border-indigo-400 hover:bg-indigo-50/40">
                                        <img
                                            id="pa-img-{{ $kind }}"
                                            src="{{ $hasFile ? route('authenticated-permit-images.show', ['permit_kind' => $kind]) : '' }}"
                                            alt=""
                                            class="{{ $hasFile ? 'block' : 'hidden' }} relative z-0 h-full w-full object-contain p-2"
                                        >
                                        <div
                                            id="pa-placeholder-{{ $kind }}"
                                            class="{{ $hasFile ? 'hidden' : 'flex' }} pointer-events-none relative z-0 min-h-[7rem] w-full flex-col items-center justify-center px-3 text-center"
                                        >
                                            <svg class="mb-2 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-sm font-medium text-gray-600">{{ __('Toca para elegir imagen') }}</span>
                                            <span class="mt-1 text-xs text-gray-400">{{ __('Arrastra, suelta o pega (cursor sobre esta tarjeta)') }}</span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <button type="submit" id="pa-btn-{{ $kind }}" disabled class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-indigo-300">
                                {{ __('Guardar imagen') }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end border-t border-gray-100 pt-4">
                <button type="button" x-data="" x-on:click.prevent="$dispatch('close-modal', 'permit-authenticated-upload')" class="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">
                    {{ __('Listo') }}
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal name="permit-auth-editor" :show="false" maxWidth="4xl" focusable>
        <div id="permit-auth-editor-modal" class="p-4">
        <div class="w-full rounded bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="text-sm font-semibold text-gray-800">{{ __('Editar imagen') }}</h3>
                <button id="permit-auth-editor-close" type="button" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Cerrar') }}</button>
            </div>
            <div class="p-4">
                <div class="h-[70vh] max-h-[70vh] w-full overflow-auto rounded bg-slate-100">
                    <img id="permit-auth-editor-image" alt="Editor" class="block max-h-none max-w-none" />
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 border-t px-4 py-3">
                <div class="flex flex-1 flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <button id="permit-auth-editor-rotate-left" type="button" class="rounded border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-100">{{ __('Girar izquierda') }}</button>
                        <button id="permit-auth-editor-rotate-right" type="button" class="rounded border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-100">{{ __('Girar derecha') }}</button>
                    </div>
                    <div class="flex min-w-[18rem] flex-1 flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-gray-600">{{ __('Ajuste fino') }}</span>
                        <input id="permit-auth-editor-rotate-fine" type="range" min="-10" max="10" step="0.1" value="0" class="h-2 min-w-[10rem] flex-1 cursor-pointer accent-indigo-600">
                        <span id="permit-auth-editor-rotate-value" class="w-14 text-right text-xs font-medium text-gray-600">0.0°</span>
                        <button id="permit-auth-editor-rotate-reset" type="button" class="rounded border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-100">{{ __('Restablecer') }}</button>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="permit-auth-editor-cancel" type="button" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancelar') }}</button>
                    <button id="permit-auth-editor-apply" type="button" class="rounded bg-indigo-600 px-3 py-1 text-xs text-white hover:bg-indigo-700">{{ __('Guardar') }}</button>
                </div>
            </div>
        </div>
        </div>
    </x-modal>

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
