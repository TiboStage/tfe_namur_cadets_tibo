// assets/controllers/project_cards_controller.js

import { Controller } from '@hotwired/stimulus';

/**
 * Controller pour les animations des cartes projets
 * - Fade-in progressif au chargement
 * - Animation smooth
 */
export default class extends Controller {
    static targets = ['card'];

    connect() {
        console.log('✅ Project Cards Controller connecté');
        this.animateCards();
    }

    /**
     * Animation d'apparition progressive des cartes
     */
    animateCards() {
        this.cardTargets.forEach((card, index) => {
            // État initial : invisible et décalé vers le bas
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            // Animation progressive avec délai
            setTimeout(() => {
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 80); // 80ms de délai entre chaque carte
        });
    }
}
