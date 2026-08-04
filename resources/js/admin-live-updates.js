import './echo';

const liveScanPage = document.querySelector('[data-live-scan-page]');

const monitorClock = document.querySelector('[data-monitor-clock]');

if (monitorClock) {
    const updateClock = () => {
        monitorClock.textContent = new Intl.DateTimeFormat('en-PH', {
            dateStyle: 'medium',
            timeStyle: 'medium',
        }).format(new Date());
    };

    updateClock();
    window.setInterval(updateClock, 1000);
}

if (liveScanPage) {
    let reloadScheduled = false;
    const branchIds = (liveScanPage.dataset.branchIds || liveScanPage.dataset.branchId || '')
        .split(',')
        .filter(Boolean);

    branchIds.forEach((branchId) => window.Echo.private(`branches.${branchId}.rfid-scans`)
        .listen('.rfid.scan.recorded', () => {
            if (reloadScheduled) {
                return;
            }

            reloadScheduled = true;
            window.setTimeout(() => window.location.reload(), 250);
        }));
}

const activityDialog = document.querySelector('[data-activity-dialog]');

if (activityDialog) {
    const dialogPhoto = activityDialog.querySelector('[data-dialog-photo]');
    const photoFallback = activityDialog.querySelector('[data-dialog-photo-fallback]');
    let activeRow = null;

    const setText = (selector, value) => {
        activityDialog.querySelector(selector).textContent = value;
    };

    const openActivity = (row) => {
        const data = row.dataset;
        activeRow = row;

        setText('[data-dialog-name]', data.name);
        setText('[data-dialog-message]', data.message);
        setText('[data-dialog-campus-id]', data.campusId);
        setText('[data-dialog-branch]', data.branch);
        setText('[data-dialog-rfid]', data.rfid);
        setText('[data-dialog-program]', data.program);
        setText('[data-dialog-department]', data.department);
        setText('[data-dialog-type]', data.type);
        setText('[data-dialog-scanned-at]', data.scannedAt);

        const status = activityDialog.querySelector('[data-dialog-status]');
        const verified = data.status === 'valid';
        status.textContent = verified ? 'Entry verified' : 'Entry denied';
        status.className = `dialog-status ${verified ? 'valid' : 'invalid'}`;

        dialogPhoto.hidden = !data.photo;
        photoFallback.hidden = Boolean(data.photo);
        if (data.photo) {
            dialogPhoto.src = data.photo;
            dialogPhoto.alt = `Profile photo of ${data.name}`;
        } else {
            dialogPhoto.removeAttribute('src');
            dialogPhoto.alt = '';
        }

        activityDialog.showModal();
        activityDialog.querySelector('[data-dialog-close]').focus();
    };

    document.querySelectorAll('[data-activity-row]').forEach((row) => {
        row.addEventListener('click', () => openActivity(row));
        row.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openActivity(row);
            }
        });
    });

    const closeDialog = () => activityDialog.close();
    activityDialog.querySelector('[data-dialog-close]').addEventListener('click', closeDialog);
    activityDialog.addEventListener('click', (event) => {
        if (event.target === activityDialog) {
            closeDialog();
        }
    });
    activityDialog.addEventListener('close', () => activeRow?.focus());
}
