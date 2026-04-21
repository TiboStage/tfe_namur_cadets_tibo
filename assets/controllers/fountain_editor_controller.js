import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["input", "preview", "indicator", "wordCount", "pageCount", "dialogs", "actions", "slugs"]
    static values = {
        saveUrl: String,
        csrfToken: String
    }

    connect() {
        this.isDirty = false;
        this.saveTimer = null;
        this.updateStats();
    }

    // Gestion de la saisie
    onInput() {
        this.isDirty = true;
        this.setIndicator('unsaved');
        this.updateStats();

        clearTimeout(this.saveTimer);
        this.saveTimer = setTimeout(() => this.save(), 3000);
    }

    // Sauvegarde AJAX
    async save() {
        if (!this.isDirty) return;

        this.setIndicator('saving');

        const payload = {
            content: [{ type: 'raw', content: this.inputTarget.innerText }],
            summary: this.inputTarget.innerText.split('\n').find(l => l.trim().length > 0) || 'Sans titre'
        };

        try {
            const response = await fetch(this.saveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfTokenValue
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (data.success) {
                this.isDirty = false;
                this.setIndicator('saved', data.updated_at);
            } else {
                this.setIndicator('error');
            }
        } catch (e) {
            this.setIndicator('error');
        }
    }

    // Toggle Preview
    togglePreview(e) {
        const isPreview = this.inputTarget.style.display === 'none';
        if (isPreview) {
            this.inputTarget.style.display = 'block';
            this.previewTarget.style.display = 'none';
            e.currentTarget.classList.remove('active');
        } else {
            this.renderPreview();
            this.inputTarget.style.display = 'none';
            this.previewTarget.style.display = 'block';
            e.currentTarget.classList.add('active');
        }
    }

    renderPreview() {
        const lines = this.inputTarget.innerText.split('\n');
        this.previewTarget.innerHTML = lines.map(l => `<div>${l || '&nbsp;'}</div>`).join('');
    }

    updateStats() {
        const text = this.inputTarget.innerText || "";
        const words = text.trim() ? text.trim().split(/\s+/).length : 0;

        if (this.hasWordCountTarget) this.wordCountTarget.textContent = `${words} mots`;
        if (this.hasPageCountTarget) this.pageCountTarget.textContent = `~${Math.max(1, Math.ceil(words / 180))} page(s)`;

        // Stats Fountain basiques
        const slugs = (text.match(/^(INT|EXT|INT\/EXT)\./gim) || []).length;
        if (this.hasSlugsTarget) this.slugsTarget.textContent = slugs;
    }

    setIndicator(status, time = null) {
        const map = {
            saved: { text: '✓ Sauvegardé', color: '#22c55e' },
            unsaved: { text: '● Modifié', color: '#f59e0b' },
            saving: { text: '⏳...', color: '#6b7280' },
            error: { text: '✕ Erreur', color: '#ef4444' }
        };
        const s = map[status];
        this.indicatorTarget.textContent = time ? `${s.text} (${time})` : s.text;
        this.indicatorTarget.style.color = s.color;
    }

    toggleSidebar(e) {
        const side = e.currentTarget.dataset.side;
        document.getElementById(`sidebar-${side}`).classList.toggle('collapsed');
    }
}
