<template>
    <div v-if="state" style="max-width: 1040px; margin: 0 auto;">
        <div class="eyebrow">{{ t.master_eyebrow }}</div>
        <div style="display: flex; align-items: baseline; justify-content: space-between; margin-top: 4px;">
            <h2 class="section-title">{{ t.song_n_of_n(state.trackPosition + 1, state.totalTracks) }}</h2>
            <span class="pill"><SbIcon name="Music" />{{ t.playing }}</span>
        </div>

        <button
            v-if="!spotify.isAuthenticated.value"
            class="btn btn-ghost btn-sm mt-8"
            @click="spotify.connect()"
        >
            <SbIcon name="Disc" /> Connect Spotify
        </button>
        <div v-if="spotify.error.value" class="mono small muted mt-8">{{ spotify.error.value }}</div>

        <div class="grid-2" style="margin-top: 14px;">
            <div class="col">
                <SpotifyCard
                    v-if="state.track"
                    :track-name="state.track.trackName"
                    :artist-name="state.track.artistName"
                >
                    <template #tags>
                        <span class="tag">{{ t.song_n_of_n(state.trackPosition + 1, state.totalTracks) }}</span>
                    </template>
                    <template #controls>
                        <button class="spfy-btn" @click="restart"><SbIcon name="Restart" /></button>
                        <button class="spfy-btn" @click="skip"><SbIcon name="SkipForward" /></button>
                        <button class="spfy-btn play" @click="togglePlaying">
                            <SbIcon :name="state.isPlaying ? 'Pause' : 'PlayFill'" />
                        </button>
                    </template>
                </SpotifyCard>

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

                <div v-if="state.mode === 'solo'">
                    <div class="mono uc muted" style="margin-bottom: 8px;">{{ t.your_guess }}</div>
                    <div style="position: relative;">
                        <input
                            v-model="guess"
                            class="input"
                            :placeholder="t.type_a_song"
                            style="padding-right: 100px; font-size: 16px;"
                            @keydown.enter="submitGuess"
                        />
                        <button
                            class="btn btn-primary btn-sm"
                            :disabled="!guess.trim()"
                            style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%);"
                            @click="submitGuess"
                        >
                            <SbIcon name="Send" /> {{ t.submit }}
                        </button>
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
                        <div class="name">{{ player.name }}</div>
                        <div class="meta">{{ player.score }} {{ t.points }} · {{ player.guesses }}×</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import SbIcon from '../components/SbIcon.vue';
import SpotifyCard from '../components/SpotifyCard.vue';
import StepBar from '../components/StepBar.vue';
import { STEPS, type Strings } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';
import { useSpotifyPlayer } from '../composables/useSpotifyPlayer';

const props = defineProps<{ t: Strings; session: GameSession }>();

const emit = defineEmits<{
    (e: 'guess', text: string): void;
}>();

const spotify = useSpotifyPlayer();

const state = computed(() => props.session.state.value);

const guess = ref('');

function syncSpotifyIfTrackChanged(previousTrackId: string | null): void {
    const newTrackId = state.value?.spotifyTrackId ?? null;

    if (newTrackId && newTrackId !== previousTrackId && spotify.isReady.value)
    {
        spotify.playFromStart(newTrackId, state.value!.isPlaying);
    }
}

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

const leaderboard = computed(() =>
    [...(state.value?.players ?? [])].sort((a, b) => b.score - a.score),
);

function togglePlaying(): void {
    if (!state.value)
    {
        return;
    }

    const playing = !state.value.isPlaying;

    props.session.setPlaying(playing).then(() => {
        if (spotify.isReady.value)
        {
            playing ? spotify.resume() : spotify.pause();
        }
    }).catch(() => undefined);
}

function skip(): void {
    const previousTrackId = state.value?.spotifyTrackId ?? null;

    props.session.skip().then(() => syncSpotifyIfTrackChanged(previousTrackId)).catch(() => undefined);
}

function restart(): void {
    props.session.restart().then(() => {
        if (state.value?.spotifyTrackId && spotify.isReady.value)
        {
            spotify.playFromStart(state.value.spotifyTrackId, state.value.isPlaying);
        }
    }).catch(() => undefined);
}

function next(): void {
    const previousTrackId = state.value?.spotifyTrackId ?? null;

    props.session.nextSong().then(() => syncSpotifyIfTrackChanged(previousTrackId)).catch(() => undefined);
}

function submitGuess(): void {
    const text = guess.value.trim();

    if (text === '')
    {
        return;
    }

    emit('guess', text);
    guess.value = '';
}
</script>
