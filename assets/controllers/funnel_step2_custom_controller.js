// assets/controllers/funnel-step2-custom_controller.js

import { Controller } from '@hotwired/stimulus';

/**
 * Controller pour l'étape 2 en mode Custom
 * Gère la navigation multi-slides, la validation et les genres dynamiques
 */
export default class extends Controller {
    static targets = [
        'slide',
        'projectTypeInput',
        'genresContainer'
    ];

    static values = {
        currentSlide: { type: Number, default: 1 },
        genresFilm: Object,
        genresSerie: Object,
        genresJeuVideo: Object
    };

    projectData = {
        project_type: null,
        selected_features: [],
        structure_mode: 'standard',
        custom_structure: [],
        genres: []
    };

    connect() {
        console.log('✅ Step2 Custom Controller connecté');
        this.showSlide(1);
    }

    /**
     * Navigation vers la slide suivante
     */
    goToNextSlide(event) {
        event.preventDefault();
        
        if (this.validateCurrentSlide()) {
            const nextSlide = this.currentSlideValue + 1;
            this.showSlide(nextSlide);
        } else {
            alert('Veuillez compléter tous les champs requis.');
        }
    }

    /**
     * Navigation vers la slide précédente
     */
    goToPreviousSlide(event) {
        event.preventDefault();
        
        const prevSlide = this.currentSlideValue - 1;
        this.showSlide(prevSlide);
    }

    /**
     * Affiche une slide spécifique
     */
    showSlide(slideNumber) {
        // Cacher toutes les slides
        this.slideTargets.forEach(slide => {
            slide.classList.remove('active');
        });

        // Afficher la slide demandée
        const targetSlide = this.slideTargets.find(
            slide => parseInt(slide.dataset.slide) === slideNumber
        );

        if (targetSlide) {
            targetSlide.classList.add('active');
            this.currentSlideValue = slideNumber;

            // Charger les genres si on arrive sur slide 3
            if (slideNumber === 3 && this.projectData.project_type) {
                this.loadGenresForSelectedType(this.projectData.project_type);
            }

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    /**
     * Valide la slide actuelle
     */
    validateCurrentSlide() {
        if (this.currentSlideValue === 1) {
            const selectedType = this.element.querySelector(
                'input[name="project_type_temp"]:checked'
            );
            
            if (!selectedType) {
                return false;
            }

            this.projectData.project_type = selectedType.value;
            
            if (this.hasProjectTypeInputTarget) {
                this.projectTypeInputTarget.value = selectedType.value;
            }
            
            return true;
        }

        if (this.currentSlideValue === 2) {
            const selectedFeatures = this.element.querySelectorAll(
                'input[name="selected_features[]"]:checked'
            );
            this.projectData.selected_features = Array.from(selectedFeatures)
                .map(f => f.value);
            return true;
        }

        return true;
    }

    /**
     * Active le bouton suivant quand un type est sélectionné
     */
    enableNextButton(event) {
        const nextBtn = this.element.querySelector('[data-action*="goToNextSlide"]');
        if (nextBtn) {
            nextBtn.disabled = false;
        }
    }

    /**
     * Charge les genres pour le type sélectionné
     */
    loadGenresForSelectedType(type) {
        if (!this.hasGenresContainerTarget) {
            return;
        }

        let genres = {};
        
        if (type === 'film') {
            genres = this.genresFilmValue;
        } else if (type === 'serie') {
            genres = this.genresSerieValue;
        } else if (type === 'jeu_video') {
            genres = this.genresJeuVideoValue;
        }

        this.genresContainerTarget.innerHTML = '';

        Object.entries(genres).forEach(([key, label]) => {
            const checkboxLabel = document.createElement('label');
            checkboxLabel.className = 'genre-checkbox';
            checkboxLabel.innerHTML = `
                <input type="checkbox" name="genres[]" value="${key}">
                <span>${label}</span>
            `;
            this.genresContainerTarget.appendChild(checkboxLabel);
        });
    }

    /**
     * Toggle custom structure builder
     */
    toggleStructureBuilder(event) {
        const customBuilder = document.getElementById('custom-structure-builder');
        
        if (customBuilder) {
            if (event.target.value === 'custom') {
                customBuilder.classList.remove('hidden');
            } else {
                customBuilder.classList.add('hidden');
            }
        }
    }

    /**
     * Ajouter un niveau de structure personnalisée
     */
    addStructureLevel() {
        const structureLevels = document.getElementById('structure-levels');
        const currentLevels = structureLevels.querySelectorAll('.structure-level').length;

        if (currentLevels >= 5) {
            alert('Maximum 5 niveaux');
            return;
        }

        const levelCount = currentLevels + 1;
        const levelDiv = document.createElement('div');
        levelDiv.className = 'structure-level';
        levelDiv.innerHTML = `
            <input 
                type="text" 
                name="custom_structure[${levelCount}][label]" 
                placeholder="Ex: Partie, Chapitre, Scène..."
                class="form-control"
                required
            >
            <input type="hidden" name="custom_structure[${levelCount}][depth]" value="${levelCount}">
            <label>
                <input type="checkbox" name="custom_structure[${levelCount}][hasContent]" value="1">
                Contient du texte
            </label>
        `;
        structureLevels.appendChild(levelDiv);
    }
}
