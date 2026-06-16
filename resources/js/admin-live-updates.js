import './echo';

const liveScanPage = document.querySelector('[data-live-scan-page]');

if (liveScanPage) {
    let reloadScheduled = false;

    window.Echo.private('admin.rfid-scans')
        .listen('.rfid.scan.recorded', () => {
            if (reloadScheduled) {
                return;
            }

            reloadScheduled = true;
            window.setTimeout(() => window.location.reload(), 250);
        });
}
