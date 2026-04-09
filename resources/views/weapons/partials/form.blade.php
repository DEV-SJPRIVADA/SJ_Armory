@php
    $weapon = $weapon ?? null;
    $photoDescriptions = \App\Models\WeaponPhoto::DESCRIPTIONS;
    $existingPhotos = $weapon?->photos?->keyBy('description') ?? collect();
    $permitPhotoUrl = $weapon?->permitFile ? route('weapons.permit', $weapon) : null;
    $photoGuidesByType = [
        'escopeta' => [
            'lado_derecho' => asset('images/weapon-guides/Escopeta lado derecho-09.svg'),
            'lado_izquierdo' => asset('images/weapon-guides/Escopeta lado izquierdo-10.svg'),
            'canon_disparador_marca' => asset('images/weapon-guides/Escopeta cañon-11.svg'),
            'serie' => asset('images/weapon-guides/Escopeta serie.svg'),
        ],
        'pistola' => [
            'lado_derecho' => asset('images/weapon-guides/Pistola lado derecho-01.svg'),
            'lado_izquierdo' => asset('images/weapon-guides/Pistola lado izquierdo-02.svg'),
            'canon_disparador_marca' => asset('images/weapon-guides/Pistola cañon-03.svg'),
            'serie' => asset('images/weapon-guides/Pistola serie-04.svg'),
        ],
        'revolver' => [
            'lado_derecho' => asset('images/weapon-guides/Revolver lado derecho-05.svg'),
            'lado_izquierdo' => asset('images/weapon-guides/Revolver lado izquierdo-06.svg'),
            'canon_disparador_marca' => asset('images/weapon-guides/Revolver cañon-07.svg'),
            'serie' => asset('images/weapon-guides/Revolver Serie-08.svg'),
        ],
        'subametralladora' => [
            'lado_derecho' => asset('images/weapon-guides/Uzi lado derecho-13.svg'),
            'lado_izquierdo' => asset('images/weapon-guides/Uzi lado izquierdo-14.svg'),
            'canon_disparador_marca' => asset('images/weapon-guides/Uzi cañon-15.svg'),
            'serie' => asset('images/weapon-guides/Uzi serie-16.svg'),
        ],
    ];
    $weaponTypes = ['Escopeta', 'Pistola', 'Revólver', 'Subametralladora'];
    $photoIndex = 1;
@endphp

@if (!empty($showInternalCode))
    <div>
        <x-input-label for="internal_code" :value="__('Código interno')" />
        <x-text-input id="internal_code" name="internal_code" type="text" class="mt-1 block w-full" value="{{ old('internal_code', $weapon?->internal_code) }}" required />
        <x-input-error :messages="$errors->get('internal_code')" class="mt-2" />
    </div>
@endif

<div>
    <x-input-label for="serial_number" :value="__('Número de serie')" />
    <x-text-input id="serial_number" name="serial_number" type="text" class="mt-1 block w-full" value="{{ old('serial_number', $weapon?->serial_number) }}" required />
    <x-input-error :messages="$errors->get('serial_number')" class="mt-2" />
</div>

<div>
    <x-input-label for="weapon_type" :value="__('Tipo de arma')" />
    @php
        $selectedWeaponType = old('weapon_type', $weapon?->weapon_type);
    @endphp
    <select id="weapon_type" name="weapon_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm" required>
        <option value="">{{ __('Seleccione') }}</option>
        @foreach ($weaponTypes as $weaponTypeOption)
            <option value="{{ $weaponTypeOption }}" @selected($selectedWeaponType === $weaponTypeOption)>
                {{ __($weaponTypeOption) }}
            </option>
        @endforeach
        @if ($selectedWeaponType && !in_array($selectedWeaponType, $weaponTypes, true))
            <option value="{{ $selectedWeaponType }}" selected>{{ $selectedWeaponType }}</option>
        @endif
    </select>
    <x-input-error :messages="$errors->get('weapon_type')" class="mt-2" />
</div>

<div>
    <x-input-label for="caliber" :value="__('Calibre')" />
    <x-text-input id="caliber" name="caliber" type="text" class="mt-1 block w-full" value="{{ old('caliber', $weapon?->caliber) }}" required />
    <x-input-error :messages="$errors->get('caliber')" class="mt-2" />
</div>

<div>
    <x-input-label for="brand" :value="__('Marca')" />
    <x-text-input id="brand" name="brand" type="text" class="mt-1 block w-full" value="{{ old('brand', $weapon?->brand) }}" required />
    <x-input-error :messages="$errors->get('brand')" class="mt-2" />
</div>

<div>
    <x-input-label for="capacity" :value="__('Capacidad')" />
    <x-text-input id="capacity" name="capacity" type="text" class="mt-1 block w-full" value="{{ old('capacity', $weapon?->capacity) }}" required />
    <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
</div>

<div>
    <x-input-label for="ownership_type" :value="__('Tipo de propiedad')" />
    <select id="ownership_type" name="ownership_type" class="mt-1 block w-full rounded-md border-gray-300">
        @foreach ($ownershipTypes as $value => $label)
            <option value="{{ $value }}" @selected(old('ownership_type', $weapon?->ownership_type) === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('ownership_type')" class="mt-2" />
</div>

<div>
    <x-input-label for="ownership_entity" :value="__('Entidad propietaria (si aplica)')" />
    <x-text-input id="ownership_entity" name="ownership_entity" type="text" class="mt-1 block w-full" value="{{ old('ownership_entity', $weapon?->ownership_entity) }}" />
    <x-input-error :messages="$errors->get('ownership_entity')" class="mt-2" />
</div>

<div class="md:col-span-2">
    <span class="text-sm font-medium text-gray-700">{{ __('Registro fotográfico del arma') }}</span>
    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-5">
        @foreach ($photoDescriptions as $description => $label)
            @php
                $photo = $existingPhotos->get($description);
                $photoUrl = $photo?->file
                    ? Storage::disk($photo->file->disk)->url($photo->file->path)
                    : null;
            @endphp
            <label for="photo_{{ $photoIndex }}" class="block cursor-pointer" data-drop-zone tabindex="0" title="{{ __('Selecciona, arrastra o pega una foto') }}">
                <input id="photo_{{ $photoIndex }}" name="photos[]" type="file" accept="image/*" class="hidden"
                    data-photo-description="{{ $description }}"
                    data-preview-target="photo_preview_{{ $photoIndex }}"
                    data-guide-target="photo_guide_{{ $photoIndex }}"
                    data-placeholder-target="photo_placeholder_{{ $photoIndex }}" />
                <div class="relative flex h-24 w-full items-center justify-center overflow-hidden rounded border border-dashed border-gray-300 bg-gray-50 text-xs text-gray-500 transition"
                    data-drop-surface>
                    <div class="sj-paste-proxy" data-paste-proxy contenteditable="true" spellcheck="false"></div>
                    <img id="photo_guide_{{ $photoIndex }}" alt="Guía"
                        class="pointer-events-none hidden absolute inset-0 h-full w-full object-contain p-1 opacity-55" />
                    <span id="photo_placeholder_{{ $photoIndex }}"
                        @class([
                            'hidden' => $photoUrl,
                            'absolute inset-x-0 top-1 z-10 px-1 py-0.5 text-center text-[10px] font-medium text-gray-600',
                        ])>
                        {{ __('Arrastra, selecciona o pega una foto') }}
                    </span>
                    <img id="photo_preview_{{ $photoIndex }}" alt="Previsualización"
                        class="{{ $photoUrl ? '' : 'hidden' }} relative z-10 h-full w-full rounded bg-gray-50 object-fill"
                        @if ($photoUrl) src="{{ $photoUrl }}" @endif />
                </div>
                <div class="mt-1 text-xs text-gray-500">{{ $label }}</div>
            </label>
            @php $photoIndex++; @endphp
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('photos')" class="mt-2" />
    @php
        $photoErrors = collect($errors->get('photos.*'))->flatten()->all();
    @endphp
    <x-input-error :messages="$photoErrors" class="mt-2" />
</div>

<div class="md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <x-input-label for="permit_type" :value="__('Tipo de permiso')" />
    <select id="permit_type" name="permit_type" class="mt-1 block w-full rounded-md border-gray-300" required>
        <option value="">{{ __('Seleccione') }}</option>
        <option value="porte" @selected(old('permit_type', $weapon?->permit_type) === 'porte')>{{ __('Porte') }}</option>
        <option value="tenencia" @selected(old('permit_type', $weapon?->permit_type) === 'tenencia')>{{ __('Tenencia') }}</option>
    </select>
    <x-input-error :messages="$errors->get('permit_type')" class="mt-2" />
    @if ($errors->has('permit_type'))
            <div class="mt-1 text-xs text-red-600">{{ __('Seleccione el tipo de permiso: Porte o Tenencia.') }}</div>
    @endif
</div>

    <div>
        <x-input-label for="permit_number" :value="__('Número de permiso')" />
        <x-text-input id="permit_number" name="permit_number" type="text" class="mt-1 block w-full" value="{{ old('permit_number', $weapon?->permit_number) }}" />
        <x-input-error :messages="$errors->get('permit_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="permit_expires_at" :value="__('Fecha de vencimiento')" />
        <x-text-input id="permit_expires_at" name="permit_expires_at" type="date" class="mt-1 block w-full" value="{{ old('permit_expires_at', optional($weapon?->permit_expires_at)->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('permit_expires_at')" class="mt-2" />
    </div>

    <div class="md:col-start-2 md:row-start-1 md:row-span-3 flex flex-col">
        <x-input-label for="permit_photo" :value="__('Imagen del permiso')" />
        <label for="permit_photo" class="mt-1 block h-full cursor-pointer" data-drop-zone tabindex="0" title="{{ __('Selecciona, arrastra o pega una foto') }}">
            <input id="permit_photo" name="permit_photo" type="file" accept="image/*" class="hidden" @if (!empty($requirePermitPhoto)) required @endif
                data-preview-target="permit_preview" data-placeholder-target="permit_placeholder" />
            <div class="relative flex h-full min-h-[12rem] w-full items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-xs text-gray-500 transition"
                data-drop-surface>
                <div class="sj-paste-proxy" data-paste-proxy contenteditable="true" spellcheck="false"></div>
                <span id="permit_placeholder" @class(['hidden' => $permitPhotoUrl])>{{ __('Arrastra, selecciona o pega una foto') }}</span>
                <img id="permit_preview" alt="Previsualización"
                    class="{{ $permitPhotoUrl ? '' : 'hidden' }} h-full w-full rounded bg-gray-50 object-fill"
                    @if ($permitPhotoUrl) src="{{ $permitPhotoUrl }}" @endif />
            </div>
        </label>
        <x-input-error :messages="$errors->get('permit_photo')" class="mt-2" />
        @if ($errors->has('permit_photo'))
            <div class="mt-1 text-xs text-red-600">{{ __('Debe cargar la imagen del permiso.') }}</div>
        @endif
        <div id="permit_photo_alert" class="mt-1 text-xs text-red-600 hidden">
            {{ __('Debe cargar la imagen del permiso.') }}
        </div>
    </div>
</div>

<div class="md:col-span-2">
    <x-input-label for="notes" :value="__('Notas')" />
    <textarea id="notes" name="notes" spellcheck="true" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $weapon?->notes) }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
</div>

<div class="md:col-span-2 flex justify-end gap-2">
    <a href="{{ $cancelUrl }}" class="text-sm text-gray-600 hover:text-gray-900">
        {{ __('Cancelar') }}
    </a>
    <x-primary-button>
        {{ $submitLabel }}
    </x-primary-button>
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
            <div class="h-[70vh] max-h-[70vh] w-full overflow-hidden rounded bg-slate-100">
                <img id="image_editor_image" alt="Editor" class="block max-h-none max-w-none" />
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
        let activeInput = null;
        let cropper = null;
        let editorSourceFile = null;
        let editorRotation = 0;
        let editorFineRotation = 0;
        const photoGuidesByType = @json($photoGuidesByType);
        const dropZones = Array.from(document.querySelectorAll('[data-drop-zone]'));
        const normalizeText = (value) => {
            if (!value) {
                return '';
            }
            return value
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        };

        const resolveWeaponTypeKey = (weaponTypeValue) => {
            const normalized = normalizeText(weaponTypeValue);
            if (!normalized) {
                return '';
            }
            if (normalized.includes('escopeta')) {
                return 'escopeta';
            }
            if (normalized.includes('pistola')) {
                return 'pistola';
            }
            if (normalized.includes('revolver')) {
                return 'revolver';
            }
            if (normalized.includes('subametralladora') || normalized.includes('uzi')) {
                return 'subametralladora';
            }
            return '';
        };

	    const modal = document.getElementById('image_editor_modal');
	    const modalImage = document.getElementById('image_editor_image');
    const closeButton = document.getElementById('image_editor_close');
    const cancelButton = document.getElementById('image_editor_cancel');
    const cropButton = document.getElementById('image_editor_crop');
	    const rotateLeftButton = document.getElementById('image_editor_rotate_left');
	    const rotateRightButton = document.getElementById('image_editor_rotate_right');
        const fineRotateInput = document.getElementById('image_editor_rotate_fine');
        const fineRotateValue = document.getElementById('image_editor_rotate_value');
        const resetRotateButton = document.getElementById('image_editor_rotate_reset');
        const weaponTypeInput = document.getElementById('weapon_type');

        const syncGuideForInput = (input) => {
            if (!input || !input.dataset.photoDescription) {
                return;
            }

            const previewId = input.dataset.previewTarget;
            const guideId = input.dataset.guideTarget;
            const preview = previewId ? document.getElementById(previewId) : null;
            const guide = guideId ? document.getElementById(guideId) : null;
            if (!guide) {
                return;
            }

            const typeKey = resolveWeaponTypeKey(weaponTypeInput?.value || '');
            const guideUrl = typeKey ? (photoGuidesByType[typeKey]?.[input.dataset.photoDescription] || '') : '';
            const hasPhotoPreview = preview && !preview.classList.contains('hidden');

            if (guideUrl && !hasPhotoPreview) {
                guide.src = guideUrl;
                guide.classList.remove('hidden');
            } else {
                guide.removeAttribute('src');
                guide.classList.add('hidden');
            }
        };

        const syncAllGuides = () => {
            document.querySelectorAll('input[data-photo-description]').forEach((input) => {
                syncGuideForInput(input);
            });
        };

	    const setPreview = (input, preview, placeholder, file) => {
	        if (!input || !preview || !placeholder || !file) {
	            return;
	        }

        if (preview.dataset.objectUrl) {
            URL.revokeObjectURL(preview.dataset.objectUrl);
        }

        const url = URL.createObjectURL(file);
	        preview.dataset.objectUrl = url;
	        preview.src = url;
	        preview.classList.remove('hidden');
	        placeholder.classList.add('hidden');
            syncGuideForInput(input);
	    };

	    const clearPreview = (input, preview, placeholder) => {
	        if (!input || !preview || !placeholder) {
	            return;
	        }

        if (preview.dataset.objectUrl) {
            URL.revokeObjectURL(preview.dataset.objectUrl);
            delete preview.dataset.objectUrl;
        }

	        preview.removeAttribute('src');
	        preview.classList.add('hidden');
	        placeholder.classList.remove('hidden');
            syncGuideForInput(input);
	    };

    const clearEditorPreviewUrl = () => {
        if (modalImage.dataset.objectUrl) {
            URL.revokeObjectURL(modalImage.dataset.objectUrl);
            delete modalImage.dataset.objectUrl;
        }
    };

    const destroyCropper = () => {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    };

    const loadImageFromFile = (file) => new Promise((resolve, reject) => {
        const objectUrl = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(image);
        };

        image.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error('No se pudo cargar la imagen.'));
        };

        image.src = objectUrl;
    });

    const rebuildCropper = () => {
        destroyCropper();

        cropper = new Cropper(modalImage, {
            viewMode: 1,
            autoCropArea: 1,
            responsive: true,
            restore: false,
            background: false,
        });

        requestAnimationFrame(() => {
            if (cropper) {
                cropper.rotateTo(editorFineRotation);
            }
        });
    };

    const syncFineRotationUi = () => {
        if (fineRotateInput) {
            fineRotateInput.value = editorFineRotation.toString();
        }

        if (fineRotateValue) {
            fineRotateValue.textContent = `${editorFineRotation.toFixed(1)}°`;
        }
    };

    const renderEditorImage = async () => {
        if (!editorSourceFile) {
            return;
        }

        const image = await loadImageFromFile(editorSourceFile);
        const normalizedRotation = ((editorRotation % 360) + 360) % 360;
        const swapSides = normalizedRotation === 90 || normalizedRotation === 270;
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');

        if (!context) {
            return;
        }

        canvas.width = swapSides ? image.naturalHeight : image.naturalWidth;
        canvas.height = swapSides ? image.naturalWidth : image.naturalHeight;

        context.translate(canvas.width / 2, canvas.height / 2);
        context.rotate((normalizedRotation * Math.PI) / 180);
        context.drawImage(image, -image.naturalWidth / 2, -image.naturalHeight / 2);

        const blob = await new Promise((resolve) => {
            canvas.toBlob(resolve, editorSourceFile.type || 'image/jpeg', 0.92);
        });

        if (!blob) {
            return;
        }

        clearEditorPreviewUrl();
        modalImage.dataset.objectUrl = URL.createObjectURL(blob);
        modalImage.src = modalImage.dataset.objectUrl;
        rebuildCropper();
    };

    const openEditor = async (input) => {
        const file = input.files && input.files[0];
        if (!file) {
            return;
        }

        activeInput = input;
        editorSourceFile = file;
        editorRotation = 0;
        editorFineRotation = 0;
        syncFineRotationUi();

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        await renderEditorImage();
    };

    const assignFileToInput = (input, file) => {
        if (!input || !file || !file.type.startsWith('image/')) {
            return false;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;

        return true;
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

        let hoveredPasteZone = null;

        const getClipboardImage = (clipboardData) => {
            const items = Array.from(clipboardData?.items || []);
            const imageItem = items.find((item) => item.kind === 'file' && item.type.startsWith('image/'));

            return imageItem ? imageItem.getAsFile() : null;
        };

    const handleImageSelection = (input, file) => {
        if (!input || !file) {
            return false;
        }

        if (!file.type.startsWith('image/')) {
            alert(@json(__('Solo puede usar archivos de imagen.')));
            return false;
        }

        if (assignFileToInput(input, file)) {
            openEditor(input);
            return true;
        }

        return false;
    };

    const closeEditor = (discardSelection = false) => {
        destroyCropper();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        clearEditorPreviewUrl();
        modalImage.removeAttribute('src');
        editorSourceFile = null;
        editorRotation = 0;
        editorFineRotation = 0;
        syncFineRotationUi();

        if (discardSelection && activeInput) {
	            const previewId = activeInput.dataset.previewTarget;
	            const placeholderId = activeInput.dataset.placeholderTarget;
	            const preview = previewId ? document.getElementById(previewId) : null;
	            const placeholder = placeholderId ? document.getElementById(placeholderId) : null;
	            clearPreview(activeInput, preview, placeholder);
	            activeInput.value = '';
	        }

        activeInput = null;
    };

    const applyCrop = () => {
        if (!cropper || !activeInput) {
            closeEditor();
            return;
        }

        cropper.getCroppedCanvas().toBlob((blob) => {
            if (!blob) {
                closeEditor();
                return;
            }

            const fileName = activeInput.files[0]?.name || 'photo.jpg';
            const file = new File([blob], fileName, { type: blob.type });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            activeInput.files = dataTransfer.files;

	            const previewId = activeInput.dataset.previewTarget;
	            const placeholderId = activeInput.dataset.placeholderTarget;
	            const preview = previewId ? document.getElementById(previewId) : null;
	            const placeholder = placeholderId ? document.getElementById(placeholderId) : null;
	            setPreview(activeInput, preview, placeholder, file);

            closeEditor();
        }, 'image/jpeg', 0.92);
    };

    closeButton.addEventListener('click', () => closeEditor(true));
    cancelButton.addEventListener('click', () => closeEditor(true));
    cropButton.addEventListener('click', applyCrop);
    rotateLeftButton.addEventListener('click', async () => {
        if (!editorSourceFile) {
            return;
        }

        editorRotation -= 90;
        await renderEditorImage();
    });
    rotateRightButton.addEventListener('click', async () => {
        if (!editorSourceFile) {
            return;
        }

        editorRotation += 90;
        await renderEditorImage();
    });
    fineRotateInput?.addEventListener('input', () => {
        editorFineRotation = Number.parseFloat(fineRotateInput.value || '0') || 0;
        syncFineRotationUi();

        if (cropper) {
            cropper.rotateTo(editorFineRotation);
        }
    });
    resetRotateButton?.addEventListener('click', async () => {
        if (!editorSourceFile) {
            return;
        }

        editorRotation = 0;
        editorFineRotation = 0;
        syncFineRotationUi();
        await renderEditorImage();
    });

	        document.querySelectorAll('input[data-preview-target]').forEach((input) => {
	            input.addEventListener('change', () => openEditor(input));
	        });

            dropZones.forEach((zone) => {
                const input = zone.querySelector('input[type="file"]');
                const pasteProxy = zone.querySelector('[data-paste-proxy]');
                if (!input) {
                    return;
                }

                ['dragenter', 'dragover'].forEach((eventName) => {
                    zone.addEventListener(eventName, (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        hoveredPasteZone = zone;
                        setDropZoneActive(zone, true);
                    });
                });

                ['dragleave', 'dragend'].forEach((eventName) => {
                    zone.addEventListener(eventName, (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        if (event.target === zone || !zone.contains(event.relatedTarget)) {
                            setDropZoneActive(zone, false);
                        }
                    });
                });

                zone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    hoveredPasteZone = zone;
                    setDropZoneActive(zone, false);

                    const file = event.dataTransfer?.files?.[0];
                    if (!file) {
                        return;
                    }

                    handleImageSelection(input, file);
                });

                zone.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        input.click();
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
                        if (event.button === 0) {
                            event.preventDefault();
                            input.click();
                        }
                    });

                    pasteProxy.addEventListener('focus', () => {
                        hoveredPasteZone = zone;
                        pasteProxy.textContent = '';
                    });

                    pasteProxy.addEventListener('contextmenu', () => {
                        hoveredPasteZone = zone;
                        pasteProxy.focus({ preventScroll: true });
                    });

                    pasteProxy.addEventListener('paste', (event) => {
                        const file = getClipboardImage(event.clipboardData);
                        if (!file) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();
                        handleImageSelection(input, file);
                        pasteProxy.textContent = '';
                    });

                    pasteProxy.addEventListener('blur', () => {
                        pasteProxy.textContent = '';
                    });
                }
            });

            document.addEventListener('paste', (event) => {
                const file = getClipboardImage(event.clipboardData);
                if (!file) {
                    return;
                }

                const zone = hoveredPasteZone;
                const input = zone?.querySelector('input[type="file"]');

                if (!zone || !input) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                handleImageSelection(input, file);
            });

            if (weaponTypeInput) {
                weaponTypeInput.addEventListener('input', syncAllGuides);
                weaponTypeInput.addEventListener('change', syncAllGuides);
            }

            syncAllGuides();

        const form = document.querySelector('form[enctype="multipart/form-data"]');
        const permitInput = document.getElementById('permit_photo');
        const permitAlert = document.getElementById('permit_photo_alert');

        if (form && permitInput && permitAlert) {
            form.addEventListener('submit', (event) => {
                const isCreate = @json(!empty($requirePermitPhoto));
                if (!isCreate) {
                    return;
                }

                if (!permitInput.files || permitInput.files.length === 0) {
                    event.preventDefault();
                    permitAlert.classList.remove('hidden');
                    permitInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    permitAlert.classList.add('hidden');
                }
            });
        }
    </script>
@endpush




