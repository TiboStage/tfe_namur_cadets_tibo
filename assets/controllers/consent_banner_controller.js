import { Controller } from '@hotwired/stimulus';

/**
 * Gestion du consentement cookies (RGPD / ePrivacy belge).
 *
 * Stockage : localStorage → clé 'scenart_cookie_consent'
 * Format   : { necessary: true, functional: bool, analytics: bool, savedAt: ISO }
 *
 * Évènement global 'consent:manage' → ouvre la modal depuis le footer.
 */

const STORAGE_KEY = 'scenart_cookie_consent';

export default class extends Controller {
    static targets = ['modal', 'functionalToggle', 'analyticsToggle'];

    // ── Lifecycle ───────────────────────────────────────────

    connect() {
        // Écoute le lien footer "Gérer mes cookies"
        this._manageHandler = () => this.manage();
        document.addEventListener('consent:manage', this._manageHandler);

        // Affiche la bannière uniquement au premier passage
        if (!this.#hasConsent()) {
            requestAnimationFrame(() => {
                this.element.classList.add('consent-wrapper--visible');
            });
        }
    }

    disconnect() {
        document.removeEventListener('consent:manage', this._manageHandler);
    }

    // ── Actions bannière ────────────────────────────────────

    acceptAll() {
        this.#save({ necessary: true, functional: true, analytics: true });
        this.#closeBanner();
    }

    refuseAll() {
        this.#save({ necessary: true, functional: false, analytics: false });
        this.#closeBanner();
    }

    // ── Actions modal ───────────────────────────────────────

    openModal() {
        const consent = this.#getConsent();
        this.functionalToggleTarget.checked = consent?.functional ?? true;
        this.analyticsToggleTarget.checked  = consent?.analytics  ?? false;
        this.modalTarget.classList.add('consent-modal--open');
        document.body.classList.add('consent-no-scroll');
    }

    closeModal() {
        this.modalTarget.classList.remove('consent-modal--open');
        document.body.classList.remove('consent-no-scroll');
    }

    savePreferences() {
        this.#save({
            necessary:  true,
            functional: this.functionalToggleTarget.checked,
            analytics:  this.analyticsToggleTarget.checked,
        });
        this.closeModal();
        this.#closeBanner();
    }

    // ── Depuis le footer ────────────────────────────────────

    /** Appelé via l'évènement global 'consent:manage' */
    manage() {
        this.openModal();
    }

    // ── Privé ───────────────────────────────────────────────

    #hasConsent() {
        return !!localStorage.getItem(STORAGE_KEY);
    }

    #getConsent() {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : null;
    }

    #save(consent) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            ...consent,
            savedAt: new Date().toISOString(),
        }));
    }

    #closeBanner() {
        this.element.classList.remove('consent-wrapper--visible');
    }
}
