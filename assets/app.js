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
 * FILTRE ANTI-ERREUR (SYMFONY PROFILER)
 */
window.addEventListener('unhandledrejection', (event) => {
    if (event.reason?.stack?.includes('renderAjaxRequests')) {
        event.preventDefault();
    }
});
