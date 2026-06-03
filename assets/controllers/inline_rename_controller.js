import { Controller } from '@hotwired/stimulus'

/**
 * Inline rename controller.
 *
 * Usage :
 *   <span data-controller="inline-rename"
 *         data-inline-rename-url-value="/path/to/rename/1"
 *         data-inline-rename-title-value="Titre actuel">
 *     <a class="..." data-inline-rename-target="display">Titre actuel</a>
 *     <button type="button" data-action="click->inline-rename#startEdit"
 *             class="inline-rename-btn">✎</button>
 *   </span>
 *
 * Double-cliquer sur le display ou cliquer sur le bouton → mode édition.
 * Entrée / blur → sauvegarde via PATCH JSON.
 * Escape → annulation.
 */
export default class extends Controller {
    static targets = ['display']

    static values = {
        url:   String,
        title: String,
    }

    // ── Actions publiques ─────────────────────────────────────────────────────

    startEdit(e) {
        e?.preventDefault()
        e?.stopPropagation()

        if (this._editing) return
        this._editing = true

        const display = this.displayTarget
        const current = this.titleValue || display.textContent.trim()

        // Créer l'input
        this._input = document.createElement('input')
        this._input.type      = 'text'
        this._input.value     = current
        this._input.className = 'inline-rename-input'
        this._input.setAttribute('aria-label', 'Renommer')

        // Remplacer le display par l'input
        display.style.display = 'none'
        display.after(this._input)

        this._input.focus()
        this._input.select()

        // Événements
        this._input.addEventListener('keydown', (ev) => this._onInputKey(ev))
        this._input.addEventListener('blur',    ()    => this._save())
    }

    // ── Privé ─────────────────────────────────────────────────────────────────

    _onInputKey(e) {
        if (e.key === 'Enter') {
            e.preventDefault()
            this._save()
        } else if (e.key === 'Escape') {
            e.preventDefault()
            this._cancel()
        }
    }

    async _save() {
        if (!this._editing) return
        const newTitle = this._input?.value?.trim()
        if (!newTitle || newTitle === this.titleValue) {
            this._cancel()
            return
        }

        try {
            const res = await fetch(this.urlValue, {
                method:  'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body:    JSON.stringify({ title: newTitle }),
            })
            const data = await res.json()
            if (res.ok && data.success) {
                // Met à jour l'affichage et la valeur
                this.titleValue = data.title
                this.displayTarget.textContent = data.title
            }
        } catch {
            // En cas d'erreur réseau, on annule silencieusement
        }

        this._endEdit()
    }

    _cancel() {
        this._endEdit()
    }

    _endEdit() {
        if (!this._editing) return
        this._editing = false
        this._input?.remove()
        this._input = null
        this.displayTarget.style.display = ''
    }
}
