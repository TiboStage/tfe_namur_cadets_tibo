// assets/controllers/creator_like_controller.js
import { Controller } from '@hotwired/stimulus'

/**
 * Gère le bouton "Soutenir" d'une carte créateur.
 *
 * Usage :
 *   <button data-controller="creator-like"
 *           data-creator-like-url-value="..."
 *           data-action="click->creator-like#toggle">
 *     <strong data-creator-like-target="count">0</strong>
 *     <span   data-creator-like-target="label">soutiens</span>
 *   </button>
 *
 * - Stoppe la propagation pour éviter la navigation de la carte parente.
 * - Met à jour le compteur et l'état actif depuis la réponse serveur.
 */
export default class extends Controller {
    static values  = { url: String, liked: Boolean }
    static targets = ['count', 'label']

    connect () {
        this.element.setAttribute('aria-pressed', String(this.likedValue))
        this.element.classList.toggle('sc-card__like-btn--active', this.likedValue)
    }

    async toggle (event) {
        // Empêche la carte <a> de naviguer vers le profil
        event.preventDefault()
        event.stopPropagation()

        this.element.disabled = true

        let response
        try {
            response = await fetch(this.urlValue, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
        } catch {
            this.element.disabled = false
            return
        }

        this.element.disabled = false

        if (!response.ok) return

        const data = await response.json()

        // Mise à jour du compteur et du libellé (traduits via data-attributes)
        if (this.hasCountTarget) this.countTarget.textContent = data.count
        if (this.hasLabelTarget) {
            const singular = this.element.dataset.labelSingular || 'soutien'
            const plural   = this.element.dataset.labelPlural   || 'soutiens'
            this.labelTarget.textContent = data.count !== 1 ? plural : singular
        }

        // Mise à jour de l'état actif
        this.likedValue = data.liked
        this.element.setAttribute('aria-pressed', String(data.liked))
        this.element.classList.toggle('sc-card__like-btn--active', data.liked)
    }
}
