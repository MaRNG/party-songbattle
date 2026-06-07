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
            <LandingPage
                v-if="screen === 'landing'"
                :t="t"
                :lang="lang"
                @create="localScreen = 'create'"
                @join="handleJoin"
            />
            <CreatePage
                v-else-if="screen === 'create'"
                :t="t"
                :lang="lang"
                @back="localScreen = 'landing'"
                @created="handleCreated"
            />
            <RoomPage
                v-else-if="screen === 'room'"
                :t="t"
                :lang="lang"
                :session="session"
            />
            <MasterPage
                v-else-if="screen === 'play' && session.isMaster.value"
                :t="t"
                :session="session"
            />
            <PlayerPage
                v-else-if="screen === 'play'"
                :t="t"
                :session="session"
                @guess="handleGuess"
            />
            <CorrectPage
                v-else-if="screen === 'correct'"
                :t="t"
                :result="roundOverlay?.guessResult ?? null"
                :track="roundOverlay?.track ?? null"
            />
            <MissedPage
                v-else-if="screen === 'missed'"
                :t="t"
                :track="roundOverlay?.track ?? null"
            />
            <ResultsPage
                v-else-if="screen === 'results'"
                :t="t"
                :session="session"
                @restart="leaveGame"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import TopBar from './components/TopBar.vue';
import SbIcon from './components/SbIcon.vue';
import LandingPage from './pages/LandingPage.vue';
import CreatePage from './pages/CreatePage.vue';
import RoomPage from './pages/RoomPage.vue';
import MasterPage from './pages/MasterPage.vue';
import PlayerPage from './pages/PlayerPage.vue';
import CorrectPage from './pages/CorrectPage.vue';
import MissedPage from './pages/MissedPage.vue';
import ResultsPage from './pages/ResultsPage.vue';
import { useGameSession } from './composables/useGameSession';
import { SB_I18N, type Lang } from './composables/i18n';
import type { GuessResultDto, TrackInfoDto } from './api/client';

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

const localScreen = ref<'landing' | 'create'>('landing');

interface RoundOverlay {
    trackPosition: number;
    correct: boolean;
    guessResult: GuessResultDto | null;
    track: TrackInfoDto | null;
}

const roundOverlay = ref<RoundOverlay | null>(null);
let overlayTimer: ReturnType<typeof setTimeout> | null = null;
let lastSeenTrackPosition = -1;
const previousTrack = ref<TrackInfoDto | null>(null);

function clearOverlayTimer(): void {
    if (overlayTimer !== null)
    {
        clearTimeout(overlayTimer);
        overlayTimer = null;
    }
}

function showOverlay(overlay: RoundOverlay): void {
    roundOverlay.value = overlay;
    clearOverlayTimer();
    overlayTimer = setTimeout(() => {
        roundOverlay.value = null;
    }, 4500);
}

const screen = computed(() => {
    if (roundOverlay.value !== null)
    {
        return roundOverlay.value.correct ? 'correct' : 'missed';
    }

    const state = session.state.value;

    if (state === null)
    {
        return localScreen.value;
    }

    if (state.status === 'waiting')
    {
        return 'room';
    }

    if (state.status === 'playing')
    {
        return 'play';
    }

    return 'results';
});

function observeStateForOverlay(): void {
    const state = session.state.value;

    if (state === null)
    {
        return;
    }

    if (lastSeenTrackPosition === -1)
    {
        lastSeenTrackPosition = state.trackPosition;
        previousTrack.value = state.track;

        return;
    }

    if (state.trackPosition !== lastSeenTrackPosition)
    {
        const wasCorrectForPreviousTrack = roundOverlay.value?.correct === true
            && roundOverlay.value.trackPosition === lastSeenTrackPosition;

        if (!wasCorrectForPreviousTrack)
        {
            showOverlay({
                trackPosition: lastSeenTrackPosition,
                correct: false,
                guessResult: null,
                track: previousTrack.value,
            });
        }

        lastSeenTrackPosition = state.trackPosition;
    }

    previousTrack.value = state.track;
}

let observerInterval: ReturnType<typeof setInterval> | null = null;

function startObserving(): void {
    lastSeenTrackPosition = session.state.value?.trackPosition ?? -1;
    previousTrack.value = session.state.value?.track ?? null;
    session.startPolling();

    if (observerInterval !== null)
    {
        clearInterval(observerInterval);
    }

    observerInterval = setInterval(() => {
        observeStateForOverlay();
    }, 400);
}

function stopObserving(): void {
    session.stopPolling();

    if (observerInterval !== null)
    {
        clearInterval(observerInterval);
        observerInterval = null;
    }
}

onMounted(async () => {
    const restored = await session.restoreFromStorage();

    if (restored)
    {
        startObserving();
    }
});

onBeforeUnmount(() => {
    stopObserving();
    clearOverlayTimer();
});

async function handleCreated(filters: { years?: number[]; genres?: number[]; areas?: string[]; artists?: number[] }, mode: string, name: string): Promise<void> {
    await session.create(filters, mode, name);
    await session.refreshState();
    startObserving();
}

async function handleJoin(hash: string, name: string): Promise<void> {
    await session.join(hash, name);
    await session.refreshState();
    startObserving();
}

async function handleGuess(text: string): Promise<void> {
    const result = await session.submitGuess(text);

    if (result?.correct)
    {
        const state = session.state.value;

        showOverlay({
            trackPosition: state?.trackPosition ?? lastSeenTrackPosition,
            correct: true,
            guessResult: result,
            track: state?.track ?? null,
        });
    }
}

function leaveGame(): void {
    stopObserving();
    session.clear();
    roundOverlay.value = null;
    clearOverlayTimer();
    lastSeenTrackPosition = -1;
    previousTrack.value = null;
    localScreen.value = 'landing';
}
</script>
