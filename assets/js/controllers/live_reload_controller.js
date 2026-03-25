import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['iframe'];
    static values = { url: String };

    connect() {
        this._stopped = false;
        this._source = new EventSource(this.urlValue);
        this._source.addEventListener('reload', (e) => {
            const { type } = JSON.parse(e.data);
            if (type === 'page') {
                window.location.reload();
            } else if (type === 'iframe' && this.hasIframeTarget) {
                this.iframeTarget.contentWindow.location.reload();
            }
        });
        this._source.addEventListener('stop', () => {
            this._stopped = true;
            this._source.close();
        });
        this._source.onerror = () => {
            this._source.close();
            if (!this._stopped) {
                this._timer = setTimeout(() => this.connect(), 3000);
            }
        };
    }

    disconnect() {
        clearTimeout(this._timer);
        this._source?.close();
    }
}
