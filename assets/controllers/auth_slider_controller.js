import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Auth — gestion des onglets Connexion / Inscription
 * avec animation fade+slide et color-picker d'avatar.
 *
 * Palette identique à User::AVATAR_PALETTE côté PHP.
 */

const PALETTE = [
    '#3B82F6', // bleu
    '#8B5CF6', // violet
    '#EC4899', // rose
    '#F97316', // orange
    '#10B981', // vert
    '#06B6D4', // cyan
    '#EAB308', // jaune
    '#6366F1', // indigo
];

export default class extends Controller {

    static targets = [
        'loginPanel',        // div.auth-form-panel--login
        'registerPanel',     // div.auth-form-panel--register
        'tabLogin',          // bouton onglet Se connecter
        'tabRegister',       // bouton onglet Créer un compte
        'usernameInput',     // input username dans le form inscription
        'avatarColorInput',  // <input type="hidden" name="registrationForm[avatarColor]">
        'avatarPreview',     // span.u-avatar (preview dans le form)
        'swatchContainer',   // div contenant les swatches
    ];

    connect() {
        // Déterminer l'onglet initial (erreurs d'inscription → ouvrir inscription)
        const hasRegisterErrors = this.element.dataset.hasErrors === 'true';
        this._mode = hasRegisterErrors ? 'register' : 'login';
        this._selectedColor = null;

        this._applyTab(this._mode, false);
        this._buildSwatches();

        // Couleur initiale : aléatoire dans la palette.
        // Si le champ caché a déjà une valeur (rechargement après erreur de form),
        // on conserve cette valeur plutôt que d'écraser le choix précédent.
        const existingColor = this.hasAvatarColorInputTarget
            ? this.avatarColorInputTarget.value.trim()
            : '';
        const initialColor = (existingColor && PALETTE.includes(existingColor))
            ? existingColor
            : PALETTE[Math.floor(Math.random() * PALETTE.length)];

        this._pickColor(initialColor);
    }

    // ── Onglets ───────────────────────────────────────────────────────────────

    showLogin() {
        if (this._mode === 'login') return;
        this._mode = 'login';
        this._applyTab('login', true);
    }

    showRegister() {
        if (this._mode === 'register') return;
        this._mode = 'register';
        this._applyTab('register', true);
    }

    _applyTab(mode, animate) {
        const loginPanel    = this.loginPanelTarget;
        const registerPanel = this.registerPanelTarget;

        if (animate) {
            const leaving  = mode === 'login' ? registerPanel : loginPanel;
            const entering = mode === 'login' ? loginPanel    : registerPanel;

            leaving.classList.add('auth-panel--exit');
            leaving.addEventListener('animationend', () => {
                leaving.hidden = true;
                leaving.classList.remove('auth-panel--exit');
            }, { once: true });

            entering.hidden = false;
            entering.classList.add('auth-panel--enter');
            entering.addEventListener('animationend', () => {
                entering.classList.remove('auth-panel--enter');
            }, { once: true });
        } else {
            loginPanel.hidden    = (mode !== 'login');
            registerPanel.hidden = (mode !== 'register');
        }

        // Onglets actifs
        this.tabLoginTarget.classList.toggle('auth-tab--active',    mode === 'login');
        this.tabRegisterTarget.classList.toggle('auth-tab--active', mode === 'register');
    }

    // ── Color picker ─────────────────────────────────────────────────────────

    _buildSwatches() {
        if (!this.hasSwatchContainerTarget) return;
        const container = this.swatchContainerTarget;
        container.innerHTML = '';

        PALETTE.forEach(color => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'avatar-picker__swatch';
            btn.style.backgroundColor = color;
            btn.dataset.color = color;
            btn.setAttribute('aria-label', color);
            btn.addEventListener('click', () => this._pickColor(color));
            container.appendChild(btn);
        });
    }

    _pickColor(color) {
        this._selectedColor = color;
        this._syncColor(color);
    }

    _syncColor(color) {
        // Mettre à jour le champ caché
        if (this.hasAvatarColorInputTarget) {
            this.avatarColorInputTarget.value = color;
        }
        // Mettre à jour la preview
        if (this.hasAvatarPreviewTarget) {
            this.avatarPreviewTarget.style.backgroundColor = color;
        }
        // Mettre à jour les swatches actifs
        if (this.hasSwatchContainerTarget) {
            this.swatchContainerTarget.querySelectorAll('.avatar-picker__swatch').forEach(btn => {
                btn.classList.toggle('avatar-picker__swatch--active', btn.dataset.color === color);
            });
        }
    }

    // ── Username → couleur auto ───────────────────────────────────────────────
    // La couleur n'est plus dérivée du username (hash supprimé).
    // onUsernameInput reste branché dans le Twig pour d'éventuels futurs usages
    // mais ne touche plus à la couleur.
    onUsernameInput(e) {
        // Réservé pour extension future (ex: mise à jour du preview du pseudo).
    }
}
