// assets/controllers/dropdown_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    toggle(event) {
        event.preventDefault();
        event.stopPropagation(); // <--- TRÈS IMPORTANT : empêche de déclencher le "hide" du window

        this.menuTarget.classList.toggle('hidden');
        console.log("Menu basculé ! État hidden :", this.menuTarget.classList.contains('hidden'));
    }

    hide(event) {
        // Si on clique sur la fenêtre mais pas sur le dropdown lui-même
        if (!this.element.contains(event.target)) {
            this.menuTarget.classList.add('hidden');
            console.log("Menu fermé par clic extérieur");
        }
    }
}
