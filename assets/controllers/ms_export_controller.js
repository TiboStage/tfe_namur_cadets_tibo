import { Controller } from '@hotwired/stimulus';

// ── Helpers Fountain ─────────────────────────────────────────────────────────

function blockToFountain(b) {
    const c = (b.content ?? '').trim();
    if (!c) return '';
    switch ((b.type ?? '').toUpperCase()) {
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

const BLOCK_CSS = {
    SCENE:         'font-weight:700;text-transform:uppercase;font-size:13px;margin-top:24px;padding-top:16px;border-top:1px solid #ccc;letter-spacing:.5px;',
    ACTION:        'font-size:13px;margin:6px 0;line-height:1.75;',
    CHARACTER:     'font-weight:700;text-transform:uppercase;font-size:13px;text-align:center;margin-top:16px;padding-left:20%;',
    DIALOGUE:      'font-size:13px;padding:0 15% 0 10%;margin:2px 0;line-height:1.75;',
    PARENTHETICAL: 'font-size:13px;font-style:italic;padding:0 20% 0 14%;margin:2px 0;color:#555;',
    TRANSITION:    'font-weight:700;text-transform:uppercase;font-size:13px;text-align:right;margin-top:16px;',
};

function blockToHtml(b) {
    const c = (b.content ?? '').trim();
    if (!c) return '';
    const type  = (b.type ?? 'ACTION').toUpperCase();
    const style = BLOCK_CSS[type] ?? BLOCK_CSS.ACTION;
    const text  = c.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    let content = text;
    if (type === 'PARENTHETICAL') content = `(${text})`;
    return `<div style="${style}">${content}</div>`;
}

function scriptToHtml(script) {
    let html = `<div style="font-weight:700;font-size:18px;margin:32px 0 20px;border-bottom:2px solid #000;padding-bottom:8px;">${
        script.title.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    }</div>`;
    for (const b of (script.content ?? [])) {
        html += blockToHtml(b);
    }
    return html;
}

// ── Controller ───────────────────────────────────────────────────────────────

export default class extends Controller {

    static targets = ['modal', 'list', 'count'];

    static values = {
        scripts:      { type: Array,  default: [] },
        projectTitle: { type: String, default: '' },
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
        const date  = new Date().toLocaleDateString('fr-BE');

        let body = '';
        selected.forEach((script, idx) => {
            const pageBreak = idx > 0 ? 'page-break-before:always;' : '';
            body += `<div style="${pageBreak}padding:64px 72px 64px 80px;font-family:'Courier Prime','Courier New',monospace;max-width:816px;margin:0 auto;">`;
            body += scriptToHtml(script);
            body += `</div>`;
        });

        const _origin = window.location.origin;
        const html = `<!DOCTYPE html><html><head>
            <meta charset="UTF-8">
            <title>${this._esc(title)}</title>
            <style>
                @font-face{font-family:'Courier Prime';src:url('${_origin}/assets/fonts/display/CourierPrime-Regular.ttf');font-weight:400;font-style:normal}
                @font-face{font-family:'Courier Prime';src:url('${_origin}/assets/fonts/display/CourierPrime-Bold.ttf');font-weight:700;font-style:normal}
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body { background: #fff; color: #000; }
                @media print {
                    @page { margin: 0; size: A4; }
                    body { -webkit-print-color-adjust: exact; }
                }
            </style>
        </head><body>${body}</body></html>`;

        const win = window.open('', '_blank', 'width=900,height=700');
        if (!win) { alert('Autoriser les popups pour générer le PDF.'); return; }
        win.document.write(html);
        win.document.close();
        win.addEventListener('load', () => { win.focus(); win.print(); });
    }

    // ── Utilitaires ───────────────────────────────────────────────────────────

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
