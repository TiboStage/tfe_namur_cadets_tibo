// assets/controllers/dropdown_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        this.boundHide = this.hide.bind(this);
    }

    toggle(event) {
        event.stopPropagation();
        this.menuTarget.classList.toggle('show');

        if (this.menuTarget.classList.contains('show')) {
            document.addEventListener('click', this.boundHide);
        }
    }

    hide() {  // ← Renommé de close() à hide()
        this.menuTarget.classList.remove('show');
        document.removeEventListener('click', this.boundHide);
    }

    disconnect() {
        document.removeEventListener('click', this.boundHide);
    }
}
