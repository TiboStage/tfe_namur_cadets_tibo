import { Controller } from '@hotwired/stimulus'

/**
 * Contrôleur Stimulus — popup de confirmation personnalisé.
 *
 * Remplace window.confirm() natif pour les actions sensibles
 * (suppression, bannissement, décision de modération…).
 *
 * IMPORTANT : ce controller DOIT être placé sur un élément ancêtre
 * commun de tous les formulaires concernés ET de l'overlay
 * (typiquement le wrapper du layout, comme data-controller="modal").
 *
 * Usage :
 *   <form method="post"
 *         action="{{ path(...) }}"
 *         data-action="submit->confirm-modal#intercept"
 *         data-confirm-title="Bannir cet utilisateur ?"        (optionnel)
 *         data-confirm-message="Il ne pourra plus se connecter."
 *         data-confirm-label="Bannir"                          (optionnel — texte du bouton)
 *         data-confirm-tone="danger">                          (optionnel — danger|warning|success|info)
 *     <button type="submit">Bannir</button>
 *   </form>
 *
 * Les attributs data-confirm-* peuvent aussi être posés sur le bouton
 * cliqué (prioritaires sur ceux du formulaire) : utile quand un même
 * formulaire a plusieurs boutons (ex. décisions de modération).
 * Un bouton avec data-confirm-skip soumet le formulaire sans confirmation.
 */
export default class extends Controller {

    static targets = ['overlay', 'message', 'confirmBtn', 'title']

    static TONES = ['danger', 'warning', 'success', 'info']

    connect() {
        this._pendingForm      = null
        this._pendingSubmitter = null
        this._defaultLabel     = this.hasConfirmBtnTarget ? this.confirmBtnTarget.textContent.trim() : ''
        this._onKeyDown = (e) => { if (e.key === 'Escape') this.cancel() }
    }

    disconnect() {
        document.removeEventListener('keydown', this._onKeyDown)
    }

    /**
     * Intercepte la soumission d'un formulaire.
     * Appelé via data-action="submit->confirm-modal#intercept" sur le <form>.
     */
    intercept(event) {
        const form      = event.target
        const submitter = event.submitter ?? null

        // Formulaire déjà confirmé programmatiquement → laisser passer
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed
            return
        }

        // Bouton explicitement exempté de confirmation
        if (submitter?.dataset.confirmSkip !== undefined) return

        event.preventDefault()
        this._pendingForm      = form
        this._pendingSubmitter = submitter

        const opt = (key) => submitter?.dataset[key] || form.dataset[key] || ''

        this.messageTarget.textContent = opt('confirmMessage') || this.messageTarget.dataset.default || 'Confirmer cette action ?'

        if (this.hasTitleTarget) {
            const title = opt('confirmTitle')
            this.titleTarget.textContent = title
            this.titleTarget.hidden = title === ''
        }

        if (this.hasConfirmBtnTarget) {
            this.confirmBtnTarget.textContent = opt('confirmLabel') || this._defaultLabel
        }

        const tone = opt('confirmTone') || 'danger'
        this.constructor.TONES.forEach(t => this.overlayTarget.classList.toggle(`confirm-modal--${t}`, t === tone))

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
        this._pendingForm      = null
        this._pendingSubmitter = null
    }

    confirm() {
        if (this._pendingForm) {
            this._pendingForm.dataset.confirmed = 'true'
            // On renvoie le bouton cliqué pour conserver son name/value (ex. status=blocked)
            const submitter = this._pendingSubmitter?.form === this._pendingForm ? this._pendingSubmitter : undefined
            this._pendingForm.requestSubmit(submitter)
        }
        this.cancel()
    }

    backdropClick(e) {
        if (e.target === this.overlayTarget) this.cancel()
    }
}
