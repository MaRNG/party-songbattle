<template>
    <div v-if="result" style="max-width: 720px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
            <div class="eyebrow">{{ t.correct_eyebrow }}</div>
            <span v-if="result.points !== null" class="mono small muted">+{{ result.points }} {{ t.points }}</span>
        </div>

        <div class="card card-glow" style="position: relative; overflow: hidden; text-align: center; padding: 32px 28px;">
            <div aria-hidden style="position: absolute; top: 92px; left: 50%; width: 0; height: 0; pointer-events: none;">
                <span
                    v-for="(angle, index) in confettiAngles"
                    :key="index"
                    class="sb-confetti"
                    :style="{
                        '--ang': angle + 'deg',
                        background: index % 3 === 0 ? 'var(--neon-1)' : index % 3 === 1 ? 'var(--neon-2)' : 'var(--neon-3, var(--neon-1))',
                        animationDelay: (index * 0.12) + 's',
                    }"
                />
            </div>

            <div aria-hidden style="position: absolute; top: 92px; left: 50%; width: 0; height: 0; pointer-events: none;">
                <div class="sb-ring" style="position: absolute; top: 0; left: 0; width: 120px; height: 120px; border-radius: 50%; border: 2px solid var(--neon-1);" />
                <div class="sb-ring sb-ring-2" style="position: absolute; top: 0; left: 0; width: 120px; height: 120px; border-radius: 50%; border: 2px solid var(--neon-1);" />
                <div class="sb-ring sb-ring-3" style="position: absolute; top: 0; left: 0; width: 120px; height: 120px; border-radius: 50%; border: 2px solid var(--neon-1);" />
            </div>

            <div class="sb-bubble" style="position: relative; display: inline-flex; width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, var(--neon-1), var(--neon-3, var(--neon-2))); align-items: center; justify-content: center; color: var(--inv); margin-bottom: 18px;">
                <svg viewBox="0 0 24 24" width="72" height="72" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="5 12.5 10 17.5 19 7" />
                </svg>
            </div>

            <div class="sb-reveal eyebrow" style="justify-content: center; display: inline-flex;">
                {{ isOwnGuess ? t.correct_sub : t.correct_sub_other(result.guesserName ?? '?') }}
            </div>
            <h1 class="sb-reveal display" style="font-size: 40px; margin: 8px 0 4px;">{{ t.correct_title }}</h1>

            <div v-if="track" class="sb-reveal-2" style="margin-top: 20px;">
                <SpotifyCard :track-name="track.trackName" :artist-name="track.artistName">
                    <template v-if="canPlayFullTrack" #controls>
                        <button class="spfy-btn play" @click="toggleFullTrack">
                            <SbIcon :name="spotify.isPlaying.value ? 'Pause' : 'PlayFill'" />
                        </button>
                    </template>
                </SpotifyCard>
            </div>
        </div>

        <!-- These numbers (time/points/streak/score) belong to whoever's guess this
        reveal is about — only render them when that's actually the viewer themselves.
        Otherwise (spectating master, or in ALL mode a player who also got it right but
        wasn't the fastest) the header above already says whose win this was; showing
        someone else's stats under a "you"-labeled row would just be misleading. -->
        <div v-if="isOwnGuess" class="row mt-12" style="gap: 12px;">
            <div class="stat"><div class="label">{{ t.you_guessed_at }}</div><div class="value" style="color: var(--neon-1);">{{ result.atSeconds }}<span class="unit">s</span></div></div>
            <div class="stat"><div class="label">{{ t.earned }}</div><div class="value">+{{ result.points }}<span class="unit">{{ t.points }}</span></div></div>
            <div class="stat"><div class="label">{{ t.streak }}</div><div class="value">{{ result.streak }}<span class="unit">×</span></div></div>
            <div class="stat"><div class="label">{{ t.total_songs }}</div><div class="value">{{ result.score }}<span class="unit">{{ t.points }}</span></div></div>
        </div>

        <button v-if="session.isMaster.value" class="btn btn-primary btn-block mt-12" @click="emit('continue')">
            {{ t.continue_btn }} →
        </button>
        <div v-else-if="session.state.value?.mode === 'all' && autoContinueSeconds !== null" class="mono small muted center mt-12">
            {{ t.auto_advance_hint(autoContinueSeconds) }}
        </div>
        <div v-else class="mono small muted center mt-12">{{ t.waiting_master }}</div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SpotifyCard from '../components/SpotifyCard.vue';
import SbIcon from '../components/SbIcon.vue';
import { type Strings } from '../composables/i18n';
import type { RoundResultDto, TrackInfoDto } from '../api/client';
import type { GameSession } from '../composables/useGameSession';
import { useFullTrackPlayback } from '../composables/useFullTrackPlayback';

const props = defineProps<{
    t: Strings;
    result: RoundResultDto | null;
    track: TrackInfoDto | null;
    session: GameSession;
}>();

const emit = defineEmits<{
    (e: 'continue'): void;
}>();

const confettiAngles = [0, 45, 90, 135, 180, 225, 270, 315];

const isOwnGuess = computed(() =>
    props.result?.guesserName !== undefined && props.result?.guesserName === props.session.player.value?.name,
);

const autoContinueSeconds = computed(() => {
    const seconds = props.session.state.value?.revealAutoContinueInSeconds;

    return seconds === null || seconds === undefined ? null : Math.ceil(seconds);
});

const trackRef = computed(() => props.track);
const { spotify, canPlayFullTrack, toggleFullTrack } = useFullTrackPlayback(trackRef, props.session);
</script>
