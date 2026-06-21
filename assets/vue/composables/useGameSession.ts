import { ref, computed, type Ref } from 'vue';
import { SongBattleApi, type GameDto, type GameStateDto, type PlayerDto } from '../api/client';

const STORAGE_KEY = 'sb_session';

interface StoredSession {
    hash: string;
    token: string;
}

function loadStored(): StoredSession | null {
    try
    {
        const raw = localStorage.getItem(STORAGE_KEY);

        return raw ? (JSON.parse(raw) as StoredSession) : null;
    }
    catch
    {
        return null;
    }
}

function storeSession(session: StoredSession | null): void {
    if (session === null)
    {
        localStorage.removeItem(STORAGE_KEY);
    }
    else
    {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
    }
}

export function useGameSession() {
    const game: Ref<GameDto | null> = ref(null);
    const player: Ref<PlayerDto | null> = ref(null);
    const state: Ref<GameStateDto | null> = ref(null);

    let pollHandle: ReturnType<typeof setInterval> | null = null;

    const isMaster = computed(() => player.value?.role === 'master');

    function applySession(g: GameDto, p: PlayerDto): void {
        game.value = g;
        player.value = p;
        storeSession({ hash: g.hash, token: p.token });
    }

    function clear(): void {
        stopPolling();
        game.value = null;
        player.value = null;
        state.value = null;
        storeSession(null);
    }

    async function create(filters: { years?: number[]; genres?: number[]; areas?: string[]; artists?: number[] }, mode: string, name: string): Promise<void> {
        const session = await SongBattleApi.createGame(filters, mode, name);

        applySession(session.game, session.player);
    }

    async function join(hash: string, name: string): Promise<void> {
        const session = await SongBattleApi.joinGame(hash, name);

        applySession(session.game, session.player);
    }

    async function restoreFromStorage(): Promise<boolean> {
        const stored = loadStored();

        if (stored === null)
        {
            return false;
        }

        try
        {
            const fetched = await SongBattleApi.getState(stored.hash, stored.token);

            game.value = { code: fetched.code, hash: fetched.hash, mode: fetched.mode };
            player.value = { token: stored.token, name: '', initials: '', color: '', role: fetched.viewerRole };
            state.value = fetched;

            const me = fetched.players.find((candidate) => candidate.isViewer);

            if (me)
            {
                player.value.name = me.name;
                player.value.initials = me.initials;
                player.value.color = me.color;
            }

            return true;
        }
        catch
        {
            storeSession(null);

            return false;
        }
    }

    async function refreshState(): Promise<GameStateDto | null> {
        if (game.value === null || player.value === null)
        {
            return null;
        }

        const hash = game.value.hash;
        const token = player.value.token;
        const fetched = await SongBattleApi.getState(hash, token);

        if (game.value?.hash !== hash || player.value?.token !== token)
        {
            return null;
        }

        state.value = fetched;

        return state.value;
    }

    function startPolling(intervalMs = 1500): void {
        stopPolling();
        pollHandle = setInterval(() => {
            refreshState().catch(() => undefined);
        }, intervalMs);
    }

    function stopPolling(): void {
        if (pollHandle !== null)
        {
            clearInterval(pollHandle);
            pollHandle = null;
        }
    }

    async function startGame(): Promise<void> {
        if (game.value === null || player.value === null)
        {
            return;
        }

        state.value = await SongBattleApi.startGame(game.value.hash, player.value.token);
    }

    async function setPlaying(playing: boolean): Promise<void> {
        if (game.value === null || player.value === null)
        {
            return;
        }

        state.value = await SongBattleApi.setPlaying(game.value.hash, player.value.token, playing);
    }

    async function skip(): Promise<void> {
        if (game.value === null || player.value === null)
        {
            return;
        }

        state.value = await SongBattleApi.skip(game.value.hash, player.value.token);
    }

    async function nextSong(): Promise<void> {
        if (game.value === null || player.value === null)
        {
            return;
        }

        state.value = await SongBattleApi.nextSong(game.value.hash, player.value.token);
    }

    async function restart(): Promise<void> {
        if (game.value === null || player.value === null)
        {
            return;
        }

        state.value = await SongBattleApi.restart(game.value.hash, player.value.token);
    }

    async function submitGuess(guess: string) {
        if (game.value === null || player.value === null)
        {
            return null;
        }

        return SongBattleApi.submitGuess(game.value.hash, player.value.token, guess);
    }

    return {
        game,
        player,
        state,
        isMaster,
        create,
        join,
        clear,
        restoreFromStorage,
        refreshState,
        startPolling,
        stopPolling,
        startGame,
        setPlaying,
        skip,
        nextSong,
        restart,
        submitGuess,
    };
}

export type GameSession = ReturnType<typeof useGameSession>;
