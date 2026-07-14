<template>
    <div v-if="state" style="max-width: 1040px; margin: 0 auto;">
        <div class="eyebrow">{{ t.master_eyebrow }}</div>
        <div style="display: flex; align-items: baseline; justify-content: space-between; margin-top: 4px;">
            <h2 class="section-title">{{ t.song_n_of_n(state.trackPosition + 1, state.totalTracks) }}</h2>
            <span class="pill"><SbIcon name="Music" />{{ t.playing }}</span>
        </div>

        <div v-if="playback.error.value" class="mono small muted mt-8">{{ playback.error.value }}</div>

        <!-- Solo mode: master is also the one guessing — keep the input above the
        player/song visualization so it's the first thing on screen on mobile. -->
        <div v-if="state.mode === 'solo'" class="mt-12">
            <div class="mono uc muted" style="margin-bottom: 8px;">{{ t.your_guess }}</div>
            <GuessInput :t="t" :session="session" @guess="submitGuess" />
        </div>

        <div class="grid-2" style="margin-top: 14px;">
            <div class="col">
                <TrackCard
                    v-if="state.track"
                    :track-name="state.track.trackName"
                    :artist-name="state.track.artistName"
                >
                    <template #tags>
                        <span class="tag">{{ t.song_n_of_n(state.trackPosition + 1, state.totalTracks) }}</span>
                    </template>
                    <template #controls>
                        <button class="track-btn" @click="restart"><SbIcon name="Restart" /></button>
                        <button class="track-btn" @click="skip"><SbIcon name="SkipForward" /></button>
                        <button class="track-btn play" @click="togglePlaying">
                            <SbIcon :name="state.isPlaying ? 'Pause' : 'PlayFill'" />
                        </button>
                    </template>
                </TrackCard>

                <div class="card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                        <div>
                            <div class="mono uc muted">{{ t.elapsed }}</div>
                            <div class="timer" style="color: var(--neon-1);">
                                {{ state.elapsedSeconds.toFixed(1) }}<span class="small" style="margin-left: 6px;">/ {{ stepLimit }}{{ t.seconds }}</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="mono uc muted">{{ t.current_step }}</div>
                            <div class="timer">{{ stepLimit }}{{ t.seconds }}</div>
                        </div>
                    </div>

                    <StepBar :steps="STEPS" :current-idx="state.stepIndex" :t="t" :fill-percent="fillPercent" />

                    <div class="row" style="gap: 10px; margin-top: 16px;">
                        <button class="btn btn-ghost flex-1" @click="restart">
                            <SbIcon name="Restart" /> {{ t.restart_btn }}
                        </button>
                        <button class="btn btn-ghost flex-1" @click="skip">
                            <SbIcon name="SkipForward" /> {{ t.skip_btn }}
                        </button>
                        <button class="btn btn-danger flex-1" @click="next">{{ t.next_song }} →</button>
                    </div>
                    <div class="mono small muted center" style="margin-top: 8px;">
                        {{ nextStepLabel }}
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <strong>{{ t.leaderboard }}</strong>
                        <span class="mono small muted">{{ state.players.length }} {{ t.players_connected }}</span>
                    </div>
                    <div v-if="leaderboard.length === 0" class="muted">{{ t.nobody_yet }}</div>
                    <div
                        v-for="(player, index) in leaderboard"
                        :key="player.id"
                        class="player-row"
                        :class="{ guessed: index === 0 && player.score > 0 }"
                    >
                        <div class="mono small" style="width: 18px; color: var(--dim);">{{ index + 1 }}</div>
                        <div class="av" :style="{ background: player.color }">{{ player.initials }}</div>
                        <div class="name">
                            {{ player.name }}
                            <span v-if="player.isCurrentTurn" class="pill live" style="margin-left: 8px;">
                                <span class="dot" />{{ t.on_turn }}
                            </span>
                            <span v-if="state.mode === 'all' && player.answeredCorrectly" class="pill live" style="margin-left: 8px;">✓</span>
                            <span v-else-if="state.mode === 'all' && player.hasPassed" class="mono small muted" style="margin-left: 8px;">{{ t.pass_btn }}</span>
                            <span v-else-if="state.mode === 'all' && player.attemptsRemaining !== null" class="mono small muted" style="margin-left: 8px;">
                                {{ t.attempts_remaining(player.attemptsRemaining) }}
                            </span>
                        </div>
                        <div class="meta">
                            {{ player.score }} {{ t.points }} · {{ player.guesses }}×
                            <button class="btn btn-ghost btn-sm" :title="t.edit_score_prompt(player.name)" @click="editScore(player)">✎</button>
                            <button v-if="!player.isViewer" class="btn btn-ghost btn-sm" :title="t.kick_btn" @click="kickPlayer(player.id, player.name)">
                                <SbIcon name="X" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from 'vue';
import SbIcon from '../components/SbIcon.vue';
import GuessInput from '../components/GuessInput.vue';
import TrackCard from '../components/TrackCard.vue';
import StepBar from '../components/StepBar.vue';
import { STEPS, type Strings } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';
import { useLocalAudioPlayer } from '../composables/useLocalAudioPlayer';

const props = defineProps<{ t: Strings; session: GameSession }>();

const emit = defineEmits<{
    (e: 'guess', trackId: number): void;
}>();

const playback = useLocalAudioPlayer();

const state = computed(() => props.session.state.value);

const stepLimit = computed(() => STEPS[state.value?.stepIndex ?? 0]);

const fillPercent = computed(() => {
    if (!state.value)
    {
        return null;
    }

    return Math.min(100, (state.value.elapsedSeconds / stepLimit.value) * 100);
});

const nextStepLabel = computed(() => {
    const index = state.value?.stepIndex ?? 0;

    if (index < STEPS.length - 1)
    {
        return `${props.t.next_step_in} ${STEPS[index + 1]}${props.t.seconds}`;
    }

    return '—';
});

let watchdogHandle: ReturnType<typeof setInterval> | null = null;

function checkSnippetLimit(): void {
    // Gate on the <audio> element's own playing state, not `state.value.isPlaying` —
    // that flag comes from the backend's wall-clock timer and only updates once per
    // ~1.5s poll. If that clock crosses the limit and flips it to false before this
    // position-based check catches up, gating on it here would exit early and never
    // re-arm, leaving the real audio playing forever. The element's own state is the
    // ground truth for whether audio is actually still going.
    if (!playback.isPlaying.value)
    {
        return;
    }

    if (playback.getEstimatedPositionMs() >= stepLimit.value * 1000)
    {
        playback.pause();

        if (state.value?.isPlaying)
        {
            props.session.setPlaying(false).catch(() => undefined);
        }
    }
}

onMounted(() => {
    watchdogHandle = setInterval(checkSnippetLimit, 150);
});

onBeforeUnmount(() => {
    if (watchdogHandle !== null)
    {
        clearInterval(watchdogHandle);
        watchdogHandle = null;
    }
});

const leaderboard = computed(() =>
    [...(state.value?.players ?? [])].sort((a, b) => b.score - a.score),
);

async function togglePlaying(): Promise<void> {
    if (!state.value)
    {
        return;
    }

    void playback.activateElement();

    const playing = !state.value.isPlaying;

    try
    {
        await props.session.setPlaying(playing);
    }
    catch
    {
        return;
    }

    if (playing)
    {
        playback.resume();
    }
    else
    {
        playback.pause();
    }
}

async function skip(): Promise<void> {
    void playback.activateElement();

    // No explicit sync here — if this advances to a new track (last step -> next song),
    // GamePage's global watcher picks it up. If it's just a step increment within the
    // same track, there's nothing to sync.
    await props.session.skip().catch(() => undefined);
}

async function restart(): Promise<void> {
    void playback.activateElement();

    try
    {
        await props.session.restart();
    }
    catch (err)
    {
        playback.error.value = `Game restart request failed: ${err instanceof Error ? err.message : String(err)}`;

        return;
    }

    const hash = props.session.game.value?.hash;
    const token = props.session.player.value?.token;
    const audioTrackId = state.value?.audioTrackId;

    if (!hash || !token || audioTrackId == null)
    {
        playback.error.value = 'No audio track id for the current song — nothing to restart';

        return;
    }

    playback.playFromStart(hash, token, audioTrackId, state.value?.isPlaying ?? false);
}

async function next(): Promise<void> {
    void playback.activateElement();

    // GamePage's global watcher handles the sync once this resolves.
    await props.session.nextSong().catch(() => undefined);
}

function submitGuess(trackId: number): void {
    emit('guess', trackId);
}

async function editScore(player: { id: number; name: string; score: number }): Promise<void> {
    const input = window.prompt(props.t.edit_score_prompt(player.name), String(player.score));

    if (input === null)
    {
        return;
    }

    const parsed = Number.parseInt(input, 10);

    if (Number.isNaN(parsed))
    {
        return;
    }

    await props.session.setPlayerScore(player.id, parsed).catch(() => undefined);
}

async function kickPlayer(playerId: number, name: string): Promise<void> {
    if (!window.confirm(props.t.kick_confirm(name)))
    {
        return;
    }

    await props.session.kickPlayer(playerId).catch(() => undefined);
}
</script>
