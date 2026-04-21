// /**
//  * SCÉNART — Éditeur Fountain Unifié
//  */
//
// // Variables d'état (accessibles dans tout le script)
// let editor, preview;
// let isPreview = false;
// let saveTimer = null;
// let isDirty = false;
//
// // --- Initialisation ---
// function initEditor() {
//     editor = document.getElementById('editor-write');
//     preview = document.getElementById('editor-preview');
//
//     if (editor) {
//         updateStats();
//         updateDetected();
//         editor.addEventListener('input', onEditorInput);
//         editor.addEventListener('keydown', onEditorKeydown);
//         console.log("Éditeur initialisé");
//     }
// }
//
// // Support pour Turbo et chargement classique
// document.addEventListener('turbo:load', initEditor);
// document.addEventListener('DOMContentLoaded', initEditor);
//
// function onEditorInput() {
//     isDirty = true;
//     updateStats();
//     updateDetected();
//     setSaveIndicator('unsaved');
//     clearTimeout(saveTimer);
//     saveTimer = setTimeout(window.saveContent, 2000);
// }
//
// function onEditorKeydown(e) {
//     if (e.key === 'Tab') {
//         e.preventDefault();
//         document.execCommand('insertText', false, '    ');
//     }
// }
//
// // --- Fonctions Globales (pour les onclick du HTML) ---
//
// window.togglePreview = function() {
//     isPreview = !isPreview;
//     const btn = document.getElementById('btn-preview');
//     if (isPreview) {
//         renderPreview();
//         editor.style.display = 'none';
//         preview.style.display = 'block';
//         btn.classList.add('active');
//     } else {
//         editor.style.display = 'block';
//         preview.style.display = 'none';
//         btn.classList.remove('active');
//         editor.focus();
//     }
// };
//
// window.saveContent = async function() {
//     // Si on force la sauvegarde via le bouton, on ignore isDirty ou on le reset
//     const btn = document.getElementById('btn-save');
//     if (!window.SAVE_URL || !editor) return;
//
//     btn.disabled = true;
//     setSaveIndicator('saving');
//
//     const rawText = editor.innerText;
//     const content = [{ type: 'raw', content: rawText }];
//     const summary = rawText.split('\n').find(l => l.trim().length > 0) || '';
//
//     try {
//         const res = await fetch(window.SAVE_URL, {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/json',
//                 'X-CSRF-Token': window.CSRF_TOKEN,
//             },
//             body: JSON.stringify({ content, summary }),
//         });
//
//         const data = await res.json();
//         if (data.success) {
//             isDirty = false;
//             setSaveIndicator('saved', data.updated_at);
//         } else {
//             setSaveIndicator('error');
//         }
//     } catch (err) {
//         console.error("Erreur save:", err);
//         setSaveIndicator('error');
//     }
//     btn.disabled = false;
// };
//
// window.toggleSidebar = function(side) {
//     document.getElementById(`sidebar-${side}`)?.classList.toggle('collapsed');
// };
//
// window.toggleTree = function(btn) {
//     const node = btn.closest('.tree-node');
//     const children = node?.querySelector('.tree-children');
//     if (children) {
//         children.classList.toggle('hidden');
//         btn.classList.toggle('rotated');
//     }
// };
//
// // --- Logique métier (Interne) ---
//
// function renderPreview() {
//     const lines = (editor.innerText || '').split('\n');
//     let html = '';
//     let lastWasChar = false;
//
//     lines.forEach(line => {
//         const t = line.trim();
//         if (!t) { html += '<div class="fp-empty"></div>'; lastWasChar = false; return; }
//         if (/^(INT|EXT|INT\/EXT|EXT\/INT)\./i.test(t)) {
//             html += `<div class="fp-slug">${escHtml(line)}</div>`;
//             lastWasChar = false;
//         } else if (t === t.toUpperCase() && t.length > 1 && !/^\d+$/.test(t) && !/[.!?]$/.test(t)) {
//             html += `<div class="fp-char">${escHtml(line.trim())}</div>`;
//             lastWasChar = true;
//         } else if (lastWasChar) {
//             html += `<div class="fp-diag">${escHtml(line)}</div>`;
//             lastWasChar = false;
//         } else {
//             html += `<div class="fp-action">${escHtml(line)}</div>`;
//             lastWasChar = false;
//         }
//     });
//     preview.innerHTML = html || '<p class="fp-empty-hint">Rien à afficher.</p>';
// }
//
// function updateDetected() {
//     if (!editor) return;
//     const lines = editor.innerText.split('\n');
//     const chars = {};
//     const locs = {};
//     let dialogs = 0, actions = 0, slugs = 0;
//     let lastWasChar = false;
//
//     lines.forEach(line => {
//         const t = line.trim();
//         if (!t) { lastWasChar = false; return; }
//         if (/^(INT|EXT|INT\/EXT|EXT\/INT)\./i.test(t)) {
//             slugs++;
//             lastWasChar = false;
//         } else if (t === t.toUpperCase() && t.length > 1 && !/^\d+$/.test(t) && !/[.!?]$/.test(t)) {
//             chars[t] = (chars[t] || 0) + 1;
//             lastWasChar = true;
//         } else if (lastWasChar) {
//             dialogs++;
//             lastWasChar = false;
//         } else {
//             actions++;
//             lastWasChar = false;
//         }
//     });
//
//     document.getElementById('stat-dialogs').textContent = dialogs;
//     document.getElementById('stat-actions').textContent = actions;
//     document.getElementById('stat-slugs').textContent = slugs;
//     // ... (Tu peux remettre ici la mise à jour des listes de persos si tu veux)
// }
//
// function updateStats() {
//     if (!editor) return;
//     const text = editor.innerText;
//     const words = text.trim() ? text.trim().split(/\s+/).length : 0;
//     document.getElementById('stats-words').textContent = `${words} mot${words > 1 ? 's' : ''}`;
// }
//
// function escHtml(str) {
//     return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
// }
//
// function setSaveIndicator(status, time = null) {
//     const el = document.getElementById('save-indicator');
//     if (!el) return;
//     const map = {
//         saved: { text: time ? `✓ ${time}` : '✓ Sauvegardé', color: '#22c55e' },
//         unsaved: { text: '● Modifié', color: '#f59e0b' },
//         saving: { text: '⏳...', color: '#6b7280' },
//         error: { text: '✕ Erreur', color: '#ef4444' },
//     };
//     const s = map[status];
//     el.textContent = s.text;
//     el.style.color = s.color;
// }
