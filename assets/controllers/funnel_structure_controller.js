import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur du step 3 du funnel de création de projet.
 *
 * Les presets et labels sont injectés depuis Twig via data-* attributes
 * pour prendre en charge la traduction multilingue.
 */

/** Couleur par profondeur (index 0 = niveau 1) */
const LEVEL_COLORS = ['#E63946', '#F4A261', '#457B9D', '#2A9D8F'];

export default class extends Controller {

    static targets = ['depthInput', 'depthBtn', 'levelInputs', 'treePreview'];
    static values  = {
        type:         String,
        depth:        Number,
        presets:      Object,
        labelContent: String,
        labelOthers:  String,
        labelLevel:   String,
    };

    connect() {
        this._depth = this.depthValue || 3;
        const preset = this._getPreset(this.typeValue, this._depth);
        this._names  = [...preset];
        this._render();
    }

    // ── Actions ─────────────────────────────────────────────────────

    setDepth(event) {
        const next = parseInt(event.currentTarget.dataset.depth, 10);
        if (next === this._depth) return;

        const oldPreset = this._getPreset(this.typeValue, this._depth);
        const newPreset = this._getPreset(this.typeValue, next);

        // Garde les noms personnalisés si la position existe dans la nouvelle profondeur
        this._names = newPreset.map((defaultName, i) => {
            const wasCustom = this._names[i] !== undefined && this._names[i] !== (oldPreset[i] ?? '');
            return wasCustom ? this._names[i] : defaultName;
        });

        this._depth = next;
        this._render();
    }

    // ── Helpers ──────────────────────────────────────────────────────

    _getPreset(type, depth) {
        return this.presetsValue?.[type]?.[String(depth)] ?? this._fallbackPreset(depth);
    }

    _fallbackPreset(depth) {
        const tpl = this.labelLevelValue || 'Level %n%';
        return Array.from({ length: depth }, (_, i) => tpl.replace('%n%', i + 1));
    }

    // ── Rendu ────────────────────────────────────────────────────────

    _render() {
        this._renderDepthBtns();
        this._renderLevelInputs();
        this._renderTree();

        if (this.hasDepthInputTarget) {
            this.depthInputTarget.value = this._depth;
        }
    }

    _renderDepthBtns() {
        this.depthBtnTargets.forEach(btn => {
            btn.classList.toggle(
                'fn-depth-btn--active',
                parseInt(btn.dataset.depth, 10) === this._depth
            );
        });
    }

    _renderLevelInputs() {
        if (!this.hasLevelInputsTarget) return;

        const container  = this.levelInputsTarget;
        const levelTpl   = this.labelLevelValue || 'Level %n%';
        container.innerHTML = '';

        this._names.forEach((name, i) => {
            const row = document.createElement('div');
            row.className = 'fn-level-row';

            // Badge "N1", "N2"…
            const badge = document.createElement('span');
            badge.className   = 'fn-level-badge';
            badge.textContent = `N${i + 1}`;
            badge.style.setProperty('--badge-color', LEVEL_COLORS[i] ?? '#888');

            // Input texte
            const input = document.createElement('input');
            input.type      = 'text';
            input.name      = 'level_names[]';
            input.value     = name;
            input.className = 'fn-level-input';
            input.maxLength = 40;
            input.placeholder = levelTpl.replace('%n%', i + 1);

            const idx = i;
            input.addEventListener('input', () => {
                this._names[idx] = input.value;
                this._renderTree();
            });

            row.appendChild(badge);
            row.appendChild(input);
            container.appendChild(row);
        });
    }

    _renderTree() {
        if (!this.hasTreePreviewTarget) return;

        const tree        = this.treePreviewTarget;
        const contentLbl  = this.labelContentValue || 'content';
        const othersTpl   = this.labelOthersValue  || '+ more %name%…';
        const levelTpl    = this.labelLevelValue   || 'Level %n%';
        tree.innerHTML = '';

        for (let i = 0; i < this._depth; i++) {
            const name   = this._names[i] || levelTpl.replace('%n%', i + 1);
            const isLeaf = i === this._depth - 1;
            const color  = LEVEL_COLORS[i] ?? '#888';

            const node = document.createElement('div');
            node.className = `fn-tree-node fn-tree-node--d${i + 1}${isLeaf ? ' fn-tree-node--leaf' : ''}`;
            node.style.paddingLeft = `${i * 22}px`;

            // Icône connecteur
            const connector = document.createElement('span');
            connector.className = 'fn-tree-connector';
            if (i > 0) {
                connector.innerHTML = `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M2 0 v7 h10" stroke="${LEVEL_COLORS[i - 1] ?? '#444'}" stroke-width="1.5" stroke-linecap="round"/></svg>`;
            }

            // Point coloré
            const dot = document.createElement('span');
            dot.className = 'fn-tree-dot';
            dot.style.backgroundColor = color;

            // Nom
            const label = document.createElement('span');
            label.className   = 'fn-tree-name';
            label.textContent = name;

            node.appendChild(connector);
            node.appendChild(dot);
            node.appendChild(label);

            // Tag "content" sur la feuille
            if (isLeaf) {
                const tag = document.createElement('span');
                tag.className   = 'fn-tree-leaf-tag';
                tag.textContent = contentLbl;
                node.appendChild(tag);
            }

            tree.appendChild(node);
        }

        // Lignes hint "+ more X…" pour chaque niveau non-feuille
        for (let i = 0; i < this._depth - 1; i++) {
            const childName = this._names[i + 1]
                ? this._names[i + 1].toLowerCase()
                : levelTpl.replace('%n%', i + 2).toLowerCase();

            const hint = document.createElement('div');
            hint.className = 'fn-tree-hint';
            hint.style.paddingLeft = `${(i + 1) * 22 + 18}px`;
            hint.textContent = othersTpl.replace('%name%', childName);
            tree.appendChild(hint);
        }
    }
}
