// Keyboard-wedge scanners finish a scan with Enter. Keep that key from
// implicitly submitting the edit form; saving remains an explicit action.
document.querySelectorAll('.rfid-edit-form').forEach((form) => {
    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target instanceof HTMLInputElement) {
            event.preventDefault();
        }
    });
});
