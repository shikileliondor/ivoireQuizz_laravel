// Comportements légers du back-office : menu mobile, confirmations et aperçu d'image.

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-sidebar-toggle]');
    if (toggle) {
        document.documentElement.classList.toggle('sidebar-open');
    }
});

document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('change', (event) => {
    const input = event.target;
    if (!input.matches('input[type="file"][data-preview]')) {
        return;
    }

    const preview = document.getElementById(input.dataset.preview);
    const [file] = input.files;
    if (preview && file) {
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
    }
});
