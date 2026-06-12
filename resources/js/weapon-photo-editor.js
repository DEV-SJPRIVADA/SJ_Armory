const MAX_EXPORT_WIDTH = 1920;
const MAX_EXPORT_HEIGHT = 1920;
const JPEG_QUALITY = 0.88;
const TOAST_MS = 4500;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function parseConfig(root) {
    const raw = root.dataset.weaponPhotoEditorConfig;

    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch (error) {
        console.error('weapon-photo-editor config', error);
        return null;
    }
}

export function initWeaponPhotoEditor(root) {
    if (!root || root.dataset.weaponPhotoEditorReady === '1') {
        return;
    }

    const config = parseConfig(root);
    if (!config) {
        return;
    }

    if (typeof window.Cropper === 'undefined') {
        console.warn('Cropper.js is required for weapon-photo-editor');
        return;
    }

    root.dataset.weaponPhotoEditorReady = '1';

    const photoEditToggle = document.getElementById('photo_edit_toggle');
    const photoGrid = document.getElementById('weapon-photo-grid');
    const photoCards = Array.from(document.querySelectorAll('.weapon-photo-card[data-photo-editable]'));
    const dropZones = Array.from(document.querySelectorAll('[data-drop-zone]'));

    const actionModal = document.getElementById('photo_action_modal');
    const actionCrop = document.getElementById('photo_action_crop');
    const actionChange = document.getElementById('photo_action_change');
    const actionCancel = document.getElementById('photo_action_cancel');
    const pickGallery = document.getElementById('photo_pick_gallery');
    const pickCamera = document.getElementById('photo_pick_camera');
    const sourceModal = document.getElementById('photo_source_modal');
    const sourceCameraBtn = document.getElementById('photo_source_camera');
    const sourceGalleryBtn = document.getElementById('photo_source_gallery');
    const sourceCancelBtn = document.getElementById('photo_source_cancel');

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

    const photoToast = document.getElementById('weapon-photo-toast');
    const photoAlertModal = document.getElementById('weapon-photo-alert-modal');
    const photoAlertMessage = document.getElementById('weapon-photo-alert-message');
    const photoAlertOk = document.getElementById('weapon-photo-alert-ok');

    const confirmModal = document.getElementById('weapon-photo-confirm-modal');
    const confirmTitle = document.getElementById('weapon-photo-confirm-title');
    const confirmMessage = document.getElementById('weapon-photo-confirm-message');
    const confirmSave = document.getElementById('weapon-photo-confirm-save');
    const confirmDiscard = document.getElementById('weapon-photo-confirm-discard');
    const confirmCancel = document.getElementById('weapon-photo-confirm-cancel');

    const confirmDefaults = {
        title: confirmTitle?.textContent?.trim() || '',
        discard: confirmDiscard?.textContent?.trim() || '',
        save: confirmSave?.textContent?.trim() || '',
    };

    let isEditing = false;
    let editorOpen = false;
    let editorDirty = false;
    let activePhotoId = null;
    let activePhotoSrc = null;
    let activePhotoType = 'weapon';
    let activePhotoDescription = null;
    let activePhotoCard = null;
    let cropper = null;
    let editorFineRotation = 0;
    let hoveredPasteZone = null;
    let isPhotoUploading = false;
    let photoToastTimer = null;
    let pendingNavigation = null;
    let confirmResolver = null;

    const csrfHeader = () => document.querySelector('meta[name="csrf-token"]')?.content || config.csrfToken;

    const syncToggleUi = () => {
        if (!photoEditToggle) {
            return;
        }

        photoEditToggle.checked = isEditing;
        photoEditToggle.setAttribute('aria-checked', isEditing ? 'true' : 'false');
        photoEditToggle.setAttribute(
            'aria-label',
            isEditing ? config.txtToggleFinishEdit : config.txtToggleStartEdit
        );
    };

    const syncDeleteControlsVisibility = () => {
        photoCards.forEach((card) => {
            const wrap = card.querySelector('[data-photo-actions]');
            if (!wrap) {
                return;
            }

            const hasDelete = wrap.querySelector('[data-photo-delete]');
            wrap.classList.toggle('hidden', !isEditing || !hasDelete);
        });
    };

    const setEditing = (enabled) => {
        isEditing = enabled;
        syncToggleUi();
        photoGrid?.classList.toggle('photo-editing', enabled);
        photoCards.forEach((card) => {
            card.classList.toggle('cursor-pointer', enabled);
            card.classList.toggle('ring-2', enabled);
            card.classList.toggle('ring-indigo-300', enabled);
        });
        syncDeleteControlsVisibility();
    };

    const isEditorBlocking = () => editorOpen && editorDirty;
    const isNavigationRisky = () => isPhotoUploading || isEditorBlocking();
    const isSoftEditSession = () => isEditing && !isNavigationRisky();

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

    const getSurfaceHeightClass = (card) => {
        const surface = card?.querySelector('[data-drop-surface]');
        if (!surface) {
            return 'h-32';
        }

        return [...surface.classList].find((className) => className.startsWith('h-')) || 'h-32';
    };

    const buildPlaceholderSurface = (card, label) => {
        const heightClass = getSurfaceHeightClass(card);

        return `
            <div class="flex ${heightClass} w-full items-center justify-center rounded border border-dashed border-gray-300 bg-gray-50 text-center text-sm text-gray-400 transition" data-drop-surface>
                <div>
                    <div class="font-medium">${escapeHtml(config.txtPendingPhoto)}</div>
                    <div class="mt-1 text-xs text-gray-400">${escapeHtml(label)}</div>
                </div>
            </div>
        `;
    };

    const buildImageSurface = (card, slot) => {
        const heightClass = getSurfaceHeightClass(card);
        const alt = escapeHtml(slot.label || '');

        return `<img src="${escapeHtml(slot.url)}" alt="${alt}" class="${heightClass} w-full rounded object-contain bg-gray-50" data-drop-surface>`;
    };

    const syncDeleteControl = (card, slot) => {
        const wrap = card.querySelector('[data-photo-actions]');
        if (!wrap) {
            return;
        }

        if (slot.empty || slot.type === 'permit' || !slot.destroy_url) {
            wrap.innerHTML = '';
            wrap.classList.add('hidden');
            return;
        }

        wrap.innerHTML = `
            <button
                type="button"
                class="cursor-pointer px-1 py-0.5 text-red-600 hover:text-red-900 hover:underline"
                data-photo-delete
                data-destroy-url="${escapeHtml(slot.destroy_url)}"
            >${escapeHtml(config.txtDelete)}</button>
        `;
        wrap.classList.toggle('hidden', !isEditing);
    };

    const applySlotToCard = (card, slot) => {
        if (!card || !slot) {
            return;
        }

        const host = card.querySelector('[data-photo-surface-host]');
        const label = slot.label || card.dataset.photoLabel || '';
        const dateEl = card.querySelector('[data-photo-date]');

        card.dataset.photoId = slot.id ? String(slot.id) : '';
        card.dataset.photoSrc = slot.url || '';
        card.dataset.photoEmpty = slot.empty ? '1' : '0';

        if (slot.description) {
            card.dataset.photoDescription = slot.description;
        }

        if (host) {
            host.innerHTML = slot.empty || !slot.url
                ? buildPlaceholderSurface(card, label)
                : buildImageSurface(card, slot);
        }

        if (dateEl) {
            dateEl.textContent = slot.empty
                ? config.txtPending
                : (slot.created_at || config.txtPending);
        }

        syncDeleteControl(card, slot);
    };

    const showPhotoAlert = (message) => new Promise((resolve) => {
        if (!photoAlertModal) {
            resolve();
            return;
        }

        if (photoAlertMessage) {
            photoAlertMessage.textContent = message;
        }

        const onOk = () => {
            photoAlertModal.classList.add('hidden');
            photoAlertModal.classList.remove('flex');
            photoAlertOk?.removeEventListener('click', onOk);
            resolve();
        };

        photoAlertOk?.addEventListener('click', onOk);
        photoAlertModal.classList.remove('hidden');
        photoAlertModal.classList.add('flex');
    });

    const resetConfirmModalUi = () => {
        if (confirmTitle) {
            confirmTitle.textContent = confirmDefaults.title;
        }

        if (confirmDiscard) {
            confirmDiscard.textContent = confirmDefaults.discard;
        }

        if (confirmSave) {
            confirmSave.textContent = confirmDefaults.save;
        }
    };

    const showConfirm = (message, {
        showSave = true,
        showDiscard = true,
        title = null,
        discardLabel = null,
    } = {}) => new Promise((resolve) => {
        if (!confirmModal) {
            resolve('cancel');
            return;
        }

        confirmResolver = resolve;
        if (confirmMessage) {
            confirmMessage.textContent = message;
        }

        if (confirmTitle) {
            confirmTitle.textContent = title || confirmDefaults.title;
        }

        if (confirmDiscard) {
            confirmDiscard.textContent = discardLabel || confirmDefaults.discard;
        }

        confirmSave?.classList.toggle('hidden', !showSave);
        confirmDiscard?.classList.toggle('hidden', !showDiscard);
        confirmModal.classList.remove('hidden');
        confirmModal.classList.add('flex');
    });

    const closeConfirm = () => {
        confirmModal?.classList.add('hidden');
        confirmModal?.classList.remove('flex');
        resetConfirmModalUi();
        confirmResolver = null;
    };

    const resolveConfirm = (action) => {
        const resolver = confirmResolver;
        closeConfirm();
        resolver?.(action);
    };

    const showPhotoToast = (message) => {
        if (!photoToast) {
            return;
        }

        photoToast.textContent = message;
        photoToast.classList.remove('hidden');
        if (photoToastTimer) {
            clearTimeout(photoToastTimer);
        }
        photoToastTimer = window.setTimeout(() => {
            photoToast.classList.add('hidden');
            photoToastTimer = null;
        }, TOAST_MS);
    };

    const setPhotoUploading = (active) => {
        isPhotoUploading = active;
        if (cropButton) {
            cropButton.disabled = active;
            cropButton.textContent = active ? config.txtSaving : config.txtSave;
        }
        [closeButton, cancelButton].forEach((btn) => {
            if (btn) {
                btn.disabled = active;
            }
        });
    };

    const markEditorDirty = () => {
        if (editorOpen) {
            editorDirty = true;
        }
    };

    const syncFineRotationUi = () => {
        if (fineRotateInput) {
            fineRotateInput.value = editorFineRotation.toString();
        }

        if (fineRotateValue) {
            fineRotateValue.textContent = `${editorFineRotation.toFixed(1)}°`;
        }
    };

    const applyFineRotationDelta = (diff) => {
        if (cropper && diff !== 0) {
            cropper.rotate(diff);
            markEditorDirty();
        }
    };

    const initCropper = () => {
        if (cropper) {
            cropper.destroy();
        }

        cropper = new window.Cropper(editorImage, {
            viewMode: 0,
            autoCropArea: 1,
            toggleDragModeOnDblclick: false,
            responsive: true,
            crop: markEditorDirty,
            zoom: markEditorDirty,
        });
    };

    const closeEditor = ({ discardDirty = true } = {}) => {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        editorOpen = false;
        if (discardDirty) {
            editorDirty = false;
        }

        editorModal?.classList.add('hidden');
        editorModal?.classList.remove('flex');

        if (editorImage?.dataset.objectUrl) {
            URL.revokeObjectURL(editorImage.dataset.objectUrl);
            delete editorImage.dataset.objectUrl;
        }

        editorImage?.removeAttribute('src');
        editorFineRotation = 0;
        syncFineRotationUi();
    };

    const openEditor = (source, revokeAfter = false) => {
        if (!editorImage || !editorModal) {
            return;
        }

        if (editorImage.dataset.objectUrl) {
            URL.revokeObjectURL(editorImage.dataset.objectUrl);
            delete editorImage.dataset.objectUrl;
        }

        if (revokeAfter) {
            editorImage.removeAttribute('crossorigin');
            editorImage.dataset.objectUrl = source;
        } else {
            editorImage.crossOrigin = 'anonymous';
        }

        editorModal.classList.remove('hidden');
        editorModal.classList.add('flex');
        editorOpen = true;
        editorDirty = false;
        editorFineRotation = 0;
        syncFineRotationUi();

        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        const onReady = () => {
            editorImage.removeEventListener('load', onReady);
            editorImage.removeEventListener('error', onError);
            initCropper();
        };

        const onError = () => {
            editorImage.removeEventListener('load', onReady);
            editorImage.removeEventListener('error', onError);
            showPhotoAlert(config.txtCanvasError);
            closeEditor();
        };

        editorImage.addEventListener('load', onReady);
        editorImage.addEventListener('error', onError);
        editorImage.src = source;
    };

    const openEditorFromFile = (card, file) => {
        if (!isEditing || !card || !file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            showPhotoAlert(config.txtImagesOnly);
            return;
        }

        setActivePhotoFromCard(card);
        activePhotoCard = card;
        closeActionModal();

        const url = URL.createObjectURL(file);
        openEditor(url, true);
    };

    const openSourceModal = () => {
        sourceModal?.classList.remove('hidden');
        sourceModal?.classList.add('flex');
    };

    const closeSourceModal = () => {
        sourceModal?.classList.add('hidden');
        sourceModal?.classList.remove('flex');
    };

    const triggerPhotoPick = (useCamera) => {
        const input = useCamera ? pickCamera : pickGallery;
        if (!input) {
            return;
        }

        closeSourceModal();
        input.value = '';
        input.click();
    };

    const handlePhotoPickChange = (event) => {
        const file = event.target?.files?.[0];
        if (!file || !activePhotoCard) {
            return;
        }

        openEditorFromFile(activePhotoCard, file);
        event.target.value = '';
    };

    const openActionModal = () => {
        actionModal?.classList.remove('hidden');
        actionModal?.classList.add('flex');
    };

    const closeActionModal = () => {
        actionModal?.classList.add('hidden');
        actionModal?.classList.remove('flex');
    };

    const activateCard = (card) => {
        if (!isEditing || !card) {
            return;
        }

        setActivePhotoFromCard(card);
        activePhotoCard = card;

        if (card.dataset.photoEmpty === '1') {
            openSourceModal();
            return;
        }

        openActionModal();
    };

    const parsePhotoUploadError = async (response) => {
        const contentType = response.headers.get('content-type') || '';
        if (response.status === 419) {
            return config.txtSessionExpired;
        }

        if (contentType.includes('application/json')) {
            try {
                const data = await response.json();
                const first = data?.message
                    || data?.errors?.photo?.[0]
                    || Object.values(data?.errors || {}).flat()?.[0];
                if (first) {
                    return String(first);
                }
            } catch (error) {
                /* ignore */
            }
        }

        return config.txtGenericError;
    };

    const exportCroppedBlob = () => new Promise((resolve, reject) => {
        if (!cropper) {
            reject(new Error(config.txtCanvasError));
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
            reject(new Error(config.txtCanvasError));
            return;
        }

        canvas.toBlob((blob) => {
            if (!blob) {
                reject(new Error(config.txtCanvasError));
                return;
            }
            resolve(blob);
        }, 'image/jpeg', JPEG_QUALITY);
    });

    const uploadCropped = async (blob) => {
        const formData = new FormData();
        const fileName = activePhotoType === 'permit'
            ? 'permit.jpg'
            : `photo_${activePhotoDescription || activePhotoId || 'new'}.jpg`;
        const file = new File([blob], fileName, { type: blob.type || 'image/jpeg' });
        formData.append('photo', file);

        let url = config.storeUrl;
        const method = 'POST';

        if (activePhotoType === 'permit') {
            formData.append('_method', 'PATCH');
            url = config.updatePermitUrl;
        } else if (activePhotoId) {
            formData.append('_method', 'PATCH');
            url = config.updateUrlBase.replace(/\/0$/, `/${activePhotoId}`);
        } else {
            formData.append('description', activePhotoDescription || '');
        }

        let response;
        try {
            response = await fetch(url, {
                method,
                headers: {
                    'X-CSRF-TOKEN': csrfHeader(),
                    Accept: 'application/json',
                },
                body: formData,
                credentials: 'same-origin',
            });
        } catch (error) {
            throw new Error(config.txtNetworkError);
        }

        if (!response.ok) {
            throw new Error(await parsePhotoUploadError(response));
        }

        const data = await response.json();
        if (data?.ok !== true || !data?.slot) {
            throw new Error(config.txtGenericError);
        }

        return data.slot;
    };

    const applyCrop = async () => {
        if (!cropper || isPhotoUploading) {
            return false;
        }

        setPhotoUploading(true);

        try {
            const blob = await exportCroppedBlob();
            const slot = await uploadCropped(blob);
            if (activePhotoCard) {
                applySlotToCard(activePhotoCard, slot);
            }
            closeEditor();
            showPhotoToast(config.txtSaved);
            return true;
        } catch (error) {
            showPhotoAlert(error?.message || config.txtGenericError);
            return false;
        } finally {
            setPhotoUploading(false);
        }
    };

    const deletePhoto = async (card, destroyUrl) => {
        if (!isEditing || !card || !destroyUrl) {
            return;
        }

        const action = await showConfirm(config.txtDeleteConfirm, {
            showSave: false,
            title: config.txtDeleteConfirmTitle || config.txtDelete,
            discardLabel: config.txtDelete,
        });

        if (action !== 'discard') {
            return;
        }

        setPhotoUploading(true);

        try {
            const response = await fetch(destroyUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfHeader(),
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(await parsePhotoUploadError(response));
            }

            const data = await response.json();
            if (data?.ok !== true || !data?.slot) {
                throw new Error(config.txtGenericError);
            }

            applySlotToCard(card, data.slot);
            showPhotoToast(config.txtDeleted);
        } catch (error) {
            showPhotoAlert(error?.message || config.txtGenericError);
        } finally {
            setPhotoUploading(false);
        }
    };

    const attemptFinishEditing = async () => {
        if (isPhotoUploading) {
            await showPhotoAlert(config.txtUploadInProgress);
            return false;
        }

        if (isEditorBlocking()) {
            const action = await showConfirm(config.txtUnsavedEditor, { showSave: true });
            if (action === 'save') {
                const saved = await applyCrop();
                if (!saved) {
                    return false;
                }
            } else if (action === 'discard') {
                closeEditor();
            } else {
                return false;
            }
        } else if (editorOpen) {
            closeEditor();
        }

        setEditing(false);
        return true;
    };

    const runPendingNavigation = () => {
        if (!pendingNavigation) {
            return;
        }

        const { href } = pendingNavigation;
        pendingNavigation = null;
        window.location.href = href;
    };

    const confirmNavigationAway = async () => {
        if (isNavigationRisky()) {
            const action = await showConfirm(config.txtUnsavedChanges, { showSave: true });
            if (action === 'save') {
                const saved = await applyCrop();
                if (!saved) {
                    return false;
                }
                await attemptFinishEditing();
            } else if (action === 'discard') {
                closeEditor();
                setEditing(false);
            } else {
                return false;
            }
        } else if (isSoftEditSession()) {
            const action = await showConfirm(config.txtEditSessionActive, { showSave: false });
            if (action === 'discard') {
                setEditing(false);
            } else {
                return false;
            }
        }

        return true;
    };

    syncDeleteControlsVisibility();

    if (photoEditToggle) {
        photoEditToggle.setAttribute('role', 'switch');
        syncToggleUi();

        photoEditToggle.addEventListener('change', async () => {
            if (photoEditToggle.checked) {
                setEditing(true);
                return;
            }

            const finished = await attemptFinishEditing();
            if (!finished) {
                syncToggleUi();
            }
        });
    }

    photoCards.forEach((card) => {
        card.addEventListener('click', (event) => {
            if (event.target.closest('[data-photo-delete]')) {
                return;
            }
            activateCard(card);
        });
    });

    photoGrid?.addEventListener('click', (event) => {
        const deleteButton = event.target.closest('[data-photo-delete]');
        if (!deleteButton) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        const card = deleteButton.closest('.weapon-photo-card');
        deletePhoto(card, deleteButton.dataset.destroyUrl);
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
        openSourceModal();
    });

    sourceCameraBtn?.addEventListener('click', () => triggerPhotoPick(true));
    sourceGalleryBtn?.addEventListener('click', () => triggerPhotoPick(false));
    sourceCancelBtn?.addEventListener('click', closeSourceModal);
    pickGallery?.addEventListener('change', handlePhotoPickChange);
    pickCamera?.addEventListener('change', handlePhotoPickChange);

    closeButton?.addEventListener('click', () => closeEditor());
    cancelButton?.addEventListener('click', () => closeEditor());
    cropButton?.addEventListener('click', applyCrop);

    rotateLeftButton?.addEventListener('click', () => {
        if (!cropper) {
            return;
        }
        if (editorFineRotation !== 0) {
            cropper.rotate(-editorFineRotation);
            editorFineRotation = 0;
            syncFineRotationUi();
        }
        cropper.rotate(-90);
        markEditorDirty();
    });

    rotateRightButton?.addEventListener('click', () => {
        if (!cropper) {
            return;
        }
        if (editorFineRotation !== 0) {
            cropper.rotate(-editorFineRotation);
            editorFineRotation = 0;
            syncFineRotationUi();
        }
        cropper.rotate(90);
        markEditorDirty();
    });

    fineRotateInput?.addEventListener('input', () => {
        const next = Number.parseFloat(fineRotateInput.value || '0') || 0;
        const diff = next - editorFineRotation;
        editorFineRotation = next;
        applyFineRotationDelta(diff);
        syncFineRotationUi();
    });

    resetRotateButton?.addEventListener('click', () => {
        if (cropper) {
            cropper.reset();
            markEditorDirty();
        }
        editorFineRotation = 0;
        syncFineRotationUi();
    });

    confirmSave?.addEventListener('click', () => resolveConfirm('save'));
    confirmDiscard?.addEventListener('click', () => resolveConfirm('discard'));
    confirmCancel?.addEventListener('click', () => resolveConfirm('cancel'));

    photoAlertOk?.addEventListener('click', () => {
        photoAlertModal?.classList.add('hidden');
        photoAlertModal?.classList.remove('flex');
    });

    document.addEventListener('click', async (event) => {
        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        if (link.origin !== window.location.origin) {
            return;
        }

        if (!isNavigationRisky() && !isSoftEditSession()) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const allowed = await confirmNavigationAway();
        if (!allowed) {
            return;
        }

        pendingNavigation = { href: link.href };
        runPendingNavigation();
    }, true);

    window.addEventListener('beforeunload', (event) => {
        if (!isNavigationRisky() && !isSoftEditSession()) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-weapon-photo-editor]').forEach((root) => {
        initWeaponPhotoEditor(root);
    });
});
