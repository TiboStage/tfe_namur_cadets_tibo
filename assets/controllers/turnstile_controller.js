import { Controller } from '@hotwired/stimulus';

/**
 * Rendu manuel du widget Cloudflare Turnstile.
 *
 * Pourquoi pas le rendu automatique de Cloudflare (juste un data-sitekey) ?
 * Turbo remplace le <body> à chaque navigation sans recharger la page. Le script
 * Turnstile ne s'exécute (et ne scanne le DOM) qu'une fois, au tout premier
 * chargement — un nouveau <div class="cf-turnstile"> injecté par une navigation
 * Turbo ultérieure n'est donc jamais détecté, et le widget n'apparaît pas.
 *
 * connect()/disconnect() de Stimulus, eux, se déclenchent correctement à chaque
 * navigation Turbo (c'est tout l'intérêt de Stimulus avec Turbo) — donc on rend
 * le widget nous-mêmes ici, à chaque fois que l'élément entre dans le DOM.
 */
export default class extends Controller {
    static values = { sitekey: String };

    widgetId = null;
    readyListener = null;

    connect() {
        if (window.turnstile) {
            this.renderWidget();
            return;
        }

        // Script pas encore chargé (ex: tout premier chargement de page) —
        // on attend l'événement envoyé par le callback onload (voir base.html.twig).
        this.readyListener = () => this.renderWidget();
        window.addEventListener('turnstile:ready', this.readyListener, { once: true });
    }

    disconnect() {
        if (this.readyListener) {
            window.removeEventListener('turnstile:ready', this.readyListener);
            this.readyListener = null;
        }

        if (this.widgetId !== null && window.turnstile) {
            window.turnstile.remove(this.widgetId);
            this.widgetId = null;
        }
    }

    renderWidget() {
        // Turbo peut réutiliser un élément déjà rendu (cache de navigation) : on repart propre.
        this.element.innerHTML = '';

        this.widgetId = window.turnstile.render(this.element, {
            sitekey: this.sitekeyValue,
            theme: 'dark',
        });
    }
}
