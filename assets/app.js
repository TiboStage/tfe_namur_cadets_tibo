import './stimulus_bootstrap.js';
//
// /* Styles */
// // import './styles/app.css';
// // import './styles/components/_alerts.css';
//
//
// //
// // /* Scripts */
// import './scripts/pricing.js';
// //
//
// // assets/app.js
//
// document.addEventListener('turbo:load', () => {
//     if (typeof SymfonyWebProfiler !== 'undefined') {
//         // On laisse un mini délai pour que le DOM soit stable
//         setTimeout(() => {
//             const toolbar = document.querySelector('.sf-toolbar');
//             if (toolbar && window.loadToolbar) {
//                 loadToolbar(); // Force le rechargement du script de la toolbar
//             }
//         }, 100);
//     }
// });
/*
 * Note : On ne met plus l'import CSS ici pour éviter les avertissements
 * de préchargement que tu avais au début. Le CSS est appelé dans Twig.
 */

/**
 * GESTION GLOBALE DE LA NAVIGATION (TURBO)
 */

// import './bootstrap.js'; // Un seul import pour Stimulus
import './scripts/pricing.js';

/**
 * GESTION EXPERTE DE LA NAVIGATION (TURBO)
 */
let currentVisit = null;

// 1. Intercepter la visite avant qu'elle ne commence
document.addEventListener('turbo:before-visit', (event) => {
    // Si une visite est déjà en cours (clics rapides), on l'annule
    if (currentVisit) {
        currentVisit.cancel();
    }
});

// 2. Quand la visite démarre
document.addEventListener('turbo:visit', (event) => {
    // On stocke la nouvelle visite
    currentVisit = event.detail.visit;

    // Feedback visuel (bloque les clics et change l'opacité)
    document.body.classList.add('is-loading');
});

// 3. Quand la page est rendue et prête
document.addEventListener('turbo:load', () => {
    currentVisit = null;
    document.body.classList.remove('is-loading');
});

/**
 * FIX: Symfony Web Debug Toolbar + Turbo
 *
 * Le profiler injecte un inline script qui appelle loadToolbar() à chaque
 * render Turbo. Le XHR du profiler peut se terminer après que Turbo ait
 * remplacé le DOM → renderAjaxRequests() accède à null.style → crash.
 *
 * Solution : donner un id stable à .sf-toolbar + data-turbo-permanent
 * pour que Turbo le préserve d'une page à l'autre (le XHR met à jour
 * le contenu in-place, l'élément reste dans le DOM).
 */
document.addEventListener('turbo:before-render', (event) => {
    const TOOLBAR_ID = 'sf-toolbar-permanent';

    // Marquer la toolbar courante comme permanente
    const existing = document.querySelector('.sf-toolbar');
    if (existing) {
        existing.id = TOOLBAR_ID;
        existing.setAttribute('data-turbo-permanent', '');
    }

    // Même id sur la toolbar de la nouvelle page (Turbo fait le matching par id)
    const incoming = event.detail.newBody?.querySelector('.sf-toolbar');
    if (incoming) {
        incoming.id = TOOLBAR_ID;
        incoming.setAttribute('data-turbo-permanent', '');
    }
});

// Filet de sécurité : si renderAjaxRequests est exposée en global,
// on l'enveloppe dans un try/catch pour les cas résiduels.
document.addEventListener('turbo:load', () => {
    if (typeof window.renderAjaxRequests === 'function') {
        const _orig = window.renderAjaxRequests;
        window.renderAjaxRequests = function () {
            try { _orig.apply(this, arguments); } catch (_e) { /* DOM replaced */ }
        };
    }
});
