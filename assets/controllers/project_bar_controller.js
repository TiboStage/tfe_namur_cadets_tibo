import { Controller } from '@hotwired/stimulus';

/**
 * Project-bar : bascule entre deux modes d'affichage des onglets
 *   - étendu  : icône + libellé
 *   - compact : icône seule (picto)
 *
 * L'état est mémorisé dans localStorage pour persister entre les pages.
 * Le controller est posé sur l'élément .project-bar lui-même.
 */
const STORAGE_KEY = 'ws_projectbar_compact';

export default class extends Controller {

    connect() {
        if (localStorage.getItem(STORAGE_KEY) === 'true') {
            this.element.classList.add('project-bar--compact');
        }
    }

    /** Appelé par le bouton bascule (data-action="click->project-bar#toggle") */
    toggle() {
        const compact = this.element.classList.toggle('project-bar--compact');
        localStorage.setItem(STORAGE_KEY, compact ? 'true' : 'false');
    }
}
