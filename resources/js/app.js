import './bootstrap';

import Intersect from '@alpinejs/intersect';
import Collapse from '@alpinejs/collapse';

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Intersect);
    window.Alpine.plugin(Collapse);
});
