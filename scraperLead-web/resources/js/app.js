import './bootstrap';
import { initProxyStatus } from './modules/proxy-status';
import { initSearchForm, isScrapingInProgress } from './modules/search-form';
export { toSafeHttpUrl } from './lib/dom-utils';

initSearchForm();
initProxyStatus({ isScrapingInProgress });
