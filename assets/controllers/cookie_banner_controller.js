import { Controller } from '@hotwired/stimulus';

/**
 * Bannière d'information cookies (ePrivacy / RGPD belge).
 *
 * Logique :
 *  - connect() → si déjà vu (localStorage), suppression immédiate du DOM.
 *  - dismiss() → enregistre dans localStorage + animation de sortie.
 *
 * Pas de vrai "consentement" — inutile pour des cookies strictement nécessaires.
 */

const STORAGE_KEY = 'scenart_cookie_notice_dismissed';

export default class extends Controller {
    connect() {
        if (localStorage.getItem(STORAGE_KEY)) {
            // Déjà fermé lors d'une visite précédente — suppression silencieuse
            this.element.remove();
            return;
        }

        // Laisse le navigateur peindre le DOM avant d'animer
        requestAnimationFrame(() => {
            this.element.classList.add('cookie-banner--visible');
        });
    }

    dismiss() {
        localStorage.setItem(STORAGE_KEY, '1');
        this.element.classList.remove('cookie-banner--visible');
        this.element.classList.add('cookie-banner--hiding');

        // Retire le DOM après la transition CSS (300ms)
        this.element.addEventListener(
            'transitionend',
            () => this.element.remove(),
            { once: true }
        );
    }
}
