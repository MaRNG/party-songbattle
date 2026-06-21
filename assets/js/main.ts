import { createApp } from 'vue';
import App from '../vue/App.vue';
import { router } from '../vue/router';
import '../scss/main.scss';

createApp(App).use(router).mount('#app');
