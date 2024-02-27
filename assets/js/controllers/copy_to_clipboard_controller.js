import {Controller} from '@hotwired/stimulus';

export default class extends Controller  {
    static targets = ['svg', 'text'];
    static values = {
        content: String,
        copied: Boolean,
    };

    copy() {
        navigator.clipboard.writeText(this.contentValue);
        this.copiedValue = true;

        setTimeout(() => {
            this.copiedValue = false;
        }, 2000);
    }

    copiedValueChanged() {
        this.svgTarget.classList.toggle('hidden', this.copiedValue);
        this.textTarget.classList.toggle('hidden', !this.copiedValue);
    }
}