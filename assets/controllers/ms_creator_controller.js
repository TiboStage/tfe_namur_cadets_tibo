import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus — panel de création d'un élément narratif.
 *
 * Même pattern que publish-modal :
 *   - Panel hidden dans le DOM
 *   - Ouverture/fermeture par JS
 *   - Formulaire POST standard, sans turbo-frame
 *
 * Données lues sur le bouton déclencheur (data-ms-creator-*) :
 *   - action-url  : URL de soumission du formulaire
 *   - label       : nom du niveau à créer (ex: "Chapitre")
 *   - color       : couleur hex du niveau
 *   - icon        : emoji du niveau
 *   - has-content : "true" si niveau feuille (écriture)
 */
export default class extends Controller {

    static targets = ['form', 'label', 'icon', 'input', 'levelType'];

    connect() {
        this._onKeydown = (e) => { if (e.key === 'Escape') this.close(); };
        document.addEventListener('keydown', this._onKeydown);
    }

    disconnect() {
        document.removeEventListener('keydown', this._onKeydown);
    }

    // ── Ouvrir avec les données du bouton cliqué ──────────────────────────────
    open(event) {
        event.preventDefault();
        const btn = event.currentTarget;

        // Mettre à jour le formulaire
        if (this.hasFormTarget) {
            this.formTarget.action = btn.dataset.actionUrl;
        }

        // Mettre à jour les labels visuels
        if (this.hasLabelTarget) {
            this.labelTarget.textContent = btn.dataset.label ?? '';
            this.labelTarget.style.color  = btn.dataset.color ?? '';
        }

        if (this.hasIconTarget) {
            this.iconTarget.textContent   = btn.dataset.icon ?? '';
            this.iconTarget.style.color   = btn.dataset.color ?? '';
        }

        if (this.hasLevelTypeTarget) {
            const isLeaf = btn.dataset.hasContent === 'true';
            this.levelTypeTarget.textContent = isLeaf ? 'Écriture' : 'Structure';
            this.levelTypeTarget.className = 'ms-creator-level-type' + (isLeaf ? ' ms-creator-level-type--leaf' : '');
        }

        // Contexte parent
        const ctxEl = this.element.querySelector('.ms-creator-context');
        if (ctxEl) {
            ctxEl.textContent = btn.dataset.context ?? 'Racine du manuscrit';
        }

        // Reset + focus input
        if (this.hasInputTarget) {
            this.inputTarget.value = '';
            this.inputTarget.placeholder = (btn.dataset.label ?? 'Élément') + ' 1…';
        }

        // Afficher
        this.element.hidden = false;
        if (this.hasInputTarget) {
            setTimeout(() => this.inputTarget.focus(), 50);
        }
    }

    // ── Fermer ────────────────────────────────────────────────────────────────
    close() {
        this.element.hidden = true;
    }

    // ── Fermer au clic sur le backdrop ────────────────────────────────────────
    backdropClick(e) {
        if (e.target === this.element) this.close();
    }
}
