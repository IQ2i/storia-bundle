import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['iframe', 'size'];

    connect() {
        new ResizeObserver((entries) => {
            const [entry] = entries;
            this.sizeTarget.innerText = `${entry.contentRect.width} x ${entry.contentRect.height}`;
        }).observe(this.iframeTarget);
    }

    disconnect() {
        new ResizeObserver((entries) => {
            const [entry] = entries;
            this.sizeTarget.innerText = `${entry.contentRect.width} x ${entry.contentRect.height}`;
        }).observe(this.iframeTarget);
    }
}