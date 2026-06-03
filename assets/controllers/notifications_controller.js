import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus — cloche de notifications.
 *
 * Charge les notifications via AJAX au premier clic,
 * puis met à jour le badge et ferme au clic extérieur.
 */
export default class extends Controller {

    static targets = [
        'btn',       // Bouton cloche
        'badge',     // Badge rouge avec le count
        'dropdown',  // Panel déroulant
        'list',      // Zone items à l'intérieur du dropdown
    ];

    static values = {
        dropdownUrl: String,
        markAllUrl:  String,
        csrf:        String,
    };

    connect() {
        this._loaded = false;
        this._onOutsideClick = (e) => {
            if (!this.element.contains(e.target)) this.close();
        };
    }

    disconnect() {
        document.removeEventListener('click', this._onOutsideClick);
    }

    // ── Ouvrir / fermer ───────────────────────────────────────────────────────
    toggle() {
        const isOpen = !this.dropdownTarget.hidden;
        if (isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.dropdownTarget.hidden = false;
        document.addEventListener('click', this._onOutsideClick);

        if (!this._loaded) {
            this._load();
        }
    }

    close() {
        this.dropdownTarget.hidden = true;
        document.removeEventListener('click', this._onOutsideClick);
    }

    // ── Charger via AJAX ──────────────────────────────────────────────────────
    async _load() {
        try {
            const resp = await fetch(this.dropdownUrlValue, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await resp.json();
            this._render(data);
            this._loaded = true;
        } catch {
            this.listTarget.innerHTML = '<p class="notification-dropdown__error">Erreur de chargement.</p>';
        }
    }

    _render({ unreadCount, items }) {
        // Mettre à jour le badge
        if (unreadCount > 0) {
            this.badgeTarget.textContent = unreadCount > 9 ? '9+' : unreadCount;
            this.badgeTarget.classList.remove('notification-badge--hidden');
        } else {
            this.badgeTarget.textContent = '';
            this.badgeTarget.classList.add('notification-badge--hidden');
        }

        if (items.length === 0) {
            this.listTarget.innerHTML = '<p class="notification-dropdown__empty">Aucune notification.</p>';
            return;
        }

        this.listTarget.innerHTML = items.map(item => `
            <div class="notif-item ${item.isRead ? '' : 'notif-item--unread'}" data-id="${item.id}">
                <span class="notif-item__icon notif-item__icon--${item.type}">
                    ${this._typeIcon(item.type)}
                </span>
                <div class="notif-item__body">
                    ${item.link
                        ? `<a href="${item.link}" class="notif-item__content">${this._esc(item.content)}</a>`
                        : `<span class="notif-item__content">${this._esc(item.content)}</span>`
                    }
                    <span class="notif-item__date">${item.createdAt}</span>
                </div>
            </div>
        `).join('');
    }

    // ── Tout marquer comme lu ─────────────────────────────────────────────────
    async markAllRead() {
        try {
            const formData = new FormData();
            formData.append('_token', this.csrfValue);

            await fetch(this.markAllUrlValue, {
                method: 'POST',
                body:   formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            // Mettre à jour l'UI localement
            this.badgeTarget.textContent = '';
            this.badgeTarget.classList.add('notification-badge--hidden');
            this.listTarget.querySelectorAll('.notif-item--unread')
                .forEach(el => el.classList.remove('notif-item--unread'));

            this._loaded = false; // Forcer un rechargement à la prochaine ouverture
        } catch { /* silencieux */ }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    _typeIcon(type) {
        const icons = {
            invitation:        '👥',
            removed:           '🚫',
            role_changed:      '✏️',
            project_published: '🌍',
            comment:           '💬',
            reply:             '↩️',
            info:              'ℹ️',
        };
        return icons[type] ?? icons.info;
    }

    _esc(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
}
