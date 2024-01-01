import '../css/main.css';
import '../css/vendor/_highlight.css';
import { Application } from '@hotwired/stimulus';
import HighlightController from './controllers/highlight_controller.js';
import MenuController from './controllers/menu_controller.js';

const app = Application.start();
app.register('highlight', HighlightController);
app.register('menu', MenuController);