import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus — édition inline du profil créateur.
 *
 * Usage HTML :
 *   <div data-controller="profile-editor"
 *        data-profile-editor-update-url-value="/atelier/profil/modifier"
 *        data-profile-editor-csrf-value="CSRF_TOKEN">
 *
 *     <!-- Mode vue -->
 *     <div data-profile-editor-target="view"> ... </div>
 *
 *     <!-- Mode édition -->
 *     <div data-profile-editor-target="editForm" hidden> ... </div>
 *   </div>
 */
export default class extends Controller {

    static targets = [
        'view',         // Section affichage (nom, bio)
        'editForm',     // Section formulaire d'édition
        'bio',          // <textarea> bio
        'firstName',    // <input> prénom
        'lastName',     // <input> nom
        'bioDisplay',   // <p> affichage bio (mode vue)
        'nameDisplay',  // <p> affichage nom complet (mode vue)
        'saveBtn',      // Bouton Sauvegarder
    ];

    static values = {
        updateUrl: String,
        csrf:      String,
    };

    // ── Passer en mode édition ────────────────────────────────────────────
    edit() {
        this.viewTarget.hidden = true;
        this.editFormTarget.hidden = false;
        this.bioTarget.focus();
    }

    // ── Annuler et revenir en mode vue ────────────────────────────────────
    cancel() {
        this.editFormTarget.hidden = true;
        this.viewTarget.hidden = false;
    }

    // ── Sauvegarder via fetch ─────────────────────────────────────────────
    async save(event) {
        event.preventDefault();

        const btn = this.saveBtnTarget;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = 'Sauvegarde…';

        const payload = {
            bio:       this.bioTarget.value.trim(),
            firstName: this.firstNameTarget.value.trim(),
            lastName:  this.lastNameTarget.value.trim(),
        };

        try {
            const resp = await fetch(this.updateUrlValue, {
                method:  'POST',
                headers: {
                    'Content-Type':   'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token':   this.csrfValue,
                },
                body: JSON.stringify(payload),
            });

            if (!resp.ok) {
                throw new Error(`HTTP ${resp.status}`);
            }

            const data = await resp.json();

            if (data.success) {
                // Mettre à jour l'affichage sans rechargement
                if (this.hasBioDisplayTarget) {
                    this.bioDisplayTarget.textContent = data.bio || '';
                    this.bioDisplayTarget.classList.toggle(
                        'creator-profile__bio--empty',
                        !data.bio
                    );
                }
                if (this.hasNameDisplayTarget) {
                    this.nameDisplayTarget.textContent = data.fullName;
                }
                this.cancel();
            } else {
                this._showError('Erreur lors de la sauvegarde.');
            }

        } catch (err) {
            console.error('[profile-editor] Erreur:', err);
            this._showError('Impossible de sauvegarder. Réessayez.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    // ── Afficher un message d'erreur temporaire ───────────────────────────
    _showError(msg) {
        const existing = this.editFormTarget.querySelector('.profile-editor-error');
        if (existing) existing.remove();

        const el = document.createElement('p');
        el.className = 'profile-editor-error';
        el.textContent = msg;
        this.editFormTarget.insertBefore(el, this.editFormTarget.firstChild);

        setTimeout(() => el.remove(), 4000);
    }
}
