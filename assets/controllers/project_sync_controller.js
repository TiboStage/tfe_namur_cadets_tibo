// assets/controllers/project_sync_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["view", "indicator"];
    static values = { projectId: Number };

    connect() {
        this.dataBuffer = {}; // Stocke les modifs temporaires
        this.isDirty = false;

        // Timer de sauvegarde (5 min = 300000 ms)
        this.autoSaveTimer = setInterval(() => this.syncWithServer(), 300000);

        // Sécurité anti-quitter
        window.onbeforeunload = (e) => {
            if (this.isDirty) {
                e.preventDefault();
                return "Vous avez des modifications non enregistrées !";
            }
        };
    }

    // Changer de vue sans recharger
    switchView(e) {
        const viewName = e.currentTarget.dataset.view; // ex: 'characters'
        this.viewTargets.forEach(el => {
            el.classList.toggle('hidden', el.id !== `view-${viewName}`);
        });
    }

    // Capturer une modif (depuis n'importe quel champ)
    updateData(field, value) {
        this.dataBuffer[field] = value;
        this.isDirty = true;
        this.indicatorTarget.textContent = "● Changements en attente...";
    }

    async syncWithServer() {
        if (!this.isDirty) return;

        try {
            const response = await fetch(`/api/projects/${this.projectIdValue}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/merge-patch+json' },
                body: JSON.stringify(this.dataBuffer)
            });

            if (response.ok) {
                this.isDirty = false;
                this.dataBuffer = {};
                this.indicatorTarget.textContent = "✓ Synchronisé";
            }
        } catch (error) {
            console.error("Erreur de synchro", error);
        }
    }
}
