// assets/controllers/contact_form_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'email', 'emailError',
        'firstname', 'firstnameError',
        'lastname', 'lastnameError',
        'subject', 'subjectError',
        'message', 'messageError',
        'privacy', 'privacyError',
    ];

    // ── Validation par champ ────────────────────────────────────────

    validateEmail() {
        const val = this.emailTarget.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!val) {
            return this.#setError(this.emailTarget, this.emailErrorTarget, 'Une adresse email est requise.');
        }
        if (!emailRegex.test(val)) {
            return this.#setError(this.emailTarget, this.emailErrorTarget, 'Cette adresse email ne semble pas valide.');
        }
        this.#clearError(this.emailTarget, this.emailErrorTarget);
    }

    validateRequired(event) {
        const input = event.target;
        const errorTarget = this[input.dataset.contactFormTarget + 'ErrorTarget'];
        if (!input.value.trim()) {
            return this.#setError(input, errorTarget, 'Ce champ est requis.');
        }
        this.#clearError(input, errorTarget);
    }

    validateMessage() {
        const val = this.messageTarget.value.trim();
        if (!val) {
            return this.#setError(this.messageTarget, this.messageErrorTarget, 'Veuillez écrire votre message.');
        }
        if (val.length < 20) {
            return this.#setError(this.messageTarget, this.messageErrorTarget, 'Votre message doit contenir au moins 20 caractères.');
        }
        this.#clearError(this.messageTarget, this.messageErrorTarget);
    }

    validatePrivacy() {
        if (!this.privacyTarget.checked) {
            return this.#setError(this.privacyTarget, this.privacyErrorTarget, 'Vous devez accepter la politique de confidentialité.');
        }
        this.#clearError(this.privacyTarget, this.privacyErrorTarget);
    }

    // ── Soumission ──────────────────────────────────────────────────

    // La validation serveur dans PagesController gère le reste.
    // Pas de preventDefault — on laisse le form partir en POST classique.

    // ── Privé ───────────────────────────────────────────────────────

    #setError(input, errorEl, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        if (errorEl) errorEl.textContent = message;
    }

    #clearError(input, errorEl) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        if (errorEl) errorEl.textContent = '';
    }
}
