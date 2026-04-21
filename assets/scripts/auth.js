// /**
//  * assets/scripts/auth.js
//  */
// const initAuthSlider = () => {
//     const toRegister = document.getElementById('toRegister');
//     const toLogin = document.getElementById('toLogin');
//     const card = document.getElementById('auth-card');
//
//     // Très important avec Turbo : on vérifie si on est sur la bonne page
//     if (!card || !toRegister || !toLogin) return;
//
//     // On retire les anciens écouteurs s'ils existent (bonne pratique Turbo)
//     toRegister.onclick = () => {
//         card.classList.add("right-panel-active");
//     };
//
//     toLogin.onclick = () => {
//         card.classList.remove("right-panel-active");
//     };
//
//     // Si des erreurs sont présentes (ex: après soumission de formulaire par Turbo)
//     if (card.getAttribute('data-has-errors') === 'true') {
//         card.classList.add("right-panel-active");
//     }
// };
//
// // On écoute l'événement spécifique à Turbo
// document.addEventListener('turbo:load', initAuthSlider);
//
// // Optionnel : s'assurer que ça marche aussi au premier chargement sans Turbo
// document.addEventListener('DOMContentLoaded', initAuthSlider);
//
