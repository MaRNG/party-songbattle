const BASE = '/api/v1/songbattle';

export interface GameFilterOptions {
    decades: number[];
    genres: Record<string, string>;
    areas: Record<string, string>;
    poolCount: number;
}

export interface GameFilters {
    years?: number[];
    genres?: number[];
    areas?: string[];
    artists?: number[];
}

export interface GameDto {
    code: string;
    hash: string;
    mode: 'solo' | 'robin' | 'all';
}

export interface PlayerDto {
    token: string;
    name: string;
    initials: string;
    color: string;
    role: 'master' | 'player';
}

export interface SessionDto {
    game: GameDto;
    player: PlayerDto;
}

export interface TrackInfoDto {
    trackName: string;
    artistName: string;
}

export interface PlayerStateDto {
    id: number;
    name: string;
    initials: string;
    color: string;
    role: 'master' | 'player';
    score: number;
    streak: number;
    guesses: number;
    connected: boolean;
    isViewer: boolean;
}

export interface GameStateDto {
    code: string;
    hash: string;
    mode: 'solo' | 'robin' | 'all';
    status: 'waiting' | 'playing' | 'finished';
    viewerRole: 'master' | 'player';
    isPlaying: boolean;
    elapsedSeconds: number;
    stepSeconds: number;
    stepIndex: number;
    totalSteps: number;
    trackPosition: number;
    totalTracks: number;
    track: TrackInfoDto | null;
    spotifyTrackId: string | null;
    players: PlayerStateDto[];
}

export interface GuessResultDto {
    correct: boolean;
    atSeconds: number;
    points: number;
    score: number;
    streak: number;
}

class ApiError extends Error {
    constructor(message: string, public status: number)
    {
        super(message);
    }
}

async function request<T>(method: string, path: string, body?: unknown, token?: string): Promise<T> {
    const headers: Record<string, string> = {};

    if (body !== undefined)
    {
        headers['Content-Type'] = 'application/json';
    }

    if (token)
    {
        headers['X-Player-Token'] = token;
    }

    const response = await fetch(BASE + path, {
        method,
        headers,
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    const data = await response.json().catch(() => null);

    if (!response.ok)
    {
        throw new ApiError((data && data.message) || response.statusText, response.status);
    }

    return data as T;
}

function filtersToQuery(filters: GameFilters): string {
    const params = new URLSearchParams();

    (filters.years ?? []).forEach((year) => params.append('years[]', String(year)));
    (filters.genres ?? []).forEach((genre) => params.append('genres[]', String(genre)));
    (filters.areas ?? []).forEach((area) => params.append('areas[]', area));
    (filters.artists ?? []).forEach((artist) => params.append('artists[]', String(artist)));

    const query = params.toString();

    return query === '' ? '' : `?${query}`;
}

export const SongBattleApi = {
    getFilterOptions: (filters: GameFilters) =>
        request<GameFilterOptions>('GET', `/filters${filtersToQuery(filters)}`),

    createGame: (filters: GameFilters, mode: string, name: string) =>
        request<SessionDto>('POST', '/games', { ...filters, mode, name }),

    joinGame: (hash: string, name: string) =>
        request<SessionDto>('POST', `/games/${hash}/join`, { name }),

    getState: (hash: string, token: string) =>
        request<GameStateDto>('GET', `/games/${hash}/state`, undefined, token),

    startGame: (hash: string, token: string) =>
        request<GameStateDto>('POST', `/games/${hash}/start`, {}, token),

    setPlaying: (hash: string, token: string, playing: boolean) =>
        request<GameStateDto>('POST', `/games/${hash}/playback`, { playing }, token),

    skip: (hash: string, token: string) =>
        request<GameStateDto>('POST', `/games/${hash}/skip`, {}, token),

    nextSong: (hash: string, token: string) =>
        request<GameStateDto>('POST', `/games/${hash}/next`, {}, token),

    restart: (hash: string, token: string) =>
        request<GameStateDto>('POST', `/games/${hash}/restart`, {}, token),

    submitGuess: (hash: string, token: string, guess: string) =>
        request<GuessResultDto>('POST', `/games/${hash}/guess`, { guess }, token),
};

export { ApiError };
