import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus — modal de publication du projet.
 *
 * Gère :
 *   - Ouverture / fermeture de la modal
 *   - Sélection de la visibilité (non publié / privé / en ligne)
 *   - Affichage conditionnel de la section épisodes (séries)
 *   - Fermeture par Escape
 */
export default class extends Controller {

    static targets = [
        'form',             // Le <form> de soumission
        'visibilityInput',  // <input hidden name="visibility">
        'episodesSection',  // Ancienne section épisodes (rétrocompat)
        'elementsSection',  // Section éléments (tous types)
    ];

    static values = {
        isSerie: Boolean,
    };

    connect() {
        // Fermer avec Escape
        this._onKeydown = (e) => {
            if (e.key === 'Escape') this.close();
        };
        document.addEventListener('keydown', this._onKeydown);

        // Synchroniser l'affichage de la section épisodes au chargement
        this._refreshEpisodes();
    }

    disconnect() {
        document.removeEventListener('keydown', this._onKeydown);
    }

    // ── Sélectionner une visibilité ───────────────────────────────────────────
    select(event) {
        const value = event.currentTarget.dataset.value;

        // Mettre à jour l'input caché
        this.visibilityInputTarget.value = value;

        // Mettre à jour la classe visuelle des options
        this.element.querySelectorAll('.visibility-option').forEach(el => {
            el.classList.toggle('is-selected', el.dataset.value === value);
        });

        // Afficher/masquer la section épisodes
        this._refreshEpisodes();
    }

    // ── Fermer la modal ───────────────────────────────────────────────────────
    close() {
        this.element.hidden = true;
    }

    // ── Tout sélectionner ────────────────────────────────────────────────────
    selectAll() {
        this.element.querySelectorAll('input[name="element_ids[]"]')
            .forEach(cb => cb.checked = true);
    }

    // ── Tout désélectionner ──────────────────────────────────────────────────
    selectNone() {
        this.element.querySelectorAll('input[name="element_ids[]"]')
            .forEach(cb => cb.checked = false);
    }

    // ── Affichage section éléments conditionnel ───────────────────────────────
    _refreshEpisodes() {
        const visibility = this.visibilityInputTarget.value;
        const isPublic   = visibility === 'public';

        // Nouvelle section (tous types)
        if (this.hasElementsSectionTarget) {
            this.elementsSectionTarget.hidden = !isPublic;
        }
        // Ancienne section série (rétrocompat)
        if (this.hasEpisodesSectionTarget) {
            this.episodesSectionTarget.hidden = !(this.isSerieValue && isPublic);
        }
    }
}
