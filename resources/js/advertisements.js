const advertisementInput = document.querySelector('[data-ad-media-input]');

if (advertisementInput) {
    const previewWrap = document.querySelector('[data-ad-preview-wrap]');
    const imagePreview = document.querySelector('[data-ad-image-preview]');
    const videoPreview = document.querySelector('[data-ad-video-preview]');
    const fileLabel = document.querySelector('[data-ad-file-label]');
    let previewUrl = null;

    advertisementInput.addEventListener('change', () => {
        const file = advertisementInput.files?.[0];
        if (!file) return;

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
    const editHeading = advertisementEditDialog.querySelector('[data-ad-edit-heading]');
    const editTitle = advertisementEditDialog.querySelector('[data-ad-edit-title]');
    const editDescription = advertisementEditDialog.querySelector('[data-ad-edit-description]');
    const editStartsAt = advertisementEditDialog.querySelector('[data-ad-edit-starts-at]');
    const editEndsAt = advertisementEditDialog.querySelector('[data-ad-edit-ends-at]');
    const editMedia = advertisementEditDialog.querySelector('[data-ad-edit-media]');
    const editImagePreview = advertisementEditDialog.querySelector('[data-ad-edit-image-preview]');
    const editVideoPreview = advertisementEditDialog.querySelector('[data-ad-edit-video-preview]');
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
            editHeading.textContent = data.title;
            editTitle.value = data.title;
            editDescription.value = data.description || '';
            editStartsAt.value = data.startsAt || '';
            editEndsAt.value = data.endsAt || '';
            editMedia.value = '';
            showEditPreview(data.mediaType, data.mediaUrl);
            advertisementEditDialog.showModal();
            editTitle.focus();
        });
    });

    editMedia.addEventListener('change', () => {
        const file = editMedia.files?.[0];
        if (!file) return;

        if (editPreviewUrl) URL.revokeObjectURL(editPreviewUrl);
        editPreviewUrl = URL.createObjectURL(file);
        showEditPreview(file.type.startsWith('video/') ? 'video' : 'image', editPreviewUrl);
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
