@php
    $compact = $compact ?? false;
    $photoEditorConfig = [
        'csrfToken' => csrf_token(),
        'storeUrl' => route('weapons.photos.store', $weapon),
        'updateUrlBase' => route('weapons.photos.update', [$weapon, 0]),
        'updatePermitUrl' => route('weapons.permit.update', $weapon),
        'txtSaving' => __('Guardando…'),
        'txtSave' => __('Guardar'),
        'txtSaved' => __('Imagen guardada'),
        'txtDeleted' => __('Foto eliminada.'),
        'txtGenericError' => __('No se pudo actualizar la foto.'),
        'txtCanvasError' => __('No se pudo procesar la imagen. Intente otra foto o reduzca el zoom.'),
        'txtNetworkError' => __('Sin conexión o la subida tardó demasiado. Espere y vuelva a intentar una sola vez.'),
        'txtSessionExpired' => __('Su sesión expiró. Recargue la página e intente de nuevo.'),
        'txtImagesOnly' => __('Solo puede usar archivos de imagen.'),
        'txtPendingPhoto' => __('Foto pendiente'),
        'txtPending' => __('Pendiente'),
        'txtDelete' => __('Eliminar'),
        'txtDeleteConfirmTitle' => __('Eliminar foto'),
        'txtDeleteConfirm' => __('¿Eliminar foto?'),
        'txtUploadInProgress' => __('Espere a que termine la subida en curso.'),
        'txtUnsavedEditor' => __('Tiene una imagen con cambios sin guardar en el editor. ¿Desea guardarla antes de continuar?'),
        'txtUnsavedChanges' => __('Realizó cambios en las fotos que aún no se han guardado. ¿Qué desea hacer?'),
        'txtEditSessionActive' => __('Sigue en modo edición de fotos. Si sale ahora, deberá activarlo de nuevo para seguir cargando imágenes.'),
        'txtToggleStartEdit' => __('Activar edición de fotos'),
        'txtToggleFinishEdit' => __('Finalizar edición de fotos'),
    ];
@endphp

<div
    @class([
        'sj-weapon-detail-section sj-weapon-detail-photos' => $compact,
        'bg-white overflow-hidden shadow-lg rounded-xl border border-gray-200' => ! $compact,
    ])
    @can('updatePhotos', $weapon)
        data-weapon-photo-editor
        data-weapon-photo-editor-config='@json($photoEditorConfig)'
    @endcan
>
    @if ($compact)
        <div class="sj-weapon-detail-section__head">
            <div>
                <h4 class="sj-weapon-detail-section__title mb-0">{{ __('Fotos') }}</h4>
                <p class="sj-weapon-detail-section__hint mt-1 mb-0">{{ __('Fotografías del arma y permisos asociados') }}</p>
            </div>
            @can('updatePhotos', $weapon)
                <label class="sj-toggle sj-toggle--photo-mode shrink-0" title="{{ __('Modo edición de fotos') }}">
                    <input id="photo_edit_toggle" type="checkbox" class="sj-toggle-input" aria-label="{{ __('Activar edición de fotos') }}">
                    <span class="sj-toggle-track" aria-hidden="true">
                        <span class="sj-toggle-track__text sj-toggle-track__text--idle">{{ __('Editar') }}</span>
                        <span class="sj-toggle-track__text sj-toggle-track__text--active">{{ __('Guardar') }}</span>
                        <span class="sj-toggle-knob"></span>
                    </span>
                </label>
            @endcan
        </div>
    @else
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
                @can('updatePhotos', $weapon)
                    <label class="sj-toggle sj-toggle--photo-mode" title="{{ __('Modo edición de fotos') }}">
                        <input id="photo_edit_toggle" type="checkbox" class="sj-toggle-input" aria-label="{{ __('Activar edición de fotos') }}">
                        <span class="sj-toggle-track" aria-hidden="true">
                            <span class="sj-toggle-track__text sj-toggle-track__text--idle">{{ __('Editar') }}</span>
                            <span class="sj-toggle-track__text sj-toggle-track__text--active">{{ __('Guardar') }}</span>
                            <span class="sj-toggle-knob"></span>
                        </span>
                    </label>
                @endcan
            </div>
        </div>
    @endif

    <div @class(['p-6' => ! $compact])>

        @if ($errors->has('photo'))
            <div class="mt-2 text-sm text-red-600">{{ $errors->first('photo') }}</div>
        @endif

        @php
            $weaponPermitAuthTemplate = $weaponPermitAuthTemplate ?? null;
            $photoDescriptions = \App\Models\WeaponPhoto::DESCRIPTIONS;
            $photosByDescription = $weapon->photos->keyBy('description');
            $photoSurfaceClass = $compact ? 'h-32' : 'h-40';
            $photoCardPadding = $compact ? 'p-2' : 'p-3';
            $photoMetaClass = $compact ? 'text-xs' : 'text-sm';
        @endphp

        <div @class([
            'mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4',
            'xl:grid-cols-7' => $compact,
            'xl:grid-cols-6' => ! $compact,
            'mt-0' => $compact,
        ]) id="weapon-photo-grid">
            @foreach ($photoDescriptions as $description => $label)
                @php
                    $photo = $photosByDescription->get($description);
                    $photoUrl = $photo?->file ? Storage::disk($photo->file->disk)->url($photo->file->path) : null;
                @endphp
                <div
                    class="relative border rounded-lg {{ $photoCardPadding }} weapon-photo-card"
                    data-photo-type="weapon"
                    data-photo-id="{{ $photo?->id }}"
                    data-photo-description="{{ $description }}"
                    data-photo-label="{{ $label }}"
                    data-photo-src="{{ $photoUrl ?? '' }}"
                    data-photo-empty="{{ $photo ? '0' : '1' }}"
                    @can('updatePhotos', $weapon)
                        data-photo-editable="1"
                        data-drop-zone
                        tabindex="0"
                    @endcan
                    title="{{ __('Haz clic para tomar foto o elegir de galería; también arrastra o pega') }}"
                >
                    <div class="weapon-photo-surface-wrap relative">
                        @can('updatePhotos', $weapon)
                            <div class="sj-paste-proxy" data-paste-proxy contenteditable="true" spellcheck="false"></div>
                        @endcan
                        <div data-photo-surface-host>
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" alt="{{ $label }}" class="{{ $photoSurfaceClass }} w-full rounded object-contain bg-gray-50" data-drop-surface>
                            @else
                                <div class="flex {{ $photoSurfaceClass }} w-full items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-center text-sm text-gray-400 transition" data-drop-surface>
                                    <div>
                                        <div class="font-medium">{{ __('Foto pendiente') }}</div>
                                        <div class="mt-1 text-xs text-gray-400">{{ $label }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-2 flex items-center justify-between {{ $photoMetaClass }}">
                        <div class="text-gray-600 min-w-0">
                            <div class="flex flex-col gap-0.5 xl:flex-row xl:items-center xl:gap-2">
                                <span class="truncate">{{ $label }}</span>
                                <span class="text-xs text-gray-500" data-photo-date>{{ $photo?->created_at?->format('Y-m-d') ?? __('Pendiente') }}</span>
                            </div>
                        </div>

                        @can('updatePhotos', $weapon)
                            <div data-photo-actions class="hidden shrink-0">
                                @if ($photo)
                                    <button
                                        type="button"
                                        class="cursor-pointer px-1 py-0.5 text-red-600 hover:text-red-900 hover:underline"
                                        data-photo-delete
                                        data-destroy-url="{{ route('weapons.photos.destroy', [$weapon, $photo]) }}"
                                    >
                                        {{ __('Eliminar') }}
                                    </button>
                                @endif
                            </div>
                        @endcan
                    </div>
                </div>
            @endforeach

            <div
                class="relative border rounded-lg {{ $photoCardPadding }} weapon-photo-card"
                data-photo-type="permit"
                data-photo-label="{{ __('Permiso (frente)') }}"
                data-photo-src="{{ $weapon->permitFile ? route('weapons.permit', $weapon) : '' }}"
                data-photo-empty="{{ $weapon->permitFile ? '0' : '1' }}"
                @can('updatePhotos', $weapon)
                    data-photo-editable="1"
                    data-drop-zone
                    tabindex="0"
                @endcan
                title="{{ __('Haz clic para tomar foto o elegir de galería; también arrastra o pega') }}"
            >
                <div class="weapon-photo-surface-wrap relative">
                    @can('updatePhotos', $weapon)
                        <div class="sj-paste-proxy" data-paste-proxy contenteditable="true" spellcheck="false"></div>
                    @endcan
                    <div data-photo-surface-host>
                        @if ($weapon->permitFile)
                            <img src="{{ route('weapons.permit', $weapon) }}" alt="Permiso" class="{{ $photoSurfaceClass }} w-full rounded object-contain bg-gray-50" data-drop-surface>
                        @else
                            <div class="flex {{ $photoSurfaceClass }} w-full items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-center text-sm text-gray-400 transition" data-drop-surface>
                                <div>
                                    <div class="font-medium">{{ __('Foto pendiente') }}</div>
                                    <div class="mt-1 text-xs text-gray-400">{{ __('Permiso (frente)') }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="mt-2 {{ $photoMetaClass }} text-gray-600">
                    <div class="flex flex-col gap-0.5 xl:flex-row xl:items-center xl:gap-2 min-w-0">
                        <span class="truncate">{{ __('Permiso (frente)') }}</span>
                        <span class="text-xs text-gray-500" data-photo-date>{{ $weapon->permitFile?->created_at?->format('Y-m-d') ?? __('Pendiente') }}</span>
                    </div>
                </div>
            </div>

            @php
                $globalAuthPermitUrl = ($weaponPermitAuthTemplate?->file && in_array($weapon->permit_type, ['porte', 'tenencia'], true))
                    ? route('authenticated-permit-images.show', ['permit_kind' => $weapon->permit_type])
                    : '';
            @endphp
            <div class="relative rounded-lg border border-slate-200 bg-slate-50/50 {{ $photoCardPadding }}" title="{{ __('Imagen de referencia global (no editable desde esta ficha)') }}">
                @if ($globalAuthPermitUrl !== '')
                    <img src="{{ $globalAuthPermitUrl }}" alt="{{ __('Permiso autenticado (referencia)') }}" class="{{ $photoSurfaceClass }} w-full rounded object-contain bg-white">
                @else
                    <div class="flex {{ $photoSurfaceClass }} w-full items-center justify-center rounded border border-dashed border-gray-300 bg-white text-center text-sm text-gray-400">
                        <div>
                            <div class="font-medium">{{ __('Sin imagen de referencia') }}</div>
                            <div class="mt-1 text-xs text-gray-400">{{ __('Tipo de permiso del arma: :tipo', ['tipo' => $weapon->permit_type ?: '—']) }}</div>
                        </div>
                    </div>
                @endif
                <div class="mt-2 {{ $photoMetaClass }} text-gray-600">
                    <span class="truncate block">{{ __('Permiso autenticado') }}</span>
                </div>
            </div>
        </div>

        <div id="photo_source_modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-sm rounded bg-white shadow-lg">
                <div class="border-b px-4 py-3 text-sm font-semibold text-gray-800">{{ __('Agregar imagen') }}</div>
                <div class="p-4 space-y-2 text-sm text-gray-700">
                    <button id="photo_source_camera" type="button" class="w-full rounded border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-sm font-medium text-indigo-900 hover:bg-indigo-100">{{ __('Tomar foto') }}</button>
                    <button id="photo_source_gallery" type="button" class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100">{{ __('Elegir de galería') }}</button>
                </div>
                <div class="flex justify-end border-t px-4 py-2">
                    <button id="photo_source_cancel" type="button" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancelar') }}</button>
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

        <div id="image_editor_modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-hidden bg-black/50 p-2 sm:p-4">
            <div class="sj-image-editor-panel flex max-h-[calc(100dvh-0.5rem)] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-lg sm:max-h-[calc(100dvh-2rem)]">
                <div class="flex shrink-0 items-center justify-between border-b px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-800">{{ __('Editar imagen') }}</h3>
                    <button id="image_editor_close" type="button" class="text-sm text-gray-500 hover:text-gray-700">
                        {{ __('Cerrar') }}
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-3 sm:p-4">
                    <div class="sj-image-editor-canvas w-full overflow-auto rounded bg-gray-50">
                        <img id="image_editor_image" alt="Editor" class="mx-auto w-full max-w-full object-contain" />
                    </div>
                </div>
                <div class="shrink-0 border-t bg-white pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                    <div class="flex flex-col gap-3 px-3 py-3 sm:px-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                            <div class="flex flex-wrap gap-2">
                                <button id="image_editor_rotate_left" type="button" class="rounded border border-gray-300 px-3 py-2 text-xs text-gray-700 hover:bg-gray-100 sm:py-1">
                                    {{ __('Girar izquierda') }}
                                </button>
                                <button id="image_editor_rotate_right" type="button" class="rounded border border-gray-300 px-3 py-2 text-xs text-gray-700 hover:bg-gray-100 sm:py-1">
                                    {{ __('Girar derecha') }}
                                </button>
                            </div>
                            <div class="flex w-full flex-col gap-2 sm:flex-1 sm:flex-row sm:flex-wrap sm:items-center">
                                <span class="text-xs font-medium text-gray-600">{{ __('Ajuste fino') }}</span>
                                <input id="image_editor_rotate_fine" type="range" min="-10" max="10" step="0.1" value="0" class="h-2 w-full min-w-0 flex-1 cursor-pointer accent-indigo-600 sm:min-w-[8rem]">
                                <span id="image_editor_rotate_value" class="text-xs font-medium text-gray-600 sm:w-14 sm:text-right">0.0°</span>
                                <button id="image_editor_rotate_reset" type="button" class="rounded border border-gray-300 px-3 py-2 text-xs text-gray-700 hover:bg-gray-100 sm:py-1">
                                    {{ __('Restablecer') }}
                                </button>
                            </div>
                        </div>
                        <div class="flex w-full gap-2 border-t border-gray-100 pt-3">
                            <button id="image_editor_cancel" type="button" class="min-h-11 flex-1 rounded-md border border-gray-300 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:min-h-0 sm:flex-none sm:border-0 sm:bg-transparent sm:py-1">
                                {{ __('Cancelar') }}
                            </button>
                            <button id="image_editor_crop" type="button" class="min-h-11 flex-1 rounded-md bg-indigo-600 px-3 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-60 sm:min-h-0 sm:flex-none sm:py-1">
                                {{ __('Guardar') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="weapon-photo-toast" class="pointer-events-none fixed inset-x-4 bottom-[max(1rem,env(safe-area-inset-bottom))] z-[60] mx-auto hidden max-w-md rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-semibold text-emerald-800 shadow-lg sm:inset-x-auto sm:right-4 sm:mx-0" role="status" aria-live="polite"></div>

        <div id="weapon-photo-alert-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl" role="alertdialog" aria-modal="true">
                <h3 class="text-lg font-bold text-slate-900">{{ __('Aviso') }}</h3>
                <p id="weapon-photo-alert-message" class="mt-3 text-sm text-slate-600"></p>
                <div class="mt-5 flex justify-end">
                    <button type="button" id="weapon-photo-alert-ok" class="rounded-lg bg-[#0b6fb6] px-4 py-2 text-sm font-bold text-white">{{ __('Entendido') }}</button>
                </div>
            </div>
        </div>

        <div id="weapon-photo-confirm-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl" role="alertdialog" aria-modal="true" aria-labelledby="weapon-photo-confirm-title">
                <h3 id="weapon-photo-confirm-title" class="text-lg font-bold text-slate-900">{{ __('Atención') }}</h3>
                <p id="weapon-photo-confirm-message" class="mt-3 text-sm text-slate-600"></p>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" id="weapon-photo-confirm-cancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ __('Cancelar') }}
                    </button>
                    <button type="button" id="weapon-photo-confirm-discard" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                        {{ __('Salir sin guardar') }}
                    </button>
                    <button type="button" id="weapon-photo-confirm-save" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                        {{ __('Guardar cambios') }}
                    </button>
                </div>
            </div>
        </div>

        <input id="photo_pick_gallery" type="file" accept="image/jpeg,image/png,image/webp,image/*" class="hidden">
        <input id="photo_pick_camera" type="file" accept="image/jpeg,image/png,image/webp,image/*" capture="environment" class="hidden">

        @once
            @push('styles')
                <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
                <style>
                    .weapon-photo-surface-wrap > .sj-paste-proxy {
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

                    .weapon-photo-surface-wrap > .sj-paste-proxy::selection {
                        background: transparent;
                    }

                    .sj-image-editor-canvas {
                        max-height: min(42dvh, 380px);
                    }

                    .sj-image-editor-canvas .cropper-container,
                    .sj-image-editor-canvas .cropper-canvas,
                    .sj-image-editor-canvas .cropper-wrap-box,
                    .sj-image-editor-canvas img {
                        max-height: min(42dvh, 380px) !important;
                    }

                    @media (min-width: 640px) {
                        .sj-image-editor-canvas {
                            max-height: 55vh;
                        }

                        .sj-image-editor-canvas .cropper-container,
                        .sj-image-editor-canvas .cropper-canvas,
                        .sj-image-editor-canvas .cropper-wrap-box,
                        .sj-image-editor-canvas img {
                            max-height: 55vh !important;
                        }
                    }

                    .sj-toggle--photo-mode {
                        display: inline-flex;
                        align-items: center;
                        cursor: pointer;
                        user-select: none;
                    }

                    .sj-toggle--photo-mode .sj-toggle-input {
                        position: absolute;
                        width: 1px;
                        height: 1px;
                        padding: 0;
                        margin: -1px;
                        overflow: hidden;
                        clip: rect(0, 0, 0, 0);
                        white-space: nowrap;
                        border: 0;
                    }

                    .sj-toggle--photo-mode .sj-toggle-track {
                        position: relative;
                        display: inline-flex;
                        align-items: center;
                        width: 4.875rem;
                        height: 1.375rem;
                        border-radius: 9999px;
                        background: #fff1f2;
                        border: 1px solid #fda4af;
                        box-shadow:
                            inset 0 1px 2px rgba(255, 255, 255, 0.7),
                            0 0 0 1px rgba(251, 113, 133, 0.35),
                            0 0 10px rgba(244, 63, 94, 0.28);
                        transition: background 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
                        flex-shrink: 0;
                        overflow: hidden;
                    }

                    .sj-toggle--photo-mode .sj-toggle-track__text {
                        position: absolute;
                        top: 50%;
                        transform: translateY(-50%);
                        font-size: 0.625rem;
                        font-weight: 600;
                        letter-spacing: 0.02em;
                        line-height: 1;
                        pointer-events: none;
                        transition: opacity 0.18s ease, color 0.22s ease;
                        white-space: nowrap;
                    }

                    .sj-toggle--photo-mode .sj-toggle-track__text--idle {
                        right: 0.4rem;
                        color: #be123c;
                        opacity: 1;
                    }

                    .sj-toggle--photo-mode .sj-toggle-track__text--active {
                        left: 0.4rem;
                        color: #15803d;
                        opacity: 0;
                    }

                    .sj-toggle--photo-mode .sj-toggle-knob {
                        position: absolute;
                        top: 2px;
                        left: 2px;
                        width: 1rem;
                        height: 1rem;
                        border-radius: 9999px;
                        background: #ffffff;
                        border: 1px solid #fecdd3;
                        box-shadow:
                            0 1px 2px rgba(190, 18, 60, 0.12),
                            0 0 6px rgba(244, 63, 94, 0.2);
                        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.22s ease, box-shadow 0.22s ease;
                    }

                    .sj-toggle--photo-mode .sj-toggle-input:checked + .sj-toggle-track {
                        background: #ecfdf5;
                        border-color: #6ee7b7;
                        box-shadow:
                            inset 0 1px 2px rgba(255, 255, 255, 0.75),
                            0 0 0 1px rgba(52, 211, 153, 0.4),
                            0 0 10px rgba(16, 185, 129, 0.3);
                    }

                    .sj-toggle--photo-mode .sj-toggle-input:checked + .sj-toggle-track .sj-toggle-knob {
                        transform: translateX(3.625rem);
                        border-color: #a7f3d0;
                        box-shadow:
                            0 1px 2px rgba(21, 128, 61, 0.12),
                            0 0 6px rgba(16, 185, 129, 0.22);
                    }

                    .sj-toggle--photo-mode .sj-toggle-input:checked + .sj-toggle-track .sj-toggle-track__text--idle {
                        opacity: 0;
                    }

                    .sj-toggle--photo-mode .sj-toggle-input:checked + .sj-toggle-track .sj-toggle-track__text--active {
                        opacity: 1;
                    }

                    .sj-toggle--photo-mode .sj-toggle-input:focus-visible + .sj-toggle-track {
                        outline: 2px solid #6366f1;
                        outline-offset: 2px;
                    }

                    .sj-toggle--photo-mode .sj-toggle-input:disabled + .sj-toggle-track {
                        opacity: 0.55;
                        cursor: not-allowed;
                    }
                </style>
            @endpush
        @endonce
        @can('updatePhotos', $weapon)
            @push('scripts')
                <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
                @vite('resources/js/weapon-photo-editor.js')
            @endpush
        @endcan
    </div>
</div>
