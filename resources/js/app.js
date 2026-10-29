import '@fontsource-variable/fraunces/opsz.css';
import '@fontsource-variable/fraunces/opsz-italic.css';
import '@fontsource-variable/hanken-grotesk/index.css';
import Modal from 'bootstrap/js/dist/modal';
import 'bootstrap/js/dist/carousel';

import { initHeader, initReveal, initCounters } from './modules/ui';
import { initClocher } from './modules/clocher';
import { initInfiniteScroll } from './modules/infinite-scroll';
import { initNewsletter } from './modules/newsletter';
import { initShare } from './modules/share';
import { initTeam } from './modules/team';

initHeader();
initReveal();
initCounters();
initClocher();
initInfiniteScroll();
initNewsletter();
initShare();
initTeam(Modal);
