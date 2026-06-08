// assets/controllers/resource_filter_controller.js
// Filtrage + tri client-side pour les listes de personnages et de lieux

import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { containerId: String }
    static targets = ['emptyMsg']

    connect() {
        this._activeFilter = ''
        this._activeSort   = 'default'
    }

    filter(event) {
        const btn = event.currentTarget
        this.element.querySelectorAll('[data-filter-btn]').forEach(b => b.classList.remove('active'))
        btn.classList.add('active')
        this._activeFilter = btn.dataset.category ?? ''
        this._apply()
    }

    sort(event) {
        const btn = event.currentTarget
        this.element.querySelectorAll('[data-sort-btn]').forEach(b => b.classList.remove('active'))
        btn.classList.add('active')
        this._activeSort = btn.dataset.sort ?? 'default'
        this._apply()
    }

    _apply() {
        const container = document.getElementById(this.containerIdValue)
        if (!container) return

        const gridItems = [...container.querySelectorAll('.grid-only[data-category]')]
        const listItems = [...container.querySelectorAll('.list-only[data-category]')]
        const filter    = this._activeFilter
        const sort      = this._activeSort

        // ── Tri ───────────────────────────────────────────────────────────
        if (sort !== 'default') {
            const sorted = [...gridItems].sort((a, b) => this._compare(a, b, sort))
            sorted.forEach((item, idx) => {
                item.style.order = idx
                const match = listItems.find(li => li.dataset.id === item.dataset.id)
                if (match) match.style.order = idx
            })
        } else {
            gridItems.forEach(item => { item.style.order = '' })
            listItems.forEach(item => { item.style.order = '' })
        }

        // ── Filtre ────────────────────────────────────────────────────────
        gridItems.forEach(item => {
            const show = !filter || item.dataset.category === filter
            // Inline display only when hiding — otherwise let CSS decide (grid vs list)
            item.style.display = show ? '' : 'none'
        })
        listItems.forEach(item => {
            const show = !filter || item.dataset.category === filter
            item.style.display = show ? '' : 'none'
        })

        // ── État vide ─────────────────────────────────────────────────────
        if (this.hasEmptyMsgTarget) {
            const anyVisible = gridItems.some(i => i.style.display !== 'none')
            this.emptyMsgTarget.style.display = anyVisible ? 'none' : ''
        }
    }

    _compare(a, b, sort) {
        switch (sort) {
            case 'az':        return a.dataset.sortName.localeCompare(b.dataset.sortName)
            case 'za':        return b.dataset.sortName.localeCompare(a.dataset.sortName)
            case 'date-desc': return b.dataset.sortDate.localeCompare(a.dataset.sortDate)
            case 'date-asc':  return a.dataset.sortDate.localeCompare(b.dataset.sortDate)
            default:          return 0
        }
    }
}
