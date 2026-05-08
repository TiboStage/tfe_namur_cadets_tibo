import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    switch(event) {
        const btn = event.currentTarget;
        const view = btn.dataset.view;
        const container = document.getElementById('projects-container');
        const buttons = this.element.querySelectorAll('.btn');

        // Update boutons
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Update conteneur
        if (view === 'list') {
            container.classList.remove('projects-grid');
            container.classList.add('projects-list');
        } else {
            container.classList.remove('projects-list');
            container.classList.add('projects-grid');
        }
    }
}
