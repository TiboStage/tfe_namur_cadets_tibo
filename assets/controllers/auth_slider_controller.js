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

        // Si un username est déjà présent (rechargement après erreur), mettre à jour la preview
        if (this.hasUsernameInputTarget && this.usernameInputTarget.value.trim()) {
            this._updateColorFromUsername(this.usernameInputTarget.value.trim());
        }
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

    onUsernameInput(e) {
        const username = e.target.value.trim();
        // Si l'utilisateur n'a pas choisi manuellement, on auto-sélectionne depuis le username
        if (!this._selectedColor && username) {
            this._updateColorFromUsername(username);
        } else if (!username) {
            this._selectedColor = null;
        }
    }

    _updateColorFromUsername(username) {
        const color = this._hashColor(username);
        this._syncColor(color);
    }

    /**
     * Hash déterministe — miroir JS du PHP abs(crc32(strtolower(trim($username)))) % 8.
     * Utilise un hash polynomial djb2 sur la chaîne normalisée.
     */
    _hashColor(str) {
        const s = str.toLowerCase().trim();
        let hash = 0;
        for (let i = 0; i < s.length; i++) {
            hash = ((hash << 5) - hash) + s.charCodeAt(i);
            hash |= 0; // forcer int 32
        }
        const index = Math.abs(hash) % PALETTE.length;
        return PALETTE[index];
    }
}
