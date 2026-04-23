/**
 * SCÉNART — Toast Controller (Stimulus)
 *
 * Gère l'affichage et la fermeture de TOUS les toasts/alertes de l'application.
 *
 * DEUX MODES D'UTILISATION :
 *
 * 1️⃣ Flash PHP (serveur) :
 *    Les messages PHP sont automatiquement rendus dans le toast-container
 *    via base.html.twig avec data-controller="toast".
 *    Auto-fermeture après 5 secondes par défaut.
 *
 * 2️⃣ JavaScript (client) :
 *    Depuis n'importe quel controller Stimulus, appeler :
 *
 *    const container = document.getElementById('toast-container');
 *    const toastCtrl = this.application.getControllerForElementAndIdentifier(container, 'toast');
 *    toastCtrl.show('Message ici', 'success', 4000);
 *
 * TYPES DISPONIBLES :
 *    - success : toast vert avec icône check-circle
 *    - warning : toast jaune avec icône triangle-alert
 *    - error   : toast rouge avec icône x-circle
 *    - info    : toast bleu avec icône info
 *
 * DESIGN :
 *    Position : coin supérieur droit (fixe)
 *    Animation : fade-in à l'apparition, fade-out à la disparition
 *    Fermeture : automatique (timer) OU manuelle (bouton ×)
 *
 * @author Thibault (SCÉNART)
 * @date Avril 2026
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        autoClose: { type: Boolean, default: true },
        duration: { type: Number, default: 5000 }
    };

    connect() {
        // Auto-fermeture pour les flash PHP (serveur)
        if (this.autoCloseValue) {
            this.timeout = setTimeout(() => this.close(), this.durationValue);
        }
    }

    /**
     * Afficher un toast (appelé depuis JavaScript)
     *
     * @param {string} message - Le message à afficher
     * @param {string} type - Le type de toast (success, warning, error, info)
     * @param {number} duration - Durée d'affichage en ms (optionnel, défaut 5000)
     * @returns {HTMLElement} L'élément toast créé
     */
    show(message, type = 'info', duration = 5000) {
        const container = document.getElementById('toast-container');

        if (!container) {
            console.error('Toast container #toast-container not found!');
            return null;
        }

        // Créer le toast
        const toast = document.createElement('div');
        toast.className = `scenart-alert alert-${type}`;
        toast.setAttribute('data-controller', 'toast');
        toast.setAttribute('data-toast-auto-close-value', 'true');
        toast.setAttribute('data-toast-duration-value', duration);

        // Icônes Lucide selon le type
        const icons = {
            success: 'check-circle',
            warning: 'triangle-alert',
            error: 'x-circle',
            info: 'info'
        };

        toast.innerHTML = `
            <div class="alert-icon">
                <i data-lucide="${icons[type] || 'info'}"></i>
            </div>
            <div class="alert-body"><p>${this._escapeHtml(message)}</p></div>
            <button class="alert-close" data-action="click->toast#close">&times;</button>
        `;

        container.appendChild(toast);

        // Réinitialiser les icônes Lucide si disponible
        if (window.lucide) {
            window.lucide.createIcons();
        }

        return toast;
    }

    /**
     * Fermer le toast avec animation
     */
    close() {
        this.element.classList.add('alert-fade-out');
        setTimeout(() => {
            if (this.element && this.element.parentNode) {
                this.element.remove();
            }
        }, 500); // Durée de l'animation fade-out en CSS
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

    disconnect() {
        // Nettoyer le timer si le controller est déconnecté avant la fin
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }
}
