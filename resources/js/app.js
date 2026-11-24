import './bootstrap';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const themeKey = 'dashboard-theme';

function updateThemeToggle(button, dark) {
    const sun = button.querySelector('[data-theme-sun]');
    const moon = button.querySelector('[data-theme-moon]');
    const label = button.querySelector('[data-theme-label]');
    const action = dark ? 'terang' : 'gelap';

    sun?.classList.toggle('hidden', !dark);
    moon?.classList.toggle('hidden', dark);
    if (label) label.textContent = `Mode ${action}`;
    button.setAttribute('aria-label', `Aktifkan mode ${action}`);
    button.setAttribute('title', `Aktifkan mode ${action}`);
    button.setAttribute('aria-pressed', String(dark));
}

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    updateThemeToggle(button, document.documentElement.classList.contains('dark'));
    button.addEventListener('click', () => {
        const dark = !document.documentElement.classList.contains('dark');
        document.documentElement.classList.toggle('dark', dark);
        try {
            window.localStorage.setItem(themeKey, dark ? 'dark' : 'light');
        } catch (error) {
            console.error('Preferensi tema tidak dapat disimpan.', error);
        }
        document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => updateThemeToggle(toggle, dark));
        window.dispatchEvent(new CustomEvent('dashboardThemeChanged', { detail: { dark } }));
    });
});

window.Chart = Chart;
window.L = L;
window.dispatchEvent(new Event('dashboardLibrariesReady'));
