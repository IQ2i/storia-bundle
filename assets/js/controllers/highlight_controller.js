import { Controller } from '@hotwired/stimulus';
import hljs from 'highlight.js/lib/core';
import hljs_twig from 'highlight.js/lib/languages/twig';
import hljs_xml from 'highlight.js/lib/languages/xml';

hljs.registerLanguage('xml', hljs_xml);
hljs.registerLanguage('twig', hljs_twig);

export default class extends Controller {
    connect() {
        hljs.highlightElement(this.element);
    }
}