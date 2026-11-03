import { Controller } from '@hotwired/stimulus';

const SCHEMES = ['auto', 'light', 'dark'];
// Also read by the inline script of templates/iframe.html.twig, before the preview renders
const STORAGE_KEY = 'storia.color-scheme';

export default class extends Controller {
    static targets = ['iframe', 'button', 'label'];

    connect() {
        let stored = null;
        try {
            stored = window.localStorage.getItem(STORAGE_KEY);
        } catch {
            // storage disabled (private mode, file://)
        }

        this.scheme = SCHEMES.includes(stored) ? stored : 'auto';
        this.render();
    }

    toggle() {
        this.scheme = SCHEMES[(SCHEMES.indexOf(this.scheme) + 1) % SCHEMES.length];

        try {
            if (this.scheme === 'auto') {
                window.localStorage.removeItem(STORAGE_KEY);
            } else {
                window.localStorage.setItem(STORAGE_KEY, this.scheme);
            }
        } catch {
            // storage disabled
        }

        this.render();
    }

    apply() {
        if (!this.hasIframeTarget) {
            return;
        }

        let root;
        try {
            root = this.iframeTarget.contentDocument?.documentElement;
        } catch {
            // cross-origin under file://
            return;
        }

        if (!root) {
            return;
        }

        if (this.scheme === 'auto') {
            delete root.dataset.theme;
            root.style.removeProperty('color-scheme');
        } else {
            root.dataset.theme = this.scheme;
            root.style.setProperty('color-scheme', this.scheme);
        }
    }

    render() {
        const label = `Color scheme: ${this.scheme}`;

        if (this.hasButtonTarget) {
            this.buttonTarget.dataset.scheme = this.scheme;
            this.buttonTarget.title = label;
        }

        if (this.hasLabelTarget) {
            this.labelTarget.textContent = label;
        }

        this.apply();
    }
}
