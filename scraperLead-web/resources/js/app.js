import './bootstrap';
import { initProxyStatus } from './modules/proxy-status';
import { initSearchForm, isScrapingInProgress } from './modules/search-form';
import { initInstagramForm } from './modules/instagram-form';
export { toSafeHttpUrl } from './lib/dom-utils';

initSearchForm();
initInstagramForm();
initProxyStatus({ isScrapingInProgress });
