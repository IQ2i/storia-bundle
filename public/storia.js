import { Controller, Application } from '@hotwired/stimulus';

class CopyToClipboardController extends Controller  {
    static targets = ['svg', 'text'];
    static values = {
        content: String,
        copied: Boolean,
    };

    copy() {
        navigator.clipboard.writeText(this.contentValue);
        this.copiedValue = true;

        setTimeout(() => {
            this.copiedValue = false;
        }, 2000);
    }

    copiedValueChanged() {
        this.svgTarget.classList.toggle('hidden', this.copiedValue);
        this.textTarget.classList.toggle('hidden', !this.copiedValue);
    }
}

class ControlsController extends Controller {
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

class MenuController extends Controller {
    static targets = ['svg', 'submenu'];
    static classes = ['opened', 'closed'];
    static values = {
        opened: Boolean
    }

    toggle() {
        this.openedValue = !this.openedValue;
    }

    openedValueChanged() {
        if (this.openedValue) {
            this.svgTarget.classList.remove(this.closedClass);
            this.svgTarget.classList.add(this.openedClass);
            this.submenuTarget.classList.remove('hidden');
        } else {
            this.svgTarget.classList.remove(this.openedClass);
            this.svgTarget.classList.add(this.closedClass);
            this.submenuTarget.classList.add('hidden');
        }
    }
}

class ResizeController extends Controller {
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

        window.addEventListener('pointermove', doResize);
        window.addEventListener('pointerup', stopResize);
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

class TabController extends Controller {
    static values = {
        index: {
            type: Number,
            default: 0,
        },
    };
    static targets = ['tab', 'tabPanel'];

    initialize() {
        this.showTab();
    }

    change(e) {
        this.indexValue = [...e.currentTarget?.parentElement?.parentElement.children].indexOf(e.currentTarget.parentElement);
    }

    indexValueChanged() {
        this.showTab();
    }

    showTab() {
        this.tabTargets.forEach((tab, index) => {
            const panel = this.tabPanelTargets[index];
            tab.classList.toggle('tab-is-unactive', index !== this.indexValue);
            tab.classList.toggle('tab-is-active', index === this.indexValue);
            panel.classList.toggle('hidden', index !== this.indexValue);
        });
    }
}

const app = Application.start();
app.register('copy-to-clipboard', CopyToClipboardController);
app.register('controls', ControlsController);
app.register('menu', MenuController);
app.register('resize', ResizeController);
app.register('tab', TabController);
