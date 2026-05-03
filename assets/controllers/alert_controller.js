import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        autoClose: { type: Boolean, default: true },
        duration: { type: Number, default: 5000 }
    };

    connect() {
        if (this.autoCloseValue) {
            this.timeout = setTimeout(() => this.close(), this.durationValue);
        }
    }

    close() {
        this.element.classList.add('alert-fade-out');
        setTimeout(() => {
            if (this.element?.parentNode) {
                this.element.remove();
            }
        }, 500);
    }

    disconnect() {
        if (this.timeout) clearTimeout(this.timeout);
    }
}
