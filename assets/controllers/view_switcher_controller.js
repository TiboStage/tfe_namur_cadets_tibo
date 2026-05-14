// assets/controllers/view_switcher_controller.js
import { Controller } from '@hotwired/stimulus';

/**
 * Controller de switch vue grille / liste
 *
 * Générique — fonctionne pour Projets, Personnages, Lieux, etc.
 *
 * Usage :
 * <div data-controller="view-switcher" data-view-switcher-container-id-value="characters-container">
 *     <button data-action="click->view-switcher#switch" data-view="grid">Grille</button>
 *     <button data-action="click->view-switcher#switch" data-view="list">Liste</button>
 * </div>
 *
 * Si data-view-switcher-container-id-value est absent,
 * utilise "projects-container" par défaut (rétrocompatibilité)
 */
export default class extends Controller {
    static values = {
        containerId: { type: String, default: 'projects-container' },
        gridClass:   { type: String, default: '' }, // ex: "projects-grid" ou "characters-grid"
        listClass:   { type: String, default: '' }, // ex: "projects-list" ou "characters-list"
    };

    switch(event) {
        const btn = event.currentTarget;
        const view = btn.dataset.view;
        const container = document.getElementById(this.containerIdValue);
        const buttons = this.element.querySelectorAll('.btn');

        if (!container) return;

        // ── Mettre à jour les boutons ──────────────────────────
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // ── Déterminer les classes à utiliser ──────────────────
        // Priorité : data values > pattern automatique depuis l'ID
        const baseId = this.containerIdValue.replace('-container', '');
        const gridClass = this.gridClassValue || `${baseId}-grid`;
        const listClass = this.listClassValue || `${baseId}-list`;

        // ── Mettre à jour le conteneur ─────────────────────────
        if (view === 'list') {
            container.classList.remove(gridClass);
            container.classList.add(listClass);
        } else {
            container.classList.remove(listClass);
            container.classList.add(gridClass);
        }
    }
}
