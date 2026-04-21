// // assets/scripts/navbar.js
//
// export function initNavbar() {
//     // ── Dropdown navbar website ──────────────────────────────
//     const btn = document.getElementById('user-menu-btn');
//     const menu = document.getElementById('user-menu-content');
//
//     if (btn && menu) {
//         btn.onclick = (e) => {
//             e.stopPropagation();
//             menu.classList.toggle('show');
//             // Ferme l'autre dropdown si ouvert
//             topbarMenu?.classList.remove('show');
//         };
//     }
//
//     // ── Dropdown topbar workshop ─────────────────────────────
//     const topbarBtn = document.getElementById('topbar-user-menu-btn');
//     const topbarMenu = document.getElementById('topbar-user-menu-content');
//
//     if (topbarBtn && topbarMenu) {
//         topbarBtn.onclick = (e) => {
//             e.stopPropagation();
//             topbarMenu.classList.toggle('show');
//             // Ferme l'autre dropdown si ouvert
//             menu?.classList.remove('show');
//         };
//     }
// }
//
// document.addEventListener('turbo:load', () => {
//     initNavbar();
// });
//
// // Fermeture au clic extérieur — une seule fois
// if (!window.navbarClickAttached) {
//     document.addEventListener('click', () => {
//         document.getElementById('user-menu-content')?.classList.remove('show');
//         document.getElementById('topbar-user-menu-content')?.classList.remove('show');
//     });
//     window.navbarClickAttached = true;
// }
