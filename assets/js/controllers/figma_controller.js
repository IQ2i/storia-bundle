import { Controller } from '@hotwired/stimulus';

const STORAGE_KEY = 'storia.figma';

export default class extends Controller {
    static targets = ['split', 'frame', 'button'];
    static values = {
        url: String,
        open: { type: Boolean, default: false },
        size: { type: Number, default: 0.5 },
    };

    // Switching variant loads a new page: the comparison follows the user across the pages of the session
    initialize() {
        try {
            const state = JSON.parse(sessionStorage.getItem(STORAGE_KEY));
            if (state) {
                this.sizeValue = state.size;
                this.openValue = state.open;
            }
        } catch {
            // Storage blocked or corrupted: start closed
        }
    }

    connect() {
        // The split is a resize controller that reflects the dragged width in this attribute
        this.observer = new MutationObserver(() => {
            const size = Number(this.splitTarget.dataset.resizeSizeValue);
            if (this.openValue && size < 1) {
                this.sizeValue = size;
            }
        });

        if (this.hasSplitTarget) {
            this.observer.observe(this.splitTarget, { attributes: true, attributeFilter: ['data-resize-size-value'] });
        }
    }

    disconnect() {
        this.observer.disconnect();
    }

    toggle() {
        this.openValue = !this.openValue;
    }

    openValueChanged() {
        if (this.hasSplitTarget) {
            this.splitTarget.dataset.resizeSizeValue = this.openValue ? String(this.sizeValue) : '1';
        }

        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-pressed', String(this.openValue));
        }

        // The embed is heavy: it is loaded at the first opening only
        if (this.openValue && this.hasFrameTarget && !this.frameTarget.src) {
            this.frameTarget.src = this.urlValue;
        }

        this.store();
    }

    sizeValueChanged() {
        this.store();
    }

    store() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ open: this.openValue, size: this.sizeValue }));
        } catch {
            // Storage blocked: the state lives for this page only
        }
    }
}
