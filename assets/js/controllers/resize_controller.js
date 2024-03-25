import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panelA', 'panelB', 'resizer'];
    static values = {
        size: { type: Number, default: 0.20 },
        startSize: { type: Number, default: 0.20 },
        startX: { type: Number, default: 0 },
        startY: { type: Number, default: 0 },
        min: { type: Number, default: 0.1 },
        max: { type: Number, default: 0.9 },
        isResizing: { type: Boolean, default: false },
        orientation: { type: String, default: 'horizontal' }
    };

    get cursor() {
        return this.orientationValue === 'horizontal' ? 'col-resize' : 'row-resize';
    }

    resize(event) {
        event.preventDefault();

        this.isResizingValue = true;
        this.startSizeValue = this.sizeValue;
        this.startXValue = event.clientX;
        this.startYValue = event.clientY;

        const doResize = (e) => {
            const calculateSize = () => {
                if (this.orientationValue === 'horizontal') {
                    return Math.min(
                        Math.max(this.startSizeValue + (e.clientX - this.startXValue) / this.element.clientWidth, this.minValue),
                        this.maxValue,
                    );
                } else {
                    return Math.min(
                        Math.max(this.startSizeValue + (e.clientY - this.startYValue) / this.element.clientHeight, this.minValue),
                        this.maxValue,
                    );
                }
            };

            this.sizeValue = calculateSize();
        };
        const stopResize = () => {
            window.removeEventListener('pointermove', doResize);
            this.isResizingValue = false;
        };

        window.addEventListener('pointermove', doResize)
        window.addEventListener('pointerup', stopResize)
    }

    sizeValueChanged() {
        this.element.style.setProperty('--resize-size', this.sizeValue);
    }

    isResizingValueChanged() {
        if (this.isResizingValue) {
            document.body.style.setProperty('pointer-events', 'none', 'important');
            document.documentElement.style.setProperty('cursor', this.cursor, 'important');
        } else {
            document.body.style.removeProperty('pointer-events');
            document.documentElement.style.removeProperty('cursor');
        }
    }
}