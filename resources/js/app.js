import './bootstrap';

import Intersect from '@alpinejs/intersect';
import Collapse from '@alpinejs/collapse';
import Swiper from 'swiper';
import { Navigation, Pagination, EffectFade, EffectCreative, Autoplay } from 'swiper/modules';

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Intersect);
    window.Alpine.plugin(Collapse);
});

window.Swiper = Swiper;
window.SwiperModules = { Navigation, Pagination, EffectFade, EffectCreative, Autoplay };
