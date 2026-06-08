// assets/controllers/chr_filter_controller.js
// Filtrage client-side de la timeline par type d'événement

import { Controller } from '@hotwired/stimulus'

export default class extends Controller {

    filter (event) {
        const btn      = event.currentTarget
        const type     = btn.dataset.type
        const allBtns  = this.element.querySelectorAll('.chr-filter-btn')
        const allCards = this.element.querySelectorAll('.chr-event')
        const allYears = this.element.querySelectorAll('.chr-year-block')

        // Toggle bouton actif
        allBtns.forEach(b => b.classList.remove('chr-filter-btn--active'))
        btn.classList.add('chr-filter-btn--active')

        // Filtrer les events
        allCards.forEach(card => {
            const match = !type || card.dataset.type === type
            card.style.display = match ? '' : 'none'
        })

        // Masquer les blocs d'année vides
        allYears.forEach(block => {
            const visible = [...block.querySelectorAll('.chr-event')]
                .some(c => c.style.display !== 'none')
            block.style.display = visible ? '' : 'none'
        })
    }
}
