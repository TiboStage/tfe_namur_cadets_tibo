// assets/controllers/theme_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    connect() {
        // Restaure le thème sauvegardé au chargement
        const saved = localStorage.getItem('theme');
        if (saved === 'light') {
            document.documentElement.classList.add('light');
        }
    }

    toggle() {
        document.documentElement.classList.toggle('light');
        localStorage.setItem(
            'theme',
            document.documentElement.classList.contains('light') ? 'light' : 'dark'
        );
    }
}
