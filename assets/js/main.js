import '../css/main.css';
import '../css/vendor/_highlight.css';
import { Application } from '@hotwired/stimulus';
import CopyToClipboardController from './controllers/copy_to_clipboard_controller.js';
import HighlightController from './controllers/highlight_controller.js';
import IframeSizeController from './controllers/iframe_size_controller.js';
import MenuController from './controllers/menu_controller.js';

const app = Application.start();
app.register('copy-to-clipboard', CopyToClipboardController);
app.register('highlight', HighlightController);
app.register('iframe-size', IframeSizeController);
app.register('menu', MenuController);