import { Controller } from '@hotwired/stimulus';

/**
 * Gère la fermeture automatique des flash PHP (.scenart-alert)
 * Utilisé via data-controller="alert" sur chaque composant Alert Twig
 */
export default class extends Controller {
    connect() {
        // Fermeture automatique après 5 secondes
        this.timeout = setTimeout(() => this.close(), 5000);
    }

    close() {
        this.element.classList.add('alert-fade-out');
        setTimeout(() => this.element.remove(), 500);
    }

    disconnect() {
        clearTimeout(this.timeout);
    }
}
