const warning = document.querySelector('[data-session-warning]');

if (warning) {
    const countdown = warning.querySelector('[data-session-countdown]');
    const ring = warning.querySelector('[data-session-countdown-ring]');
    const continueButton = warning.querySelector('[data-session-continue]');
    const continueLabel = warning.querySelector('[data-session-continue-label]');
    const status = warning.querySelector('[data-session-status]');
    const dialog = warning.querySelector('[role="alertdialog"]');
    const timeoutSeconds = Math.max(1, Number(warning.dataset.timeoutSeconds));
    const warningSeconds = Math.min(timeoutSeconds, Math.max(5, Number(warning.dataset.warningSeconds)));
    const storageKey = 'up-cebu-session-extended-at';
    let deadline = Date.now() + (timeoutSeconds * 1000);
    let isOpen = false;
    let isExtending = false;
    let isExpired = false;
    let lastFocusedElement = null;

    const formatTime = (seconds) => {
        const minutes = Math.floor(seconds / 60);
        const remainder = seconds % 60;

        return `${minutes}:${String(remainder).padStart(2, '0')}`;
    };

    const closeWarning = () => {
        if (!isOpen) return;

        isOpen = false;
        warning.classList.remove('is-visible');
        warning.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('session-warning-open');
        status.textContent = '';
        lastFocusedElement?.focus?.();
    };

    const openWarning = () => {
        if (isOpen) return;

        isOpen = true;
        lastFocusedElement = document.activeElement;
        warning.setAttribute('aria-hidden', 'false');
        warning.classList.add('is-visible');
        document.body.classList.add('session-warning-open');
        window.requestAnimationFrame(() => continueButton.focus());
    };

    const expireSession = () => {
        if (isExpired) return;

        isExpired = true;
        status.textContent = 'Session expired. Redirecting to sign in…';
        continueButton.disabled = true;
        window.setTimeout(() => window.location.assign(warning.dataset.loginUrl), 500);
    };

    const render = () => {
        const secondsLeft = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        const progress = Math.max(0, Math.min(1, secondsLeft / warningSeconds));

        countdown.textContent = formatTime(secondsLeft);
        ring.style.setProperty('--session-progress', `${progress * 360}deg`);

        if (secondsLeft <= warningSeconds) openWarning();
        if (secondsLeft <= 0) expireSession();
    };

    const resetDeadline = (extendedAt = Date.now()) => {
        deadline = extendedAt + (timeoutSeconds * 1000);
        isExpired = false;
        continueButton.disabled = false;
        continueLabel.textContent = 'Stay signed in';
        closeWarning();
        render();
    };

    continueButton.addEventListener('click', async () => {
        if (isExtending) return;

        isExtending = true;
        continueButton.disabled = true;
        continueLabel.textContent = 'Extending session…';
        status.textContent = 'Securely refreshing your session.';

        try {
            const response = await fetch(warning.dataset.keepAliveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: '{}',
            });

            if (!response.ok) throw new Error(`Session refresh failed with status ${response.status}`);

            const extendedAt = Date.now();
            window.localStorage.setItem(storageKey, String(extendedAt));
            resetDeadline(extendedAt);
        } catch {
            continueButton.disabled = false;
            continueLabel.textContent = 'Try again';
            status.textContent = 'We could not extend your session. Please try again or log out safely.';
        } finally {
            isExtending = false;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (!isOpen || event.key !== 'Tab') return;

        const focusable = [...dialog.querySelectorAll('button:not(:disabled)')];
        const first = focusable[0];
        const last = focusable.at(-1);

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    window.addEventListener('storage', (event) => {
        if (event.key !== storageKey || !event.newValue) return;
        resetDeadline(Number(event.newValue));
    });

    window.addEventListener('session-expiry:preview', (event) => {
        const previewSeconds = Math.max(1, Number(event.detail?.seconds ?? 15));
        deadline = Date.now() + (previewSeconds * 1000);
        openWarning();
        render();
    });

    window.setInterval(render, 1000);
    render();
}
