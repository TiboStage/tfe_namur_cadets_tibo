// // assets/controllers/dropdown_controller.js
// import { Controller } from '@hotwired/stimulus';
//
// export default class extends Controller {
//     static targets = ['menu'];
//
//     connect() {
//         this.boundHide = this.hide.bind(this);
//     }
//
//     toggle(event) {
//         event.stopPropagation();
//         this.menuTarget.classList.toggle('show');
//
//         if (this.menuTarget.classList.contains('show')) {
//             document.addEventListener('click', this.boundHide);
//         }
//     }
//
//     hide() {  // ← Renommé de close() à hide()
//         this.menuTarget.classList.remove('show');
//         document.removeEventListener('click', this.boundHide);
//     }
//
//     disconnect() {
//         document.removeEventListener('click', this.boundHide);
//     }
// }

// assets/controllers/dropdown_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    // ── Hover : affiche le menu ────────────────────────────────
    show() {
        this.menuTarget.classList.add('show');
    }

    // ── Hover out : masque le menu ─────────────────────────────
    hide() {
        this.menuTarget.classList.remove('show');
    }

    // ── Garde la compatibilité avec l'ancien toggle (clic) ─────
    // Si tu as encore data-action="click->dropdown#toggle" quelque part
    toggle(event) {
        event.stopPropagation();
        this.menuTarget.classList.toggle('show');
    }

    disconnect() {
        this.menuTarget.classList.remove('show');
    }
}
