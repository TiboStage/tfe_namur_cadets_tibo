import { Controller } from '@hotwired/stimulus';

/**
 * Adapte automatiquement le nombre d'éléments par page selon la taille d'écran.
 * Navigue vers la même URL avec ?per_page=N&page=1 si la valeur actuelle ne correspond pas.
 * Affiche les skeletons pendant la transition pour éviter le flash de contenu.
 *
 * Breakpoints : ≥1280px → 10  |  ≥768px → 6  |  <768px → 4
 *
 * Targets attendus dans le template :
 *   data-per-page-target="skeleton"  → grille de skeleton cards (hidden par défaut)
 *   data-per-page-target="content"   → wrapper du vrai contenu
 */
export default class extends Controller {
    static targets = ['skeleton', 'content'];

    connect() {
        this._currentBreakpoint = this._breakpoint();
        this._syncPerPage();

        this._resizeHandler = this._debounce(() => {
            const bp = this._breakpoint();
            if (bp !== this._currentBreakpoint) {
                this._currentBreakpoint = bp;
                this._syncPerPage();
            }
        }, 400);

        window.addEventListener('resize', this._resizeHandler);
    }

    disconnect() {
        window.removeEventListener('resize', this._resizeHandler);
    }

    _syncPerPage() {
        const desired = this._perPage();
        const url     = new URL(window.location.href);
        const current = parseInt(url.searchParams.get('per_page') || '0', 10);

        if (current !== desired) {
            // Affiche les skeletons, masque le vrai contenu avant de naviguer
            this._showSkeleton(desired);

            url.searchParams.set('per_page', desired);
            url.searchParams.delete('page');
            window.location.replace(url.toString());
        }
    }

    _showSkeleton(targetCount) {
        // 1. Retire le contenu du layout immédiatement (pas de "visibility" qui garde l'espace)
        if (this.hasContentTarget) {
            this.contentTarget.hidden = true;
        }

        if (!this.hasSkeletonTarget) return;

        const grid  = this.skeletonTarget;
        const cards = grid.querySelectorAll('.sc-card--skeleton');

        // 2. Affiche seulement N cartes skeleton selon le breakpoint cible
        cards.forEach((card, i) => {
            card.hidden = i >= targetCount;
        });

        // 3. Affiche la grille skeleton avec un léger fade-in
        grid.hidden            = false;
        grid.style.opacity     = '0';
        grid.style.transition  = 'opacity 0.12s ease';

        requestAnimationFrame(() => {
            grid.style.opacity = '1';
        });
    }

    _breakpoint() {
        const w = window.innerWidth;
        if (w >= 1280) return 'lg';
        if (w >= 768)  return 'md';
        return 'sm';
    }

    _perPage() {
        switch (this._breakpoint()) {
            case 'lg': return 10;
            case 'md': return 6;
            default:   return 4;
        }
    }

    _debounce(fn, delay) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    }
}
