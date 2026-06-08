// assets/controllers/note_list_controller.js
//
// Actions rapides sur la liste des notes :
//   - Changement de statut en 1 clic (AJAX + toast undo)
//   - Changement de priorité inline (AJAX)
//   - Suppression optimiste (DOM immédiat → timer 4s → AJAX réel)
//   - Filtres client-side (statut + priorité)

import { Controller } from '@hotwired/stimulus'

const UNDO_DELAY = 4500

export default class extends Controller {

    static values = {
        labelDeleted: String,
        labelUndo:    String,
        labelStatus:  String,
    }

    connect () {
        this._toast          = this._ensureToastContainer()
        this._activeFilters  = { status: '', priority: '' }
    }

    // ══════════════════════════════════════════════════════════════════
    // CHANGEMENT DE STATUT
    // ══════════════════════════════════════════════════════════════════

    async changeStatus (event) {
        const btn    = event.currentTarget
        const row    = btn.closest('.nl-row')
        const newSt  = btn.dataset.status
        const prevSt = row.dataset.noteStatus
        const url    = row.dataset.statusUrl
        const csrf   = row.dataset.statusCsrf

        if (newSt === prevSt) return

        this._applyStatus(row, newSt)
        this._updateSummary()
        this._applyFilters()

        try {
            const res = await fetch(url, {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded',
                           'X-Requested-With': 'XMLHttpRequest' },
                body: `status=${encodeURIComponent(newSt)}&_token=${encodeURIComponent(csrf)}`,
            })
            if (!res.ok) throw new Error()
        } catch {
            this._applyStatus(row, prevSt)
            this._updateSummary()
            this._applyFilters()
            return
        }

        this._showToast(
            this.labelStatusValue,
            () => {
                this._applyStatus(row, prevSt)
                this._updateSummary()
                this._applyFilters()
                fetch(url, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded',
                               'X-Requested-With': 'XMLHttpRequest' },
                    body: `status=${encodeURIComponent(prevSt)}&_token=${encodeURIComponent(csrf)}`,
                })
            }
        )
    }

    _applyStatus (row, status) {
        row.dataset.noteStatus = status

        // Boutons statut — actif/inactif
        row.querySelectorAll('.nl-status-btn').forEach(b => {
            b.classList.toggle('nl-status-btn--active', b.dataset.status === status)
        })

        // Badge statut texte
        const badge = row.querySelector('.nl-status')
        if (badge) {
            badge.className = `nl-status nl-status--${status}`
            const labels = { note: 'Référence', todo: 'À faire', done: 'Terminée', archived: 'Archivée' }
            const icons  = { note: 'pencil-line', todo: 'circle-dashed', done: 'circle-check', archived: 'archive' }
            badge.innerHTML = `<span class="nl-status-icon nl-status-icon--${status}"></span>${labels[status] ?? status}`
        }

        // Classe couleur de la ligne
        row.classList.remove('nl-row--note', 'nl-row--todo', 'nl-row--done', 'nl-row--archived')
        row.classList.add(`nl-row--${status}`)

        // Opacité réduite si archivée
        row.style.opacity = status === 'archived' ? '0.5' : ''
    }

    // ══════════════════════════════════════════════════════════════════
    // CHANGEMENT DE PRIORITÉ
    // ══════════════════════════════════════════════════════════════════

    async changePriority (event) {
        const select   = event.target
        const row      = select.closest('.nl-row')
        const newPrio  = select.value
        const prevPrio = select.dataset.currentPriority
        const url      = row.dataset.priorityUrl
        const csrf     = row.dataset.priorityCsrf

        if (newPrio === prevPrio) return

        this._applyPriority(row, select, newPrio)

        try {
            const res = await fetch(url, {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded',
                           'X-Requested-With': 'XMLHttpRequest' },
                body: `priority=${encodeURIComponent(newPrio)}&_token=${encodeURIComponent(csrf)}`,
            })
            if (!res.ok) throw new Error()
        } catch {
            this._applyPriority(row, select, prevPrio)
        }
    }

    _applyPriority (row, select, priority) {
        row.dataset.notePriority       = priority
        select.dataset.currentPriority = priority
        select.value                   = priority
        select.className = `nl-priority-select nl-priority-select--${priority}`
    }

    // ══════════════════════════════════════════════════════════════════
    // SUPPRESSION OPTIMISTE
    // ══════════════════════════════════════════════════════════════════

    deleteNote (event) {
        const btn   = event.currentTarget
        const row   = btn.closest('.nl-row')
        const url   = row.dataset.deleteUrl
        const csrf  = row.dataset.deleteCsrf
        const title = row.dataset.noteTitle

        this._hideRow(row)

        const timer = setTimeout(() => this._execDelete(row, url, csrf), UNDO_DELAY)

        this._showToast(
            `${this.labelDeletedValue} « ${title} »`,
            () => {
                clearTimeout(timer)
                this._showRow(row)
            }
        )
    }

    async _execDelete (row, url, csrf) {
        const fd = new FormData()
        fd.append('_token', csrf)
        try {
            await fetch(url, { method: 'POST', body: fd,
                                headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        } catch { /* silencieux */ }
        row.remove()
        this._updateSummary()
        this._hideGroupIfEmpty(row)
    }

    _hideGroupIfEmpty (row) {
        const body = row.closest('.nl-body')
        if (body && body.querySelectorAll('.nl-row').length === 0) {
            body.closest('.nl-group')?.remove()
        }
    }

    _hideRow (row) {
        row.style.transition = 'opacity .2s, transform .2s'
        row.style.opacity    = '0'
        row.style.transform  = 'translateX(-8px)'
        setTimeout(() => { row.style.display = 'none' }, 220)
    }

    _showRow (row) {
        row.style.display = ''
        row.style.opacity = '0'
        requestAnimationFrame(() => {
            row.style.opacity   = '1'
            row.style.transform = 'translateX(0)'
        })
    }

    // ══════════════════════════════════════════════════════════════════
    // FILTRES CLIENT-SIDE
    // ══════════════════════════════════════════════════════════════════

    filterStatus (event) {
        this._activeFilters.status = event.currentTarget.dataset.status
        this._applyFilters()
        this._setActiveFilter(event.currentTarget, '.nl-filter-btn')
    }

    filterPriority (event) {
        this._activeFilters.priority = event.target.value
        this._applyFilters()
    }

    _applyFilters () {
        const { status, priority } = this._activeFilters

        this.element.querySelectorAll('.nl-row').forEach(row => {
            const rowStatus     = row.dataset.noteStatus
            // "Toutes" masque les archivées — seul le filtre explicite "archived" les montre
            const matchStatus   = status === 'archived'
                ? rowStatus === 'archived'
                : status
                    ? rowStatus === status
                    : rowStatus !== 'archived'
            const matchPriority = !priority || row.dataset.notePriority === priority
            row.style.display = matchStatus && matchPriority ? '' : 'none'
        })

        // Masquer les groupes entièrement vides après filtre
        this.element.querySelectorAll('.nl-group').forEach(group => {
            const visible = [...group.querySelectorAll('.nl-row')]
                .some(r => r.style.display !== 'none')
            group.style.display = visible ? '' : 'none'
        })
    }

    _setActiveFilter (active, selector) {
        this.element.querySelectorAll(selector).forEach(btn =>
            btn.classList.toggle('nl-filter-btn--active', btn === active)
        )
    }

    // ══════════════════════════════════════════════════════════════════
    // RÉSUMÉ — mise à jour en temps réel
    // ══════════════════════════════════════════════════════════════════

    _updateSummary () {
        const rows = [...this.element.querySelectorAll('.nl-row')]

        const counts = { note: 0, todo: 0, done: 0, archived: 0 }
        rows.forEach(r => {
            const s = r.dataset.noteStatus
            if (s in counts) counts[s]++
        })

        const total = rows.length
        this._setSummary('total',    total)
        this._setSummary('note',     counts.note)
        this._setSummary('todo',     counts.todo)
        this._setSummary('done',     counts.done)
        this._setSummary('archived', counts.archived)
    }

    _setSummary (key, value) {
        const el = this.element.querySelector(`[data-summary="${key}"]`)
        if (el) el.textContent = value
    }

    // ══════════════════════════════════════════════════════════════════
    // TOAST
    // ══════════════════════════════════════════════════════════════════

    _showToast (message, undoCb) {
        const toast = document.createElement('div')
        toast.className = 'tl-toast'
        toast.innerHTML = `
            <span class="tl-toast__msg">${message}</span>
            <button type="button" class="tl-toast__undo">${this.labelUndoValue}</button>
        `
        this._toast.appendChild(toast)
        requestAnimationFrame(() => toast.classList.add('tl-toast--in'))

        const dismiss = () => {
            toast.classList.remove('tl-toast--in')
            toast.classList.add('tl-toast--out')
            setTimeout(() => toast.remove(), 250)
        }

        toast.querySelector('.tl-toast__undo').addEventListener('click', () => {
            undoCb()
            clearTimeout(timer)
            dismiss()
        })

        const timer = setTimeout(dismiss, UNDO_DELAY)
    }

    _ensureToastContainer () {
        let c = document.getElementById('tl-toast-container')
        if (!c) {
            c = document.createElement('div')
            c.id = 'tl-toast-container'
            document.body.appendChild(c)
        }
        return c
    }
}
