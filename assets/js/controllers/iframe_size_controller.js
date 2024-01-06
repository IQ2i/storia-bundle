import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['iframe', 'size'];

    connect() {
        this.sizeTarget.innerText = `${this.iframeTarget.offsetWidth} x ${this.iframeTarget.offsetHeight}`;
    }
}