import { createRouter, createWebHistory } from 'vue-router';
import LandingPage from '../pages/LandingPage.vue';
import CreatePage from '../pages/CreatePage.vue';
import GamePage from '../pages/GamePage.vue';

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'landing', component: LandingPage },
        { path: '/create', name: 'create', component: CreatePage },
        { path: '/game/:hash', name: 'game', component: GamePage },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});
