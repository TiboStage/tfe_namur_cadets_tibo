import { Controller } from '@hotwired/stimulus'

/**
 * Réordonnancement ↑↓ de l'arborescence manuscrit.
 *
 * Poser sur chaque conteneur direct (root tree ou folder-body) :
 *   <div data-controller="ms-reorder"
 *        data-ms-reorder-url-value="{{ path('app_manuscript_reorder', ...) }}"
 *        data-sort-parent-id="">
 *
 * Chaque enfant direct sortable doit avoir data-sort-id.
 * Les boutons à l'intérieur de chaque item :
 *   <button data-action="click->ms-reorder#up">↑</button>
 *   <button data-action="click->ms-reorder#down">↓</button>
 */
export default class extends Controller {
    static values = { url: String }

    connect() { this._refresh() }

    up(e)   { this._shift(e.currentTarget, -1) }
    down(e) { this._shift(e.currentTarget,  1) }

    // ── Privé ──────────────────────────────────────────────────────────

    _shift(btn, dir) {
        const item = btn.closest('[data-sort-id]')
        if (!item || item.parentElement !== this.element) return

        const siblings = [...this.element.querySelectorAll(':scope > [data-sort-id]')]
        const idx  = siblings.indexOf(item)
        const swap = siblings[idx + dir]
        if (!swap) return

        if (dir === -1) this.element.insertBefore(item, swap)
        else            this.element.insertBefore(swap, item)

        this._refresh()
        this._save()
    }

    _save() {
        const items    = [...this.element.querySelectorAll(':scope > [data-sort-id]')]
        const ids      = items.map(el => parseInt(el.dataset.sortId, 10))
        const raw      = this.element.dataset.sortParentId
        const parentId = raw !== '' && raw !== undefined ? parseInt(raw, 10) : null

        fetch(this.urlValue, {
            method:  'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body:    JSON.stringify({ ids, parent_id: parentId }),
        })
    }

    _refresh() {
        const items = [...this.element.querySelectorAll(':scope > [data-sort-id]')]
        items.forEach((item, i) => {
            const up   = item.querySelector('.ms-order-btn--up')
            const down = item.querySelector('.ms-order-btn--down')
            if (up)   up.disabled   = i === 0
            if (down) down.disabled = i === items.length - 1
        })
    }
}
