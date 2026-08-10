const formatMegabytes = (bytes) => `${(bytes / (1024 * 1024)).toFixed(1)} MB`;

const notifyMediaLimit = (message) => {
    window.dispatchEvent(new CustomEvent('admin:notify', {
        detail: { type: 'warn', message, duration: 7000 },
    }));
};

const validateMediaSize = (input, warning) => {
    const file = input.files?.[0];

    if (!file) return true;

    input.setCustomValidity('');
    warning.hidden = true;
    warning.textContent = '';

    const isVideo = file.type.startsWith('video/');
    const maxBytes = Number(isVideo
        ? input.dataset.maxVideoBytes || 500 * 1024 * 1024
        : input.dataset.maxImageBytes || 50 * 1024 * 1024);
    if (file.size <= maxBytes) return true;

    const maxMegabytes = Math.round(maxBytes / (1024 * 1024));
    const message = `${file.name} is ${formatMegabytes(file.size)}. The maximum upload size is ${maxMegabytes} MB. Choose a smaller file.`;

    input.value = '';
    input.setCustomValidity(message);
    warning.textContent = message;
    warning.hidden = false;
    notifyMediaLimit(message);

    return false;
};

const advertisementInput = document.querySelector('[data-ad-media-input]');

if (advertisementInput) {
    const previewWrap = document.querySelector('[data-ad-preview-wrap]');
    const imagePreview = document.querySelector('[data-ad-image-preview]');
    const videoPreview = document.querySelector('[data-ad-video-preview]');
    const fileLabel = document.querySelector('[data-ad-file-label]');
    const mediaWarning = document.querySelector('[data-ad-media-warning]');
    const defaultFileLabel = fileLabel.textContent;
    let previewUrl = null;

    const resetPreview = () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        imagePreview.removeAttribute('src');
        videoPreview.pause();
        videoPreview.removeAttribute('src');
        previewWrap.hidden = true;
        fileLabel.textContent = defaultFileLabel;
    };

    advertisementInput.addEventListener('change', () => {
        const file = advertisementInput.files?.[0];
        if (!file) return;

        if (!validateMediaSize(advertisementInput, mediaWarning)) {
            resetPreview();
            return;
        }

        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        const isVideo = file.type.startsWith('video/');
        imagePreview.hidden = isVideo;
        videoPreview.hidden = !isVideo;

        if (isVideo) {
            imagePreview.removeAttribute('src');
            videoPreview.src = previewUrl;
            videoPreview.load();
        } else {
            videoPreview.pause();
            videoPreview.removeAttribute('src');
            imagePreview.src = previewUrl;
        }
        previewWrap.hidden = false;
        fileLabel.textContent = file.name;
    });

    window.addEventListener('beforeunload', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });
}

const advertisementEditDialog = document.querySelector('[data-ad-edit-dialog]');

if (advertisementEditDialog) {
    const editForm = advertisementEditDialog.querySelector('[data-ad-edit-form]');
    const deleteDialog = document.querySelector('[data-ad-delete-dialog]');
    const deleteForm = deleteDialog.querySelector('[data-ad-delete-form]');
    const deleteButton = advertisementEditDialog.querySelector('[data-ad-edit-delete]');
    const deleteName = deleteDialog.querySelector('[data-ad-delete-name]');
    const deleteCancel = deleteDialog.querySelector('[data-ad-delete-cancel]');
    const editHeading = advertisementEditDialog.querySelector('[data-ad-edit-heading]');
    const editTitle = advertisementEditDialog.querySelector('[data-ad-edit-title]');
    const editDescription = advertisementEditDialog.querySelector('[data-ad-edit-description]');
    const editStartsAt = advertisementEditDialog.querySelector('[data-ad-edit-starts-at]');
    const editEndsAt = advertisementEditDialog.querySelector('[data-ad-edit-ends-at]');
    const editMedia = advertisementEditDialog.querySelector('[data-ad-edit-media]');
    const editImagePreview = advertisementEditDialog.querySelector('[data-ad-edit-image-preview]');
    const editVideoPreview = advertisementEditDialog.querySelector('[data-ad-edit-video-preview]');
    const editMediaWarning = advertisementEditDialog.querySelector('[data-ad-edit-media-warning]');
    let editPreviewUrl = null;

    const showEditPreview = (mediaType, mediaUrl) => {
        const isVideo = mediaType === 'video';
        editImagePreview.hidden = isVideo;
        editVideoPreview.hidden = !isVideo;

        if (isVideo) {
            editImagePreview.removeAttribute('src');
            editVideoPreview.src = mediaUrl;
            editVideoPreview.load();
        } else {
            editVideoPreview.pause();
            editVideoPreview.removeAttribute('src');
            editImagePreview.src = mediaUrl;
        }
    };

    const closeEditDialog = () => advertisementEditDialog.close();

    document.querySelectorAll('[data-edit-advertisement]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = button.dataset;
            editForm.action = data.updateUrl;
            deleteForm.action = data.deleteUrl;
            deleteName.textContent = `“${data.title}”`;
            editHeading.textContent = data.title;
            editTitle.value = data.title;
            editDescription.value = data.description || '';
            editStartsAt.value = data.startsAt || '';
            editEndsAt.value = data.endsAt || '';
            editMedia.value = '';
            editMedia.setCustomValidity('');
            editMediaWarning.hidden = true;
            editMediaWarning.textContent = '';
            showEditPreview(data.mediaType, data.mediaUrl);
            advertisementEditDialog.showModal();
            editTitle.focus();
        });
    });

    editMedia.addEventListener('change', () => {
        const file = editMedia.files?.[0];
        if (!file) return;

        if (!validateMediaSize(editMedia, editMediaWarning)) return;

        if (editPreviewUrl) URL.revokeObjectURL(editPreviewUrl);
        editPreviewUrl = URL.createObjectURL(file);
        showEditPreview(file.type.startsWith('video/') ? 'video' : 'image', editPreviewUrl);
    });

    deleteButton.addEventListener('click', () => {
        deleteDialog.showModal();
        deleteCancel.focus();
    });

    deleteCancel.addEventListener('click', () => deleteDialog.close());
    deleteDialog.addEventListener('click', (event) => {
        if (event.target === deleteDialog) deleteDialog.close();
    });
    deleteDialog.addEventListener('close', () => {
        if (advertisementEditDialog.open) deleteButton.focus();
    });

    advertisementEditDialog.querySelector('[data-ad-edit-close]').addEventListener('click', closeEditDialog);
    advertisementEditDialog.querySelector('[data-ad-edit-cancel]').addEventListener('click', closeEditDialog);
    advertisementEditDialog.addEventListener('click', (event) => {
        if (event.target === advertisementEditDialog) closeEditDialog();
    });
    advertisementEditDialog.addEventListener('close', () => {
        editVideoPreview.pause();
        if (editPreviewUrl) {
            URL.revokeObjectURL(editPreviewUrl);
            editPreviewUrl = null;
        }
    });
}
