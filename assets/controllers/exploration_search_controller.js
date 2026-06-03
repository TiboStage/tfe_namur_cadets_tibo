import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus — exploration filtrée.
 *
 * Comportement :
 *  • Au chargement sans filtre → sections discovery visibles, résultats masqués
 *  • Dès qu'un filtre/recherche est actif → discovery masquée, résultats visibles
 *  • Cartes rendues côté serveur (même composant Twig)
 */
export default class extends Controller {

    static targets = [
        'input',
        'clearBtn',
        'modeBtn',
        'typeSection',          // cols 3+4 (verrouillées en mode auteur)
        'typeSelect',           // <select> Types (caché, sync JS)
        'typeLabel',            // texte affiché dans le bouton type custom
        'genreSelect',          // <select> caché pour compat JS
        'genreBtn',             // bouton "Ajouter des filtres"
        'genreLabel',           // texte affiché dans le bouton genre
        'projectSortOptions',   // panneau tri projets
        'authorSortOptions',    // panneau tri auteurs
        'sortLabel',            // texte affiché dans "Trier par"
        'authorSortLabel',      // idem auteur
        'activeFilters',        // chips actifs
        'resultsSection',       // conteneur résultats AJAX
        'resultsHeader',
        'resultsGrid',
        'resultsPagination',
        'discoverySection',     // sections découverte statiques (films/séries/jeux)
    ];

    static values = {
        url:          String,
        initialQuery: { type: String, default: '' },
        initialType:  { type: String, default: '' },
        initialGenre: { type: String, default: '' },
    };

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    connect() {
        this._query = this.initialQueryValue;
        this._type  = this.initialTypeValue;
        this._genre = this.initialGenreValue;
        this._sort  = 'recent';
        this._mode  = 'projet';
        this._page  = 1;
        this._pages = 0;
        this._timer = null;

        // Restaurer l'état depuis les paramètres URL
        if (this._query) {
            this.inputTarget.value     = this._query;
            this.clearBtnTarget.hidden = false;
        }
        if (this.hasTypeSelectTarget  && this._type)  this.typeSelectTarget.value  = this._type;
        if (this.hasGenreSelectTarget && this._genre) this.genreSelectTarget.value = this._genre;

        this._filterGenreOptions();

        // Charger les résultats dès le connect (défaut : projets, tous types, plus récents)
        this._fetch();
    }

    disconnect() { clearTimeout(this._timer); }

    // ── Saisie ────────────────────────────────────────────────────────────────

    onInput(e) {
        this._query = e.target.value.trim();
        this._page  = 1;
        this.clearBtnTarget.hidden = !this._query;
        clearTimeout(this._timer);
        this._timer = setTimeout(() => this._fetch(), 350);
    }

    onKeydown(e) {
        if (e.key === 'Escape') this.clear();
    }

    clear() {
        this.inputTarget.value     = '';
        this._query                = '';
        this.clearBtnTarget.hidden = true;
        this._page                 = 1;
        this._fetch();
    }

    // ── Mode Projet / Auteur ──────────────────────────────────────────────────

    setMode(e) {
        const mode = e.currentTarget.dataset.mode;
        if (mode === this._mode) return;

        this._mode  = mode;
        this._type  = '';
        this._genre = '';
        this._sort  = mode === 'auteur' ? 'az' : 'recent';
        this._page  = 1;

        // Boutons de mode
        this.modeBtnTargets.forEach(btn =>
            btn.classList.toggle('active', btn.dataset.mode === mode)
        );

        // Colonnes 3+4 : verrouillées en mode auteur (pas cachées, juste disabled)
        this.typeSectionTargets.forEach(col => {
            col.classList.toggle('xfilter-col--locked', mode === 'auteur');
        });

        // Panneau tri : switcher entre projet et auteur
        if (this.hasProjectSortOptionsTarget)
            this.projectSortOptionsTarget.hidden = mode === 'auteur';
        if (this.hasAuthorSortOptionsTarget)
            this.authorSortOptionsTarget.hidden = mode !== 'auteur';

        // Reset selects
        if (this.hasTypeSelectTarget)  this.typeSelectTarget.value  = '';
        if (this.hasGenreSelectTarget) this.genreSelectTarget.value = '';

        // Reset radios sort vers défaut du mode
        this.element.querySelectorAll('[name="xsort"]').forEach(r => {
            r.checked = r.value === this._sort;
        });

        this._fetch();
    }

    // ── Filtres ───────────────────────────────────────────────────────────────

    onTypeChange(e) {
        this._type  = e.target.value;
        this._genre = '';
        this._page  = 1;

        this._filterGenreOptions();
        if (this.hasGenreSelectTarget) this.genreSelectTarget.value = '';

        this._fetch();
    }

    onGenreChange(e) {
        this._genre = e.target.value;
        this._page  = 1;
        this._fetch();
    }

    /** Bouton type de projet — dropdown custom */
    setTypeOption(e) {
        const val   = e.currentTarget.dataset.value;
        const label = e.currentTarget.dataset.label || e.currentTarget.textContent.trim();
        this._type  = val;
        this._genre = '';
        this._page  = 1;

        // Sync le select caché
        if (this.hasTypeSelectTarget) this.typeSelectTarget.value = val;
        // Mettre à jour le bouton label
        if (this.hasTypeLabelTarget) this.typeLabelTarget.textContent = label || this._allTypesLabel();

        // Marquer option active
        e.currentTarget.closest('[data-xfilter-panel]')
            ?.querySelectorAll('.xfilter-option')
            .forEach(b => b.classList.toggle('xfilter-option--active', b === e.currentTarget));

        // Fermer le dropdown
        const dd = e.currentTarget.closest('[data-controller~="dropdown"]');
        if (dd) {
            const ctrl = this.application.getControllerForElementAndIdentifier(dd, 'dropdown');
            ctrl ? ctrl.close() : this._closeDropdownFallback(dd);
        }

        this._filterGenreOptions();
        if (this.hasGenreSelectTarget) this.genreSelectTarget.value = '';
        if (this.hasGenreLabelTarget)  this.genreLabelTarget.textContent = this._allLabel();

        this._fetch();
    }

    /** Bouton "Ajouter des filtres" — dropdown genre custom */
    setGenreOption(e) {
        const val   = e.currentTarget.dataset.value;
        const label = e.currentTarget.dataset.label || e.currentTarget.textContent.trim();
        this._genre = val;
        this._page  = 1;

        // Sync le select caché pour la compat
        if (this.hasGenreSelectTarget) this.genreSelectTarget.value = val;

        // Mettre à jour le bouton label
        if (this.hasGenreLabelTarget) this.genreLabelTarget.textContent = label || 'Tous';

        // Marquer option active dans le panneau
        e.currentTarget.closest('[data-xfilter-panel]')
            ?.querySelectorAll('.xfilter-option')
            .forEach(b => b.classList.toggle('xfilter-option--active', b === e.currentTarget));

        // Fermer le dropdown via l'API Stimulus du dropdown_controller
        const dd = e.currentTarget.closest('[data-controller~="dropdown"]');
        if (dd) {
            const ctrl = this.application.getControllerForElementAndIdentifier(dd, 'dropdown');
            ctrl ? ctrl.close() : this._closeDropdownFallback(dd);
        }

        this._filterGenreOptions();
        this._fetch();
    }

    onSortChange(e) {
        this._sort = e.target.value;
        this._page = 1;
        this._fetch();
    }

    // ── Dropdown custom "Trier par" ───────────────────────────────────────────

    setSortOption(e) {
        const val   = e.currentTarget.dataset.value;
        const label = e.currentTarget.textContent.trim();
        this._sort  = val;
        this._page  = 1;

        // Mettre à jour le libellé du bouton
        const target = this._mode === 'auteur'
            ? (this.hasAuthorSortLabelTarget ? this.authorSortLabelTarget : null)
            : (this.hasSortLabelTarget       ? this.sortLabelTarget       : null);
        if (target) target.textContent = label;

        // Marquer l'option active dans le panneau
        e.currentTarget.closest('[data-xfilter-panel]')
            ?.querySelectorAll('.xfilter-option')
            .forEach(b => b.classList.toggle('xfilter-option--active', b === e.currentTarget));

        // Fermer le dropdown via l'API Stimulus du dropdown_controller
        const dd = e.currentTarget.closest('[data-controller~="dropdown"]');
        if (dd) {
            const ctrl = this.application.getControllerForElementAndIdentifier(dd, 'dropdown');
            ctrl ? ctrl.close() : this._closeDropdownFallback(dd);
        }

        this._fetch();
    }

    // ── Retrait des filtres actifs (chips) ────────────────────────────────────

    resetSort() {
        this._sort = this._mode === 'auteur' ? 'az' : 'recent';
        this._page = 1;
        const label = this._mode === 'auteur' ? 'Nom A-Z' : 'Plus récents';
        if (this.hasSortLabelTarget) this.sortLabelTarget.textContent = label;
        this._fetch();
    }

    clearType() {
        this._type  = '';
        this._genre = '';
        this._page  = 1;
        if (this.hasTypeSelectTarget)  this.typeSelectTarget.value  = '';
        if (this.hasGenreSelectTarget) this.genreSelectTarget.value = '';
        if (this.hasTypeLabelTarget)   this.typeLabelTarget.textContent  = this._allTypesLabel();
        if (this.hasGenreLabelTarget)  this.genreLabelTarget.textContent = this._allLabel();
        // Remettre l'option "Tous" active dans le panneau type
        this._resetTypeOptions();
        this._filterGenreOptions();
        this._fetch();
    }

    clearGenre() {
        this._genre = '';
        this._page  = 1;
        if (this.hasGenreSelectTarget) this.genreSelectTarget.value = '';
        this._fetch();
    }

    // ── quickSearch (boutons "Voir tous" depuis la discovery) ─────────────────

    quickSearch(e) {
        const type = e.currentTarget.dataset.type || '';
        const mode = e.currentTarget.dataset.mode || 'projet';

        this._mode  = mode;
        this._type  = type;
        this._genre = '';
        this._sort  = 'recent';
        this._page  = 1;
        this._query = '';

        this.inputTarget.value     = '';
        this.clearBtnTarget.hidden = true;

        // Boutons de mode
        this.modeBtnTargets.forEach(btn =>
            btn.classList.toggle('active', btn.dataset.mode === mode)
        );

        // Colonnes 3+4 — utilise forEach comme setMode (les DEUX colonnes)
        this.typeSectionTargets.forEach(col => {
            col.classList.toggle('xfilter-col--locked', mode === 'auteur');
        });

        if (this.hasProjectSortOptionsTarget)
            this.projectSortOptionsTarget.hidden = mode === 'auteur';
        if (this.hasAuthorSortOptionsTarget)
            this.authorSortOptionsTarget.hidden = mode !== 'auteur';

        if (this.hasTypeSelectTarget)  this.typeSelectTarget.value  = type;
        if (this.hasGenreSelectTarget) this.genreSelectTarget.value = '';
        if (this.hasTypeLabelTarget)   this.typeLabelTarget.textContent  = type ? this._typeLabel(type) : this._allTypesLabel();
        if (this.hasGenreLabelTarget)  this.genreLabelTarget.textContent = this._allLabel();
        this._resetTypeOptions(type);
        this._filterGenreOptions();

        this._fetch();

        // Scroller vers la section résultats
        this.resultsSectionTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ── Pagination ────────────────────────────────────────────────────────────

    prevPage() {
        if (this._page > 1) { this._page--; this._fetch(); }
    }

    nextPage() {
        if (this._page < this._pages) { this._page++; this._fetch(); }
    }

    // ── Filtrage des options genre ────────────────────────────────────────────

    /**
     * Masque les options du select genre incompatibles avec le type sélectionné.
     * Les genres avec data-types="[]" sont universels → toujours visibles.
     */
    _filterGenreOptions() {
        if (!this.hasGenreSelectTarget) return;

        const select = this.genreSelectTarget;
        select.querySelectorAll('option').forEach(opt => {
            if (!opt.value) return; // "Toutes les catégories" → toujours visible

            let types = [];
            try { types = JSON.parse(opt.dataset.types || '[]'); } catch { /* ignore */ }

            // Masquer si un type est sélectionné ET le genre n'est pas compatible
            opt.hidden = !!this._type && types.length > 0 && !types.includes(this._type);
        });

        // Si la sélection actuelle devient masquée → réinitialiser
        const selected = select.options[select.selectedIndex];
        if (selected?.hidden) {
            select.value = '';
            this._genre  = '';
        }
    }

    // ── Fetch AJAX ────────────────────────────────────────────────────────────

    async _fetch() {
        const params = new URLSearchParams({
            mode: this._mode,
            sort: this._sort,
            page: this._page,
        });
        if (this._query) params.set('q',     this._query);
        if (this._type)  params.set('type',  this._type);
        if (this._genre) params.set('genre', this._genre);

        // Skeleton adapté selon le mode (projet ≠ auteur)
        if (this.hasResultsGridTarget) {
            const skeleton = this._mode === 'auteur'
                ? this._skeletonAuthorCard()
                : this._skeletonCard();
            this.resultsGridTarget.innerHTML = Array(5).fill(skeleton).join('');
        }

        try {
            const resp = await fetch(`${this.urlValue}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!resp.ok) {
                throw new Error(`Erreur serveur : ${resp.status}`);
            }

            const data = await resp.json();
            this._render(data);

        } catch (err) {
            console.error('[exploration-search] Erreur fetch :', err);
            if (this.hasResultsGridTarget) {
                this.resultsGridTarget.innerHTML =
                    `<p class="efs-empty">Erreur de chargement.<br><small>${err.message}</small></p>`;
            }
        }
    }

    // ── Rendu ─────────────────────────────────────────────────────────────────

    _render({ total, page, pages, cardsHtml }) {
        this._pages = pages ?? 0;

        // Chips filtres actifs
        this._renderActiveFilters();

        // Compteur
        const qLabel = this._query ? ` pour « ${this._esc(this._query)} »` : '';
        if (this.hasResultsHeaderTarget) {
            this.resultsHeaderTarget.innerHTML =
                `<strong>${total}</strong> résultat${total > 1 ? 's' : ''}${qLabel}`;
        }

        // Grille
        if (this.hasResultsGridTarget) {
            this.resultsGridTarget.innerHTML = !total
                ? `<div class="efs-empty">
                       Aucun résultat${this._query ? ` pour « ${this._esc(this._query)} »` : ''}.
                       <br><small>Essayez d'autres termes ou filtres.</small>
                   </div>`
                : (cardsHtml || '');
        }

        // Pagination
        if (this.hasResultsPaginationTarget) {
            this.resultsPaginationTarget.innerHTML = pages <= 1 ? '' : `
                <nav class="efs-pagination">
                    <button class="efs-page-btn"
                            data-action="click->exploration-search#prevPage"
                            ${page <= 1 ? 'disabled' : ''}>← Précédent</button>
                    <span class="efs-page-info">Page ${page} / ${pages}</span>
                    <button class="efs-page-btn"
                            data-action="click->exploration-search#nextPage"
                            ${page >= pages ? 'disabled' : ''}>Suivant →</button>
                </nav>`;
        }
    }

    // ── Skeleton card ─────────────────────────────────────────────────────────

    _skeletonCard() {
        return `
        <article class="sc-card sc-card--skeleton" aria-hidden="true">

            <div class="sc-card__header">
                <span class="sk-block" style="height:11px;width:70px"></span>
                <span class="sk-block" style="height:11px;width:80px"></span>
            </div>

            <div class="sc-card__media sc-card__media--film sk-media">
                <div class="sc-card__media-badges">
                    <span class="sk-block" style="height:10px;width:60px"></span>
                    <span class="sk-block" style="height:10px;width:60px"></span>
                </div>
            </div>

            <div class="sc-card__body">

                <h3 class="sc-card__title">
                    <span class="sk-block" style="height:15px;width:80%;margin-bottom:4px"></span>
                    <span class="sk-block" style="height:15px;width:55%"></span>
                </h3>

                <div class="sc-card__creator" style="pointer-events:none">
                    <span class="sk-block u-avatar u-avatar--xs" style="flex-shrink:0;background:rgba(255,255,255,0.06)"></span>
                    <span class="sk-block" style="height:11px;width:120px"></span>
                </div>

                <p class="sc-card__desc sk-block" style="min-height:54px"></p>

                <div class="sc-card__divider"></div>

                <div class="sc-card__footer">
                    <span class="sk-block" style="height:11px;width:80px"></span>
                    <span class="sk-block" style="height:11px;width:100px"></span>
                </div>

            </div>
        </article>`;
    }

    _skeletonAuthorCard() {
        return `
        <a class="sc-card sc-card--creator sc-card--skeleton" aria-hidden="true">

            <div class="sc-card__media sc-card__media--auteur sk-media">
                <span class="sk-block u-avatar u-avatar--xl" style="background:rgba(255,255,255,0.06)"></span>
            </div>

            <div class="sc-card__body">

                <h3 class="sc-card__title">
                    <span class="sk-block" style="height:15px;width:65%"></span>
                </h3>

                <p class="sc-card__creator-fullname sk-block"
                   style="height:11px;width:45%;margin:4px 0 10px"></p>

                <p class="sc-card__desc sk-block" style="min-height:40px"></p>

                <div class="sc-card__divider"></div>

                <div class="sc-card__footer">
                    <span class="sk-block" style="height:11px;width:90px"></span>
                    <span class="sk-block" style="height:11px;width:80px"></span>
                </div>

            </div>
        </a>`;
    }

    // ── Chips filtres actifs ─────────────────────────────────────────────────

    _renderActiveFilters() {
        if (!this.hasActiveFiltersTarget) return;
        const chips = [];

        // Chip mode (toujours présent)
        const modeLabel = this._mode === 'auteur' ? 'Auteur' : 'Projets';
        chips.push(`
            <button type="button" class="xfilter-chip xfilter-chip--mode"
                    data-action="click->exploration-search#setMode"
                    data-mode="${this._mode === 'auteur' ? 'projet' : 'auteur'}">
                ${modeLabel}
                <span class="xfilter-chip__x" aria-hidden="true">×</span>
            </button>`);

        // Chip mots clé
        if (this._query) {
            chips.push(`
                <button type="button" class="xfilter-chip xfilter-chip--mode"
                        data-action="click->exploration-search#clear">
                    ${this._esc(this._query)}
                    <span class="xfilter-chip__x" aria-hidden="true">×</span>
                </button>`);
        }

        // Chip type
        if (this._type) {
            chips.push(`
                <button type="button" class="xfilter-chip"
                        data-action="click->exploration-search#clearType">
                    ${this._esc(this._typeLabel(this._type))}
                    <span class="xfilter-chip__x" aria-hidden="true">×</span>
                </button>`);
        }

        // Chip genre
        if (this._genre) {
            const opt = this.hasGenreSelectTarget
                ? this.genreSelectTarget.querySelector(`option[value="${this._genre}"]`)
                : null;
            const label = opt?.textContent.trim() ?? this._genre;
            chips.push(`
                <button type="button" class="xfilter-chip xfilter-chip--genre"
                        data-action="click->exploration-search#clearGenre">
                    ${this._esc(label)}
                    <span class="xfilter-chip__x" aria-hidden="true">×</span>
                </button>`);
        }

        // Chip tri (si pas défaut)
        const sortDefault = this._mode === 'auteur' ? 'az' : 'recent';
        if (this._sort !== sortDefault) {
            const sortLabels = { recent: 'Plus récents', oldest: 'Plus anciens', az: 'A-Z', za: 'Z-A' };
            chips.push(`
                <button type="button" class="xfilter-chip"
                        data-action="click->exploration-search#resetSort">
                    ${sortLabels[this._sort] ?? this._sort}
                    <span class="xfilter-chip__x" aria-hidden="true">×</span>
                </button>`);
        }

        this.activeFiltersTarget.innerHTML = chips.length
            ? `<span class="xfilters-chips-label">Filtres :</span>${chips.join('')}`
            : '';
    }

    // ── Utilitaires ───────────────────────────────────────────────────────────

    _typeLabel(type) {
        return { film: 'Film', serie: 'Série', jeu_video: 'Jeu vidéo' }[type] ?? type;
    }

    _allTypesLabel() {
        // Récupère le label "Tous les types" depuis le premier bouton du panneau type
        return this.element.querySelector('[data-action*="setTypeOption"][data-value=""]')
            ?.textContent.trim() ?? 'Tous';
    }

    _allLabel() {
        return this.element.querySelector('[data-action*="setGenreOption"][data-value=""]')
            ?.textContent.trim() ?? 'Tous';
    }

    /** Remet l'option active dans le panneau type custom */
    _resetTypeOptions(activeValue = '') {
        this.element.querySelectorAll('[data-action*="setTypeOption"]').forEach(btn => {
            btn.classList.toggle('xfilter-option--active', btn.dataset.value === activeValue);
        });
    }

    _esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /** Fermeture de secours pour un dropdown sans contrôleur Stimulus trouvé */
    _closeDropdownFallback(el) {
        el?.querySelector('[data-dropdown-target="menu"]')?.classList.remove('show', 'reduced');
        el?.classList.remove('is-open');
    }
}
