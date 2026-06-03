import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur léger — color picker d'avatar sur la page profil.
 * Même palette que auth_slider_controller.js et User::AVATAR_PALETTE.
 */

const PALETTE = [
    '#3B82F6',
    '#8B5CF6',
    '#EC4899',
    '#F97316',
    '#10B981',
    '#06B6D4',
    '#EAB308',
    '#6366F1',
];

export default class extends Controller {

    static targets = ['avatarPreview', 'colorInput', 'swatchContainer'];
    static values  = { initial: String };

    connect() {
        this._current = this.initialValue || PALETTE[0];
        this._buildSwatches();
        this._highlight(this._current);
    }

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
            btn.addEventListener('click', () => this._pick(color));
            container.appendChild(btn);
        });
    }

    _pick(color) {
        this._current = color;

        // Mettre à jour le champ caché
        if (this.hasColorInputTarget) {
            this.colorInputTarget.value = color;
        }

        // Mettre à jour la preview
        if (this.hasAvatarPreviewTarget) {
            this.avatarPreviewTarget.style.backgroundColor = color;
        }

        this._highlight(color);
    }

    _highlight(color) {
        if (!this.hasSwatchContainerTarget) return;
        this.swatchContainerTarget.querySelectorAll('.avatar-picker__swatch').forEach(btn => {
            btn.classList.toggle('avatar-picker__swatch--active', btn.dataset.color === color);
        });
    }
}
