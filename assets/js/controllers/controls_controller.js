import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['iframe'];
    static values = {
        scale: { type: Number, default: 1 },
    };

    refreshIframe() {
        this.iframeTarget.contentWindow.location.reload();
    }

    zoomIn() {
        this.scaleValue = this.scaleValue * 1.1;
    }

    zoomOut() {
        this.scaleValue = this.scaleValue * 0.9;
    }

    resetZoom() {
        this.scaleValue = 1;
    }

    scaleValueChanged() {
        this.iframeTarget.style.transform = `scale(${this.scaleValue})`;
    }

    copyLink() {
        navigator.clipboard.writeText(window.location.href);
    }
}