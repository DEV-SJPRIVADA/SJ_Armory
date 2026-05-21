@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
        <style>
            .sj-image-editor-canvas { max-height: min(42dvh, 380px); }
            .sj-image-editor-canvas .cropper-container,
            .sj-image-editor-canvas .cropper-canvas,
            .sj-image-editor-canvas .cropper-wrap-box,
            .sj-image-editor-canvas img { max-height: min(42dvh, 380px) !important; }
            .revista-slot-card { cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
            .revista-slot-card:hover { border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(11, 111, 182, 0.12); }
            .revista-slot-card:disabled { cursor: wait; opacity: 0.65; pointer-events: none; }
        </style>
    @endpush
@endonce

<div id="revista_photo_source_modal" class="fixed inset-0 z-[1060] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-sm rounded-xl bg-white shadow-lg">
        <div class="border-b px-4 py-3 text-sm font-semibold text-gray-800">{{ __('Agregar imagen') }}</div>
        <div class="space-y-2 p-4">
            <button type="button" data-revista-source="camera" class="w-full rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-sm font-medium text-indigo-900">{{ __('Tomar foto') }}</button>
            <button type="button" data-revista-source="gallery" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-700">{{ __('Elegir de galería') }}</button>
        </div>
        <div class="flex justify-end border-t px-4 py-2">
            <button type="button" data-revista-source-cancel class="text-sm text-gray-600">{{ __('Cancelar') }}</button>
        </div>
    </div>
</div>

<div id="revista_image_editor_modal" class="fixed inset-0 z-[1070] hidden items-center justify-center overflow-hidden bg-black/50 p-2 sm:p-4">
    <div class="sj-image-editor-panel flex max-h-[calc(100dvh-0.5rem)] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-lg">
        <div class="flex shrink-0 items-center justify-between border-b px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-800">{{ __('Editar imagen') }}</h3>
            <button type="button" data-revista-editor-close class="text-sm text-gray-500">{{ __('Cerrar') }}</button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto p-3 sm:p-4">
            <div class="sj-image-editor-canvas w-full overflow-auto rounded bg-gray-50">
                <img id="revista_image_editor_image" alt="" class="mx-auto w-full max-w-full object-contain">
            </div>
            <p id="revista-editor-status" class="mt-2 hidden text-center text-xs text-slate-500"></p>
        </div>
        <div class="shrink-0 border-t bg-white pb-[max(0.75rem,env(safe-area-inset-bottom))]">
            <div class="flex gap-2 px-3 py-3 sm:px-4">
                <button type="button" data-revista-editor-cancel class="min-h-11 flex-1 rounded-md border border-gray-300 px-3 py-2.5 text-sm font-medium text-gray-700">{{ __('Cancelar') }}</button>
                <button type="button" data-revista-editor-save class="min-h-11 flex-1 rounded-md bg-indigo-600 px-3 py-2.5 text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-60">{{ __('Guardar') }}</button>
            </div>
        </div>
    </div>
</div>

<div id="revista-photo-alert-modal" class="fixed inset-0 z-[1080] hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl" role="alertdialog" aria-modal="true">
        <h3 class="text-lg font-bold text-slate-900">{{ __('Aviso') }}</h3>
        <p id="revista-photo-alert-message" class="mt-3 text-sm text-slate-600"></p>
        <div class="mt-5 flex justify-end">
            <button type="button" id="revista-photo-alert-ok" class="rounded-lg bg-[#0b6fb6] px-4 py-2 text-sm font-bold text-white">{{ __('Entendido') }}</button>
        </div>
    </div>
</div>

<div
    id="revista-photo-toast"
    class="pointer-events-none fixed inset-x-4 bottom-[max(1rem,env(safe-area-inset-bottom))] z-[1085] mx-auto hidden max-w-md rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-semibold text-emerald-800 shadow-lg sm:inset-x-auto sm:right-4 sm:mx-0"
    role="status"
    aria-live="polite"
></div>

<input id="revista_photo_pick_gallery" type="file" accept="image/jpeg,image/png,image/webp,image/*" class="hidden">
<input id="revista_photo_pick_camera" type="file" accept="image/jpeg,image/png,image/webp,image/*" capture="environment" class="hidden">

@once
    @push('scripts')
        <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
        <script>
        window.initRevistaPhotoCapture = (config) => {
            const MAX_EXPORT_WIDTH = 1920;
            const MAX_EXPORT_HEIGHT = 1920;
            const JPEG_QUALITY = 0.88;
            const TOAST_MS = 4500;

            const sourceModal = document.getElementById('revista_photo_source_modal');
            const editorModal = document.getElementById('revista_image_editor_modal');
            const editorImage = document.getElementById('revista_image_editor_image');
            const editorStatus = document.getElementById('revista-editor-status');
            const editorSaveBtn = document.querySelector('[data-revista-editor-save]');
            const editorCloseBtn = document.querySelector('[data-revista-editor-close]');
            const editorCancelBtn = document.querySelector('[data-revista-editor-cancel]');
            const pickGallery = document.getElementById('revista_photo_pick_gallery');
            const pickCamera = document.getElementById('revista_photo_pick_camera');
            const photoAlertModal = document.getElementById('revista-photo-alert-modal');
            const photoAlertMessage = document.getElementById('revista-photo-alert-message');
            const photoAlertOk = document.getElementById('revista-photo-alert-ok');
            const photoToast = document.getElementById('revista-photo-toast');

            const csrfToken = config.csrfToken;
            const txtSaving = @json(__('Guardando…'));
            const txtSave = @json(__('Guardar'));
            const txtPreparing = @json(__('Preparando imagen…'));
            const txtSaved = @json(__('Imagen guardada'));
            const txtGenericError = @json(__('No se pudo guardar la imagen.'));
            const txtCanvasError = @json(__('No se pudo procesar la imagen en este dispositivo. Intente otra foto o reduzca el zoom.'));
            const txtNetworkError = @json(__('Sin conexión o la subida tardó demasiado. Espere y vuelva a intentar una sola vez.'));
            const txtSessionError = @json(__('Su sesión expiró. Salga y vuelva a ingresar con el código de acceso.'));
            const txtSizeError = @json(__('La imagen es demasiado pesada. Se comprimirá automáticamente; si persiste, use otra foto.'));
            const txtInvalidImage = @json(__('Solo puede usar archivos de imagen (JPG, PNG o WebP).'));

            let storeUrl = '';
            let activeDescription = '';
            let cropper = null;
            let objectUrl = null;
            let isUploading = false;
            let toastTimer = null;

            const csrfHeader = () => document.querySelector('meta[name="csrf-token"]')?.content || csrfToken;

            const openModal = (el) => { el?.classList.remove('hidden'); el?.classList.add('flex'); };
            const closeModal = (el) => { el?.classList.add('hidden'); el?.classList.remove('flex'); };

            const setEditorStatus = (text, visible = true) => {
                if (!editorStatus) return;
                editorStatus.textContent = text;
                editorStatus.classList.toggle('hidden', !visible || !text);
            };

            const setSavingState = (active) => {
                isUploading = active;
                if (editorSaveBtn) {
                    editorSaveBtn.disabled = active;
                    editorSaveBtn.textContent = active ? txtSaving : txtSave;
                }
                [editorCloseBtn, editorCancelBtn].forEach((btn) => {
                    if (btn) btn.disabled = active;
                });
                document.querySelectorAll('[data-revista-source]').forEach((btn) => {
                    btn.disabled = active;
                });
                config.onUploadingChange?.(active);
            };

            const showAlert = (message) => {
                if (photoAlertMessage) photoAlertMessage.textContent = message;
                openModal(photoAlertModal);
            };

            photoAlertOk?.addEventListener('click', () => closeModal(photoAlertModal));

            const showToast = (message) => {
                if (!photoToast) return;
                photoToast.textContent = message;
                photoToast.classList.remove('hidden');
                if (toastTimer) clearTimeout(toastTimer);
                toastTimer = setTimeout(() => {
                    photoToast.classList.add('hidden');
                    toastTimer = null;
                }, TOAST_MS);
            };

            const destroyCropper = () => {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }
                editorImage?.removeAttribute('src');
            };

            const closeEditor = () => {
                destroyCropper();
                closeModal(editorModal);
                setEditorStatus('', false);
                setSavingState(false);
            };

            const parseUploadError = async (response) => {
                const contentType = response.headers.get('content-type') || '';
                if (response.status === 419) {
                    return txtSessionError;
                }
                if (response.status === 403) {
                    return @json(__('No tiene permiso para subir fotos de esta arma.'));
                }
                if (contentType.includes('application/json')) {
                    try {
                        const data = await response.json();
                        const first = data?.message
                            || data?.errors?.photo?.[0]
                            || data?.errors?.description?.[0]
                            || Object.values(data?.errors || {}).flat()?.[0];
                        if (first) {
                            return String(first);
                        }
                    } catch (e) {
                        /* ignore */
                    }
                }
                if (response.status === 422) {
                    return txtSizeError;
                }
                return txtGenericError;
            };

            const uploadBlob = async (blob) => {
                const formData = new FormData();
                formData.append('photo', blob, 'revista.jpg');
                formData.append('description', activeDescription);

                let response;
                try {
                    response = await fetch(storeUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfHeader(),
                            'Accept': 'application/json',
                        },
                        body: formData,
                        credentials: 'same-origin',
                    });
                } catch (e) {
                    throw new Error(txtNetworkError);
                }

                if (!response.ok) {
                    throw new Error(await parseUploadError(response));
                }

                return response.json();
            };

            const exportCroppedBlob = () => new Promise((resolve, reject) => {
                if (!cropper) {
                    reject(new Error(txtCanvasError));
                    return;
                }

                const canvas = cropper.getCroppedCanvas({
                    maxWidth: MAX_EXPORT_WIDTH,
                    maxHeight: MAX_EXPORT_HEIGHT,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                    fillColor: '#ffffff',
                });

                if (!canvas) {
                    reject(new Error(txtCanvasError));
                    return;
                }

                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error(txtCanvasError));
                        return;
                    }
                    resolve(blob);
                }, 'image/jpeg', JPEG_QUALITY);
            });

            const openEditor = (file) => {
                if (!file.type.startsWith('image/')) {
                    showAlert(txtInvalidImage);
                    return;
                }

                destroyCropper();
                setEditorStatus(txtPreparing, true);
                openModal(editorModal);

                objectUrl = URL.createObjectURL(file);
                const onImageReady = () => {
                    editorImage.removeEventListener('load', onImageReady);
                    editorImage.removeEventListener('error', onImageError);
                    setEditorStatus('', false);
                    cropper = new Cropper(editorImage, {
                        viewMode: 1,
                        autoCropArea: 1,
                        responsive: true,
                    });
                };
                const onImageError = () => {
                    editorImage.removeEventListener('load', onImageReady);
                    editorImage.removeEventListener('error', onImageError);
                    setEditorStatus('', false);
                    closeEditor();
                    showAlert(txtInvalidImage);
                };
                editorImage.addEventListener('load', onImageReady);
                editorImage.addEventListener('error', onImageError);
                editorImage.src = objectUrl;
            };

            document.querySelectorAll('[data-revista-source]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (isUploading) return;
                    const kind = btn.getAttribute('data-revista-source');
                    closeModal(sourceModal);
                    if (kind === 'camera') pickCamera.click();
                    if (kind === 'gallery') pickGallery.click();
                });
            });
            document.querySelector('[data-revista-source-cancel]')?.addEventListener('click', () => {
                if (!isUploading) closeModal(sourceModal);
            });

            const onPick = (event) => {
                const file = event.target.files?.[0];
                event.target.value = '';
                if (file) openEditor(file);
            };
            pickGallery?.addEventListener('change', onPick);
            pickCamera?.addEventListener('change', onPick);

            editorCloseBtn?.addEventListener('click', () => { if (!isUploading) closeEditor(); });
            editorCancelBtn?.addEventListener('click', () => { if (!isUploading) closeEditor(); });

            editorSaveBtn?.addEventListener('click', async () => {
                if (!cropper || isUploading) return;

                setSavingState(true);
                setEditorStatus(txtSaving, true);

                try {
                    const blob = await exportCroppedBlob();
                    await uploadBlob(blob);
                    closeEditor();
                    showToast(txtSaved);
                    await config.onSuccess?.();
                } catch (error) {
                    closeEditor();
                    showAlert(error?.message || txtGenericError);
                }
            });

            return {
                openSlot(url, description) {
                    if (isUploading) return;
                    storeUrl = url;
                    activeDescription = description;
                    openModal(sourceModal);
                },
                isUploading: () => isUploading,
            };
        };
        </script>
    @endpush
@endonce
