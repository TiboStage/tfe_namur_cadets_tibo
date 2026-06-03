import { Controller } from '@hotwired/stimulus'

/**
 * Modal controller — Turbo Frame-powered overlay.
 *
 * IMPORTANT: ce controller DOIT être placé sur un élément ancêtre
 * commun de tous les triggers ET de l'overlay. Typiquement le
 * wrapper principal du layout :
 *
 *   <div data-controller="modal">          ← controller ici
 *     <main>
 *       <a data-turbo-frame="modal-frame"
 *          data-action="click->modal#open"> ← triggers ici ✓
 *       </a>
 *     </main>
 *     <div class="ws-modal"
 *          data-modal-target="overlay">     ← overlay ici ✓
 *       <turbo-frame id="modal-frame">…</turbo-frame>
 *     </div>
 *   </div>
 *
 * Le bouton Annuler à l'intérieur de la turbo-frame peut fermer
 * le modal grâce au bubbling :
 *   <button type="button" data-action="click->modal#close">Annuler</button>
 */
export default class extends Controller {

    static targets = ['overlay']

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    connect() {
        // Fermer avec Escape
        this._onKeyDown = (e) => { if (e.key === 'Escape') this.close() }
        document.addEventListener('keydown', this._onKeyDown)

        // Autofocus au chargement du contenu de la frame
        this._onFrameLoad = () => this._autoFocus()
        this.element.addEventListener('turbo:frame-load', this._onFrameLoad)

        // Ouverture programmatique depuis un sous-contrôleur (ex : annotation bloc)
        this._onOpenUrl = (e) => this.openUrl(e.detail?.url)
        this.element.addEventListener('modal:open-url', this._onOpenUrl)

        // Ferme le modal dès qu'un formulaire interne reçoit une réponse turbo-stream
        // (indique un succès côté serveur : note/tâche créée, etc.)
        this._onSubmitEnd = (e) => {
            const ct = e.detail?.fetchResponse?.contentType ?? ''
            if (ct.startsWith('text/vnd.turbo-stream.html')) {
                this.close()
            }
        }
        this.element.addEventListener('turbo:submit-end', this._onSubmitEnd)
    }

    disconnect() {
        document.removeEventListener('keydown', this._onKeyDown)
        this.element.removeEventListener('turbo:frame-load', this._onFrameLoad)
        this.element.removeEventListener('modal:open-url', this._onOpenUrl)
        this.element.removeEventListener('turbo:submit-end', this._onSubmitEnd)
        document.body.classList.remove('modal-open')
    }

    // ─── Actions publiques ────────────────────────────────────────────────────

    /**
     * Charge une URL dans la turbo-frame et ouvre le modal.
     * Appelé programmatiquement via l'événement modal:open-url.
     */
    openUrl(url) {
        if (!url) return
        const frame = this.overlayTarget.querySelector('turbo-frame')
        if (frame) {
            frame.innerHTML = '<div class="ws-modal-loading"><span class="ws-modal-spinner"></span></div>'
            frame.setAttribute('src', url)
        }
        this.open()
    }

    /**
     * Appelé par data-action="click->modal#open" sur les liens déclencheurs.
     * Turbo Frame gère le chargement du formulaire en parallèle.
     */
    open() {
        this.overlayTarget.classList.add('modal--open')
        document.body.classList.add('modal-open')
    }

    /**
     * Ferme le modal et réinitialise la turbo-frame.
     * Appelé par le bouton ✕, le backdrop, Escape,
     * ou le bouton Annuler à l'intérieur du formulaire.
     */
    close() {
        this.overlayTarget.classList.remove('modal--open')
        document.body.classList.remove('modal-open')

        // Remet le spinner et annule tout chargement en cours
        const frame = this.overlayTarget.querySelector('turbo-frame')
        if (frame) {
            frame.removeAttribute('src')   // arrête un chargement éventuel
            frame.innerHTML = '<div class="ws-modal-loading"><span class="ws-modal-spinner"></span></div>'
        }
    }

    /**
     * Ferme uniquement si on clique sur le fond sombre (backdrop),
     * pas sur la boîte blanche elle-même.
     */
    backdropClick(e) {
        if (e.target === this.overlayTarget) this.close()
    }

    // ─── Privé ────────────────────────────────────────────────────────────────

    _autoFocus() {
        const frame = this.overlayTarget.querySelector('turbo-frame')
        const first = frame?.querySelector('input:not([type=hidden]):not([disabled]), textarea:not([disabled])')
        if (first) setTimeout(() => first.focus(), 50)
    }
}
