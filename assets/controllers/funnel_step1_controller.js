// assets/controllers/funnel-step1_controller.js

import { Controller } from '@hotwired/stimulus';

/**
 * Controller pour l'étape 1 du funnel (choix du mode)
 * Gère les animations d'apparition des cards
 */
export default class extends Controller {
    static targets = ['card'];
    cardTargets;

    connect() {
        console.log('✅ Step1 Controller connecté');

        // Animation d'apparition progressive des cards
        this.cardTargets.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 150);
        });
    }
}
