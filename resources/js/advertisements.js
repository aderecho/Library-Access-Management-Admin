const advertisementInput = document.querySelector('[data-ad-image-input]');

if (advertisementInput) {
    const previewWrap = document.querySelector('[data-ad-preview-wrap]');
    const preview = document.querySelector('[data-ad-preview]');
    const fileLabel = document.querySelector('[data-ad-file-label]');
    let previewUrl = null;

    advertisementInput.addEventListener('change', () => {
        const file = advertisementInput.files?.[0];
        if (!file) return;

        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        previewWrap.hidden = false;
        fileLabel.textContent = file.name;
    });

    window.addEventListener('beforeunload', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });
}
