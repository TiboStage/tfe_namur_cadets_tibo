import './stimulus_bootstrap.js';

/* Styles */
import './styles/app.css';
import './styles/components/_alerts.css';


//
// /* Scripts */
// import './scripts/navbar.js';
import './scripts/pricing.js';
// import './scripts/auth.js';
// import './scripts/alerts.js';
//

// assets/app.js

document.addEventListener('turbo:load', () => {
    if (typeof SymfonyWebProfiler !== 'undefined') {
        // On laisse un mini délai pour que le DOM soit stable
        setTimeout(() => {
            const toolbar = document.querySelector('.sf-toolbar');
            if (toolbar && window.loadToolbar) {
                loadToolbar(); // Force le rechargement du script de la toolbar
            }
        }, 100);
    }
});
