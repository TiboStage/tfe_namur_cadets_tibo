import { Controller } from '@hotwired/stimulus';

// ── Constantes blocs ────────────────────────────────────────────────────────

const BLOCK_TYPES = ['ACTION', 'SCENE', 'CHARACTER', 'DIALOGUE', 'PARENTHETICAL', 'TRANSITION'];

const NEXT_TYPE_MAP = {
    ACTION:        'ACTION',
    SCENE:         'ACTION',
    CHARACTER:     'DIALOGUE',
    DIALOGUE:      'CHARACTER',
    PARENTHETICAL: 'DIALOGUE',
    TRANSITION:    'SCENE',
};

const TYPE_LABELS = {
    ACTION:        'Action',
    SCENE:         'Décor',
    CHARACTER:     'Personnage',
    DIALOGUE:      'Dialogue',
    PARENTHETICAL: 'Didascalie',
    TRANSITION:    'Transition',
};

const TYPE_PLACEHOLDERS = {
    ACTION:        'Description de la scène…',
    SCENE:         'INT./EXT. LIEU — JOUR/NUIT',
    CHARACTER:     'NOM DU PERSONNAGE',
    DIALOGUE:      'Réplique du personnage…',
    PARENTHETICAL: '(didascalie)',
    TRANSITION:    'FONDU ENCHAÎNÉ :',
};

const AUTO_DETECT_RULES = [
    { pattern: /^(INT\.|EXT\.|INT\.\/EXT\.|I\/E)\s/i, type: 'SCENE' },
    { pattern: /^(FONDU|CUT TO:|FADE (IN|OUT)|SMASH CUT|MATCH CUT)/i, type: 'TRANSITION' },
];

const PREVIEW_CSS = {
    SCENE:         'fp-slug',
    CHARACTER:     'fp-char',
    DIALOGUE:      'fp-diag',
    PARENTHETICAL: 'fp-paren',
    TRANSITION:    'fp-transition',
    ACTION:        'fp-action',
};

// Hauteur de contenu disponible par page (1100px papier - 128px padding)
// On garde 44px de marge de sécurité pour éviter les blocs à cheval
const PAGE_CONTENT_HEIGHT = 928;

// ── Constantes mentions ─────────────────────────────────────────────────────

const MENTION_TRIGGERS = {
    '@': { label: 'Personnage', icon: 'user',    cssClass: 'mention--char' },
    '#': { label: 'Lieu',       icon: 'map-pin', cssClass: 'mention--loc'  },
};

// ── Controller ──────────────────────────────────────────────────────────────

export default class extends Controller {
    static targets = [
        'stage', 'preview',
        'saveIndicator', 'saveButton',
        'wordCount', 'charCount',
        'lastSaved', 'wordCountStat', 'charCountStat', 'lastSavedStat',
        'detectedChars', 'typePill',
        'estimatedPages', 'estimatedScreenTime', 'estimatedReadTime',
    ];

    static values = {
        saveUrl:             String,
        elementId:           Number,
        elementTitle:        { type: String,  default: 'Script' },
        wordsPerPage:        { type: Number,  default: 170 },
        readingWpm:          { type: Number,  default: 200 },
        screenTimePerPage:   { type: Number,  default: 60 },
        autoSaveInterval:    { type: Number,  default: 30000 },
        debounceDelay:       { type: Number,  default: 2000 },
        content:             { type: Array,   default: [] },
        characters:          { type: Array,   default: [] },
        locations:           { type: Array,   default: [] },
        noteNewUrl:          { type: String,  default: '' },
        i18n:                { type: Object,  default: {} },
    };

    // ── Lifecycle ────────────────────────────────────────────────────────────

    connect() {
        this.isDirty         = false;
        this.isSaving        = false;
        this.isReading       = false;
        this.debounceTimer   = null;
        this.autoSaveTimer   = null;
        this.lastSavedAt     = null;
        this._pageBreakTimer = null;

        this._mentionActive   = false;
        this._mentionTrigger  = null;
        this._mentionQuery    = '';
        this._mentionBlock    = null;
        this._mentionFocusIdx = 0;

        this._buildMentionDropdown();
        this._initBlocks();

        this.autoSaveTimer = setInterval(() => {
            if (this.isDirty) this._doSave();
        }, this.autoSaveIntervalValue);

        this._beforeUnloadHandler = (e) => {
            if (this.isDirty) { e.preventDefault(); e.returnValue = ''; }
        };
        window.addEventListener('beforeunload', this._beforeUnloadHandler);

        this._outsideClick = (e) => {
            if (!this._mentionDropdown.contains(e.target)) this._closeMention();
        };
        document.addEventListener('mousedown', this._outsideClick);

        this._setIndicatorState('saved');
    }

    disconnect() {
        clearTimeout(this.debounceTimer);
        clearTimeout(this._pageBreakTimer);
        clearInterval(this.autoSaveTimer);
        window.removeEventListener('beforeunload', this._beforeUnloadHandler);
        document.removeEventListener('mousedown', this._outsideClick);
        this._mentionDropdown?.remove();
        document.body.classList.remove('sidebar-open');
    }

    // ── Init blocs ───────────────────────────────────────────────────────────

    _initBlocks() {
        const stage = this.stageTarget;
        stage.innerHTML = '';

        let blocks = Array.isArray(this.contentValue) ? [...this.contentValue] : [];

        if (blocks.length > 0 && blocks[0]?.type === 'raw') {
            const tmp = document.createElement('div');
            tmp.innerHTML = blocks[0].content ?? '';
            const text = (tmp.innerText || tmp.textContent || '').trim();
            blocks = text
                ? text.split('\n').filter(l => l.trim()).map(l => ({ type: 'ACTION', content: l.trim() }))
                : [];
        }

        if (blocks.length === 0) blocks = [{ type: 'ACTION', content: '' }];

        // Créer la première page et y ajouter tous les blocs
        const firstPage  = this._createPageEl(1);
        const firstPaper = firstPage.querySelector('.editor-paper');
        stage.appendChild(firstPage);

        for (const b of blocks) {
            firstPaper.appendChild(this._createBlock(b.type ?? 'ACTION', b.content ?? ''));
        }

        this.updateStats();
        this._updateDetectedChars();

        // Attendre que le navigateur ait calculé les hauteurs avant de paginer
        requestAnimationFrame(() => {
            setTimeout(() => {
                this._paginateBlocks();
                const first = stage.querySelector('.block-wrapper .block');
                if (first) this._moveCursorToEnd(first);
            }, 80);
        });
    }

    // ── Création d'un bloc ───────────────────────────────────────────────────

    _createBlock(type = 'ACTION', content = '') {
        const el = document.createElement('div');
        el.className       = 'block';
        el.dataset.type    = type;
        el.dataset.label   = this._typeLabel(type);
        el.dataset.placeholder = this._typePlaceholder(type);
        el.contentEditable = 'true';
        el.spellcheck      = false;
        el.textContent     = content;

        el.addEventListener('keydown', (e) => this._onKeyDown(e, el));
        el.addEventListener('input',   ()  => this._onInput(el));
        el.addEventListener('focus',   ()  => this._onFocus(el));
        el.addEventListener('paste',   (e) => this._onPaste(e, el));

        const wrapper = document.createElement('div');
        wrapper.className = 'block-wrapper';
        wrapper.appendChild(el);

        if (this.noteNewUrlValue) {
            const noteBtn = document.createElement('button');
            noteBtn.type      = 'button';
            noteBtn.className = 'block-comment-btn';
            noteBtn.tabIndex  = -1;
            noteBtn.setAttribute('aria-label', this.i18nValue?.note_aria ?? 'Ajouter une note sur ce bloc');
            noteBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>';
            noteBtn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                this._openBlockNote();
            });
            wrapper.appendChild(noteBtn);
        }

        return wrapper;
    }

    _setBlockType(el, type) {
        el.dataset.type        = type;
        el.dataset.label       = this._typeLabel(type);
        el.dataset.placeholder = this._typePlaceholder(type);
    }

    // ── Helpers i18n ─────────────────────────────────────────────────────────

    _typeLabel(type) {
        return this.i18nValue?.['type_' + type.toLowerCase()] ?? TYPE_LABELS[type] ?? type;
    }

    _typePlaceholder(type) {
        return this.i18nValue?.['ph_' + type.toLowerCase()] ?? TYPE_PLACEHOLDERS[type] ?? '';
    }

    _mentionLabel(trigger) {
        if (trigger === '@') return this.i18nValue?.mention_char ?? MENTION_TRIGGERS[trigger]?.label ?? '';
        if (trigger === '#') return this.i18nValue?.mention_loc  ?? MENTION_TRIGGERS[trigger]?.label ?? '';
        return MENTION_TRIGGERS[trigger]?.label ?? '';
    }

    _mentionHint() {
        return this.i18nValue?.mention_hint ?? '↑↓ naviguer · ↵ valider · Échap fermer';
    }

    // ── Événements clavier ───────────────────────────────────────────────────

    _onKeyDown(e, el) {
        if (this._mentionActive) {
            switch (e.key) {
                case 'ArrowDown': e.preventDefault(); this._mentionNavigate(1);  return;
                case 'ArrowUp':   e.preventDefault(); this._mentionNavigate(-1); return;
                case 'Enter': case 'Tab':
                    if (this._mentionConfirm()) { e.preventDefault(); return; }
                    break;
                case 'Escape': this._closeMention(); return;
            }
        }

        switch (e.key) {
            case 'Enter':
                e.preventDefault();
                this._handleEnter(el);
                break;
            case 'Tab':
                e.preventDefault();
                this._handleTab(el, e.shiftKey);
                break;
            case 'Backspace':
                if (el.textContent.trim() === '') {
                    e.preventDefault();
                    this._handleBackspaceEmpty(el);
                }
                break;
            case 'ArrowUp':
                if (this._isAtStart(el)) { e.preventDefault(); this._focusPrev(el); }
                break;
            case 'ArrowDown':
                if (this._isAtEnd(el)) { e.preventDefault(); this._focusNext(el); }
                break;
        }
    }

    _onInput(el) {
        this._tryAutoDetect(el);
        this._updateTypePill(el.dataset.type);
        this.markDirty();
        this.updateStats();
        this._updateDetectedChars();
        this.scheduleDebounce();
        this._checkMentionTrigger(el);
        // Repagination (debounced — laisse le temps au DOM de se stabiliser)
        clearTimeout(this._pageBreakTimer);
        this._pageBreakTimer = setTimeout(() => this._paginateBlocks(), 400);
    }

    _onFocus(el) {
        this._updateTypePill(el.dataset.type);
    }

    _onPaste(e, el) {
        e.preventDefault();
        const raw   = (e.clipboardData || window.clipboardData).getData('text/plain');
        const lines = raw.split('\n');

        if (lines.length <= 1) {
            document.execCommand('insertText', false, raw);
            return;
        }

        el.textContent = (el.textContent + lines[0]).trim();
        let lastWrapper = el.parentElement;
        for (let i = 1; i < lines.length; i++) {
            const text = lines[i];
            if (!text.trim() && i === lines.length - 1) continue;
            const nbWrapper = this._createBlock('ACTION', text.trim());
            lastWrapper.after(nbWrapper);
            lastWrapper = nbWrapper;
        }
        const lastBlock = lastWrapper.querySelector('.block');
        if (lastBlock) { lastBlock.focus(); this._moveCursorToEnd(lastBlock); }
        this.markDirty();
        this.scheduleDebounce();
    }

    // ── Handlers blocs ───────────────────────────────────────────────────────

    _handleEnter(el) {
        this._closeMention();
        const nextType = NEXT_TYPE_MAP[el.dataset.type] ?? 'ACTION';
        const nb = this._createBlock(nextType, '');
        el.parentElement.after(nb);
        const newBlock = nb.querySelector('.block');
        newBlock.focus();
        this._updateTypePill(nextType);
        this.markDirty();
        this.scheduleDebounce();
        // Repaginer après ajout de bloc
        clearTimeout(this._pageBreakTimer);
        this._pageBreakTimer = setTimeout(() => this._paginateBlocks(), 150);
    }

    _handleTab(el, backwards = false) {
        const idx    = BLOCK_TYPES.indexOf(el.dataset.type);
        const newIdx = backwards
            ? (idx - 1 + BLOCK_TYPES.length) % BLOCK_TYPES.length
            : (idx + 1) % BLOCK_TYPES.length;
        this._setBlockType(el, BLOCK_TYPES[newIdx]);
        this._updateTypePill(el.dataset.type);
        this.markDirty();
        this.scheduleDebounce();
    }

    _handleBackspaceEmpty(el) {
        this._closeMention();
        const all = this._allBlocks();
        if (all.length <= 1) { el.textContent = ''; return; }
        const idx  = all.indexOf(el);
        const prev = all[idx - 1] ?? null;
        el.parentElement.remove();
        if (prev) { prev.focus(); this._moveCursorToEnd(prev); this._updateTypePill(prev.dataset.type); }
        this.markDirty();
        this.scheduleDebounce();
        // Repaginer après suppression de bloc
        clearTimeout(this._pageBreakTimer);
        this._pageBreakTimer = setTimeout(() => this._paginateBlocks(), 150);
    }

    _tryAutoDetect(el) {
        const text = el.textContent;
        for (const { pattern, type } of AUTO_DETECT_RULES) {
            if (pattern.test(text)) { this._setBlockType(el, type); return; }
        }
    }

    // ── Système de mentions ──────────────────────────────────────────────────

    _buildMentionDropdown() {
        this._mentionDropdown = document.createElement('div');
        this._mentionDropdown.className = 'mention-dropdown';
        this._mentionDropdown.setAttribute('role', 'listbox');
        this._mentionDropdown.setAttribute('aria-label', 'Suggestions');
        this._mentionDropdown.style.display = 'none';
        document.body.appendChild(this._mentionDropdown);
    }

    _checkMentionTrigger(el) {
        const caretPos   = this._getCaretOffset(el);
        const textBefore = el.textContent.slice(0, caretPos);
        const match      = textBefore.match(/(?:^|(?<=\s))([@#])([^\s@#]*)$/);

        if (!match) { this._closeMention(); return; }

        const trigger = match[1];
        const query   = match[2];
        const items   = this._getMentionItems(trigger, query);

        if (items.length === 0) { this._closeMention(); return; }

        this._mentionActive   = true;
        this._mentionTrigger  = trigger;
        this._mentionQuery    = query;
        this._mentionBlock    = el;

        this._renderMentionDropdown(trigger, items, el);
    }

    _getMentionItems(trigger, query) {
        const pool = trigger === '@' ? this.charactersValue : this.locationsValue;
        if (!query) return pool.slice(0, 10);
        const q = query.toLowerCase();
        return pool.filter(item => item.name.toLowerCase().includes(q)).slice(0, 10);
    }

    _renderMentionDropdown(trigger, items, blockEl) {
        const cfg = MENTION_TRIGGERS[trigger];
        const dd  = this._mentionDropdown;

        const headerHtml = `
            <div class="mention-header">
                <span class="mention-header-label">${this._mentionLabel(trigger)}</span>
                <span class="mention-header-hint">${this._mentionHint()}</span>
            </div>`;

        const itemsHtml = items.map((item, i) => `
            <div class="mention-item ${i === 0 ? 'mention-item--focused' : ''}"
                 data-idx="${i}" data-name="${this._escAttr(item.name)}" data-trigger="${trigger}"
                 role="option" aria-selected="${i === 0}">
                <span class="mention-item-trigger ${cfg.cssClass}">${trigger}</span>
                <span class="mention-item-name">${this._esc(item.name)}</span>
            </div>`).join('');

        dd.innerHTML = headerHtml + itemsHtml;
        this._mentionFocusIdx = 0;

        dd.querySelectorAll('.mention-item').forEach(item => {
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                this._insertMention(item.dataset.trigger, item.dataset.name);
            });
        });

        this._positionMentionDropdown(blockEl);
        dd.style.display = 'block';
    }

    _positionMentionDropdown(blockEl) {
        const dd   = this._mentionDropdown;
        const rect = blockEl.getBoundingClientRect();
        const ddWidth = 260;
        dd.style.position = 'fixed';
        dd.style.top      = `${rect.bottom + 6}px`;
        dd.style.left     = `${Math.max(8, rect.left)}px`;
        dd.style.width    = `${ddWidth}px`;
    }

    _mentionNavigate(direction) {
        const items = this._mentionDropdown.querySelectorAll('.mention-item');
        if (!items.length) return;
        items[this._mentionFocusIdx]?.classList.remove('mention-item--focused');
        items[this._mentionFocusIdx]?.setAttribute('aria-selected', 'false');
        this._mentionFocusIdx = (this._mentionFocusIdx + direction + items.length) % items.length;
        const focused = items[this._mentionFocusIdx];
        focused?.classList.add('mention-item--focused');
        focused?.setAttribute('aria-selected', 'true');
        focused?.scrollIntoView({ block: 'nearest' });
    }

    _mentionConfirm() {
        const focused = this._mentionDropdown.querySelector('.mention-item--focused');
        if (!focused) return false;
        this._insertMention(focused.dataset.trigger, focused.dataset.name);
        return true;
    }

    _insertMention(trigger, name) {
        const el = this._mentionBlock;
        if (!el) return;
        const caretPos     = this._getCaretOffset(el);
        const textBefore   = el.textContent.slice(0, caretPos);
        const triggerMatch = textBefore.match(/(?:^|(?<=\s))([@#])([^\s@#]*)$/);
        if (!triggerMatch) return;
        const triggerStart = caretPos - triggerMatch[0].length;
        const before  = el.textContent.slice(0, triggerStart);
        const after   = el.textContent.slice(caretPos);
        const mention = trigger + name;
        el.textContent = before + mention + ' ' + after;
        this._setCaretOffset(el, triggerStart + mention.length + 1);
        this._closeMention();
        this.markDirty();
        this.scheduleDebounce();
        this._updateDetectedChars();
    }

    _closeMention() {
        this._mentionActive  = false;
        this._mentionTrigger = null;
        this._mentionQuery   = '';
        this._mentionBlock   = null;
        if (this._mentionDropdown) this._mentionDropdown.style.display = 'none';
    }

    // ── Pagination multi-pages ───────────────────────────────────────────────

    /**
     * Distribue les blocs sur des pages de hauteur fixe (1100px).
     * Chaque .editor-page contient un .editor-paper avec les blocs qui y tiennent.
     *
     * Appelé à l'init et après chaque modification (debounced 400ms).
     * Ne recrée pas les blocs — déplace seulement les DOM nodes entre pages.
     */
    _paginateBlocks() {
        const stage = this.stageTarget;

        // Mémoriser le bloc focusé pour le retrouver après le DOM reflow
        const focused   = document.activeElement;
        const hadFocus  = focused?.classList.contains('block');

        // 1. Collecter TOUS les block-wrappers de TOUTES les pages (dans l'ordre)
        const allWrappers = [...stage.querySelectorAll('.editor-paper .block-wrapper')];
        if (allWrappers.length === 0) return;

        // 2. Mesurer les hauteurs MAINTENANT (blocs encore dans le DOM)
        const heights = allWrappers.map(w => Math.max(w.getBoundingClientRect().height, 30));

        // 3. Calculer la distribution : quels blocs vont sur quelle page
        const groups = [[]]; // groups[pageIdx] = [wrapperIdx, ...]
        let acc = 0;

        heights.forEach((h, i) => {
            // Nouveau bloc : trop grand pour la page courante → nouvelle page
            if (acc + h > PAGE_CONTENT_HEIGHT && acc > 0) {
                groups.push([]);
                acc = 0;
            }
            groups[groups.length - 1].push(i);
            acc += h;
        });

        // 4. Synchroniser le nombre de pages dans le DOM
        let pages = [...stage.querySelectorAll('.editor-page')];

        // Créer les pages manquantes
        while (pages.length < groups.length) {
            const page = this._createPageEl(pages.length + 1);
            stage.appendChild(page);
            pages.push(page);
        }

        // Supprimer les pages en trop (de la fin)
        while (pages.length > groups.length) {
            pages[pages.length - 1].remove();
            pages.pop();
        }

        // 5. Déplacer les blocs dans la bonne page si nécessaire
        groups.forEach((wrapperIndices, pageIdx) => {
            const paper = pages[pageIdx].querySelector('.editor-paper');
            wrapperIndices.forEach(wi => {
                const w = allWrappers[wi];
                if (w.parentElement !== paper) {
                    paper.appendChild(w); // déplacement DOM sans perte d'events
                }
            });
        });

        // 6. Mettre à jour les numéros de page
        pages.forEach((page, i) => {
            const num = page.querySelector('.editor-page-num');
            if (num) num.textContent = `Page ${i + 1}`;
        });

        // 7. Restaurer le focus (le déplacement DOM peut le perdre)
        if (hadFocus && focused && document.body.contains(focused)) {
            focused.focus();
        }
    }

    /** Crée un élément de page (.editor-page > .editor-paper + .editor-page-num) */
    _createPageEl(num) {
        const page = document.createElement('div');
        page.className = 'editor-page';

        const paper = document.createElement('div');
        paper.className = 'editor-paper';

        const numEl = document.createElement('div');
        numEl.className   = 'editor-page-num';
        numEl.textContent = `Page ${num}`;

        page.appendChild(paper);
        page.appendChild(numEl);
        return page;
    }

    // ── Actions publiques ─────────────────────────────────────────────────────

    saveManual() {
        clearTimeout(this.debounceTimer);
        this._doSave();
    }

    toggleReading() {
        this._closeMention();
        this.isReading = !this.isReading;

        const btn   = this.element.querySelector('#btn-reading-mode');
        const label = this.element.querySelector('#reading-mode-label');
        const i18n  = this.i18nValue ?? {};

        if (this.isReading) {
            // Mode lecture : désactiver l'édition sur tous les blocs
            this._allBlocks().forEach(el => { el.contentEditable = 'false'; });
            if (label) label.textContent = i18n.mode_writing ?? 'Écriture';
            btn?.classList.add('topbar-btn--active');
            this.element.classList.add('reading-mode');
        } else {
            // Mode écriture : réactiver l'édition
            this._allBlocks().forEach(el => { el.contentEditable = 'true'; });
            if (label) label.textContent = i18n.mode_reading ?? 'Lecture';
            btn?.classList.remove('topbar-btn--active');
            this.element.classList.remove('reading-mode');
            // Remettre le focus sur le premier bloc
            requestAnimationFrame(() => {
                const first = this.stageTarget.querySelector('.block');
                if (first) first.focus();
            });
        }
    }

    setBlockTypeTool({ params: { type, prefix } }) {
        const focused = this.stageTarget.querySelector('.block:focus');
        if (focused) {
            this._setBlockType(focused, type);
            if (prefix && !focused.textContent.trim()) {
                focused.textContent = prefix;
                this._moveCursorToEnd(focused);
            }
            this._updateTypePill(type);
            focused.focus();
        } else {
            const nb = this._createBlock(type, prefix ?? '');
            const last = this.stageTarget.lastElementChild;
            last ? last.after(nb) : this.stageTarget.appendChild(nb);
            const newBlock = nb.querySelector('.block');
            newBlock.focus();
            this._moveCursorToEnd(newBlock);
            this._updateTypePill(type);
        }
        this.markDirty();
        this.scheduleDebounce();
    }

    exportFountain() {
        const blocks = this._collectBlocks();
        const title  = this.elementTitleValue || 'Script';
        let out = `Title: ${title}\nDraft date: ${new Date().toLocaleDateString('fr-BE')}\n\n===\n\n`;
        for (const b of blocks) {
            const c = b.content.trim();
            if (!c) continue;
            switch (b.type) {
                case 'SCENE':         out += c.toUpperCase() + '\n\n'; break;
                case 'CHARACTER':     out += c.toUpperCase() + '\n'; break;
                case 'DIALOGUE':      out += c + '\n\n'; break;
                case 'PARENTHETICAL': out += (/^\(.*\)$/.test(c) ? c : `(${c})`) + '\n'; break;
                case 'TRANSITION':    out += c.toUpperCase() + '\n\n'; break;
                default:              out += c + '\n\n';
            }
        }
        const blob = new Blob([out.trimEnd() + '\n'], { type: 'text/plain;charset=utf-8' });
        const url  = URL.createObjectURL(blob);
        const a    = Object.assign(document.createElement('a'), {
            href: url, download: title.replace(/[^a-z0-9\-_]/gi, '_') + '.fountain',
        });
        document.body.appendChild(a); a.click();
        document.body.removeChild(a); URL.revokeObjectURL(url);
    }

    exportPDF() {
        const title = this.elementTitleValue || 'Script';

        // Récupérer les blocs par page (ordre exact = fidèle à l'éditeur)
        const pages = [...this.stageTarget.querySelectorAll('.editor-page')];
        let html = '';

        pages.forEach((page, pageIdx) => {
            if (pageIdx > 0) html += `<div style="page-break-before:always"></div>`;
            const blocksOnPage = [...page.querySelectorAll('.block-wrapper .block')];
            for (const el of blocksOnPage) {
                const b = { type: el.dataset.type ?? 'ACTION', content: el.textContent.trim() };
                const c = b.content;
                if (!c) { html += '<div class="fp-blank"></div>'; continue; }
                const cssMap = {
                    SCENE:'fp-slug', CHARACTER:'fp-char', DIALOGUE:'fp-diag',
                    PARENTHETICAL:'fp-paren', TRANSITION:'fp-transition', ACTION:'fp-action',
                };
                html += `<div class="${cssMap[b.type] ?? 'fp-action'}">${this._esc(c)}</div>`;
            }
        });

        const win = window.open('', '_blank');
        if (!win) { alert('Autorisez les popups pour exporter en PDF.'); return; }
        win.document.write(`<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>${this._esc(title)}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Courier Prime','Courier New',monospace;font-size:12pt;line-height:1.6;color:#000;background:#fff;padding:1.2in 1.5in 1in}
@page{margin:1in}
h1.pdf-title{font-size:18pt;text-align:center;margin-bottom:3em;text-transform:uppercase}
.fp-slug{text-transform:uppercase;font-weight:700;margin:1.5em 0 .5em}
.fp-char{text-transform:uppercase;margin:1em 0 0;margin-left:40%}
.fp-diag{margin:.1em 0 .8em;margin-left:25%;margin-right:15%}
.fp-paren{margin-left:30%;margin-right:20%;font-style:italic}
.fp-transition{text-transform:uppercase;text-align:right;margin:1em 0}
.fp-action{margin:.75em 0}
.fp-blank{height:.6em}
</style></head>
<body>
<h1 class="pdf-title">${this._esc(title)}</h1>
${html}
</body></html>`);
        win.document.close();
        setTimeout(() => { win.focus(); win.print(); }, 400);
    }

    saveParams() {
        // Paramètres gérés côté projet — no-op ici
    }

    // ── Collecte / Sauvegarde ────────────────────────────────────────────────

    _collectBlocks() {
        return this._allBlocks().map(el => ({
            type:    el.dataset.type ?? 'ACTION',
            content: el.textContent.trim(),
        }));
    }

    _allBlocks() {
        return [...this.stageTarget.querySelectorAll('.block-wrapper .block')];
    }

    markDirty() {
        this.isDirty = true;
        this._setIndicatorState('dirty');
    }

    scheduleDebounce() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            if (this.isDirty) this._doSave();
        }, this.debounceDelayValue);
    }

    async _doSave() {
        if (this.isSaving) return;
        this.isSaving = true;
        this._setIndicatorState('saving');
        const blocks = this._collectBlocks();
        // Le résumé est géré indépendamment via le panneau Paramètres (PATCH)
        // → on ne l'écrase jamais ici avec le contenu du script
        try {
            const res  = await fetch(this.saveUrlValue, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content: blocks }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.isDirty = false; this.lastSavedAt = new Date();
                this._setIndicatorState('saved');
            } else { this._setIndicatorState('error'); }
        } catch { this._setIndicatorState('error'); }
        finally  { this.isSaving = false; }
    }

    _setIndicatorState(state) {
        if (!this.hasSaveIndicatorTarget) return;
        const el   = this.saveIndicatorTarget;
        const i18n = this.i18nValue ?? {};
        el.className   = `save-indicator save-indicator--${state}`;
        el.textContent = {
            dirty:  i18n.save_dirty  ?? '● Non sauvegardé',
            saving: i18n.save_saving ?? '↻ Sauvegarde…',
            saved:  i18n.save_saved  ?? '✓ Sauvegardé',
            error:  i18n.save_error  ?? '⚠ Erreur',
        }[state] ?? '';
        if (this.hasSaveButtonTarget) this.saveButtonTarget.disabled = (state === 'saving');
        if (state === 'saved' && this.lastSavedAt) {
            const t = this.lastSavedAt.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
            if (this.hasLastSavedTarget)     this.lastSavedTarget.textContent     = (i18n.save_at ?? 'Sauvegardé à %time%').replace('%time%', t);
            if (this.hasLastSavedStatTarget) this.lastSavedStatTarget.textContent = t;
        }
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    updateStats() {
        const text  = this._allBlocks().map(el => el.textContent).join(' ');
        const words = text.trim() ? text.trim().split(/\s+/).length : 0;
        const chars = text.replace(/\s/g, '').length;
        const i18n  = this.i18nValue ?? {};

        if (this.hasWordCountTarget)     this.wordCountTarget.textContent     = `${words} ${words <= 1 ? (i18n.word_one ?? 'mot') : (i18n.word_other ?? 'mots')}`;
        if (this.hasCharCountTarget)     this.charCountTarget.textContent     = `${chars} ${chars <= 1 ? (i18n.char_one ?? 'caractère') : (i18n.char_other ?? 'caractères')}`;
        if (this.hasWordCountStatTarget) this.wordCountStatTarget.textContent = words.toLocaleString();
        if (this.hasCharCountStatTarget) this.charCountStatTarget.textContent = chars.toLocaleString();

        const wpp    = Math.max(1, this.wordsPerPageValue);
        const wpm    = Math.max(1, this.readingWpmValue);
        const stp    = Math.max(1, this.screenTimePerPageValue);
        const pages  = words / wpp;
        const scrSec = pages * stp;
        const rdSec  = (words / wpm) * 60;

        if (this.hasEstimatedPagesTarget)       this.estimatedPagesTarget.textContent       = words > 0 ? pages.toFixed(1) : '—';
        if (this.hasEstimatedScreenTimeTarget)  this.estimatedScreenTimeTarget.textContent  = words > 0 ? this._formatDuration(scrSec) : '—';
        if (this.hasEstimatedReadTimeTarget)    this.estimatedReadTimeTarget.textContent     = words > 0 ? this._formatDuration(rdSec) : '—';
    }

    _updateDetectedChars() {
        if (!this.hasDetectedCharsTarget) return;
        const names = new Set(
            this._allBlocks()
                .filter(el => el.dataset.type === 'CHARACTER' && el.textContent.trim().length > 1)
                .map(el => el.textContent.trim())
        );
        const c = this.detectedCharsTarget;
        c.innerHTML = names.size > 0
            ? [...names].map(n => `<span class="edetect-char-chip">${this._esc(n)}</span>`).join('')
            : `<p class="edetect-empty">${this.i18nValue?.detect_hint ?? 'Utilisez des blocs Personnage pour détecter les noms.'}</p>`;
    }

    _updateTypePill(type) {
        if (!this.hasTypePillTarget) return;
        const t = type ?? 'ACTION';
        this.typePillTarget.textContent  = TYPE_LABELS[t] ?? t;
        this.typePillTarget.dataset.type = t;
    }

    // ── Rendu lecture ────────────────────────────────────────────────────────

    _renderPreview(blocks) {
        return blocks.map(b => {
            if (!b.content.trim()) return '<div class="fp-blank"></div>';
            const content = this._highlightMentions(this._esc(b.content));
            return `<div class="${PREVIEW_CSS[b.type] ?? 'fp-action'}">${content}</div>`;
        }).join('\n');
    }

    _highlightMentions(escapedHtml) {
        return escapedHtml
            .replace(/@([\wÀ-ɏ'-]+(?:\s[\wÀ-ɏ'-]+)*)/g,
                '<span class="fp-mention fp-mention--char">@$1</span>')
            .replace(/#([\wÀ-ɏ'-]+(?:\s[\wÀ-ɏ'-]+)*)/g,
                '<span class="fp-mention fp-mention--loc">#$1</span>');
    }

    // ── Annotation de blocs ──────────────────────────────────────────────────

    _openBlockNote() {
        if (!this.noteNewUrlValue) return;
        this.element.dispatchEvent(new CustomEvent('modal:open-url', {
            bubbles: true, composed: true,
            detail: { url: this.noteNewUrlValue },
        }));
    }

    // ── Utilitaires curseur ───────────────────────────────────────────────────

    _focusPrev(el) {
        const all = this._allBlocks(); const i = all.indexOf(el);
        if (i > 0) { all[i - 1].focus(); this._moveCursorToEnd(all[i - 1]); }
    }

    _focusNext(el) {
        const all = this._allBlocks(); const i = all.indexOf(el);
        if (i < all.length - 1) all[i + 1].focus();
    }

    _moveCursorToEnd(el) {
        el.focus();
        const range = document.createRange(); const sel = window.getSelection();
        range.selectNodeContents(el); range.collapse(false);
        sel?.removeAllRanges(); sel?.addRange(range);
    }

    _getCaretOffset(el) {
        const sel = window.getSelection();
        if (!sel?.rangeCount) return 0;
        const r = sel.getRangeAt(0).cloneRange();
        r.selectNodeContents(el);
        r.setEnd(sel.getRangeAt(0).startContainer, sel.getRangeAt(0).startOffset);
        return r.toString().length;
    }

    _setCaretOffset(el, offset) {
        if (!el.firstChild) { this._moveCursorToEnd(el); return; }
        const range = document.createRange(); const sel = window.getSelection();
        const node  = el.firstChild;
        const pos   = Math.min(offset, node.textContent?.length ?? 0);
        try {
            range.setStart(node, pos); range.collapse(true);
            sel?.removeAllRanges(); sel?.addRange(range);
        } catch { this._moveCursorToEnd(el); }
    }

    _isAtStart(el) { return this._getCaretOffset(el) === 0; }
    _isAtEnd(el)   { return this._getCaretOffset(el) >= el.textContent.length; }

    // ── Helpers ───────────────────────────────────────────────────────────────

    _formatDuration(seconds) {
        const mins = Math.round(seconds / 60);
        if (mins < 1)  return '< 1 min';
        if (mins < 60) return `~${mins} min`;
        const h = Math.floor(mins / 60), m = mins % 60;
        return `~${h}h${m > 0 ? ` ${m}min` : ''}`;
    }

    _esc(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>').replace(/_(.+?)_/g, '<em>$1</em>');
    }

    _escAttr(s) {
        return s.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
}
