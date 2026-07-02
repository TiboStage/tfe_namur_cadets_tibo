import { Controller } from '@hotwired/stimulus';

// Certains scripts ont été enregistrés avec d'anciens codes de type (courts
// et/ou en minuscules : "slug", "char", "diag"…). On les normalise vers les
// codes longs utilisés par fountain_editor_controller.js, pour que l'export
// applique la bonne mise en forme quelle que soit l'ancienneté du script.
const LEGACY_TYPE_MAP = {
    SLUG: 'SCENE', SCENE: 'SCENE',
    ACTION: 'ACTION',
    CHAR: 'CHARACTER', CHARACTER: 'CHARACTER',
    DIAG: 'DIALOGUE', DIALOGUE: 'DIALOGUE',
    PAREN: 'PARENTHETICAL', PARENTHETICAL: 'PARENTHETICAL',
    TRANS: 'TRANSITION', TRANSITION: 'TRANSITION',
};

function normalizeBlockType(type) {
    return LEGACY_TYPE_MAP[(type ?? 'ACTION').toUpperCase()] ?? 'ACTION';
}

// ── Helpers Fountain ─────────────────────────────────────────────────────────

function blockToFountain(b) {
    const c = (b.content ?? '').trim();
    if (!c) return '';
    switch (normalizeBlockType(b.type)) {
        case 'SCENE':         return c.toUpperCase() + '\n\n';
        case 'CHARACTER':     return c.toUpperCase() + '\n';
        case 'DIALOGUE':      return c + '\n\n';
        case 'PARENTHETICAL': return (/^\(.*\)$/.test(c) ? c : `(${c})`) + '\n';
        case 'TRANSITION':    return c.toUpperCase() + '\n\n';
        default:              return c + '\n\n';
    }
}

function scriptToFountain(script) {
    let out = `# ${script.title}\n\n`;
    for (const b of (script.content ?? [])) {
        out += blockToFountain(b);
    }
    return out.trimEnd() + '\n';
}

// ── Helpers PDF ──────────────────────────────────────────────────────────────
// Classes fp-* et PDF_STYLESHEET identiques à fountain_editor_controller.js
// — garantit un rendu strictement identique entre l'export d'un seul script
// et l'export manuscrit multi-scripts.
//
// Contrairement à l'export d'un script seul, on n'a pas ici la pagination
// déjà calculée par l'éditeur (les autres scripts ne sont pas ouverts) : le
// contenu de chaque script s'écoule donc naturellement sur autant de pages
// que nécessaire, avec les mêmes règles anti-orphelin (voir PDF_STYLESHEET).

const PDF_CSS_CLASS = {
    SCENE: 'fp-slug', CHARACTER: 'fp-char', DIALOGUE: 'fp-diag',
    PARENTHETICAL: 'fp-paren', TRANSITION: 'fp-transition', ACTION: 'fp-action',
};

function blockToHtml(b) {
    const c = (b.content ?? '').trim();
    if (!c) return '<div class="fp-blank"></div>';
    const type = normalizeBlockType(b.type);
    const cls  = PDF_CSS_CLASS[type] ?? 'fp-action';
    let text   = c.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    if (type === 'PARENTHETICAL' && !/^\(.*\)$/.test(c)) text = `(${text})`;
    return `<div class="${cls}">${text}</div>`;
}

function scriptToHtml(script, isFirst) {
    const title = script.title.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const style = isFirst ? '' : ' style="page-break-before:always"';
    let html = `<div class="fp-title-page"${style}><h1 class="pdf-title">${title}</h1></div>`;
    let body = '';
    for (const b of (script.content ?? [])) {
        body += blockToHtml(b);
    }
    html += `<div class="fp-page">${body}</div>`;
    return html;
}

/**
 * Feuille de style de l'export PDF — copie exacte de
 * fountain_editor_controller.js#PDF_STYLESHEET (voir ce fichier pour le
 * détail du calibrage : dimensions et marges reprennent .editor-paper).
 */
function PDF_STYLESHEET(fontRegularUrl, fontBoldUrl) {
    return `
@font-face{font-family:'Courier Prime';src:url('${fontRegularUrl}');font-weight:400;font-style:normal}
@font-face{font-family:'Courier Prime';src:url('${fontBoldUrl}');font-weight:700;font-style:normal}
*{box-sizing:border-box;margin:0;padding:0}
@page{size:700px 1100px;margin:64px 72px 64px 80px}
body{font-family:'Courier Prime','Courier New',monospace;color:#000;background:#fff}
.fp-title-page{display:flex;align-items:center;justify-content:center;min-height:972px;page-break-after:always;break-after:page}
h1.pdf-title{font-size:24px;text-align:center;text-transform:uppercase}
.fp-page:first-of-type .fp-slug:first-child{margin-top:0}
.fp-slug{font-size:12px;line-height:1.8;text-transform:uppercase;font-weight:700;margin-top:32px;padding:3px 0 3px 10px;page-break-after:avoid;break-after:avoid-page}
.fp-char{font-size:13px;line-height:1.8;text-transform:uppercase;font-weight:700;text-align:center;margin-top:28px;padding:3px 0;page-break-after:avoid;break-after:avoid-page}
.fp-diag{font-size:14px;line-height:1.8;padding:3px 90px;page-break-inside:avoid;break-inside:avoid}
.fp-paren{font-size:13px;line-height:1.8;font-style:italic;padding:3px 130px;page-break-after:avoid;break-after:avoid-page}
.fp-transition{font-size:12px;line-height:1.8;text-transform:uppercase;font-weight:600;text-align:right;margin-top:24px;padding:3px 10px 3px 0;page-break-after:avoid;break-after:avoid-page}
.fp-action{font-size:14px;line-height:1.8;padding:3px 0 3px 10px;page-break-inside:avoid;break-inside:avoid}
.fp-blank{height:1.6em}`;
}

// ── Controller ───────────────────────────────────────────────────────────────

export default class extends Controller {

    static targets = ['modal', 'list', 'count'];

    static values = {
        scripts:       { type: Array,  default: [] },
        projectTitle:  { type: String, default: '' },
        fontRegularUrl: { type: String, default: '' },
        fontBoldUrl:    { type: String, default: '' },
    };

    connect() {
        // Copie mutable de la liste (pour le réordonnancement)
        this._items = this.scriptsValue.map(s => ({ ...s, checked: true }));
        this._render();

        // Fermer avec Échap
        this._onKeyDown = (e) => { if (e.key === 'Escape') this.closeModal(); };
        document.addEventListener('keydown', this._onKeyDown);
    }

    disconnect() {
        document.removeEventListener('keydown', this._onKeyDown);
    }

    // ── Modal ─────────────────────────────────────────────────────────────────

    togglePanel() { this.openModal(); } // alias pour le bouton header

    openModal() {
        this.modalTarget.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    closeModal() {
        this.modalTarget.hidden = true;
        document.body.style.overflow = '';
    }

    // ── Sélection ─────────────────────────────────────────────────────────────

    selectAll()  { this._items.forEach(i => i.checked = true);  this._render(); }
    selectNone() { this._items.forEach(i => i.checked = false); this._render(); }

    _onCheck(e) {
        const idx = parseInt(e.currentTarget.dataset.idx, 10);
        this._items[idx].checked = e.currentTarget.checked;
        this._updateCount();
    }

    // ── Réordonnancement ──────────────────────────────────────────────────────

    moveUp(e) {
        const idx = parseInt(e.currentTarget.dataset.idx, 10);
        if (idx === 0) return;
        [this._items[idx - 1], this._items[idx]] = [this._items[idx], this._items[idx - 1]];
        this._render();
    }

    moveDown(e) {
        const idx = parseInt(e.currentTarget.dataset.idx, 10);
        if (idx >= this._items.length - 1) return;
        [this._items[idx], this._items[idx + 1]] = [this._items[idx + 1], this._items[idx]];
        this._render();
    }

    // ── Rendu liste ───────────────────────────────────────────────────────────

    _render() {
        const list = this.listTarget;
        list.innerHTML = '';

        this._items.forEach((item, idx) => {
            const row = document.createElement('div');
            row.className = 'msex-item' + (item.checked ? '' : ' msex-item--unchecked');
            row.innerHTML = `
                <label class="msex-item-check">
                    <input type="checkbox"
                           ${item.checked ? 'checked' : ''}
                           data-idx="${idx}"
                           data-action="change->ms-export#_onCheck">
                </label>
                <span class="msex-item-num">${idx + 1}</span>
                <span class="msex-item-title">${this._esc(item.title)}</span>
                <span class="msex-item-words">${this._wordCount(item.content)} mots</span>
                <div class="msex-item-btns">
                    <button type="button" class="ms-order-btn" title="Monter"
                            data-idx="${idx}"
                            data-action="click->ms-export#moveUp"
                            ${idx === 0 ? 'disabled' : ''}>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                    </button>
                    <button type="button" class="ms-order-btn" title="Descendre"
                            data-idx="${idx}"
                            data-action="click->ms-export#moveDown"
                            ${idx === this._items.length - 1 ? 'disabled' : ''}>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                </div>
            `;
            list.appendChild(row);
        });

        this._updateCount();
    }

    _updateCount() {
        const n = this._items.filter(i => i.checked).length;
        if (this.hasCountTarget) {
            this.countTarget.textContent = n > 0
                ? `${n} script${n > 1 ? 's' : ''} sélectionné${n > 1 ? 's' : ''}`
                : 'Aucun script sélectionné';
        }
    }

    // ── Export Fountain ───────────────────────────────────────────────────────

    exportFountain() {
        const selected = this._items.filter(i => i.checked);
        if (!selected.length) { alert('Aucun script sélectionné.'); return; }

        const title = this.projectTitleValue || 'Manuscrit';
        const date  = new Date().toLocaleDateString('fr-BE');

        let out = `Title: ${title}\nDraft date: ${date}\n\n===\n\n`;
        for (const script of selected) {
            out += scriptToFountain(script);
            out += '\n\n';
        }

        this._download(
            out.trimEnd() + '\n',
            title.replace(/[^a-z0-9\-_]/gi, '_') + '.fountain',
            'text/plain;charset=utf-8'
        );
    }

    // ── Export / Impression PDF ───────────────────────────────────────────────

    exportPDF() {
        const selected = this._items.filter(i => i.checked);
        if (!selected.length) { alert('Aucun script sélectionné.'); return; }

        const title = this.projectTitleValue || 'Manuscrit';

        let body = '';
        selected.forEach((script, idx) => {
            body += scriptToHtml(script, idx === 0);
        });

        const _origin = window.location.origin;
        const fontRegularUrl = _origin + this.fontRegularUrlValue;
        const fontBoldUrl    = _origin + this.fontBoldUrlValue;
        const html = `<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><title>${this._esc(title)}</title>
<style>
${PDF_STYLESHEET(fontRegularUrl, fontBoldUrl)}
</style>
</head><body>${body}</body></html>`;

        const win = window.open('', '_blank', 'width=900,height=700');
        if (!win) { alert('Autoriser les popups pour générer le PDF.'); return; }
        win.document.write(html);
        win.document.close();
        this._printWhenFontsReady(win);
    }

    // ── Utilitaires ───────────────────────────────────────────────────────────

    /**
     * Attend que les polices (Courier Prime) soient effectivement chargées
     * avant d'imprimer — sinon le navigateur peut laisser le texte invisible
     * (flash of invisible text) le temps que la police charge, ce qui donne
     * une page imprimée/PDF sans aucun texte.
     */
    _printWhenFontsReady(win) {
        const doPrint = () => { win.focus(); win.print(); };
        if (win.document.fonts?.ready) {
            win.document.fonts.ready.then(doPrint).catch(doPrint);
        } else {
            doPrint();
        }
    }

    _download(content, filename, mime) {
        const blob = new Blob([content], { type: mime });
        const url  = URL.createObjectURL(blob);
        const a    = Object.assign(document.createElement('a'), { href: url, download: filename });
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    _esc(str) {
        return (str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    _wordCount(content) {
        const text = (content ?? []).map(b => b.content ?? '').join(' ').trim();
        return text ? text.split(/\s+/).length : 0;
    }
}
