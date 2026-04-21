import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["card"]

    showSignup() {
        this.cardTarget.classList.add("right-panel-active");
    }

    showLogin() {
        this.cardTarget.classList.remove("right-panel-active");
    }
}
