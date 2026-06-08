// assets/controllers/task_list_controller.js
//
// Gestion de la liste des tâches :
//   - Changement de statut en 1 clic (AJAX immédiat + toast undo)
//   - Suppression optimiste (DOM immédiat → timer 4s → AJAX réel)
//   - Filtres client-side (statut, priorité, assigné)

import { Controller } from '@hotwired/stimulus'

const UNDO_DELAY = 4500 // ms avant suppression réelle

export default class extends Controller {

    static values = {
        labelDeleted:    String,   // "Tâche supprimée"
        labelUndo:       String,   // "Annuler"
        labelStatus:     String,   // "Statut mis à jour"
    }

    // ── Connexion ───────────────────────────────────────────────────────────────

    connect () {
        this._toastContainer = this._ensureToastContainer()
        this._activeFilters  = { status: '', priority: '', assignee: '' }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CHANGEMENT DE STATUT
    // ══════════════════════════════════════════════════════════════════════════

    async changeStatus (event) {
        const btn     = event.currentTarget
        const row     = btn.closest('.tl-row')
        const taskId  = row.dataset.taskId
        const newSt   = btn.dataset.status
        const prevSt  = row.dataset.taskStatus
        const url     = row.dataset.statusUrl
        const csrf    = row.dataset.statusCsrf

        if (newSt === prevSt) return

        // Mise à jour optimiste du DOM
        this._setRowStatus(row, newSt)
        this._updateSummary()
        this._applyFilters()

        try {
            const res = await fetch(url, {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded',
                           'X-Requested-With': 'XMLHttpRequest' },
                body:    `status=${encodeURIComponent(newSt)}&_token=${encodeURIComponent(csrf)}`,
            })
            if (!res.ok) throw new Error()
        } catch {
            // Rollback si échec serveur
            this._setRowStatus(row, prevSt)
            this._updateSummary()
            return
        }

        // Toast avec undo
        this._showToast(
            this.labelStatusValue,
            () => this._revertStatus(row, url, csrf, prevSt, newSt),
        )
    }

    async _revertStatus (row, url, csrf, prevSt, newSt) {
        this._setRowStatus(row, prevSt)
        this._updateSummary()
        await fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded',
                       'X-Requested-With': 'XMLHttpRequest' },
            body:    `status=${encodeURIComponent(prevSt)}&_token=${encodeURIComponent(csrf)}`,
        })
    }

    _setRowStatus (row, status) {
        row.dataset.taskStatus = status

        // Active le bon bouton
        row.querySelectorAll('.tl-status-btn').forEach(b => {
            b.classList.toggle('tl-status-btn--active', b.dataset.status === status)
            b.setAttribute('aria-pressed', String(b.dataset.status === status))
        })

        // Met à jour la classe couleur de la ligne
        row.classList.remove('tl-row--todo','tl-row--in_progress','tl-row--review','tl-row--done','tl-row--archived')
        row.classList.add(`tl-row--${status}`)
    }

    // ══════════════════════════════════════════════════════════════════════════
    // SUPPRESSION OPTIMISTE
    // ══════════════════════════════════════════════════════════════════════════

    deleteTask (event) {
        const btn   = event.currentTarget
        const row   = btn.closest('.tl-row')
        const url   = row.dataset.deleteUrl
        const csrf  = row.dataset.deleteCsrf
        const title = row.dataset.taskTitle

        // Masque immédiatement
        this._hideRow(row)

        let timer = setTimeout(() => this._execDelete(row, url, csrf), UNDO_DELAY)

        // Toast avec undo
        this._showToast(
            `${this.labelDeletedValue} « ${title} »`,
            () => {
                clearTimeout(timer)
                this._showRow(row)
            },
        )
    }

    async archiveTask (event) {
        const btn    = event.currentTarget
        const row    = btn.closest('.tl-row')
        const prevSt = row.dataset.taskStatus
        const url    = row.dataset.statusUrl
        const csrf   = row.dataset.statusCsrf

        this._setRowStatus(row, 'archived')
        this._updateSummary()
        this._applyFilters()

        try {
            const res = await fetch(url, {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded',
                           'X-Requested-With': 'XMLHttpRequest' },
                body:    `status=archived&_token=${encodeURIComponent(csrf)}`,
            })
            if (!res.ok) throw new Error()
        } catch {
            this._setRowStatus(row, prevSt)
            this._updateSummary()
            this._applyFilters()
            return
        }

        this._showToast(
            this.labelStatusValue,
            () => this._revertStatus(row, url, csrf, prevSt, 'archived'),
        )
    }

    async _execDelete (row, url, csrf) {
        const fd = new FormData()
        fd.append('_token', csrf)
        try {
            await fetch(url, { method: 'POST', body: fd,
                                headers: {'X-Requested-With': 'XMLHttpRequest'} })
        } catch { /* silencieux */ }
        row.remove()
        this._updateSummary()
    }

    _hideRow (row) {
        row.style.transition = 'opacity .2s, transform .2s'
        row.style.opacity    = '0'
        row.style.transform  = 'translateX(-8px)'
        setTimeout(() => row.style.display = 'none', 220)
    }

    _showRow (row) {
        row.style.display   = ''
        row.style.opacity   = '0'
        requestAnimationFrame(() => {
            row.style.opacity   = '1'
            row.style.transform = 'translateX(0)'
        })
    }

    // ══════════════════════════════════════════════════════════════════════════
    // FILTRES CLIENT-SIDE
    // ══════════════════════════════════════════════════════════════════════════

    filterStatus (event) {
        this._activeFilters.status = event.currentTarget.dataset.status
        this._applyFilters()
        this._setActiveFilter(event.currentTarget, '.tl-filter-status')
    }

    filterPriority (event) {
        this._activeFilters.priority = event.target.value
        this._applyFilters()
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CHANGEMENT DE PRIORITÉ
    // ══════════════════════════════════════════════════════════════════════════

    async changePriority (event) {
        const select   = event.target
        const row      = select.closest('.tl-row')
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
                body:    `priority=${encodeURIComponent(newPrio)}&_token=${encodeURIComponent(csrf)}`,
            })
            if (!res.ok) throw new Error()
        } catch {
            this._applyPriority(row, select, prevPrio)
        }
    }

    _applyPriority (row, select, priority) {
        row.dataset.taskPriority        = priority
        select.dataset.currentPriority  = priority
        select.value                    = priority
        select.className = `tl-priority-select tl-priority-select--${priority}`
        this._updateSummary()
    }

    _applyFilters () {
        const { status, priority } = this._activeFilters

        this.element.querySelectorAll('.tl-row').forEach(row => {
            const rowStatus = row.dataset.taskStatus
            // En vue "toutes" (status vide), exclure les archivées
            const matchStatus = status
                ? rowStatus === status
                : rowStatus !== 'archived'
            const matchPriority = !priority || row.dataset.taskPriority === priority
            row.style.display = matchStatus && matchPriority ? '' : 'none'
        })
    }

    _setActiveFilter (active, selector) {
        this.element.querySelectorAll(selector).forEach(btn =>
            btn.classList.toggle('tl-filter-btn--active', btn === active)
        )
    }

    // ══════════════════════════════════════════════════════════════════════════
    // RÉCAP — mise à jour après suppression
    // ══════════════════════════════════════════════════════════════════════════

    _updateSummary () {
        const rows  = [...this.element.querySelectorAll('.tl-row')]
        const total = rows.length
        const done  = rows.filter(r => r.dataset.taskStatus === 'done').length
        const today = new Date(); today.setHours(0,0,0,0)

        const overdue = rows.filter(r => {
            const due = r.dataset.taskDue
            return due && new Date(due) < today && r.dataset.taskStatus !== 'done'
        }).length

        const urgent = rows.filter(r =>
            r.dataset.taskPriority === 'urgent' && r.dataset.taskStatus !== 'done'
        ).length

        const elOverdue  = this.element.querySelector('[data-summary="overdue"]')
        const elUrgent   = this.element.querySelector('[data-summary="urgent"]')
        const elDone     = this.element.querySelector('[data-summary="done"]')
        const elTotal    = this.element.querySelector('[data-summary="total"]')
        const elProgress = this.element.querySelector('.tl-progress-bar__fill')

        if (elOverdue) elOverdue.textContent = overdue
        if (elUrgent)  elUrgent.textContent  = urgent
        if (elDone)    elDone.textContent     = done
        if (elTotal)   elTotal.textContent    = total
        if (elProgress) {
            const pct = total > 0 ? Math.round((done / total) * 100) : 0
            elProgress.style.width = `${pct}%`
            elProgress.style.background = pct === 100
                ? 'var(--color-green)'
                : pct <= 30
                    ? 'var(--color-red)'
                    : 'var(--color-primary)'
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // TOAST
    // ══════════════════════════════════════════════════════════════════════════

    _showToast (message, undoCb) {
        const toast = document.createElement('div')
        toast.className = 'tl-toast'
        toast.innerHTML = `
            <span class="tl-toast__msg">${message}</span>
            <button type="button" class="tl-toast__undo">${this.labelUndoValue}</button>
        `
        this._toastContainer.appendChild(toast)

        // Force reflow pour l'animation d'entrée
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
