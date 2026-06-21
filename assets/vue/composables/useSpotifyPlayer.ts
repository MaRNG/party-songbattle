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
let loadedTrackId: string | null = null;
let positionBaselineMs = 0;
let positionBaselineAt = Date.now();

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
        console.info('[spotify] ready', device_id);
        deviceId.value = device_id;
        isReady.value = true;
    });

    player.addListener('not_ready', ({ device_id }) => {
        console.warn('[spotify] not_ready', device_id);
        isReady.value = false;
        loadedTrackId = null;
    });

    player.addListener('player_state_changed', (state) => {
        console.log('[spotify] player_state_changed', state);

        if (state)
        {
            isPlaying.value = !state.paused;
            positionBaselineMs = state.position;
            positionBaselineAt = Date.now();
        }
    });

    player.addListener('initialization_error', ({ message }) => { console.error('[spotify] initialization_error', message); error.value = message; });
    player.addListener('authentication_error', ({ message }) => { console.error('[spotify] authentication_error', message); error.value = message; clearAuth(); });
    player.addListener('account_error', ({ message }) => { console.error('[spotify] account_error', message); error.value = `Spotify Premium required: ${message}`; });
    player.addListener('playback_error', ({ message }) => { console.error('[spotify] playback_error', message); error.value = message; });

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

    console.log('[spotify] request', init.method ?? 'GET', path, init.body ?? '');

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
        const body = await response.clone().text().catch(() => '');
        console.error('[spotify] request failed', response.status, path, body);
        error.value = `Spotify playback command failed (${response.status})`;
    }

    return response;
}

async function activateElement(): Promise<void> {
    if (player === null)
    {
        return;
    }

    try
    {
        await player.activateElement();
    }
    catch (err)
    {
        console.warn('[spotify] activateElement failed', err);
    }
}

async function waitForTrackToLoad(spotifyTrackId: string, timeoutMs = 4000): Promise<boolean> {
    if (player === null)
    {
        return false;
    }

    const expectedUri = `spotify:track:${spotifyTrackId}`;
    const deadline = Date.now() + timeoutMs;

    while (Date.now() < deadline)
    {
        const current = await player.getCurrentState();

        if (current?.track_window.current_track.uri === expectedUri)
        {
            return true;
        }

        await new Promise((resolve) => setTimeout(resolve, 150));
    }

    return false;
}

async function waitForPaused(timeoutMs = 2000): Promise<void> {
    if (player === null)
    {
        return;
    }

    const deadline = Date.now() + timeoutMs;

    while (Date.now() < deadline)
    {
        const current = await player.getCurrentState();

        if (current === null || current.paused)
        {
            return;
        }

        await new Promise((resolve) => setTimeout(resolve, 100));
    }
}

async function ensureReady(timeoutMs = 5000): Promise<boolean> {
    if (isReady.value)
    {
        return true;
    }

    // Spotify demotes an idle Connect device to `not_ready` after a while without
    // active playback (e.g. the master sat on a paused/loaded track for several
    // minutes). Reconnecting and giving it a moment recovers without the user
    // having to log in again — `connect()` is safe to call on an already-connected
    // player too.
    if (player !== null)
    {
        try
        {
            await player.connect();
        }
        catch (err)
        {
            console.warn('[spotify] reconnect attempt failed', err);
        }
    }

    const deadline = Date.now() + timeoutMs;

    while (Date.now() < deadline)
    {
        if (isReady.value)
        {
            return true;
        }

        await new Promise((resolve) => setTimeout(resolve, 200));
    }

    return isReady.value;
}

function getEstimatedPositionMs(): number {
    if (!isPlaying.value)
    {
        return positionBaselineMs;
    }

    return positionBaselineMs + (Date.now() - positionBaselineAt);
}

async function pause(): Promise<void> {
    if (player === null)
    {
        return;
    }

    try
    {
        // The SDK's own instance method controls this exact in-browser playback
        // session directly. The equivalent `/me/player/pause` Web API call is meant
        // for controlling ANY Connect device remotely and routes through Spotify's
        // cloud even when the target is this same tab — that round trip (commonly
        // 200ms-1s) is what was causing the snippet watchdog to overshoot the step
        // limit and pause-then-seek to land on a non-zero position.
        await player.pause();
    }
    catch (err)
    {
        console.error('[spotify] local pause failed', err);
        error.value = 'Spotify pause failed';
    }
}

async function resume(): Promise<void> {
    if (player === null)
    {
        return;
    }

    try
    {
        await player.resume();
    }
    catch (err)
    {
        console.error('[spotify] local resume failed', err);
        error.value = 'Spotify resume failed';
    }
}

async function seek(positionMs: number): Promise<void> {
    if (player === null)
    {
        return;
    }

    try
    {
        await player.seek(positionMs);
    }
    catch (err)
    {
        console.error('[spotify] local seek failed', err);
        error.value = 'Spotify seek failed';
    }
}

async function loadNewTrack(spotifyTrackId: string, shouldBePlaying: boolean): Promise<void> {
    if (deviceId.value === null)
    {
        return;
    }

    await spotifyFetch(`/me/player/play?device_id=${deviceId.value}`, {
        method: 'PUT',
        body: JSON.stringify({ uris: [`spotify:track:${spotifyTrackId}`], position_ms: 0 }),
    });

    loadedTrackId = spotifyTrackId;

    // Wait for the SDK to confirm the device actually loaded this track before
    // pausing — pausing immediately after the `play` request only confirms Spotify
    // *accepted* the command, not that audio has started buffering. Pausing too
    // early can abort the load entirely, leaving the device with no active context
    // (so a later resume/seek has nothing to act on and nothing audible ever plays).
    await waitForTrackToLoad(spotifyTrackId);

    if (!shouldBePlaying)
    {
        await pause();

        // Spotify's REST API confirms a pause command was *accepted* immediately,
        // but the device takes a noticeable moment longer to actually stop —
        // seeking before that has genuinely happened lands the seek on a device
        // that's still playing, so it keeps drifting forward after the seek and
        // settles on some non-zero position instead of 0. Wait for the SDK to
        // confirm playback actually stopped before resetting position.
        await waitForPaused();

        // The track was genuinely playing for real (and the snippet clock was
        // ticking) for however long the load confirmation above took. Reset the
        // position back to 0 so that budget isn't already spent by the time the
        // master actually presses play.
        await seek(0);
        positionBaselineMs = 0;
        positionBaselineAt = Date.now();
    }
}

async function playFromStart(spotifyTrackId: string, shouldBePlaying: boolean): Promise<void> {
    console.log('[spotify] playFromStart called', spotifyTrackId, shouldBePlaying, 'deviceId=', deviceId.value, 'loadedTrackId=', loadedTrackId);

    if (deviceId.value === null)
    {
        error.value = 'Spotify player not ready yet';

        return;
    }

    try
    {
        if (loadedTrackId === spotifyTrackId)
        {
            // Same track already loaded on this device — just seek back to 0 instead of
            // reissuing a fresh `play` with `uris`, which tears down and recreates the
            // DRM (Widevine) license session for the content and can fail with a 400 on
            // the widevine-license endpoint if done repeatedly in quick succession.
            await seek(0);
            positionBaselineMs = 0;
            positionBaselineAt = Date.now();

            if (shouldBePlaying)
            {
                await resume();
            }
            else
            {
                await pause();
            }
        }
        else
        {
            await loadNewTrack(spotifyTrackId, shouldBePlaying);
        }
    }
    catch
    {
        // error already recorded by spotifyFetch
    }
}

async function ensureTrackLoaded(spotifyTrackId: string, shouldBePlaying: boolean): Promise<void> {
    console.log('[spotify] ensureTrackLoaded called', spotifyTrackId, shouldBePlaying, 'deviceId=', deviceId.value, 'loadedTrackId=', loadedTrackId);

    // Passive sync for a track change that happened for a reason other than an explicit
    // restart click (skip advancing past the last step, next song, or a correct guess
    // ending the round) — comparing against the module-level `loadedTrackId` (not a
    // per-component "previous id") means this is safe to call from anywhere, including
    // right after a component remounts, without redundantly reloading/repositioning a
    // track that's already correctly loaded.
    if (loadedTrackId === spotifyTrackId)
    {
        return;
    }

    const ready = await ensureReady();

    if (!ready)
    {
        error.value = 'Spotify player not ready (isReady=false) — track changed but nothing was sent to Spotify';

        return;
    }

    try
    {
        await loadNewTrack(spotifyTrackId, shouldBePlaying);
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
        getEstimatedPositionMs,
        activateElement,
        ensureReady,
        ensureTrackLoaded,
    };
}
