import { Controller } from '@hotwired/stimulus'

/**
 * Contrôleur Stimulus — popup de confirmation personnalisé.
 *
 * Remplace window.confirm() natif pour les actions destructrices
 * (suppression de lieu, personnage, événement…).
 *
 * IMPORTANT : ce controller DOIT être placé sur un élément ancêtre
 * commun de tous les formulaires de suppression ET de l'overlay
 * (typiquement le wrapper du layout, comme data-controller="modal").
 *
 * Usage sur un formulaire de suppression :
 *   <form method="post"
 *         action="{{ path(...) }}"
 *         data-action="submit->confirm-modal#intercept"
 *         data-confirm-message="Supprimer ce lieu ?">
 *     <button type="submit">Supprimer</button>
 *   </form>
 */
export default class extends Controller {

    static targets = ['overlay', 'message', 'confirmBtn']

    connect() {
        this._pendingForm = null
        this._onKeyDown = (e) => { if (e.key === 'Escape') this.cancel() }
    }

    disconnect() {
        document.removeEventListener('keydown', this._onKeyDown)
    }

    /**
     * Intercepte la soumission d'un formulaire de suppression.
     * Appelé via data-action="submit->confirm-modal#intercept" sur le <form>.
     */
    intercept(event) {
        const form = event.target

        // Formulaire déjà confirmé programmatiquement → laisser passer
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed
            return
        }

        event.preventDefault()
        this._pendingForm = form
        this.messageTarget.textContent = form.dataset.confirmMessage || this.messageTarget.dataset.default || 'Confirmer cette action ?'
        this.open()
    }

    open() {
        this.overlayTarget.classList.add('confirm-modal--open')
        document.body.classList.add('modal-open')
        document.addEventListener('keydown', this._onKeyDown)
        setTimeout(() => this.confirmBtnTarget.focus(), 50)
    }

    cancel() {
        this.overlayTarget.classList.remove('confirm-modal--open')
        document.body.classList.remove('modal-open')
        document.removeEventListener('keydown', this._onKeyDown)
        this._pendingForm = null
    }

    confirm() {
        if (this._pendingForm) {
            this._pendingForm.dataset.confirmed = 'true'
            this._pendingForm.requestSubmit()
        }
        this.cancel()
    }

    backdropClick(e) {
        if (e.target === this.overlayTarget) this.cancel()
    }
}
