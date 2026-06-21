<template>
    <div class="sb-frame" :data-theme="theme" :data-neon="neon">
        <TopBar :t="t" :lang="lang" :theme="theme" @update:lang="onLang" @update:theme="onTheme">
            <template #right>
                <button v-if="session.game.value" class="btn btn-ghost btn-sm" @click="leaveGame">
                    <SbIcon name="X" /> {{ lang === 'cs' ? 'Opustit hru' : 'Leave game' }}
                </button>
            </template>
        </TopBar>

        <div class="page">
            <router-view :t="t" :lang="lang" :session="session" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import TopBar from './components/TopBar.vue';
import SbIcon from './components/SbIcon.vue';
import { useGameSession } from './composables/useGameSession';
import { useSpotifyPlayer } from './composables/useSpotifyPlayer';
import { SB_I18N, type Lang } from './composables/i18n';

const lang = ref<Lang>('cs');
const theme = ref<'dark' | 'light'>('dark');
const neon = ref<'pink' | 'blue' | 'green'>('pink');

const t = computed(() => SB_I18N[lang.value]);

function onLang(value: Lang): void {
    lang.value = value;
}

function onTheme(value: 'dark' | 'light'): void {
    theme.value = value;
}

const session = useGameSession();
const spotify = useSpotifyPlayer();
const route = useRoute();
const router = useRouter();

onMounted(async () => {
    await spotify.handleAuthCallback(router);

    if (route.name === 'game')
    {
        return;
    }

    const restored = await session.restoreFromStorage();

    if (restored && session.game.value)
    {
        router.replace({ name: 'game', params: { hash: session.game.value.hash } });
    }
});

function leaveGame(): void {
    session.clear();
    router.push('/');
}
</script>
