import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['editor'];
    static values = {
        saveUrl: String,
        elementId: Number
    };

    connect() {
        this.hasUnsavedChanges = false;
        this.saveTimeout = null;

        this.editorTarget.addEventListener('input', () => {
            this.hasUnsavedChanges = true;
            this.scheduleSave();
        });
    }

    scheduleSave() {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        this.saveTimeout = setTimeout(() => {
            this.save();
        }, 2000);
    }

    async save() {
        if (!this.hasUnsavedChanges) return;

        const content = this.editorTarget.innerHTML;

        // Récupère le token CSRF depuis la meta tag (ajoutée dans base.html.twig)
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        try {
            const response = await fetch(this.saveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                    content: [
                        { type: 'raw', content: content }
                    ]
                })
            });

            if (!response.ok) {
                throw new Error('Erreur réseau : ' + response.status);
            }

            const data = await response.json();

            if (data.success) {
                this.hasUnsavedChanges = false;
                this._showSavedIndicator();
            }
        } catch (error) {
            console.error('Erreur de sauvegarde :', error);
            this._showErrorIndicator();
        }
    }

    _showSavedIndicator() {
        // Optionnel : afficher un indicateur visuel "Sauvegardé"
        const indicator = this.element.querySelector('.save-indicator');
        if (indicator) {
            indicator.textContent = '✓ Sauvegardé';
            indicator.classList.add('saved');
            setTimeout(() => indicator.classList.remove('saved'), 2000);
        }
    }

    _showErrorIndicator() {
        const indicator = this.element.querySelector('.save-indicator');
        if (indicator) {
            indicator.textContent = '⚠ Erreur de sauvegarde';
            indicator.classList.add('error');
        }
    }

    disconnect() {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
    }
}
