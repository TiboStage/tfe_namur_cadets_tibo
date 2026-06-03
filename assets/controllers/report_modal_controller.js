import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus — modal de signalement.
 *
 * Usage :
 *   Placer data-controller="report-modal" sur un conteneur parent
 *   (ex. .pub-project ou le layout).
 *
 * Bouton déclencheur :
 *   <button
 *     data-action="click->report-modal#open"
 *     data-report-modal-url-param="{{ path('app_report', {_locale:..., type:'project', id:X}) }}"
 *     data-report-modal-token-param="{{ csrf_token('report_project_X') }}"
 *     data-report-modal-label-param="ce projet"
 *   >Signaler</button>
 *
 * Cibles attendues dans le DOM :
 *   overlay, panel, form, tokenInput, reasonInputs,
 *   descriptionGroup, description, feedback, labelText
 */
export default class extends Controller {

    static targets = [
        'overlay',          // .report-modal-overlay (fond semi-transparent)
        'panel',            // .report-modal__panel (boîte blanche)
        'form',             // <form>
        'tokenInput',       // <input name="_token" hidden>
        'descriptionGroup', // Groupe textarea (caché sauf raison "other")
        'description',      // <textarea name="description">
        'feedback',         // Zone de retour succès / erreur
        'labelText',        // Span affichant "Signaler {label}"
    ];

    connect() {
        // Fermer avec Escape
        this._onKeydown = (e) => { if (e.key === 'Escape') this.close(); };
        document.addEventListener('keydown', this._onKeydown);
    }

    disconnect() {
        document.removeEventListener('keydown', this._onKeydown);
    }

    // ── Ouvrir ───────────────────────────────────────────────────────────────

    open(event) {
        const { url, token, label } = event.params;

        // Configurer le formulaire
        this.formTarget.action = url;
        this.tokenInputTarget.value = token;

        // Mise à jour du libellé contextuel
        if (this.hasLabelTextTarget && label) {
            this.labelTextTarget.textContent = label;
        }

        // Réinitialiser
        this.formTarget.querySelectorAll('input[type="radio"]').forEach(r => r.checked = false);
        this.descriptionTarget.value = '';
        this.descriptionGroupTarget.hidden = true;
        this.feedbackTarget.className = 'report-modal__feedback';
        this.feedbackTarget.textContent = '';

        // Afficher
        this.overlayTarget.hidden = false;
        this.overlayTarget.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    // ── Fermer ────────────────────────────────────────────────────────────────

    close() {
        this.overlayTarget.hidden = true;
        this.overlayTarget.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    closeOnBackdrop(event) {
        if (event.target === this.overlayTarget) {
            this.close();
        }
    }

    // ── Sélection de la raison ────────────────────────────────────────────────

    onReasonChange(event) {
        const isOther = event.target.value === 'other';
        this.descriptionGroupTarget.hidden = !isOther;
        if (!isOther) {
            this.descriptionTarget.value = '';
        }
    }

    // ── Soumission async ──────────────────────────────────────────────────────

    async submit(event) {
        event.preventDefault();

        // Validation côté client
        const checked = this.formTarget.querySelector('input[type="radio"]:checked');
        if (!checked) {
            this._setFeedback('Veuillez choisir une raison.', 'error');
            return;
        }

        const submitBtn = this.formTarget.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const resp = await fetch(this.formTarget.action, {
                method: 'POST',
                body: new FormData(this.formTarget),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const json = await resp.json();

            if (resp.ok && json.success) {
                this._setFeedback(json.message, 'success');
                setTimeout(() => this.close(), 2200);
            } else {
                this._setFeedback(json.error ?? 'Une erreur est survenue.', 'error');
                if (submitBtn) submitBtn.disabled = false;
            }
        } catch {
            this._setFeedback('Erreur réseau. Veuillez réessayer.', 'error');
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    // ── Privé ─────────────────────────────────────────────────────────────────

    _setFeedback(message, type) {
        this.feedbackTarget.textContent = message;
        this.feedbackTarget.className = `report-modal__feedback report-modal__feedback--${type}`;
    }
}
