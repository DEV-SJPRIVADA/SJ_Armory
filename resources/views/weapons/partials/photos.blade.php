<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">{{ __('Fotos') }}</h3>
            @can('update', $weapon)
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input id="photo_edit_toggle" type="checkbox" class="rounded">
                    <span class="text-gray-800">{{ __('Editar') }}</span>
                </label>
            @endcan
        </div>

        @if ($errors->has('photo'))
            <div class="mt-2 text-sm text-red-600">{{ $errors->first('photo') }}</div>
        @endif

        @php
            $photoDescriptions = \App\Models\WeaponPhoto::DESCRIPTIONS;
            $photosByDescription = $weapon->photos->keyBy('description');
        @endphp

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3" id="weapon-photo-grid">
            @foreach ($photoDescriptions as $description => $label)
                @php
                    $photo = $photosByDescription->get($description);
                    $photoUrl = $photo?->file ? Storage::disk($photo->file->disk)->url($photo->file->path) : null;
                @endphp
                <div
                    class="border rounded-lg p-3 weapon-photo-card"
                    data-photo-type="weapon"
                    data-photo-id="{{ $photo?->id }}"
                    data-photo-description="{{ $description }}"
                    data-photo-src="{{ $photoUrl ?? '' }}"
                    data-photo-empty="{{ $photo ? '0' : '1' }}"
                >
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="{{ $label }}" class="h-40 w-full rounded object-contain bg-gray-50">
                    @else
                        <div class="flex h-40 w-full items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-center text-sm text-gray-400">
                            <div>
                                <div class="font-medium">{{ __('Foto pendiente') }}</div>
                                <div class="mt-1 text-xs text-gray-400">{{ $label }}</div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-2 flex items-center justify-between text-sm">
                        <div class="text-gray-600">
                            <div class="flex items-center gap-2">
                                <span>{{ $label }}</span>
                                <span class="text-xs text-gray-500">{{ $photo?->created_at?->format('Y-m-d') ?? __('Pendiente') }}</span>
                            </div>
                        </div>

                        @can('update', $weapon)
                            @if ($photo)
                                <form method="POST" action="{{ route('weapons.photos.destroy', [$weapon, $photo]) }}" onclick="event.stopPropagation();">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:text-red-900" onclick="return confirm(@js(__('¿Eliminar foto?')))">
                                        {{ __('Eliminar') }}
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            @endforeach

            <div class="border rounded-lg p-3 weapon-photo-card" data-photo-type="permit" data-photo-src="{{ $weapon->permitFile ? route('weapons.permit', $weapon) : '' }}" data-photo-empty="{{ $weapon->permitFile ? '0' : '1' }}">
                @if ($weapon->permitFile)
                    <img src="{{ route('weapons.permit', $weapon) }}" alt="Permiso" class="h-40 w-full rounded object-contain bg-gray-50">
                @else
                    <div class="flex h-40 w-full items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-center text-sm text-gray-400">
                        <div>
                            <div class="font-medium">{{ __('Foto pendiente') }}</div>
                            <div class="mt-1 text-xs text-gray-400">{{ __('Permiso') }}</div>
                        </div>
                    </div>
                @endif
                <div class="mt-2 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <span>{{ __('Permiso') }}</span>
                        <span class="text-xs text-gray-500">{{ $weapon->permitFile?->created_at?->format('Y-m-d') ?? __('Pendiente') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="photo_action_modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-sm rounded bg-white shadow-lg">
                <div class="border-b px-4 py-3 text-sm font-semibold text-gray-800">
                    {{ __('Editar imagen') }}
                </div>
                <div class="p-4 space-y-2 text-sm text-gray-700">
                    <button id="photo_action_crop" type="button" class="w-full rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        {{ __('Recortar o mover') }}
                    </button>
                    <button id="photo_action_change" type="button" class="w-full rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        {{ __('Cambiar imagen') }}
                    </button>
                </div>
                <div class="flex justify-end border-t px-4 py-2">
                    <button id="photo_action_cancel" type="button" class="text-sm text-gray-600 hover:text-gray-900">
                        {{ __('Cancelar') }}
                    </button>
                </div>
            </div>
        </div>

        <div id="image_editor_modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-3xl rounded bg-white shadow-lg">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-800">{{ __('Editar imagen') }}</h3>
                    <button id="image_editor_close" type="button" class="text-sm text-gray-500 hover:text-gray-700">
                        {{ __('Cerrar') }}
                    </button>
                </div>
                <div class="p-4">
                    <div class="max-h-[70vh] w-full overflow-hidden">
                        <img id="image_editor_image" alt="Editor" class="max-h-[70vh] w-full object-contain" />
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 border-t px-4 py-3">
                    <div class="flex flex-1 flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <button id="image_editor_rotate_left" type="button" class="rounded border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-100">
                                {{ __('Girar izquierda') }}
                            </button>
                            <button id="image_editor_rotate_right" type="button" class="rounded border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-100">
                                {{ __('Girar derecha') }}
                            </button>
                        </div>
                        <div class="flex min-w-[18rem] flex-1 flex-wrap items-center gap-2">
                            <span class="text-xs font-medium text-gray-600">{{ __('Ajuste fino') }}</span>
                            <input id="image_editor_rotate_fine" type="range" min="-5" max="5" step="0.1" value="0" class="h-2 min-w-[10rem] flex-1 cursor-pointer accent-indigo-600">
                            <span id="image_editor_rotate_value" class="w-14 text-right text-xs font-medium text-gray-600">0.0°</span>
                            <button id="image_editor_rotate_reset" type="button" class="rounded border border-gray-300 px-3 py-1 text-xs text-gray-700 hover:bg-gray-100">
                                {{ __('Restablecer') }}
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="image_editor_cancel" type="button" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancelar') }}
                        </button>
                        <button id="image_editor_crop" type="button" class="rounded bg-indigo-600 px-3 py-1 text-xs text-white hover:bg-indigo-700">
                            {{ __('Guardar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <input id="photo_replace_input" type="file" accept="image/*" class="hidden">

        @once
            @push('styles')
                <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
            @endpush
        @endonce
        @push('scripts')
            <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
            <script>
            const photoEditToggle = document.getElementById('photo_edit_toggle');
            const photoGrid = document.getElementById('weapon-photo-grid');
            const photoCards = Array.from(document.querySelectorAll('.weapon-photo-card'));
            const actionModal = document.getElementById('photo_action_modal');
            const actionCrop = document.getElementById('photo_action_crop');
            const actionChange = document.getElementById('photo_action_change');
            const actionCancel = document.getElementById('photo_action_cancel');
            const replaceInput = document.getElementById('photo_replace_input');

            const editorModal = document.getElementById('image_editor_modal');
            const editorImage = document.getElementById('image_editor_image');
            const closeButton = document.getElementById('image_editor_close');
            const cancelButton = document.getElementById('image_editor_cancel');
            const cropButton = document.getElementById('image_editor_crop');
            const rotateLeftButton = document.getElementById('image_editor_rotate_left');
            const rotateRightButton = document.getElementById('image_editor_rotate_right');
            const fineRotateInput = document.getElementById('image_editor_rotate_fine');
            const fineRotateValue = document.getElementById('image_editor_rotate_value');
            const resetRotateButton = document.getElementById('image_editor_rotate_reset');

            let isEditing = false;
            let activePhotoId = null;
            let activePhotoSrc = null;
            let activePhotoType = 'weapon';
            let activePhotoDescription = null;
            let cropper = null;
            let editorRotation = 0;
            let editorFineRotation = 0;

            const csrfToken = @json(csrf_token());
            const storeUrl = @json(route('weapons.photos.store', $weapon));
            const updateUrlBase = @json(route('weapons.photos.update', [$weapon, 0]));
            const updatePermitUrl = @json(route('weapons.permit.update', $weapon));

            const setEditing = (enabled) => {
                isEditing = enabled;
                photoGrid?.classList.toggle('photo-editing', enabled);
                photoCards.forEach((card) => {
                    card.classList.toggle('cursor-pointer', enabled);
                    card.classList.toggle('ring-2', enabled);
                    card.classList.toggle('ring-indigo-300', enabled);
                });
            };

            const openActionModal = () => {
                actionModal.classList.remove('hidden');
                actionModal.classList.add('flex');
            };

            const closeActionModal = () => {
                actionModal.classList.add('hidden');
                actionModal.classList.remove('flex');
            };

            const syncFineRotationUi = () => {
                if (fineRotateInput) {
                    fineRotateInput.value = editorFineRotation.toString();
                }

                if (fineRotateValue) {
                    fineRotateValue.textContent = `${editorFineRotation.toFixed(1)}°`;
                }
            };

            const applyEditorRotation = () => {
                if (cropper) {
                    cropper.rotateTo(editorRotation + editorFineRotation);
                }
            };

            const openEditor = (source, revokeAfter = false) => {
                if (editorImage.dataset.objectUrl) {
                    URL.revokeObjectURL(editorImage.dataset.objectUrl);
                    delete editorImage.dataset.objectUrl;
                }

                editorImage.src = source;
                if (revokeAfter) {
                    editorImage.dataset.objectUrl = source;
                }

                editorModal.classList.remove('hidden');
                editorModal.classList.add('flex');
                editorRotation = 0;
                editorFineRotation = 0;
                syncFineRotationUi();

                if (cropper) {
                    cropper.destroy();
                }

                cropper = new Cropper(editorImage, {
                    viewMode: 1,
                    autoCropArea: 1,
                });

                requestAnimationFrame(() => {
                    applyEditorRotation();
                });
            };

            const closeEditor = () => {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                editorModal.classList.add('hidden');
                editorModal.classList.remove('flex');
                if (editorImage.dataset.objectUrl) {
                    URL.revokeObjectURL(editorImage.dataset.objectUrl);
                    delete editorImage.dataset.objectUrl;
                }
                editorImage.removeAttribute('src');
                editorRotation = 0;
                editorFineRotation = 0;
                syncFineRotationUi();
            };

            const uploadCropped = (blob) => {
                if (!blob) {
                    return;
                }

                const formData = new FormData();
                const fileName = activePhotoType === 'permit'
                    ? 'permit.jpg'
                    : `photo_${activePhotoDescription || activePhotoId || 'new'}.jpg`;
                const file = new File([blob], fileName, { type: blob.type });
                formData.append('photo', file);

                let url = storeUrl;
                let method = 'POST';

                if (activePhotoType === 'permit') {
                    formData.append('_method', 'PATCH');
                    url = updatePermitUrl;
                } else if (activePhotoId) {
                    formData.append('_method', 'PATCH');
                    url = updateUrlBase.replace(/\/0$/, `/${activePhotoId}`);
                } else {
                    formData.append('description', activePhotoDescription || '');
                }

                fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Upload failed');
                        }

                        const contentType = response.headers.get('content-type') || '';
                        if (contentType.includes('application/json')) {
                            return response.json();
                        }

                        return null;
                    })
                    .then(() => {
                        window.location.reload();
                    })
                    .catch(() => {
                        alert(@json(__('No se pudo actualizar la foto.')));
                    });
            };

            const applyCrop = () => {
                if (!cropper) {
                    closeEditor();
                    return;
                }

                cropper.getCroppedCanvas().toBlob((blob) => {
                    if (!blob) {
                        closeEditor();
                        return;
                    }
                    uploadCropped(blob);
                    closeEditor();
                }, 'image/jpeg', 0.92);
            };

            if (photoEditToggle) {
                photoEditToggle.addEventListener('change', (event) => {
                    setEditing(event.target.checked);
                });
            }

            photoCards.forEach((card) => {
                card.addEventListener('click', () => {
                    if (!isEditing) {
                        return;
                    }

                    activePhotoId = card.dataset.photoId || null;
                    activePhotoSrc = card.dataset.photoSrc || null;
                    activePhotoType = card.dataset.photoType || 'weapon';
                    activePhotoDescription = card.dataset.photoDescription || null;

                    if (card.dataset.photoEmpty === '1') {
                        replaceInput?.click();
                        return;
                    }

                    openActionModal();
                });
            });

            actionCancel?.addEventListener('click', closeActionModal);

            actionCrop?.addEventListener('click', () => {
                closeActionModal();
                if (activePhotoSrc) {
                    openEditor(activePhotoSrc, false);
                }
            });

            actionChange?.addEventListener('click', () => {
                closeActionModal();
                replaceInput?.click();
            });

            replaceInput?.addEventListener('change', () => {
                const file = replaceInput.files && replaceInput.files[0];
                if (!file) {
                    return;
                }
                const url = URL.createObjectURL(file);
                openEditor(url, true);
                replaceInput.value = '';
            });

            closeButton?.addEventListener('click', closeEditor);
            cancelButton?.addEventListener('click', closeEditor);
            cropButton?.addEventListener('click', applyCrop);
            rotateLeftButton?.addEventListener('click', () => {
                editorRotation -= 90;
                applyEditorRotation();
            });
            rotateRightButton?.addEventListener('click', () => {
                editorRotation += 90;
                applyEditorRotation();
            });
            fineRotateInput?.addEventListener('input', () => {
                editorFineRotation = Number.parseFloat(fineRotateInput.value || '0') || 0;
                syncFineRotationUi();
                applyEditorRotation();
            });
            resetRotateButton?.addEventListener('click', () => {
                editorRotation = 0;
                editorFineRotation = 0;
                syncFineRotationUi();
                applyEditorRotation();
            });
            </script>
        @endpush
    </div>
</div>
