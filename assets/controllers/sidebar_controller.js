import { Controller } from '@hotwired/stimulus';

const STORAGE_KEY = 'ws_sidebar_collapsed';

/**
 * Sidebar projet :
 *   Desktop : collapse/expand avec animation + persistance localStorage
 *   Mobile  : slide-in depuis la gauche via la classe `.open`
 */
export default class extends Controller {

    connect() {
        // ── Événements cross-composant (burger mobile) ───────────────────────
        this._onToggle  = () => this._mobileToggle();
        this._onClose   = () => this._mobileClose();
        this._onKeyDown = (e) => { if (e.key === 'Escape') this._mobileClose(); };

        document.addEventListener('sidebar:toggle', this._onToggle);
        document.addEventListener('sidebar:close',  this._onClose);
        document.addEventListener('keydown',        this._onKeyDown);

        // ── Overlay mobile ───────────────────────────────────────────────────
        this._overlay = document.createElement('div');
        this._overlay.className = 'sidebar-overlay';
        this._overlay.setAttribute('aria-hidden', 'true');
        this._overlay.addEventListener('click', () => this._mobileClose());
        document.body.appendChild(this._overlay);

        // ── Fermer après navigation sur mobile ───────────────────────────────
        this._onNavClick = (e) => {
            if (window.innerWidth < 768 && e.target.closest('a[href]')) {
                this._mobileClose();
            }
        };
        this.element.addEventListener('click', this._onNavClick);

        // ── Restaurer l'état desktop depuis localStorage ─────────────────────
        if (window.innerWidth >= 768) {
            const wasCollapsed = localStorage.getItem(STORAGE_KEY) === 'true';
            if (wasCollapsed) {
                this._desktopCollapse(false); // sans animation au chargement
            }
        }
    }

    disconnect() {
        document.removeEventListener('sidebar:toggle', this._onToggle);
        document.removeEventListener('sidebar:close',  this._onClose);
        document.removeEventListener('keydown',        this._onKeyDown);
        this.element.removeEventListener('click',      this._onNavClick);
        this._overlay?.remove();
        document.body.classList.remove('sidebar-open');
    }

    // ── Actions publiques ─────────────────────────────────────────────────────

    /** Appelé par le bouton toggle dans le header de la sidebar */
    toggle() {
        if (window.innerWidth < 768) { this._mobileToggle(); return; }

        const container = this.element.closest('.workspace-container');
        if (!container) return;

        const isCollapsed = container.classList.contains('sidebar-collapsed');
        isCollapsed ? this._desktopExpand(container) : this._desktopCollapse(true, container);
    }

    // ── Desktop ───────────────────────────────────────────────────────────────

    _desktopCollapse(animate = true, container = null) {
        const c = container ?? this.element.closest('.workspace-container');
        if (!c) return;

        if (!animate) c.classList.add('no-transition');
        c.classList.add('sidebar-collapsed');
        document.body.classList.add('sidebar-collapsed');       // pour le CSS du topbar
        localStorage.setItem(STORAGE_KEY, 'true');

        if (!animate) {
            requestAnimationFrame(() => c.classList.remove('no-transition'));
        }
    }

    _desktopExpand(container = null) {
        const c = container ?? this.element.closest('.workspace-container');
        if (!c) return;

        c.classList.remove('sidebar-collapsed');
        document.body.classList.remove('sidebar-collapsed');    // pour le CSS du topbar
        localStorage.setItem(STORAGE_KEY, 'false');
    }

    // ── Mobile ────────────────────────────────────────────────────────────────

    _mobileToggle() {
        if (window.innerWidth >= 768) return;
        this.element.classList.contains('open')
            ? this._mobileClose()
            : this._mobileOpen();
    }

    _mobileOpen() {
        this.element.classList.add('open');
        document.body.classList.add('sidebar-open');
        this._overlay.classList.add('active');
    }

    _mobileClose() {
        this.element.classList.remove('open');
        document.body.classList.remove('sidebar-open');
        this._overlay.classList.remove('active');
    }
}
