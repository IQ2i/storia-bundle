import { Controller, Application } from '@hotwired/stimulus';
import hljs from 'highlight.js/lib/core';
import hljs_twig from 'highlight.js/lib/languages/twig';
import hljs_xml from 'highlight.js/lib/languages/xml';

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

hljs.registerLanguage('xml', hljs_xml);
hljs.registerLanguage('twig', hljs_twig);

class HighlightController extends Controller {
    connect() {
        hljs.highlightElement(this.element);
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
app.register('highlight', HighlightController);
app.register('controls', ControlsController);
app.register('menu', MenuController);
app.register('tab', TabController);
