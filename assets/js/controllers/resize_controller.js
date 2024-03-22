import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sidebar', 'content', 'draggable'];
    static values = {
        width: { type: Number, default: 288 },
    };

    resize(event) {
        event.preventDefault();

        const doResize = (e) => {
            this.widthValue = e.pageX - this.sidebarTarget.getBoundingClientRect().left + 2.5;
        };
        const stopResize = () => {
            window.removeEventListener('mousemove', doResize);
        };

        window.addEventListener('mousemove', doResize)
        window.addEventListener('mouseup', stopResize)
    }

    widthValueChanged() {
        this.sidebarTarget.style.width = `${this.widthValue}px`;
        this.contentTarget.style.left = `${this.widthValue}px`;
        this.contentTarget.style.setProperty('--offset-left', `${this.widthValue}px`);
        this.draggableTarget.style.transform = `translate(${this.widthValue}px, 0px)`;
    }
}