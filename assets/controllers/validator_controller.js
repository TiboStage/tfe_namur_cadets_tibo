import { Controller } from '@hotwired/stimulus';

/**
 * Gestion de la validation frontend des formulaires.
 *
 * 2 niveaux de feedback :
 *   1. Erreur inline  → .is-invalid / .is-valid sur le champ (au blur ou submit)
 *   2. Toast warning  → Utilise toast_controller pour afficher le message (au submit)
 */
export default class extends Controller {
    static values = {
        error: String  // Message affiché dans le toast (data-validator-error-value)
    };

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

        // Email
        if (input.type === 'email') {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }

        // Checkbox
        if (input.type === 'checkbox') {
            return input.checked;
        }

        // Select
        if (input.tagName === 'SELECT') {
            return value !== '' && value !== null;
        }

        // Textarea
        if (input.tagName === 'TEXTAREA') {
            // Textarea optionnel (pas de required) → toujours valide
            if (!input.required && !input.closest('.form-block')?.querySelector('span.required')) {
                return true;
            }
            return value.length >= 2;
        }

        // Champ texte
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

    // ─── Toast via toast_controller ───────────────────────────────────────────

    _showToast(message, type = 'warning') {
        const container = document.getElementById('toast-container');
        if (!container) {
            console.error('Toast container #toast-container not found!');
            return;
        }

        // Récupérer ou créer le toast controller
        let toastController = this.application.getControllerForElementAndIdentifier(container, 'toast');

        if (!toastController) {
            // Créer un toast controller temporaire
            container.setAttribute('data-controller', 'toast');
            toastController = this.application.getControllerForElementAndIdentifier(container, 'toast');
        }

        // Afficher le toast via le controller
        if (toastController && toastController.show) {
            toastController.show(message, type, 4000);
        } else {
            // Fallback si toast controller pas disponible
            console.warn('Toast controller not available, using fallback');
            this._fallbackToast(container, message, type);
        }
    }

    // ─── Fallback si toast_controller pas disponible ──────────────────────────

    _fallbackToast(container, message, type) {
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
