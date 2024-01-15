import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        index: {
            type: Number,
            default: 0,
        },
    };
    static targets = ['tab', 'tabPanel'];

    initialize() {
        this.showTab();
    }

    change(e) {
        this.indexValue = [...e.currentTarget?.parentElement?.parentElement.children].indexOf(e.currentTarget.parentElement);
    }

    indexValueChanged() {
        this.showTab();
    }

    showTab() {
        console.log(this.indexValue)
        this.tabTargets.forEach((tab, index) => {
            const panel = this.tabPanelTargets[index];
            tab.classList.toggle('tab-is-unactive', index !== this.indexValue);
            tab.classList.toggle('tab-is-active', index === this.indexValue);
            panel.classList.toggle('hidden', index !== this.indexValue);
        });
    }
}
