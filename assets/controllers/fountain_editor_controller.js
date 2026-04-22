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

        // Détection des modifications
        this.editorTarget.addEventListener('input', () => {
            this.hasUnsavedChanges = true;
            this.scheduleSave();
        });

        console.log('Fountain editor connected');
    }

    scheduleSave() {
        // Annule le timer précédent
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        // Nouveau timer : sauvegarde dans 2 secondes
        this.saveTimeout = setTimeout(() => {
            this.save();
        }, 2000);
    }

    async save() {
        if (!this.hasUnsavedChanges) return;

        const content = this.editorTarget.innerHTML;

        try {
            const response = await fetch(this.saveUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    content: [
                        { type: 'raw', content: content }
                    ]
                })
            });

            if (response.ok) {
                const data = await response.json();
                this.hasUnsavedChanges = false;
                this.showSaveIndicator('Sauvegardé ' + data.updated_at);
            } else {
                this.showSaveIndicator('Erreur de sauvegarde', true);
            }
        } catch (error) {
            console.error('Save failed:', error);
            this.showSaveIndicator('Erreur réseau', true);
        }
    }

    showSaveIndicator(message, isError = false) {
        // Affiche un petit toast temporaire
        console.log(message);
        // Tu pourras améliorer ça plus tard avec une vraie notification visuelle
    }
}
