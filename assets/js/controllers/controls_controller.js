import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['iframe', 'iframeLink'];
    static values = {
        scale: { type: Number, default: 1 },
    };

    refreshIframe() {
        try {
            this.iframeTarget.contentWindow.location.reload();
        } catch {
            // cross-origin under file://
            this.iframeTarget.src = this.iframeTarget.src;
        }
    }

    preview(url) {
        if (this.hasIframeTarget) {
            this.iframeTarget.src = url;
        }

        if (this.hasIframeLinkTarget) {
            this.iframeLinkTarget.href = url;
        }
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
        // No iframe on the home page, where no view is displayed
        if (!this.hasIframeTarget) {
            return;
        }

        this.iframeTarget.style.transform = `scale(${this.scaleValue})`;
    }

    copyLink() {
        navigator.clipboard.writeText(window.location.href);
    }
}