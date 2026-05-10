// assets/controllers/form_validation_controller.js
import { Controller } from '@hotwired/stimulus';

/**
 * Controller de validation de formulaire RÉUTILISABLE
 *
 * Lit automatiquement les contraintes HTML5 :
 * - required
 * - type="email"
 * - minlength / maxlength
 * - pattern
 *
 * Valide au blur (unfocus) et à la soumission.
 * Empêche la soumission si le formulaire est invalide.
 */
export default class extends Controller {
    static targets = ['field'];

    // ══════════════════════════════════════════════════════════════
    // VALIDATION D'UN CHAMP (au blur)
    // ══════════════════════════════════════════════════════════════

    validate(event) {
        this.validateField(event.target);
    }

    // ══════════════════════════════════════════════════════════════
    // VALIDATION DE TOUS LES CHAMPS (à la soumission)
    // ══════════════════════════════════════════════════════════════

    submit(event) {
        let isValid = true;

        this.fieldTargets.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        if (!isValid) {
            event.preventDefault();
            event.stopImmediatePropagation();

            // Scroller vers la première erreur
            const firstError = this.element.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    }

    // ══════════════════════════════════════════════════════════════
    // LOGIQUE DE VALIDATION
    // Chaque contrainte est vérifiée indépendamment dans l'ordre
    // → On retourne à la première erreur trouvée
    // ══════════════════════════════════════════════════════════════

    validateField(field) {
        const value   = field.value.trim();
        const type    = field.type;
        const isCheck = type === 'checkbox';

        // ── 1. Champ requis (vide ou checkbox non cochée) ──────────
        if (field.hasAttribute('required')) {
            const empty = isCheck ? !field.checked : !value;
            if (empty) {
                return this.showError(field, this.getErrorMessage(field, 'required'));
            }
        }

        // ── 2. Format email (valeur présente mais invalide) ────────
        if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
            if (!emailRegex.test(value)) {
                return this.showError(field, this.getErrorMessage(field, 'email'));
            }
        }

        // ── 3. Longueur minimale ───────────────────────────────────
        if (field.hasAttribute('minlength') && value) {
            const min = parseInt(field.getAttribute('minlength'));
            if (value.length < min) {
                return this.showError(field, this.getErrorMessage(field, 'minlength', { limit: min }));
            }
        }

        // ── 4. Longueur maximale ───────────────────────────────────
        if (field.hasAttribute('maxlength') && value) {
            const max = parseInt(field.getAttribute('maxlength'));
            if (value.length > max) {
                return this.showError(field, this.getErrorMessage(field, 'maxlength', { limit: max }));
            }
        }

        // ── 5. Pattern personnalisé ────────────────────────────────
        if (field.hasAttribute('pattern') && value) {
            const pattern = new RegExp(field.getAttribute('pattern'));
            if (!pattern.test(value)) {
                return this.showError(field, this.getErrorMessage(field, 'pattern'));
            }
        }

        // ── Aucune erreur → champ valide ───────────────────────────
        this.clearError(field);
        return true;
    }

    // ══════════════════════════════════════════════════════════════
    // AFFICHAGE DES ERREURS
    // ══════════════════════════════════════════════════════════════

    showError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');

        let errorContainer = field.parentElement.querySelector('.invalid-feedback');

        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.className = 'invalid-feedback';
            field.parentElement.appendChild(errorContainer);
        }

        // Remplace le contenu (écrase un ul li éventuel de Symfony)
        errorContainer.textContent = message;
        errorContainer.style.display = 'block';

        return false;
    }

    clearError(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');

        const errorContainer = field.parentElement.querySelector('.invalid-feedback');
        if (errorContainer) {
            errorContainer.style.display = 'none';
        }

        return true;
    }

    // ══════════════════════════════════════════════════════════════
    // MESSAGES D'ERREUR
    // Lit les data-error-* traduits dans le template
    // ══════════════════════════════════════════════════════════════

    getErrorMessage(field, errorType, params = {}) {
        const customMessage = field.getAttribute(`data-error-${errorType}`);

        if (customMessage) {
            // Remplace {{ limit }} par la valeur réelle
            return customMessage.replace(/\{\{\s*(\w+)\s*\}\}/g, (match, key) => {
                return params[key] !== undefined ? params[key] : match;
            });
        }

        // Messages par défaut (fallback si la traduction est absente)
        const defaults = {
            required:  'Ce champ est requis.',
            email:     'Veuillez entrer une adresse email valide.',
            minlength: `Minimum ${params.limit} caractères.`,
            maxlength: `Maximum ${params.limit} caractères.`,
            pattern:   'Le format est invalide.',
        };

        return defaults[errorType] ?? 'Ce champ est invalide.';
    }
}
