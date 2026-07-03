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
            :result="roundOverlay?.guessResult ?? null"
            :track="roundOverlay?.track ?? null"
            @continue="dismissOverlay"
        />
        <MissedPage
            v-else-if="screen === 'missed'"
            :t="t"
            :session="session"
            :track="roundOverlay?.track ?? null"
            @continue="dismissOverlay"
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
import type { GuessResultDto, TrackInfoDto } from '../api/client';
import { useSpotifyPlayer } from '../composables/useSpotifyPlayer';

const props = defineProps<{ t: Strings; lang: Lang; session: GameSession }>();

const route = useRoute();
const router = useRouter();
const spotify = useSpotifyPlayer();

interface RoundOverlay {
    trackPosition: number;
    correct: boolean;
    guessResult: GuessResultDto | null;
    track: TrackInfoDto | null;
}

const roundOverlay = ref<RoundOverlay | null>(null);

function showOverlay(overlay: RoundOverlay): void {
    roundOverlay.value = overlay;
}

function dismissOverlay(): void {
    roundOverlay.value = null;

    // CorrectPage/MissedPage may have played the just-revealed track to full length,
    // which repoints the Spotify device away from the current round's track. Re-sync
    // it back now, since nothing else will detect this — the `spotifyTrackId` watcher
    // below only fires on a *change*, and this track was already the current one
    // before the overlay played over it.
    const trackId = props.session.state.value?.spotifyTrackId;

    if (props.session.isMaster.value && trackId)
    {
        void spotify.ensureTrackLoaded(trackId, props.session.state.value?.isPlaying ?? false);
    }
}

// Reacts to any `trackPosition` change — from this viewer's own guess, someone else's
// guess, or a master skip/next — regardless of *why* `state` was refreshed. Declared
// before the `spotifyTrackId` watcher below so both run in the same reactivity flush,
// in this order: this sets `roundOverlay` first, so the sync watcher's guard already
// sees it and won't preempt the reveal by loading the next track's audio too early.
watch(() => props.session.state.value?.trackPosition, (newPosition, oldPosition) => {
    if (typeof newPosition !== 'number' || typeof oldPosition !== 'number' || newPosition === oldPosition)
    {
        return;
    }

    // handleGuess() already shows an overlay itself for both a correct guess and a
    // wrong guess that used up the last step — skip re-showing it once state catches
    // up to the trackPosition change that guess triggered.
    const alreadyRevealedForPreviousTrack = roundOverlay.value?.trackPosition === oldPosition;

    if (!alreadyRevealedForPreviousTrack)
    {
        // `state.previousTrack` is computed server-side from the (already advanced)
        // position - 1, and unlike `state.track` it's revealed to every viewer
        // regardless of role/mode, since that round has already concluded.
        showOverlay({
            trackPosition: oldPosition,
            correct: false,
            guessResult: null,
            track: props.session.state.value?.previousTrack ?? null,
        });
    }
});

// Lives here (not in MasterPage) because MasterPage unmounts during the Correct/Missed
// overlay — exactly the window in which a correct guess (or skip's last-step advance)
// changes the track. Comparing against the singleton's own `loadedTrackId` internally
// makes this safe to fire from a freshly (re)mounted GamePage too.
watch(() => props.session.state.value?.spotifyTrackId, (trackId) => {
    if (!trackId || !props.session.isMaster.value)
    {
        return;
    }

    if (roundOverlay.value !== null)
    {
        // The backend already advanced to this track the instant the round ended, but
        // the correct/missed overlay is still showing the track that was just played —
        // don't preempt it by loading the next one onto the device yet. dismissOverlay()
        // syncs to it once the player actually continues.
        return;
    }

    void spotify.ensureTrackLoaded(trackId, props.session.state.value?.isPlaying ?? false);
});

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

const flashMessage = ref<string | null>(null);
let flashTimer: ReturnType<typeof setTimeout> | null = null;

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
    const previousPosition = props.session.state.value?.trackPosition ?? -1;
    const result = await props.session.submitGuess(text);

    if (!result)
    {
        return;
    }

    if (!result.roundOver)
    {
        // Wrong guess, but steps remain — the backend already auto-advanced to the next
        // (longer) step. Flash the miss and let the background refresh pick up the
        // longer snippet; no overlay for this, the player keeps guessing.
        showFlash(props.t.incorrect);
        void props.session.refreshState();

        return;
    }

    // Show the reveal immediately using the guess response's own track info — don't
    // wait on refreshState() for it. `state.value` (and with it `spotifyTrackId`) only
    // changes once that refresh resolves, and the `spotifyTrackId` watcher above reacts
    // to that change on the very next reactivity flush — before this function would
    // otherwise get a chance to set `roundOverlay` and have its guard take effect.
    showOverlay({
        trackPosition: previousPosition,
        correct: result.correct,
        guessResult: result.correct ? result : null,
        track: result.track,
    });

    // Now safe to refresh in the background so `session.state` (trackPosition,
    // spotifyTrackId, isPlaying, ...) catches up to the backend's already-advanced game.
    void props.session.refreshState();
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
    props.session.startPolling();
});

onBeforeUnmount(() => {
    props.session.stopPolling();

    if (flashTimer !== null)
    {
        clearTimeout(flashTimer);
    }
});
</script>
