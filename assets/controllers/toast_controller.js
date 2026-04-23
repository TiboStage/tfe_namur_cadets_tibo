import { Controller } from '@hotwired/stimulus';

/**
 * Controller générique pour afficher et gérer les toasts/alertes.
 *
 * Utilisation :
 * 1. Flash PHP (serveur) : data-controller="toast" sur .scenart-alert
 * 2. JavaScript (client) : this.application.getControllerForElementAndIdentifier(container, 'toast').show(message, type)
 *
 * Types disponibles : success, warning, error, info
 */
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
     * @param {string} message - Le message à afficher
     * @param {string} type - Le type de toast (success, warning, error, info)
     * @param {number} duration - Durée d'affichage en ms (optionnel, défaut 5000)
     */
    show(message, type = 'info', duration = 5000) {
        const container = document.getElementById('toast-container');
        if (!container) {
            console.error('Toast container #toast-container not found!');
            return;
        }

        // Créer le toast
        const toast = document.createElement('div');
        toast.className = `scenart-alert alert-${type}`;
        toast.setAttribute('data-controller', 'toast');
        toast.setAttribute('data-toast-auto-close-value', 'true');
        toast.setAttribute('data-toast-duration-value', duration);

        // Icône selon le type
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
            <div class="alert-body"><p>${message}</p></div>
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
        setTimeout(() => this.element.remove(), 500);
    }

    disconnect() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }
}
