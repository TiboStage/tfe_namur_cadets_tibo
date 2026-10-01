import { Controller } from '@hotwired/stimulus';

/**
 * Bouton "Renvoyer l'email" de la page "Vérifiez vos emails".
 *
 * Désactivé pendant le délai entre deux demandes (compte à rebours m:ss),
 * puis réactivé. Purement visuel : la vraie limite est appliquée côté serveur
 * (rate limiter "reset_password_cooldown").
 *
 * <a data-controller="resend-countdown"
 *    data-resend-countdown-seconds-value="120"
 *    data-resend-countdown-label-value="Renvoyer l'email"
 *    data-resend-countdown-waiting-value="Renvoyer dans %time%"
 *    data-action="click->resend-countdown#guard">…</a>
 */
export default class extends Controller {
    static values = {
        seconds: Number,
        label:   String,
        waiting: String,
    };

    #timer = null;

    connect() {
        this.#render();
        if (this.secondsValue > 0) {
            this.#timer = setInterval(() => {
                this.secondsValue--;
                this.#render();
                if (this.secondsValue <= 0) clearInterval(this.#timer);
            }, 1000);
        }
    }

    disconnect() {
        clearInterval(this.#timer);
    }

    /** Bloque le clic tant que le délai n'est pas écoulé. */
    guard(event) {
        if (this.secondsValue > 0) event.preventDefault();
    }

    #render() {
        const waiting = this.secondsValue > 0;
        this.element.classList.toggle('is-disabled', waiting);
        this.element.setAttribute('aria-disabled', waiting ? 'true' : 'false');

        if (waiting) {
            const m = Math.floor(this.secondsValue / 60);
            const s = String(this.secondsValue % 60).padStart(2, '0');
            this.element.textContent = this.waitingValue.replace('%time%', `${m}:${s}`);
        } else {
            this.element.textContent = this.labelValue;
        }
    }
}
