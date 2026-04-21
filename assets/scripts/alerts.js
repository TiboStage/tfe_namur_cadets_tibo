// // assets/scripts/alerts.js
//
// export function initAlerts() {
//     const alerts = document.querySelectorAll('.scenart-alert');
//
//     alerts.forEach(alert => {
//         // 1. Timer automatique (5 secondes)
//         const timer = setTimeout(() => {
//             removeAlert(alert);
//         }, 5000);
//
//         // 2. Clic sur la croix
//         const closeBtn = alert.querySelector('.alert-close');
//         if (closeBtn) {
//             closeBtn.addEventListener('click', () => {
//                 clearTimeout(timer); // On stoppe le timer si on clique manuellement
//                 removeAlert(alert);
//             });
//         }
//     });
// }
//
// function removeAlert(alert) {
//     if (alert) {
//         alert.classList.add('alert-fade-out');
//         // On attend la fin de l'anim CSS avant de supprimer du DOM
//         setTimeout(() => {
//             alert.remove();
//         }, 500);
//     }
// }
//
// // On branche l'écouteur d'événements
// document.addEventListener('turbo:load', initAlerts);
