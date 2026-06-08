// assets/controllers/advanced_form_controller.js
// Toggle la section "Paramètres avancés" dans les modals de création/édition.
// Si des champs avancés ont déjà une valeur (mode édition), la section s'ouvre automatiquement.

import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['panel', 'checkbox']

    connect () {
        // Mode édition : ouvrir automatiquement si un champ avancé a du contenu
        if (this.hasPanelTarget) {
            const hasContent = [...this.panelTarget.querySelectorAll('textarea, input[type="text"]')]
                .some(el => el.value.trim() !== '')

            if (hasContent) {
                this.panelTarget.hidden = false
                if (this.hasCheckboxTarget) {
                    this.checkboxTarget.checked = true
                }
            }
        }
    }

    toggle (event) {
        if (this.hasPanelTarget) {
            this.panelTarget.hidden = !event.target.checked
        }
    }
}
