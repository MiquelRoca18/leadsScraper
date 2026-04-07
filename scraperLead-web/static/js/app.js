import './bootstrap.js';
import { initProxyStatus } from './modules/proxy-status.js';
import { initSearchForm, isScrapingInProgress } from './modules/search-form.js';
import { initInstagramForm } from './modules/instagram-form.js';
export { toSafeHttpUrl } from './lib/dom-utils.js';

initSearchForm();
initInstagramForm();
initProxyStatus({ isScrapingInProgress });
