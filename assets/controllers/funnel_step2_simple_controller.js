// assets/controllers/funnel-step2-simple_controller.js
// VERSION PROPRE - Traductions via data-attributes

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'explanationZone',
        'genresSection',
        'genresContainer',
        'submitBtn'
    ];

    // ═══════════════════════════════════════════════════════════════
    // VALUES : Données passées depuis le HTML via data-attributes
    // Les traductions sont injectées par Twig dans le HTML,
    // pas dans le JS !
    // ═══════════════════════════════════════════════════════════════
    static values = {
        genresFilm: Object,
        genresSerie: Object,
        genresJeuVideo: Object
    };

    connect() {
        console.log('✅ Step2 Simple Controller connecté');
    }

    /**
     * Appelé quand un type de projet est sélectionné
     */
    selectType(event) {
        const type = event.currentTarget.dataset.type;

        // Afficher l'explication correspondante
        this.updateExplanation(type);

        // Charger les genres pour ce type
        this.loadGenres(type);

        // Activer le bouton submit
        if (this.hasSubmitBtnTarget) {
            this.submitBtnTarget.disabled = false;
        }
    }

    /**
     * Met à jour la zone d'explication avec le template correspondant
     */
    updateExplanation(type) {
        const template = document.getElementById(`explanation-${type}`);

        if (template && this.hasExplanationZoneTarget) {
            this.explanationZoneTarget.innerHTML = template.innerHTML;
        }
    }

    /**
     * Charge les genres pour le type sélectionné
     * Les données viennent des values (injectées par Twig)
     */
    loadGenres(type) {
        if (!this.hasGenresContainerTarget) {
            return;
        }

        // Récupérer les genres depuis les values
        let genres = {};

        if (type === 'film') {
            genres = this.genresFilmValue;
        } else if (type === 'serie') {
            genres = this.genresSerieValue;
        } else if (type === 'jeu_video') {
            genres = this.genresJeuVideoValue;
        }

        // Vider le container
        this.genresContainerTarget.innerHTML = '';

        // Créer les checkboxes pour chaque genre
        Object.entries(genres).forEach(([key, label]) => {
            const checkboxLabel = document.createElement('label');
            checkboxLabel.className = 'genre-checkbox';
            checkboxLabel.innerHTML = `
                <input type="checkbox" name="genres[]" value="${key}">
                <span>${label}</span>
            `;
            this.genresContainerTarget.appendChild(checkboxLabel);
        });

        // Afficher la section genres
        if (this.hasGenresSectionTarget) {
            this.genresSectionTarget.classList.remove('hidden');
        }
    }
}
