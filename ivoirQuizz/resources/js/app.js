// Site public : menu mobile et bandeau d'information cookies.

const NOTICE_KEY = 'ivoirquizz-cookie-notice';

function readNotice() {
    try {
        return window.localStorage.getItem(NOTICE_KEY);
    } catch {
        return null;
    }
}

function storeNotice() {
    try {
        window.localStorage.setItem(NOTICE_KEY, '1');
    } catch {
        // Stockage indisponible (navigation privée) : le bandeau réapparaîtra, sans gravité.
    }
}

const notice = document.querySelector('[data-cookie-notice]');

if (notice && readNotice() !== '1') {
    notice.hidden = false;
    notice.querySelector('[data-cookie-dismiss]')?.addEventListener('click', () => {
        storeNotice();
        notice.hidden = true;
    });
}

const menuButton = document.querySelector('[data-menu-toggle]');
const menu = document.querySelector('[data-menu]');

menuButton?.addEventListener('click', () => {
    const open = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!open));
    menu.hidden = open;
});
