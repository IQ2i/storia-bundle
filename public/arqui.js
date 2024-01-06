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

class IframeSizeController extends Controller {
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

const app = Application.start();
app.register('copy-to-clipboard', CopyToClipboardController);
app.register('highlight', HighlightController);
app.register('iframe-size', IframeSizeController);
app.register('menu', MenuController);
