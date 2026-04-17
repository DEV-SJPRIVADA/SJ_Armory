<div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-200">
    <div class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 px-6 py-5">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                    <div class="bg-blue-100 p-2 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    {{ __('Fotos') }}
                </h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Fotografías del arma y permisos asociados') }}</p>
            </div>
            @can('update', $weapon)
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <div class="relative">
                        <input id="photo_edit_toggle" type="checkbox" class="sr-only">
                        <div class="block bg-gray-300 w-10 h-6 rounded-full transition-colors"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-200"></div>
                    </div>
                    <span class="text-gray-700 font-medium">{{ __('Modo edición') }}</span>
                </label>
            @endcan
        </div>
    </div>
    
    <div class="p-6">

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
                    class="relative border rounded-lg p-3 weapon-photo-card"
                    data-photo-type="weapon"
                    data-photo-id="{{ $photo?->id }}"
                    data-photo-description="{{ $description }}"
                    data-photo-src="{{ $photoUrl ?? '' }}"
                    data-photo-empty="{{ $photo ? '0' : '1' }}"
                    data-drop-zone
                    tabindex="0"
                    title="{{ __('Haz clic, arrastra o pega una imagen') }}"
                >
                    <div class="sj-paste-proxy" data-paste-proxy contenteditable="true" spellcheck="false"></div>
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="{{ $label }}" class="h-40 w-full rounded object-contain bg-gray-50" data-drop-surface>
                    @else
                        <div class="flex h-40 w-full items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-center text-sm text-gray-400 transition" data-drop-surface>
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

            <div class="relative border rounded-lg p-3 weapon-photo-card" data-photo-type="permit" data-photo-src="{{ $weapon->permitFile ? route('weapons.permit', $weapon) : '' }}" data-photo-empty="{{ $weapon->permitFile ? '0' : '1' }}" data-drop-zone tabindex="0" title="{{ __('Haz clic, arrastra o pega una imagen') }}">
                <div class="sj-paste-proxy" data-paste-proxy contenteditable="true" spellcheck="false"></div>
                @if ($weapon->permitFile)
                    <img src="{{ route('weapons.permit', $weapon) }}" alt="Permiso" class="h-40 w-full rounded object-contain bg-gray-50" data-drop-surface>
                @else
                    <div class="flex h-40 w-full items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-center text-sm text-gray-400 transition" data-drop-surface>
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
                            <input id="image_editor_rotate_fine" type="range" min="-10" max="10" step="0.1" value="0" class="h-2 min-w-[10rem] flex-1 cursor-pointer accent-indigo-600">
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
                <style>
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

                    .sj-paste-proxy::selection {
                        background: transparent;
                    }
                </style>
            @endpush
        @endonce
        @push('scripts')
            <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
            <script>
            const photoEditToggle = document.getElementById('photo_edit_toggle');
            const photoGrid = document.getElementById('weapon-photo-grid');
            const photoCards = Array.from(document.querySelectorAll('.weapon-photo-card'));
            const dropZones = Array.from(document.querySelectorAll('[data-drop-zone]'));
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
            let hoveredPasteZone = null;

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

            const setDropZoneActive = (zone, active) => {
                const surface = zone?.querySelector('[data-drop-surface]');
                if (!surface) {
                    return;
                }

                surface.classList.toggle('border-indigo-400', active);
                surface.classList.toggle('bg-indigo-50', active);
                surface.classList.toggle('ring-2', active);
                surface.classList.toggle('ring-indigo-200', active);
            };

            const getClipboardImage = (clipboardData) => {
                const items = Array.from(clipboardData?.items || []);
                const imageItem = items.find((item) => item.kind === 'file' && item.type.startsWith('image/'));

                return imageItem ? imageItem.getAsFile() : null;
            };

            const setActivePhotoFromCard = (card) => {
                activePhotoId = card.dataset.photoId || null;
                activePhotoSrc = card.dataset.photoSrc || null;
                activePhotoType = card.dataset.photoType || 'weapon';
                activePhotoDescription = card.dataset.photoDescription || null;
            };

            const openEditorFromFile = (card, file) => {
                if (!isEditing || !card || !file) {
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    alert(@json(__('Solo puede usar archivos de imagen.')));
                    return;
                }

                setActivePhotoFromCard(card);
                closeActionModal();

                const url = URL.createObjectURL(file);
                openEditor(url, true);
            };

            const activateCard = (card) => {
                if (!isEditing || !card) {
                    return;
                }

                setActivePhotoFromCard(card);

                if (card.dataset.photoEmpty === '1') {
                    replaceInput?.click();
                    return;
                }

                openActionModal();
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
                    activateCard(card);
                });
            });

            dropZones.forEach((zone) => {
                const pasteProxy = zone.querySelector('[data-paste-proxy]');

                ['dragenter', 'dragover'].forEach((eventName) => {
                    zone.addEventListener(eventName, (event) => {
                        if (!isEditing) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();
                        hoveredPasteZone = zone;
                        setDropZoneActive(zone, true);
                    });
                });

                ['dragleave', 'dragend'].forEach((eventName) => {
                    zone.addEventListener(eventName, (event) => {
                        if (!isEditing) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();
                        if (event.target === zone || !zone.contains(event.relatedTarget)) {
                            setDropZoneActive(zone, false);
                        }
                    });
                });

                zone.addEventListener('drop', (event) => {
                    if (!isEditing) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    hoveredPasteZone = zone;
                    setDropZoneActive(zone, false);

                    const file = event.dataTransfer?.files?.[0];
                    if (!file) {
                        return;
                    }

                    openEditorFromFile(zone, file);
                });

                zone.addEventListener('keydown', (event) => {
                    if (!isEditing) {
                        return;
                    }

                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        activateCard(zone);
                    }
                });

                if (pasteProxy) {
                    zone.addEventListener('mouseenter', () => {
                        hoveredPasteZone = zone;
                    });

                    zone.addEventListener('mouseleave', () => {
                        if (hoveredPasteZone === zone) {
                            hoveredPasteZone = null;
                        }
                    });

                    pasteProxy.addEventListener('mousedown', (event) => {
                        if (!isEditing) {
                            return;
                        }

                        if (event.button === 0) {
                            event.preventDefault();
                            activateCard(zone);
                        }
                    });

                    pasteProxy.addEventListener('focus', () => {
                        if (!isEditing) {
                            return;
                        }

                        hoveredPasteZone = zone;
                        pasteProxy.textContent = '';
                    });

                    pasteProxy.addEventListener('contextmenu', () => {
                        if (!isEditing) {
                            return;
                        }

                        hoveredPasteZone = zone;
                        pasteProxy.focus({ preventScroll: true });
                    });

                    pasteProxy.addEventListener('paste', (event) => {
                        if (!isEditing) {
                            return;
                        }

                        const file = getClipboardImage(event.clipboardData);
                        if (!file) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();
                        openEditorFromFile(zone, file);
                        pasteProxy.textContent = '';
                    });

                    pasteProxy.addEventListener('blur', () => {
                        pasteProxy.textContent = '';
                    });
                }
            });

            document.addEventListener('paste', (event) => {
                if (!isEditing) {
                    return;
                }

                const file = getClipboardImage(event.clipboardData);
                if (!file) {
                    return;
                }

                const zone = hoveredPasteZone;
                if (!zone) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                openEditorFromFile(zone, file);
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
