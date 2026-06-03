// assets/controllers/comment_vote_controller.js
import { Controller } from '@hotwired/stimulus'

/**
 * Gère les votes 👍 / 👎 sur un commentaire via fetch JSON.
 *
 * Usage :
 *   <div data-controller="comment-vote"
 *        data-comment-vote-url-value="..."
 *        data-comment-vote-csrf-value="..."
 *        data-comment-vote-user-vote-value="up|down|">
 *     <button data-action="click->comment-vote#vote" data-vote-value="up"
 *             data-comment-vote-target="upBtn">
 *       <span data-comment-vote-target="upCount">0</span>
 *     </button>
 *     ...
 *   </div>
 */
export default class extends Controller {
    static values  = { url: String, csrf: String, userVote: String }
    static targets = ['upCount', 'downCount', 'upBtn', 'downBtn']

    async vote (event) {
        const value = event.currentTarget.dataset.voteValue

        let response
        try {
            response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ value, _token: this.csrfValue }),
            })
        } catch {
            return
        }

        if (!response.ok) return

        const data = await response.json()

        // Mise à jour des compteurs
        if (this.hasUpCountTarget)   this.upCountTarget.textContent   = data.upvotes
        if (this.hasDownCountTarget) this.downCountTarget.textContent = data.downvotes

        // Mise à jour de l'état actif
        const uv = data.user_vote ?? ''
        this.userVoteValue = uv

        if (this.hasUpBtnTarget) {
            this.upBtnTarget.classList.toggle('vote-btn--active-up',   uv === 'up')
            this.upBtnTarget.setAttribute('aria-pressed', String(uv === 'up'))
        }
        if (this.hasDownBtnTarget) {
            this.downBtnTarget.classList.toggle('vote-btn--active-down', uv === 'down')
            this.downBtnTarget.setAttribute('aria-pressed', String(uv === 'down'))
        }
    }
}
