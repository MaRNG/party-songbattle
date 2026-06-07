<template>
    <div v-if="state" style="max-width: 1040px; margin: 0 auto;">
        <div class="eyebrow">{{ t.master_eyebrow }}</div>
        <div style="display: flex; align-items: baseline; justify-content: space-between; margin-top: 4px;">
            <h2 class="section-title">{{ t.song_n_of_n(state.trackPosition + 1, state.totalTracks) }}</h2>
            <span class="pill"><SbIcon name="Music" />{{ t.playing }}</span>
        </div>

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
                        <div class="name">{{ player.name }}</div>
                        <div class="meta">{{ player.score }} {{ t.points }} · {{ player.guesses }}×</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SbIcon from '../components/SbIcon.vue';
import SpotifyCard from '../components/SpotifyCard.vue';
import StepBar from '../components/StepBar.vue';
import { STEPS, type Strings } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';

const props = defineProps<{ t: Strings; session: GameSession }>();

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

const leaderboard = computed(() =>
    [...(state.value?.players ?? [])].sort((a, b) => b.score - a.score),
);

function togglePlaying(): void {
    if (!state.value)
    {
        return;
    }

    props.session.setPlaying(!state.value.isPlaying).catch(() => undefined);
}

function skip(): void {
    props.session.skip().catch(() => undefined);
}

function next(): void {
    props.session.nextSong().catch(() => undefined);
}
</script>
