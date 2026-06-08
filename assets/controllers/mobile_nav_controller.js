import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    toggle() {
        document.body.classList.toggle('mobile-nav-open');
    }

    close() {
        document.body.classList.remove('mobile-nav-open');
    }
}
