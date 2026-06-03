import { Controller } from '@hotwired/stimulus';

/**
 * Accordéon simple pour les sections de la sidebar droite de l'éditeur.
 * Usage :
 *   <div data-controller="accordion">
 *     <button data-action="accordion#toggle">Titre</button>
 *     <div class="hidden" data-accordion-target="content">Contenu</div>
 *   </div>
 */
export default class extends Controller {
    static targets = ['content'];

    toggle() {
        if (!this.hasContentTarget) return;
        this.contentTarget.classList.toggle('hidden');
        this.element.classList.toggle('accordion--open');
    }
}
