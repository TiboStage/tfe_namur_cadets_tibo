import { Controller } from '@hotwired/stimulus';

/**
 * Gère l'expansion/réduction d'un nœud dans l'arborescence narrative.
 * Usage :
 *   <div data-controller="tree-node" data-tree-node-open-value="true">
 *     <button data-action="tree-node#toggle" data-tree-node-target="toggle">…</button>
 *     <div class="tree-children hidden" data-tree-node-target="children">…</div>
 *   </div>
 */
export default class extends Controller {
    static targets = ['children', 'toggle'];
    static values  = { open: { type: Boolean, default: false } };

    connect() {
        // Ouvre automatiquement si le nœud contient l'élément actif ou si open=true
        const hasActive = this.element.querySelector('.tree-node-row.active');
        if (hasActive || this.openValue) {
            this._open();
        } else {
            this._close();
        }
    }

    toggle() {
        if (!this.hasChildrenTarget) return;
        if (this.childrenTarget.classList.contains('hidden')) {
            this._open();
        } else {
            this._close();
        }
    }

    _open() {
        if (!this.hasChildrenTarget) return;
        this.childrenTarget.classList.remove('hidden');
        if (this.hasToggleTarget) {
            this.toggleTarget.classList.add('expanded');
        }
    }

    _close() {
        if (!this.hasChildrenTarget) return;
        this.childrenTarget.classList.add('hidden');
        if (this.hasToggleTarget) {
            this.toggleTarget.classList.remove('expanded');
        }
    }
}
