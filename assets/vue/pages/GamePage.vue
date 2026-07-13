<template>
    <div v-if="session.state.value">
        <div v-if="flashMessage" class="sb-toast">{{ flashMessage }}</div>

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
            :session="session"
            :result="session.state.value.roundResult"
            :track="session.state.value.previousTrack"
            @continue="continueRound"
        />
        <MissedPage
            v-else-if="screen === 'missed'"
            :t="t"
            :session="session"
            :track="session.state.value.previousTrack"
            @continue="continueRound"
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import RoomPage from './RoomPage.vue';
import MasterPage from './MasterPage.vue';
import PlayerPage from './PlayerPage.vue';
import CorrectPage from './CorrectPage.vue';
import MissedPage from './MissedPage.vue';
import ResultsPage from './ResultsPage.vue';
import type { Strings, Lang } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';
import { useLocalAudioPlayer } from '../composables/useLocalAudioPlayer';

const props = defineProps<{ t: Strings; lang: Lang; session: GameSession }>();

const route = useRoute();
const router = useRouter();
const playback = useLocalAudioPlayer();

async function syncPlayback(audioTrackId: number | null, shouldBePlaying: boolean): Promise<void> {
    const hash = props.session.game.value?.hash;
    const token = props.session.player.value?.token;

    if (audioTrackId == null || !hash || !token)
    {
        return;
    }

    await playback.ensureTrackLoaded(hash, token, audioTrackId, shouldBePlaying);
}

// The Correct/Missed reveal is entirely server-driven via `state.roundResult` (set the
// instant a round ends, cleared only by the master's continueRound() call) — so every
// viewer, not just whoever guessed, sees the same outcome, and screen transitions happen
// in lockstep with polled/refreshed state instead of local per-viewer bookkeeping.
const screen = computed(() => {
    const state = props.session.state.value;

    if (state === null || state.status === 'waiting')
    {
        return 'room';
    }

    if (state.roundResult !== null)
    {
        // In every other mode there's only one guesser per round, so "did anyone get
        // it" and "did I get it" are the same question — `roundResult.correct` alone is
        // enough. In ALL mode several players answer independently, and `roundResult`
        // only ever carries the single fastest correct guess — a player who personally
        // missed (wrong guess, or ran out of attempts) must still see "missed" even
        // though someone else won the round. The master has no guess of their own, so
        // they keep seeing the shared/global outcome.
        if (state.mode === 'all' && !props.session.isMaster.value)
        {
            const me = state.players.find((player) => player.isViewer);

            return me?.answeredCorrectly ? 'correct' : 'missed';
        }

        return state.roundResult.correct ? 'correct' : 'missed';
    }

    if (state.status === 'playing')
    {
        return 'play';
    }

    return 'results';
});

// Lives here (not in MasterPage) because MasterPage unmounts during the Correct/Missed
// overlay — exactly the window in which a correct guess (or skip's last-step advance)
// changes the track. Comparing against the singleton's own loaded-track bookkeeping
// internally makes this safe to fire from a freshly (re)mounted GamePage too.
watch(
    () => props.session.state.value?.audioTrackId ?? null,
    (audioTrackId) => {
        if (audioTrackId == null || !props.session.isMaster.value)
        {
            return;
        }

        if (props.session.state.value?.roundResult !== null)
        {
            // The backend already advanced to this track the instant the round ended, but
            // the Correct/Missed reveal is still showing the track that was just played —
            // don't preempt it by loading the next one onto the device yet. continueRound()
            // syncs to it once the master actually continues.
            return;
        }

        void syncPlayback(audioTrackId, props.session.state.value?.isPlaying ?? false);
    },
);

// ALL mode clears the reveal automatically (server-timed), with no click on
// continueRound() to trigger a resync — `audioTrackId` itself already changed the
// instant the round ended (well before this), so that watcher above won't refire on
// its own. Catch the reveal->null transition directly instead, mirroring what
// continueRound() does manually for the master-click case elsewhere.
watch(() => props.session.state.value?.roundResult, (result, previous) => {
    if (result !== null || previous === undefined || previous === null || !props.session.isMaster.value)
    {
        return;
    }

    void syncPlayback(props.session.state.value?.audioTrackId ?? null, props.session.state.value?.isPlaying ?? false);
});

const flashMessage = ref<string | null>(null);
let flashTimer: ReturnType<typeof setTimeout> | null = null;

watch(() => props.session.kicked.value, (isKicked) => {
    if (!isKicked)
    {
        return;
    }

    window.alert(props.t.kicked_message);
    router.push('/');
});

function showFlash(message: string): void {
    flashMessage.value = message;

    if (flashTimer !== null)
    {
        clearTimeout(flashTimer);
    }

    flashTimer = setTimeout(() => {
        flashMessage.value = null;
        flashTimer = null;
    }, 2000);
}

async function handleGuess(text: string): Promise<void> {
    const result = await props.session.submitGuess(text);

    if (!result)
    {
        return;
    }

    if (!result.roundOver)
    {
        // Wrong guess, but steps remain — the backend already auto-advanced to the next
        // (longer) step. Flash the miss; the player keeps guessing.
        showFlash(props.t.incorrect);
    }

    // Refresh so `session.state` (roundResult, trackPosition, audioTrackId, ...)
    // catches up to the backend's already-applied outcome — this is what actually
    // flips `screen` to 'correct'/'missed' for a round-ending guess, for every viewer
    // polling this same shared state, not just this one.
    await props.session.refreshState();
}

async function continueRound(): Promise<void> {
    if (!props.session.isMaster.value)
    {
        return;
    }

    await props.session.continueRound();

    // CorrectPage/MissedPage may have played the just-revealed track to full length,
    // which repoints playback away from the current round's track. Re-sync it back now
    // — the track-id watcher above won't fire on its own here, since that id already
    // changed (and was ignored) the instant the round ended, well before this continue
    // click.
    void syncPlayback(props.session.state.value?.audioTrackId ?? null, props.session.state.value?.isPlaying ?? false);
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

    // ALL mode's timing is authoritative server-side either way (see GameSessionManager),
    // so tighter client polling buys nothing there — a slightly slower interval is fine.
    props.session.startPolling(props.session.state.value?.mode === 'all' ? 2000 : 1500);
});

onBeforeUnmount(() => {
    props.session.stopPolling();

    if (flashTimer !== null)
    {
        clearTimeout(flashTimer);
    }
});
</script>
