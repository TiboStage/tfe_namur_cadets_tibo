// assets/controllers/faq_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item']

    toggle(event) {
        const item = event.currentTarget.closest('.faq-item');
        item.classList.toggle('active');

        const answer = item.querySelector('.faq-answer');
        answer.style.display = item.classList.contains('active') ? 'block' : 'none';

        const icon = event.currentTarget.querySelector('i');
        icon.classList.toggle('fa-plus');
        icon.classList.toggle('fa-xmark');
    }
}
