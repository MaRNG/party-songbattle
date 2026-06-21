<template>
    <div v-if="session.state.value">
        <RoomPage
            v-if="screen === 'room'"
            :t="t"
            :lang="lang"
            :session="session"
        />
        <MasterPage
            v-else-if="screen === 'play' && session.isMaster.value"
            :t="t"
            :session="session"
            @guess="handleGuess"
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
            @restart="leave"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import RoomPage from './RoomPage.vue';
import MasterPage from './MasterPage.vue';
import PlayerPage from './PlayerPage.vue';
import CorrectPage from './CorrectPage.vue';
import MissedPage from './MissedPage.vue';
import ResultsPage from './ResultsPage.vue';
import type { Strings, Lang } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';
import type { GuessResultDto, TrackInfoDto } from '../api/client';

const props = defineProps<{ t: Strings; lang: Lang; session: GameSession }>();

const route = useRoute();
const router = useRouter();

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

    const state = props.session.state.value;

    if (state === null || state.status === 'waiting')
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
    const state = props.session.state.value;

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
    lastSeenTrackPosition = props.session.state.value?.trackPosition ?? -1;
    previousTrack.value = props.session.state.value?.track ?? null;
    props.session.startPolling();

    if (observerInterval !== null)
    {
        clearInterval(observerInterval);
    }

    observerInterval = setInterval(() => {
        observeStateForOverlay();
    }, 400);
}

function stopObserving(): void {
    props.session.stopPolling();

    if (observerInterval !== null)
    {
        clearInterval(observerInterval);
        observerInterval = null;
    }
}

async function handleGuess(text: string): Promise<void> {
    const result = await props.session.submitGuess(text);

    if (result?.correct)
    {
        const state = props.session.state.value;

        showOverlay({
            trackPosition: state?.trackPosition ?? lastSeenTrackPosition,
            correct: true,
            guessResult: result,
            track: state?.track ?? null,
        });
    }
}

function leave(): void {
    props.session.clear();
    router.push('/');
}

onMounted(async () => {
    const hash = route.params.hash as string;

    if (props.session.game.value === null)
    {
        const restored = await props.session.restoreFromStorage();

        if (!restored)
        {
            router.replace('/');

            return;
        }
    }

    if (props.session.game.value?.hash !== hash)
    {
        router.replace('/');

        return;
    }

    await props.session.refreshState();
    startObserving();
});

onBeforeUnmount(() => {
    stopObserving();
    clearOverlayTimer();
});
</script>
