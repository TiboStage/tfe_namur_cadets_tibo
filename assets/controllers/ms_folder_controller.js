import { Controller } from '@hotwired/stimulus'

/**
 * Collapse / expand d'un dossier manuscrit.
 *
 * Usage :
 *   <div data-controller="ms-folder"
 *        data-ms-folder-id-value="{{ element.id }}">
 *     <div class="ms-folder-header">
 *       <button data-action="click->ms-folder#toggle">
 *         <svg data-ms-folder-target="chevron">…</svg>
 *       </button>
 *     </div>
 *     <div class="ms-folder-body" data-ms-folder-target="body">…</div>
 *   </div>
 *
 * L'état est persisté dans localStorage (clé ms-folder-{id}).
 */
export default class extends Controller {
    static targets = ['body', 'chevron']
    static values  = {
        id:   Number,
        open: { type: Boolean, default: true },
    }

    connect() {
        const stored = localStorage.getItem(`ms-folder-${this.idValue}`)
        if (stored !== null) this.openValue = stored === '1'
        this._apply()
    }

    toggle(e) {
        e.preventDefault()
        e.stopPropagation()
        this.openValue = !this.openValue
        localStorage.setItem(`ms-folder-${this.idValue}`, this.openValue ? '1' : '0')
        this._apply()
    }

    _apply() {
        if (this.hasBodyTarget) {
            this.bodyTarget.hidden = !this.openValue
        }
        if (this.hasChevronTarget) {
            this.chevronTarget.classList.toggle('ms-chevron--closed', !this.openValue)
        }
    }
}
