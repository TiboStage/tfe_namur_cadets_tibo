import { Controller } from '@hotwired/stimulus';

/**
 * Gestion d'un groupe de champs mot de passe :
 *  - Bascule visibilité (œil)  sur le champ principal ET sur la confirmation
 *  - Indicateur de force (4 règles) : visible dès l'affichage, masqué
 *    automatiquement quand toutes les règles sont respectées
 *  - Vérification de correspondance confirm (uniquement "identique ou non",
 *    les règles ne sont affichées que sous le premier champ)
 *
 * Structure HTML attendue :
 *
 * <div data-controller="password-field"
 *      data-password-field-show-strength-value="true"
 *      data-password-field-has-confirm-value="true">
 *
 *   <div class="pw-input-wrap">
 *     <input data-password-field-target="input" ...>
 *     <button data-password-field-target="toggleBtn"
 *             data-action="click->password-field#toggleVisibility">
 *       <span class="pw-eye-show">{{ ux_icon('lucide:eye') }}</span>
 *       <span class="pw-eye-hide" hidden>{{ ux_icon('lucide:eye-off') }}</span>
 *     </button>
 *   </div>
 *
 *   <div class="pw-strength" data-password-field-target="strength">
 *     <div class="pw-rule" data-rule="length"    data-password-field-target="rule">…</div>
 *     <div class="pw-rule" data-rule="uppercase" data-password-field-target="rule">…</div>
 *     <div class="pw-rule" data-rule="digit"     data-password-field-target="rule">…</div>
 *     <div class="pw-rule" data-rule="special"   data-password-field-target="rule">…</div>
 *   </div>
 *
 *   <div class="pw-input-wrap">
 *     <input data-password-field-target="confirm" ...>
 *     <button data-password-field-target="confirmToggleBtn"
 *             data-action="click->password-field#toggleConfirmVisibility">…
 *     </button>
 *   </div>
 *   <p data-password-field-target="matchStatus"></p>
 * </div>
 */

const RULES = {
    length:    (v) => v.length >= 8,
    uppercase: (v) => /[A-Z]/.test(v),
    digit:     (v) => /[0-9]/.test(v),
    special:   (v) => /[\W_]/.test(v),
};

export default class extends Controller {
    static targets = [
        'input', 'toggleBtn',
        'strength', 'rule',
        'confirm', 'confirmToggleBtn', 'matchStatus',
    ];
    static values = {
        showStrength: { type: Boolean, default: false },
        hasConfirm:   { type: Boolean, default: false },
    };

    connect() {
        this.checkStrength();
    }

    // ── Visibilité — champ principal ────────────────────────

    toggleVisibility() {
        this.#toggleInput(this.inputTarget, this.toggleBtnTarget);
    }

    // ── Visibilité — confirmation ───────────────────────────

    toggleConfirmVisibility() {
        if (this.hasConfirmToggleBtnTarget) {
            this.#toggleInput(this.confirmTarget, this.confirmToggleBtnTarget);
        }
    }

    // ── Force du mot de passe ───────────────────────────────

    checkStrength() {
        if (!this.showStrengthValue) return;
        const value = this.inputTarget.value;
        let allPass = true;

        this.ruleTargets.forEach((rule) => {
            const key  = rule.dataset.rule;
            const pass = RULES[key]?.(value) ?? false;
            rule.classList.toggle('pw-rule--pass', pass);
            rule.classList.toggle('pw-rule--fail', value.length > 0 && !pass);
            allPass &&= pass;
        });

        // Règles visibles tant qu'il en reste une à respecter.
        // Pas de lien avec le focus : cliquer sur l'œil ne les fait plus disparaître.
        if (this.hasStrengthTarget) {
            this.strengthTarget.classList.toggle('pw-strength--open', !allPass);
        }

        // Si un champ confirm existe → re-vérifier la correspondance
        if (this.hasConfirmValue && this.hasConfirmTarget && this.confirmTarget.value) {
            this.checkMatch();
        }
    }

    // ── Correspondance confirmation ─────────────────────────

    checkMatch() {
        if (!this.hasMatchStatusTarget) return;

        const pw      = this.inputTarget.value;
        const confirm = this.confirmTarget.value;
        const el      = this.matchStatusTarget;

        if (!confirm) {
            el.textContent = '';
            el.dataset.state = '';
            return;
        }

        if (pw === confirm) {
            el.innerHTML = '<span class="pw-match-ok">✓ Les mots de passe correspondent</span>';
            el.dataset.state = 'ok';
        } else {
            el.innerHTML = '<span class="pw-match-fail">✗ Les mots de passe ne correspondent pas</span>';
            el.dataset.state = 'fail';
        }
    }

    // ── Privé ────────────────────────────────────────────────

    #toggleInput(input, btn) {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        const showSpan = btn.querySelector('.pw-eye-show');
        const hideSpan = btn.querySelector('.pw-eye-hide');
        if (showSpan) showSpan.hidden = isPassword;
        if (hideSpan) hideSpan.hidden = !isPassword;

        btn.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Voir le mot de passe');
    }
}
