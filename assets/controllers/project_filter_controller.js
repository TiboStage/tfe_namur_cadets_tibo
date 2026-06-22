// assets/controllers/project_filter_controller.js
// Filtrage client-side des projets par statut et type — sans rechargement de page

import { Controller } from '@hotwired/stimulus'

export default class extends Controller {

    activeStatus = ''
    activeType   = ''

    connect () {
        // Lire l'état initial depuis l'URL (si l'utilisateur avait bookmarké avec des filtres)
        const params = new URLSearchParams(window.location.search)
        this.activeStatus = params.get('status') || ''
        this.activeType   = params.get('type')   || ''

        if (this.activeStatus || this.activeType) {
            this.#apply()
            this.#syncPills()
            this.#updateReset()
        }
    }

    // ── Actions ────────────────────────────────────────────────────────────
    setStatus (e) {
        e.preventDefault()
        const val = e.currentTarget.dataset.value
        // Toggle : re-cliquer désactive
        this.activeStatus = this.activeStatus === val ? '' : val
        this.#apply()
        this.#syncPills()
        this.#updateReset()
        this.#updateUrl()
    }

    setType (e) {
        e.preventDefault()
        const val = e.currentTarget.dataset.value
        this.activeType = this.activeType === val ? '' : val
        this.#apply()
        this.#syncPills()
        this.#updateReset()
        this.#updateUrl()
    }

    reset (e) {
        e.preventDefault()
        this.activeStatus = ''
        this.activeType   = ''
        this.#apply()
        this.#syncPills()
        this.#updateReset()
        this.#updateUrl()
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    /** Affiche / cache les lignes du projet selon les filtres actifs. */
    #apply () {
        document.querySelectorAll('.pf-item').forEach(item => {
            const statusOk = !this.activeStatus || item.dataset.status === this.activeStatus
            const typeOk   = !this.activeType   || item.dataset.type   === this.activeType
            item.classList.toggle('pf-hidden', !(statusOk && typeOk))
        })
    }

    /** Met à jour la classe is-active sur les pills. */
    #syncPills () {
        this.element.querySelectorAll('[data-filter-group="status"]').forEach(pill => {
            pill.classList.toggle('is-active', pill.dataset.value === this.activeStatus)
        })
        this.element.querySelectorAll('[data-filter-group="type"]').forEach(pill => {
            pill.classList.toggle('is-active', pill.dataset.value === this.activeType)
        })
    }

    /** Montre / cache le bouton reset selon l'état des filtres. */
    #updateReset () {
        const btn = this.element.querySelector('.pf-reset')
        if (!btn) return
        btn.style.display = (this.activeStatus || this.activeType) ? '' : 'none'
    }

    /** Met à jour l'URL sans recharger la page (pour pouvoir partager / bookmarker). */
    #updateUrl () {
        const url = new URL(window.location.href)
        if (this.activeStatus) url.searchParams.set('status', this.activeStatus)
        else url.searchParams.delete('status')
        if (this.activeType) url.searchParams.set('type', this.activeType)
        else url.searchParams.delete('type')
        history.replaceState({}, '', url)
    }
}
