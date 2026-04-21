import { Controller } from '@hotwired/stimulus';

/**
 * Gestion de la validation frontend des formulaires.
 *
 * 3 niveaux de feedback :
 *   1. Erreur inline  → .field-error sous le champ (au blur ou submit)
 *   2. Toast warning  → .scenart-alert.alert-warning dans #toast-container (au submit)
 *   3. Flash PHP      → géré par alert_controller.js (retour serveur)
 */
export default class extends Controller {
    static values = {
        error: String  // Message affiché dans le toast (data-validator-error-value)
    }

    // ─── Vérification au blur (champ par champ) ───────────────────────────────

    check(event) {
        const input = event.currentTarget;
        const isValid = this._checkLogic(input);
        this._updateInlineError(input, isValid);
    }

    // ─── Validation complète au submit ────────────────────────────────────────

    validateForm(event) {
        const inputs = this.element.querySelectorAll('input, select, textarea');
        let isFormValid = true;

        inputs.forEach(input => {
            const isValid = this._checkLogic(input);
            if (!isValid) {
                isFormValid = false;
                this._updateInlineError(input, false);
            }
        });

        if (!isFormValid) {
            event.preventDefault();
            this._showToast(this.errorValue, 'warning');
        }
    }

    // ─── Logique de validation ────────────────────────────────────────────────

    _checkLogic(input) {
        // Champs cachés ou désactivés → toujours valides
        if (input.type === 'hidden' || input.disabled) return true;

        const value = input.value.trim();

        if (input.type === 'email') {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }
        if (input.type === 'checkbox') {
            return input.checked;
        }
        if (input.tagName === 'SELECT') {
            return value !== '' && value !== null;
        }
        if (input.tagName === 'TEXTAREA') {
            // Textarea optionnel (pas de required) → toujours valide
            if (!input.required && !input.closest('.form-block')?.querySelector('span.required')) {
                return true;
            }
            return value.length >= 2;
        }

        // Champ texte optionnel → valide
        const formBlock = input.closest('.form-block');
        const isRequired = formBlock?.querySelector('span.required') !== null;
        if (!isRequired) return true;

        return value.length >= 2;
    }

    // ─── Mise à jour erreur inline ────────────────────────────────────────────

    _updateInlineError(input, isValid) {
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
        }
    }

    // ─── Toast dans le container global ──────────────────────────────────────

    _showToast(message, type = 'warning') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        // Créer une .scenart-alert avec le bon type
        const toast = document.createElement('div');
        toast.className = `scenart-alert alert-${type}`;
        toast.innerHTML = `
            <div class="alert-icon">
                <i data-lucide="triangle-alert"></i>
            </div>
            <div class="alert-body"><p>${message}</p></div>
            <button class="alert-close" onclick="this.closest('.scenart-alert').remove()">&times;</button>
        `;

        container.appendChild(toast);

        // Fermeture automatique après 4 secondes
        setTimeout(() => {
            toast.classList.add('alert-fade-out');
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }
}
