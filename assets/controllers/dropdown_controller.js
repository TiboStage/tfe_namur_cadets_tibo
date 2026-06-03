import { Controller } from '@hotwired/stimulus';

/**
 * Controller Stimulus — dropdown au clic.
 *
 * Usage :
 *   data-controller="dropdown"
 *   data-action="click->dropdown#toggle"    ← sur le trigger
 *   data-dropdown-target="menu"             ← sur le panneau
 *
 * Cas multi-panneaux (ex : tri projets/auteurs) :
 *   Plusieurs éléments peuvent avoir data-dropdown-target="menu".
 *   Le dropdown ouvre/ferme le premier qui n'est pas caché (hidden).
 *
 * Fermeture automatique :
 *   - Clic en dehors du composant
 *   - Touche Echap
 *   - Ouverture d'un autre dropdown (pas de stopPropagation)
 */
export default class extends Controller {
    static targets = ['menu'];

    connect() {
        // Réinitialiser l'état au cas où Turbo restaure un snapshot avec le dropdown ouvert
        this.menuTargets.forEach(m => m.classList.remove('show', 'reduced'));
        this.element.classList.remove('is-open');

        this._outsideClick = (e) => {
            if (!this.element.contains(e.target)) this.close();
        };
        this._keydown = (e) => {
            if (e.key === 'Escape') this.close();
        };
    }

    disconnect() {
        this._cleanup();
    }

    // ── Toggle au clic sur le trigger ─────────────────────────────────────
    // NOTE : pas de stopPropagation → les _outsideClick des autres dropdowns
    //        ouverts peuvent se déclencher et se fermer automatiquement.

    toggle(event) {
        const isOpen = this.element.classList.contains('is-open');
        isOpen ? this.close() : this.open();
    }

    // ── Ouvrir ────────────────────────────────────────────────────────────

    open() {
        const menu = this._activeMenu;
        if (!menu) return;

        const isCollapsed = !!document.querySelector('.workspace-container.sidebar-collapsed')
            ?.contains(this.element);

        menu.classList.add('show');
        menu.classList.toggle('reduced', isCollapsed);
        this.element.classList.add('is-open');

        document.addEventListener('click',   this._outsideClick);
        document.addEventListener('keydown', this._keydown);
    }

    // ── Fermer ────────────────────────────────────────────────────────────

    close() {
        // Fermer tous les panneaux (y compris le panneau qui était actif)
        this.menuTargets.forEach(m => m.classList.remove('show', 'reduced'));
        this.element.classList.remove('is-open');
        this._cleanup();
    }

    // ── Compat hover ──────────────────────────────────────────────────────
    show() { this.open(); }
    hide() { this.close(); }

    // ── Panneau actif : premier non-caché ─────────────────────────────────
    // Utile quand plusieurs panneaux partagent le même dropdown (ex: tri).

    get _activeMenu() {
        return this.menuTargets.find(m => !m.hidden) ?? this.menuTargets[0];
    }

    // ── Cleanup listeners ─────────────────────────────────────────────────

    _cleanup() {
        document.removeEventListener('click',   this._outsideClick);
        document.removeEventListener('keydown', this._keydown);
    }
}
