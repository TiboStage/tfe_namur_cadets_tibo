import { Controller } from '@hotwired/stimulus';

/**
 * Vérifie la disponibilité d'un nom d'utilisateur en temps réel.
 *
 * Usage :
 * <div data-controller="username-check"
 *      data-username-check-url-value="/api/check-username"
 *      data-username-check-exclude-value="{{ user.id }}">
 *   <input data-username-check-target="input" data-action="input->username-check#check">
 *   <div  data-username-check-target="status"></div>
 * </div>
 */
export default class extends Controller {
    static targets = ['input', 'status'];
    static values  = {
        url:     { type: String, default: '/api/check-username' },
        exclude: { type: String, default: '' },  // ID user courant (profil)
    };

    #debounceTimer = null;
    #lastChecked   = '';
    #controller    = null;

    // ── Cycle de vie ────────────────────────────────────────

    connect() {
        // Si un contenu préfill → vérifier d'emblée
        if (this.inputTarget.value.trim().length >= 3) {
            this.#debounce();
        }
    }

    disconnect() {
        this.#controller?.abort();
        clearTimeout(this.#debounceTimer);
    }

    // ── Action ──────────────────────────────────────────────

    check() {
        clearTimeout(this.#debounceTimer);
        const value = this.inputTarget.value.trim();

        if (value.length < 3) {
            this.#setStatus('');
            return;
        }

        if (value === this.#lastChecked) return;

        this.#setStatus('loading');
        this.#debounce();
    }

    // ── Privé ────────────────────────────────────────────────

    #debounce() {
        this.#debounceTimer = setTimeout(() => this.#fetch(), 400);
    }

    async #fetch() {
        const username = this.inputTarget.value.trim();
        if (username.length < 3) return;
        this.#lastChecked = username;

        // Annule la requête précédente si encore en cours
        this.#controller?.abort();
        this.#controller = new AbortController();

        const url = new URL(this.urlValue, window.location.origin);
        url.searchParams.set('username', username);
        if (this.excludeValue) {
            url.searchParams.set('exclude', this.excludeValue);
        }

        try {
            const resp = await fetch(url, { signal: this.#controller.signal });
            const data = await resp.json();

            if (data.available === true)  this.#setStatus('available');
            else if (data.available === false) this.#setStatus('taken');
            else this.#setStatus('');
        } catch (e) {
            if (e.name !== 'AbortError') this.#setStatus('');
        }
    }

    #setStatus(state) {
        const el = this.statusTarget;
        el.dataset.state = state;

        const messages = {
            loading:   '<span class="uc-loading">…</span>',
            available: '<span class="uc-available">✓ Disponible</span>',
            taken:     '<span class="uc-taken">✗ Déjà utilisé</span>',
            '':        '',
        };

        el.innerHTML = messages[state] ?? '';
    }
}
