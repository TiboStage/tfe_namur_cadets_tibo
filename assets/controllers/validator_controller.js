/**
 * SCÉNART — Validator Controller (Stimulus)
 *
 * Gère la validation frontend des formulaires avec feedback visuel en temps réel.
 *
 * DEUX NIVEAUX DE FEEDBACK :
 *
 * 1️⃣ Inline (champ par champ) :
 *    - Au blur (quand l'utilisateur quitte le champ)
 *    - Ajoute .is-invalid (rouge) ou .is-valid (vert) sur le champ
 *    - Feedback immédiat sans soumettre le formulaire
 *
 * 2️⃣ Toast (au submit) :
 *    - Si le formulaire est invalide lors de la soumission
 *    - Affiche un toast warning via toast_controller
 *    - Empêche la soumission (event.preventDefault())
 *
 * UTILISATION :
 *    <form data-controller="validator"
 *          data-validator-error-value="Veuillez corriger les erreurs"
 *          data-action="submit->validator#validateForm">
 *
 *       <input type="email"
 *              data-action="blur->validator#check">
 *    </form>
 *
 * RÈGLES DE VALIDATION :
 *    - Email : format valide (x@y.z)
 *    - Checkbox : doit être cochée
 *    - Select : valeur non vide
 *    - Textarea : min 2 caractères si requis
 *    - Input texte : min 2 caractères si requis
 *
 * DÉTECTION DU REQUIRED :
 *    Cherche <span class="required">*</span> dans le .form-block parent
 *    OU l'attribut HTML required sur le champ
 *
 * @author Thibault (SCÉNART)
 * @date Avril 2026
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        error: { type: String, default: 'Veuillez corriger les erreurs avant de soumettre' }
    };

    /**
     * Vérification au blur (champ par champ)
     * Appelé quand l'utilisateur quitte un champ
     */
    check(event) {
        const input = event.currentTarget;
        const isValid = this._checkLogic(input);
        this._updateInlineError(input, isValid);
    }

    /**
     * Validation complète au submit
     * Vérifie tous les champs avant de laisser passer la soumission
     */
    validateForm(event) {
        const inputs = this.element.querySelectorAll('input, select, textarea');
        let isFormValid = true;

        // Vérifier tous les champs
        inputs.forEach(input => {
            const isValid = this._checkLogic(input);

            if (!isValid) {
                isFormValid = false;
                this._updateInlineError(input, false);
            }
        });

        // Si invalide : bloquer + afficher toast
        if (!isFormValid) {
            event.preventDefault();
            this._showToast(this.errorValue, 'warning');
        }
    }

    /**
     * Logique de validation d'un champ
     * @param {HTMLElement} input - Le champ à valider
     * @returns {boolean} true si valide, false sinon
     * @private
     */
    _checkLogic(input) {
        // Champs cachés ou désactivés → toujours valides
        if (input.type === 'hidden' || input.disabled) {
            return true;
        }

        const value = input.value.trim();

        // Validation email
        if (input.type === 'email') {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }

        // Validation checkbox
        if (input.type === 'checkbox') {
            return input.checked;
        }

        // Validation select
        if (input.tagName === 'SELECT') {
            return value !== '' && value !== null;
        }

        // Validation textarea
        if (input.tagName === 'TEXTAREA') {
            // Textarea optionnel (pas de required) → toujours valide
            const formBlock = input.closest('.form-block');
            const isRequired = formBlock?.querySelector('span.required') !== null || input.required;

            if (!isRequired) return true;

            return value.length >= 2;
        }

        // Validation input texte (par défaut)
        const formBlock = input.closest('.form-block');
        const isRequired = formBlock?.querySelector('span.required') !== null || input.required;

        if (!isRequired) return true;

        return value.length >= 2;
    }

    /**
     * Mise à jour de l'erreur inline (classes CSS)
     * @param {HTMLElement} input - Le champ à marquer
     * @param {boolean} isValid - true si valide, false sinon
     * @private
     */
    _updateInlineError(input, isValid) {
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
        }
    }

    /**
     * Afficher un toast via toast_controller
     * @param {string} message - Le message à afficher
     * @param {string} type - Le type de toast (success, warning, error, info)
     * @private
     */
    _showToast(message, type = 'warning') {
        const container = document.getElementById('toast-container');

        if (!container) {
            console.error('Toast container #toast-container not found!');
            return;
        }

        // Récupérer le toast controller
        let toastController = this.application.getControllerForElementAndIdentifier(container, 'toast');

        // Si pas trouvé, créer l'attribut data-controller
        if (!toastController) {
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

    /**
     * Fallback si toast_controller pas disponible
     * Crée un toast basique sans passer par le controller
     * @param {HTMLElement} container - Le conteneur de toasts
     * @param {string} message - Le message
     * @param {string} type - Le type de toast
     * @private
     */
    _fallbackToast(container, message, type) {
        const toast = document.createElement('div');
        toast.className = `scenart-alert alert-${type}`;
        toast.innerHTML = `
            <div class="alert-icon">
                <i data-lucide="triangle-alert"></i>
            </div>
            <div class="alert-body"><p>${this._escapeHtml(message)}</p></div>
            <button class="alert-close" onclick="this.closest('.scenart-alert').remove()">&times;</button>
        `;

        container.appendChild(toast);

        // Fermeture automatique après 4 secondes
        setTimeout(() => {
            toast.classList.add('alert-fade-out');
            setTimeout(() => toast.remove(), 500);
        }, 4000);

        // Réinitialiser les icônes Lucide si disponible
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    /**
     * Échapper le HTML pour éviter les injections XSS
     * @param {string} text - Texte à échapper
     * @returns {string} Texte échappé
     * @private
     */
    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
