import '../scss/main.scss';
import '../vendor/_highlight.css';
import { Application } from '@hotwired/stimulus';
import CopyToClipboardController from './controllers/copy_to_clipboard_controller.js';
import HighlightController from './controllers/highlight_controller.js';
import ControlsController from './controllers/controls_controller.js';
import MenuController from './controllers/menu_controller.js';
import ResizeController from './controllers/resize_controller.js';
import TabController from './controllers/tab_controller.js';

const app = Application.start();
app.register('copy-to-clipboard', CopyToClipboardController);
app.register('highlight', HighlightController);
app.register('controls', ControlsController);
app.register('menu', MenuController);
app.register('resize', ResizeController);
app.register('tab', TabController);