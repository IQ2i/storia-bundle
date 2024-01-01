import { Controller, Application } from '@hotwired/stimulus';
import hljs from 'highlight.js/lib/core';
import hljs_twig from 'highlight.js/lib/languages/twig';
import hljs_xml from 'highlight.js/lib/languages/xml';

hljs.registerLanguage('xml', hljs_xml);
hljs.registerLanguage('twig', hljs_twig);

class HighlightController extends Controller {
    connect() {
        hljs.highlightElement(this.element);
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
app.register('highlight', HighlightController);
app.register('menu', MenuController);
