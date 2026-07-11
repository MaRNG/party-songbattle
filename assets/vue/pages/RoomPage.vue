<template>
    <div style="max-width: 920px; margin: 0 auto;">
        <div class="eyebrow">{{ t.room_eyebrow }}</div>
        <h2 class="section-title">{{ t.room_title }}</h2>
        <p class="lead">{{ t.room_share }}</p>

        <div class="grid-2" style="margin-top: 18px;">
            <div class="card card-glow center" style="display: flex; flex-direction: column; align-items: center; gap: 14px; padding: 20px;">
                <div class="uc muted">songbattle.party</div>
                <div class="code-display">{{ code }}</div>
                <div class="qr"><div class="qr-grid" /><div class="corner" /></div>
                <button class="btn btn-ghost btn-sm" @click="copyCode">
                    <SbIcon name="Copy" /> {{ lang === 'cs' ? 'Kopírovat kód' : 'Copy code' }}
                </button>
            </div>

            <div class="card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <strong>{{ players.length }} {{ t.players_connected }}</strong>
                    <span class="pill"><SbIcon name="Users" />{{ players.length }}</span>
                </div>
                <div
                    v-for="player in players"
                    :key="player.id"
                    class="player-row"
                    :class="{ current: player.isViewer }"
                >
                    <div class="av" :style="{ background: player.color }">{{ player.initials }}</div>
                    <div class="name">
                        {{ player.name }}
                        <span v-if="player.role === 'master'" class="pill" style="margin-left: 8px;">
                            <SbIcon name="Crown" />{{ t.master }}
                        </span>
                    </div>
                    <span class="meta">{{ player.connected ? t.ready : t.waiting }}</span>
                    <button
                        v-if="session.isMaster.value && !player.isViewer"
                        class="btn btn-ghost btn-sm"
                        style="margin-left: 8px;"
                        :title="t.kick_btn"
                        @click="kickPlayer(player.id, player.name)"
                    >
                        <SbIcon name="X" />
                    </button>
                </div>

                <div class="divider" />

                <button
                    v-if="session.isMaster.value && !spotify.isAuthenticated.value"
                    class="btn btn-ghost btn-block mt-8"
                    @click="spotify.connect()"
                >
                    <SbIcon name="Disc" /> {{ lang === 'cs' ? 'Připojit Spotify' : 'Connect Spotify' }}
                </button>
                <div v-if="spotify.error.value" class="mono small muted center mt-8">{{ spotify.error.value }}</div>
                <div v-if="startError" class="mono small muted center mt-8">{{ startError }}</div>

                <button v-if="session.isMaster.value" class="btn btn-primary btn-block mt-8" @click="start">
                    <SbIcon name="PlayFill" /> {{ t.start_game }}
                </button>
                <div v-else class="mono small muted center">
                    {{ lang === 'cs' ? 'Čekání, až master spustí hru…' : 'Waiting for the master to start…' }}
                </div>

                <div class="mono small muted center mt-8">
                    {{ lang === 'cs' ? 'Vstoupil jsi jako' : 'You joined as' }}:
                    <strong :style="{ color: 'var(--neon-1)' }">{{ session.isMaster.value ? t.master : (lang === 'cs' ? 'Hráč' : 'Player') }}</strong>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import SbIcon from '../components/SbIcon.vue';
import type { Strings, Lang } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';
import { useSpotifyPlayer } from '../composables/useSpotifyPlayer';
import { ApiError } from '../api/client';

const props = defineProps<{ t: Strings; lang: Lang; session: GameSession }>();

const spotify = useSpotifyPlayer();
const startError = ref<string | null>(null);

const code = computed(() => props.session.game.value?.code ?? '');
const players = computed(() => props.session.state.value?.players ?? []);

async function kickPlayer(playerId: number, name: string): Promise<void> {
    if (!window.confirm(props.t.kick_confirm(name)))
    {
        return;
    }

    await props.session.kickPlayer(playerId).catch(() => undefined);
}

function copyCode(): void {
    if (code.value !== '' && navigator.clipboard)
    {
        navigator.clipboard.writeText(code.value).catch(() => undefined);
    }
}

async function start(): Promise<void> {
    void spotify.activateElement();
    startError.value = null;

    try
    {
        await props.session.startGame();
    }
    catch (error)
    {
        if (error instanceof ApiError && error.status === 422)
        {
            startError.value = props.session.game.value?.mode === 'all'
                ? props.t.all_min_players
                : props.t.robin_min_players;
        }
        else
        {
            startError.value = null;
        }

        return;
    }

    const state = props.session.state.value;

    if (!state?.spotifyTrackId)
    {
        return;
    }

    const ready = await spotify.ensureReady();

    if (!ready)
    {
        spotify.error.value = 'Spotify player not ready (isReady=false) — game started but nothing was sent to Spotify';

        return;
    }

    spotify.playFromStart(state.spotifyTrackId, state.isPlaying);
}
</script>
