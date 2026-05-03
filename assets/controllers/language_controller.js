import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dropdown', 'arrow'];

    toggle(event) {
        event.stopPropagation();
        this.dropdownTarget.classList.toggle('hidden');
        if (this.hasArrowTarget) {
            this.arrowTarget.classList.toggle('rotate-180');
        }
    }

    close(event) {
        if (!this.element.contains(event.target)) {
            this.dropdownTarget.classList.add('hidden');
            if (this.hasArrowTarget) {
                this.arrowTarget.classList.remove('rotate-180');
            }
        }
    }
}
