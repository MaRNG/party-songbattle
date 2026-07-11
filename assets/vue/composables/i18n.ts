export type Lang = 'cs' | 'en';

export interface Strings {
    brand: string;
    brandSub: string;
    landing_eyebrow: string;
    landing_title: string;
    landing_lead: string;
    cta_create: string;
    cta_join: string;
    code_placeholder: string;
    or: string;
    landing_play_solo: string;
    landing_play_party: string;
    create_title: string;
    create_sub: string;
    mode_solo_t: string;
    mode_solo_d: string;
    mode_robin_t: string;
    mode_robin_d: string;
    mode_all_t: string;
    mode_all_d: string;
    filters: string;
    f_decade: string;
    f_genre: string;
    f_country: string;
    f_artist: string;
    f_artist_ph: string;
    songs_in_pool: string;
    scoring_title: string;
    scoring_hint: string;
    show_leaderboard_label: string;
    show_leaderboard_hint: string;
    create_cta: string;
    room_eyebrow: string;
    room_title: string;
    room_share: string;
    players_connected: string;
    you: string;
    master: string;
    waiting: string;
    ready: string;
    start_game: string;
    over_eyebrow: string;
    over_title: string;
    over_sub: string;
    tab_songs: string;
    tab_players: string;
    tab_settings: string;
    total_songs: string;
    played: string;
    guessed: string;
    missed: string;
    remaining: string;
    col_song: string;
    col_artist: string;
    col_genre: string;
    col_year: string;
    col_status: string;
    col_result: string;
    status_guessed: string;
    status_missed: string;
    status_now: string;
    status_next: string;
    open_master: string;
    master_eyebrow: string;
    skip_btn: string;
    next_song: string;
    restart_btn: string;
    end_game: string;
    current_step: string;
    elapsed: string;
    next_step_in: string;
    seconds: string;
    of: string;
    song_n_of_n: (n: number, total: number) => string;
    leaderboard: string;
    points: string;
    guessed_at: string;
    nobody_yet: string;
    on_turn: string;
    player_eyebrow: string;
    playing: string;
    your_guess: string;
    type_a_song: string;
    suggestions: string;
    waiting_master: string;
    waiting_turn: string;
    your_turn: string;
    robin_min_players: string;
    all_min_players: string;
    submit: string;
    correct: string;
    incorrect: string;
    correct_eyebrow: string;
    correct_title: string;
    correct_sub: string;
    correct_sub_other: (name: string) => string;
    you_guessed_at: string;
    earned: string;
    streak: string;
    your_rank: string;
    continue_btn: string;
    missed_eyebrow: string;
    missed_title: string;
    missed_sub: string;
    by: string;
    no_points: string;
    keep_going: string;
    results_title: string;
    results_sub: string;
    rank: string;
    play_again: string;
    new_game: string;
    avg_time: string;
    fastest: string;
    best_streak: string;
    kick_btn: string;
    kick_confirm: (name: string) => string;
    edit_score_prompt: (name: string) => string;
    attempts_remaining: (n: number) => string;
    answered_waiting: string;
    auto_advance_hint: (s: number) => string;
    kicked_message: string;
    decades: string[];
    genres: string[];
    countries: string[];
}

export const SB_I18N: Record<Lang, Strings> = {
    cs: {
        brand: 'SONG BATTLE',
        brandSub: 'PARTY MIX',
        landing_eyebrow: 'PARTY HUDEBNÍ KVÍZ',
        landing_title: 'Uhodni písničku\ndřív než dohraje',
        landing_lead: 'Vytvoř hru, pusť Spotify a hádej. Čím rychleji, tím víc bodů. Hraj sám, na střídačku nebo všichni najednou.',
        cta_create: 'Vytvořit hru',
        cta_join: 'Připojit se',
        code_placeholder: 'Kód hry',
        or: 'nebo',
        landing_play_solo: 'Hraj sám',
        landing_play_party: 'Hraj na párty',
        create_title: 'Nová hra',
        create_sub: 'Vyber mód a nastav filtry',
        mode_solo_t: 'Sám',
        mode_solo_d: 'Hraješ sám proti písničkám. Skipuješ když nevíš.',
        mode_robin_t: 'Po jednom',
        mode_robin_d: 'Hráči se střídají v kolečku, vždy hádá jeden.',
        mode_all_t: 'Všichni najednou',
        mode_all_d: 'Všichni píšou tipy paralelně, kdo dřív, ten líp.',
        filters: 'Filtry',
        f_decade: 'Dekáda',
        f_genre: 'Žánr',
        f_country: 'Země',
        f_artist: 'Interpret',
        f_artist_ph: 'Hledat interpreta…',
        songs_in_pool: 'písniček v poolu',
        scoring_title: 'Body za úseky',
        scoring_hint: 'Kolik bodů dostane hráč za uhodnutí v daném úseku skladby.',
        show_leaderboard_label: 'Zobrazit žebříček hráčům',
        show_leaderboard_hint: 'Hráči (kromě mastera) uvidí aktuální pořadí a skóre všech ostatních.',
        create_cta: 'Vytvořit hru',
        room_eyebrow: 'ČEKÁRNA',
        room_title: 'Pozvi parťáky',
        room_share: 'Sdílej kód nebo nechte naskenovat QR. Spustí se, až bude master připraven.',
        players_connected: 'připojených',
        you: 'TY',
        master: 'MASTER',
        waiting: 'čeká',
        ready: 'připraven',
        start_game: 'Spustit hru',
        over_eyebrow: 'HRA',
        over_title: 'Přehled hry',
        over_sub: 'Vidíš to ty jako master. Hráči vidí jen aktuální stav.',
        tab_songs: 'Písničky',
        tab_players: 'Hráči',
        tab_settings: 'Nastavení',
        total_songs: 'Celkem',
        played: 'Přehráno',
        guessed: 'Uhodnuto',
        missed: 'Neuhodnuto',
        remaining: 'Zbývá',
        col_song: 'Písnička',
        col_artist: 'Interpret',
        col_genre: 'Žánr',
        col_year: 'Rok',
        col_status: 'Status',
        col_result: 'Výsledek',
        status_guessed: 'Uhodnuto',
        status_missed: 'Skip',
        status_now: 'Hraje',
        status_next: 'V pořadí',
        open_master: 'Otevřít pohled mastera',
        master_eyebrow: 'POHLED MASTERA',
        skip_btn: 'Skip → další úsek',
        next_song: 'Další písnička',
        restart_btn: 'Znovu od začátku',
        end_game: 'Ukončit hru',
        current_step: 'Aktuální úsek',
        elapsed: 'Hraje',
        next_step_in: 'Skip prodlouží na',
        seconds: 's',
        of: 'z',
        song_n_of_n: (n, total) => `${n}/${total}`,
        leaderboard: 'Pořadí',
        points: 'b',
        guessed_at: 'Uhodli',
        nobody_yet: 'Zatím nikdo neuhodl',
        on_turn: 'Na řadě',
        player_eyebrow: 'POHLED HRÁČE',
        playing: 'Přehrává se',
        your_guess: 'Tvůj tip',
        type_a_song: 'Napiš písničku nebo interpreta…',
        suggestions: 'Návrhy',
        waiting_master: 'Čekej na mastera…',
        waiting_turn: 'Čekej, až přijdeš na řadu',
        your_turn: 'Jsi na řadě!',
        robin_min_players: 'Pro mód „Po jednom“ potřebuješ aspoň 2 hráče (kromě mastera).',
        all_min_players: 'Pro mód „Všichni najednou“ potřebuješ aspoň 2 hráče (kromě mastera).',
        submit: 'Tipnout',
        correct: 'Správně! 🎉',
        incorrect: 'Bohužel, zkus to dál',
        correct_eyebrow: 'TREFA',
        correct_title: 'Máš to!',
        correct_sub: 'Uhodl jsi písničku',
        correct_sub_other: (name: string) => `${name} uhodl(a) písničku`,
        you_guessed_at: 'Tip na',
        earned: 'Získáno',
        streak: 'Šňůra',
        your_rank: 'Tvoje místo',
        continue_btn: 'Pokračovat',
        missed_eyebrow: 'KONEC KOLA',
        missed_title: 'Nikdo neuhodl',
        missed_sub: 'Písnička byla',
        by: 'od',
        no_points: 'Tentokrát bez bodů',
        keep_going: 'Díl, prr! Další je tvoje.',
        results_title: 'Konec hry',
        results_sub: 'Pořadí a souhrn',
        rank: 'Místo',
        play_again: 'Znovu',
        new_game: 'Nová hra',
        avg_time: 'Průměrný čas',
        fastest: 'Nejrychlejší tip',
        best_streak: 'Nejlepší šňůra',
        kick_btn: 'Vyhodit',
        kick_confirm: (name: string) => `Opravdu vyhodit hráče ${name} ze hry?`,
        edit_score_prompt: (name: string) => `Nové skóre pro ${name}:`,
        attempts_remaining: (n: number) => `${n}× pokus zbývá`,
        answered_waiting: 'Uhodnuto! Čekej na ostatní…',
        auto_advance_hint: (s: number) => `Další písnička za ~${s}s`,
        kicked_message: 'Master tě vyhodil ze hry.',
        decades: ['60. léta', '70. léta', '80. léta', '90. léta', '00.', '10.', '20.'],
        genres: ['Pop', 'Rock', 'Hip-Hop', 'Elektronika', 'Synthwave', 'Jazz', 'Country', 'R&B', 'Metal', 'Indie', 'Folk'],
        countries: ['🇨🇿 ČR', '🇺🇸 USA', '🇬🇧 UK', '🇩🇪 DE', '🇫🇷 FR', '🇸🇪 SE', '🇯🇵 JP', '🇮🇹 IT', '🇧🇷 BR'],
    },
    en: {
        brand: 'SONG BATTLE',
        brandSub: 'PARTY MIX',
        landing_eyebrow: 'PARTY MUSIC QUIZ',
        landing_title: 'Guess the song\nbefore it ends',
        landing_lead: 'Create a game, hit play on Spotify, and guess. The faster you nail it, the more points you score. Solo, round-robin, or everyone at once.',
        cta_create: 'Create game',
        cta_join: 'Join game',
        code_placeholder: 'Game code',
        or: 'or',
        landing_play_solo: 'Play solo',
        landing_play_party: 'Play with friends',
        create_title: 'New game',
        create_sub: 'Pick a mode and tune the pool',
        mode_solo_t: 'Solo',
        mode_solo_d: 'Just you against the playlist. Skip when stuck.',
        mode_robin_t: 'Round-robin',
        mode_robin_d: 'Players take turns guessing one song at a time.',
        mode_all_t: 'Free-for-all',
        mode_all_d: 'Everyone types in parallel. Fastest correct wins.',
        filters: 'Filters',
        f_decade: 'Decade',
        f_genre: 'Genre',
        f_country: 'Country',
        f_artist: 'Artist',
        f_artist_ph: 'Search artist…',
        songs_in_pool: 'songs in pool',
        scoring_title: 'Points per step',
        scoring_hint: 'How many points a player earns for guessing right during each snippet step.',
        show_leaderboard_label: 'Show leaderboard to players',
        show_leaderboard_hint: 'Players (not just the master) will see everyone\'s live rank and score.',
        create_cta: 'Create game',
        room_eyebrow: 'WAITING ROOM',
        room_title: 'Invite the crew',
        room_share: 'Share the code or scan the QR. Starts when the master hits go.',
        players_connected: 'connected',
        you: 'YOU',
        master: 'MASTER',
        waiting: 'waiting',
        ready: 'ready',
        start_game: 'Start game',
        over_eyebrow: 'GAME',
        over_title: 'Game overview',
        over_sub: 'Master view. Players only see live state.',
        tab_songs: 'Songs',
        tab_players: 'Players',
        tab_settings: 'Settings',
        total_songs: 'Total',
        played: 'Played',
        guessed: 'Guessed',
        missed: 'Missed',
        remaining: 'Left',
        col_song: 'Song',
        col_artist: 'Artist',
        col_genre: 'Genre',
        col_year: 'Year',
        col_status: 'Status',
        col_result: 'Result',
        status_guessed: 'Guessed',
        status_missed: 'Skipped',
        status_now: 'Playing',
        status_next: 'Up next',
        open_master: 'Open master view',
        master_eyebrow: 'MASTER VIEW',
        skip_btn: 'Skip → extend',
        next_song: 'Next song',
        restart_btn: 'Restart from start',
        end_game: 'End game',
        current_step: 'Current step',
        elapsed: 'Elapsed',
        next_step_in: 'Skip extends to',
        seconds: 's',
        of: 'of',
        song_n_of_n: (n, total) => `${n}/${total}`,
        leaderboard: 'Leaderboard',
        points: 'pt',
        guessed_at: 'Guessed',
        nobody_yet: 'No one has guessed yet',
        on_turn: 'On turn',
        player_eyebrow: 'PLAYER VIEW',
        playing: 'Now playing',
        your_guess: 'Your guess',
        type_a_song: 'Type a song or artist…',
        suggestions: 'Suggestions',
        waiting_master: 'Waiting for master…',
        waiting_turn: 'Wait for your turn',
        your_turn: 'Your turn!',
        robin_min_players: 'Round-robin mode needs at least 2 players (besides the master).',
        all_min_players: 'Free-for-all mode needs at least 2 players (besides the master).',
        submit: 'Guess',
        correct: 'Correct! 🎉',
        incorrect: 'Nope, keep guessing',
        correct_eyebrow: 'GOT IT',
        correct_title: 'Nailed it!',
        correct_sub: 'You guessed the song',
        correct_sub_other: (name: string) => `${name} guessed the song`,
        you_guessed_at: 'Guessed at',
        earned: 'Earned',
        streak: 'Streak',
        your_rank: 'Your rank',
        continue_btn: 'Continue',
        missed_eyebrow: 'ROUND OVER',
        missed_title: 'Nobody got it',
        missed_sub: 'The song was',
        by: 'by',
        no_points: 'No points this round',
        keep_going: 'Shake it off — the next one is yours.',
        results_title: 'Game over',
        results_sub: 'Final standings',
        rank: 'Rank',
        play_again: 'Replay',
        new_game: 'New game',
        avg_time: 'Avg. guess time',
        fastest: 'Fastest guess',
        best_streak: 'Best streak',
        kick_btn: 'Kick',
        kick_confirm: (name: string) => `Remove ${name} from the game?`,
        edit_score_prompt: (name: string) => `New score for ${name}:`,
        attempts_remaining: (n: number) => `${n} attempt${n === 1 ? '' : 's'} left`,
        answered_waiting: 'Nailed it! Waiting for others…',
        auto_advance_hint: (s: number) => `Next song in ~${s}s`,
        kicked_message: 'The master removed you from the game.',
        decades: ['60s', '70s', '80s', '90s', '00s', '10s', '20s'],
        genres: ['Pop', 'Rock', 'Hip-Hop', 'Electronic', 'Synthwave', 'Jazz', 'Country', 'R&B', 'Metal', 'Indie', 'Folk'],
        countries: ['🇨🇿 CZ', '🇺🇸 US', '🇬🇧 UK', '🇩🇪 DE', '🇫🇷 FR', '🇸🇪 SE', '🇯🇵 JP', '🇮🇹 IT', '🇧🇷 BR'],
    },
};

export const STEPS = [0.5, 1, 2, 5, 10, 15];

export const DEFAULT_POINTS_PER_STEP = [500, 300, 200, 100, 75, 50];
