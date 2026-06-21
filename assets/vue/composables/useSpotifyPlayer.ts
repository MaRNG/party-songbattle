import { ref } from 'vue';
import type { Router } from 'vue-router';

const AUTH_STORAGE_KEY = 'sb_spotify_auth';
const PKCE_STORAGE_KEY = 'sb_spotify_pkce';
const SCOPES = 'streaming user-read-email user-read-private user-modify-playback-state user-read-playback-state';
const SPOTIFY_API_BASE = 'https://api.spotify.com/v1';

interface SpotifyConfig {
    clientId: string;
    redirectUri: string;
}

interface SpotifyTokenResponse {
    access_token: string;
    expires_in: number;
    refresh_token?: string;
}

interface StoredAuth {
    access_token: string;
    refresh_token: string;
    expires_at: number;
}

const isAuthenticated = ref(false);
const isReady = ref(false);
const deviceId = ref<string | null>(null);
const error = ref<string | null>(null);
const isPlaying = ref(false);

let player: Spotify.Player | null = null;
let sdkLoadPromise: Promise<void> | null = null;

function loadStoredAuth(): StoredAuth | null {
    try
    {
        const raw = localStorage.getItem(AUTH_STORAGE_KEY);

        return raw ? (JSON.parse(raw) as StoredAuth) : null;
    }
    catch
    {
        return null;
    }
}

function storeAuth(tokens: SpotifyTokenResponse, fallbackRefreshToken?: string): void {
    const stored: StoredAuth = {
        access_token: tokens.access_token,
        refresh_token: tokens.refresh_token ?? fallbackRefreshToken ?? '',
        expires_at: Date.now() + tokens.expires_in * 1000,
    };

    localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(stored));
}

function clearAuth(): void {
    localStorage.removeItem(AUTH_STORAGE_KEY);
    isAuthenticated.value = false;
}

async function fetchSpotifyConfig(): Promise<SpotifyConfig> {
    const response = await fetch('/api/v1/spotify/config');

    return response.json() as Promise<SpotifyConfig>;
}

async function exchangeToken(params: URLSearchParams): Promise<SpotifyTokenResponse> {
    const response = await fetch('https://accounts.spotify.com/api/token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString(),
    });

    if (!response.ok)
    {
        throw new Error('Spotify token exchange failed');
    }

    return response.json() as Promise<SpotifyTokenResponse>;
}

function base64UrlEncode(bytes: Uint8Array): string {
    let str = '';

    for (const byte of bytes)
    {
        str += String.fromCharCode(byte);
    }

    return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function generateRandomString(length: number): string {
    const bytes = new Uint8Array(length);
    crypto.getRandomValues(bytes);

    return base64UrlEncode(bytes).slice(0, length);
}

async function sha256(input: string): Promise<Uint8Array> {
    const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(input));

    return new Uint8Array(digest);
}

async function ensureFreshToken(): Promise<string | null> {
    const stored = loadStoredAuth();

    if (stored === null)
    {
        return null;
    }

    if (Date.now() < stored.expires_at - 60_000)
    {
        return stored.access_token;
    }

    try
    {
        const config = await fetchSpotifyConfig();
        const tokens = await exchangeToken(new URLSearchParams({
            grant_type: 'refresh_token',
            refresh_token: stored.refresh_token,
            client_id: config.clientId,
        }));

        storeAuth(tokens, stored.refresh_token);

        return tokens.access_token;
    }
    catch
    {
        clearAuth();

        return null;
    }
}

function loadSdkScript(): Promise<void> {
    if (sdkLoadPromise !== null)
    {
        return sdkLoadPromise;
    }

    sdkLoadPromise = new Promise((resolve) => {
        window.onSpotifyWebPlaybackSDKReady = () => resolve();

        const script = document.createElement('script');
        script.src = 'https://sdk.scdn.co/spotify-player.js';
        script.async = true;
        document.head.appendChild(script);
    });

    return sdkLoadPromise;
}

async function loadSdkAndCreatePlayer(): Promise<void> {
    if (player !== null)
    {
        return;
    }

    await loadSdkScript();

    player = new window.Spotify.Player({
        name: 'Party Songbattle',
        getOAuthToken: (callback) => {
            ensureFreshToken().then((token) => callback(token ?? ''));
        },
        volume: 0.8,
    });

    player.addListener('ready', ({ device_id }) => {
        deviceId.value = device_id;
        isReady.value = true;
    });

    player.addListener('not_ready', () => {
        isReady.value = false;
    });

    player.addListener('player_state_changed', (state) => {
        if (state)
        {
            isPlaying.value = !state.paused;
        }
    });

    player.addListener('initialization_error', ({ message }) => { error.value = message; });
    player.addListener('authentication_error', ({ message }) => { error.value = message; clearAuth(); });
    player.addListener('account_error', ({ message }) => { error.value = `Spotify Premium required: ${message}`; });
    player.addListener('playback_error', ({ message }) => { error.value = message; });

    await player.connect();
}

async function connect(): Promise<void> {
    const verifier = generateRandomString(64);
    const challenge = base64UrlEncode(await sha256(verifier));
    const state = generateRandomString(32);

    sessionStorage.setItem(PKCE_STORAGE_KEY, JSON.stringify({ verifier, state }));

    const config = await fetchSpotifyConfig();

    const params = new URLSearchParams({
        client_id: config.clientId,
        response_type: 'code',
        redirect_uri: config.redirectUri,
        code_challenge_method: 'S256',
        code_challenge: challenge,
        state,
        scope: SCOPES,
    });

    window.location.assign(`https://accounts.spotify.com/authorize?${params.toString()}`);
}

async function handleAuthCallback(router: Router): Promise<boolean> {
    const url = new URL(window.location.href);
    const code = url.searchParams.get('code');
    const returnedState = url.searchParams.get('state');

    if (code === null)
    {
        return false;
    }

    const cleanup = (): void => {
        router.replace(url.pathname + url.hash).catch(() => undefined);
    };

    const storedRaw = sessionStorage.getItem(PKCE_STORAGE_KEY);
    sessionStorage.removeItem(PKCE_STORAGE_KEY);

    if (storedRaw === null)
    {
        error.value = 'Spotify login failed (missing PKCE state)';
        cleanup();

        return false;
    }

    const stored = JSON.parse(storedRaw) as { verifier: string; state: string };

    if (returnedState !== stored.state)
    {
        error.value = 'Spotify login failed (state mismatch)';
        cleanup();

        return false;
    }

    try
    {
        const config = await fetchSpotifyConfig();
        const tokens = await exchangeToken(new URLSearchParams({
            grant_type: 'authorization_code',
            code,
            redirect_uri: config.redirectUri,
            client_id: config.clientId,
            code_verifier: stored.verifier,
        }));

        storeAuth(tokens);
        isAuthenticated.value = true;
        void loadSdkAndCreatePlayer();

        return true;
    }
    catch (err)
    {
        error.value = err instanceof Error ? err.message : 'Spotify login failed';

        return false;
    }
    finally
    {
        cleanup();
    }
}

async function spotifyFetch(path: string, init: RequestInit = {}): Promise<Response> {
    const token = await ensureFreshToken();

    if (token === null)
    {
        error.value = 'Not connected to Spotify';
        throw new Error('Not connected to Spotify');
    }

    const response = await fetch(`${SPOTIFY_API_BASE}${path}`, {
        ...init,
        headers: {
            ...(init.headers ?? {}),
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
        },
    });

    if (!response.ok && response.status !== 204)
    {
        error.value = `Spotify playback command failed (${response.status})`;
    }

    return response;
}

async function pause(): Promise<void> {
    if (deviceId.value === null)
    {
        return;
    }

    try
    {
        await spotifyFetch(`/me/player/pause?device_id=${deviceId.value}`, { method: 'PUT' });
    }
    catch
    {
        // error already recorded by spotifyFetch
    }
}

async function resume(): Promise<void> {
    if (deviceId.value === null)
    {
        return;
    }

    try
    {
        await spotifyFetch(`/me/player/play?device_id=${deviceId.value}`, { method: 'PUT' });
    }
    catch
    {
        // error already recorded by spotifyFetch
    }
}

async function playFromStart(spotifyTrackId: string, shouldBePlaying: boolean): Promise<void> {
    if (deviceId.value === null)
    {
        error.value = 'Spotify player not ready yet';

        return;
    }

    try
    {
        await spotifyFetch(`/me/player/play?device_id=${deviceId.value}`, {
            method: 'PUT',
            body: JSON.stringify({ uris: [`spotify:track:${spotifyTrackId}`], position_ms: 0 }),
        });

        if (!shouldBePlaying)
        {
            await pause();
        }
    }
    catch
    {
        // error already recorded by spotifyFetch
    }
}

const initialStored = loadStoredAuth();

if (initialStored !== null)
{
    isAuthenticated.value = true;
    void loadSdkAndCreatePlayer();
}

export function useSpotifyPlayer() {
    return {
        isAuthenticated,
        isReady,
        deviceId,
        error,
        isPlaying,
        connect,
        handleAuthCallback,
        playFromStart,
        pause,
        resume,
    };
}
