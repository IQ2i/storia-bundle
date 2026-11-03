import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['split', 'frame', 'button'];
    static values = {
        url: String,
        open: { type: Boolean, default: false },
    };

    toggle() {
        this.openValue = !this.openValue;
    }

    openValueChanged() {
        // The split is a resize controller that reads its size from this attribute
        if (this.hasSplitTarget) {
            this.splitTarget.dataset.resizeSizeValue = this.openValue ? '0.5' : '1';
        }

        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-pressed', String(this.openValue));
        }

        // The embed is heavy: it is loaded at the first opening only
        if (this.openValue && this.hasFrameTarget && !this.frameTarget.src) {
            this.frameTarget.src = this.urlValue;
        }
    }
}
