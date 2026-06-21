# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Party Songbattle — a multiplayer music-guessing game. Players hear a short snippet of a track (progressively longer on each skip) and type the song name/artist. Backend: **Nette Framework + Apitte** (REST API) + **Doctrine ORM** (Nettrine) + **Symfony Console**. Frontend: **Vue 3 + TypeScript**, bundled by Webpack.

## Commands

```bash
# PHP CLI console
php bin/console                                    # list all commands
php bin/console import:spotify:playlist <id|url>   # import tracks from a Spotify playlist
php bin/console import:musicbrainz:tracks          # import MusicBrainz track tags
php bin/console import:musicbrainz:artists         # import artist country data
php bin/console import:consolidate-genres          # consolidate track genres
php bin/console track:generate-hashes              # generate track name/artist hashes
php bin/console spotify:connect                    # OAuth wizard for Spotify credentials
php bin/console orm:schema-tool:update --dump-sql  # preview pending schema changes
php bin/console migrations:migrate                 # run DB migrations
php bin/console migrations:generate                # generate new migration file

# Frontend
npm run dev      # development build (no watch)
npm run watch    # development build with watch
npm run build    # production build → www/assets/build/

# Cache / temp cleanup
make clean       # rm var/temp/* and var/log/*
```

## Configuration

- `config/config.neon` — root config, includes all sub-configs
- `config/app/parameters.neon` — parameter defaults (Spotify credentials go here as empty strings)
- `config/app/services.neon` — **all services are registered here explicitly** (auto-discovery is disabled/commented out)
- `config/ext/apitte.neon` — Apitte plugins and middleware stack
- `config/ext/nettrine.neon` — Doctrine ORM/DBAL/Migrations setup
- `config/ext/contributte.neon` — Guzzle, Console, Monolog
- `config/local.neon` — local overrides (DB credentials, Spotify tokens, debug mode). Not committed.

Debug mode is enabled automatically when `HTTP_HOST` contains `.localhost`.

## PHP backend architecture

### Registering anything new

Every new service, repository, command, controller, or facade **must be added manually** to `config/app/services.neon`. There is no auto-discovery.

### API controllers (Apitte)

Routing is built by nesting PHP attributes on the class hierarchy:

```
BaseController          #[Path('/api')]
  └─ BaseV1Controller   #[Path('/v1')]
       └─ BasePublicV1Controller
            └─ GameController  #[Path('/songbattle')]
                 └─ method     #[Path('/games')], #[Method('POST')]
```

Final URL: `/api/v1/songbattle/games`. Path parameters use `#[RequestParameter(name: 'hash', type: 'string', in: 'path')]`.

New controller steps:
1. Create class in `App/Api/Controller/V1/Public/<Domain>/`, extend the appropriate base.
2. Use `#[Path]` on class and `#[Path]` + `#[Method]` on each action method.
3. Inject dependencies via constructor.
4. Register in `config/app/services.neon`.

Throw `ClientErrorException` (from `Apitte\Core\Exception\Api`) with an HTTP status code for API errors.

### Facades (`App/Api/Facade/`)

Facades are the bridge between the HTTP layer and the domain model. Controllers should not touch repositories or domain services directly — they delegate to a facade. Facades are `final readonly` classes injected via constructor.

### Domain model (`App/Model/`)

Business logic lives here: `GameFactory`, `GameSessionManager`, `GameStateProvider`, `GameFilterOptionsProvider`, `GameRules`. Data transferred between layers as DTOs (`App/Model/Game/Dto/`). Enums are in `App/Model/Enum/`.

`GameRules` contains game constants: snippet step durations, avatar colors, and point calculation formula.

### Entities (`App/Infrastructure/Database/Entity/`)

Doctrine ORM entities using PHP attributes. All extend `BaseEntity` (provides `id` + `created`). Mapping is defined by attributes; proxy classes are auto-generated.

Entity manager is wrapped by `EntityManagerDecorator` (configured in `nettrine.neon`).

### Repositories (`App/Infrastructure/Database/Repository/`)

All extend `BaseRepository extends EntityRepository`. Constructor takes only `EntityManagerInterface`. Must implement `protected static function getEntityClass(): string`. Provides `getBaseQuery()` returning a `QueryBuilder` aliased to `'u'`.

```php
final class FooRepository extends BaseRepository
{
    protected static function getEntityClass(): string { return Foo::class; }
}
```

### Nette UI Presenters (`App/UI/Modules/`)

Used for server-rendered HTML pages (non-API). Hierarchy: `BasePresenter → BaseFrontPresenter → concrete presenter`. Mapping: `Front: [App\UI\Modules\Front, *, *\*Presenter]` (config.neon). Templates live in `App/UI/Modules/Front/templates/<Presenter>/<action>.latte`. Dependencies can be injected via `#[Inject]` public property or constructor.

Routes are defined in `App/Model/Router/RouterFactory.php`.

### Commands (`App/Commands/`)

Symfony Console commands using `#[AsCommand(name: '...')]`. Extend `Symfony\Component\Console\Command\Command`. Register in `config/app/services.neon`. Commands with constructor parameters requiring config values get those passed inline in neon (see `SpotifyConnectWizardCommand`).

### External clients (`App/Infrastructure/ExternalClient/`)

Wrapper clients over Guzzle. `SpotifyExternalClient` takes an `$accessToken` constructor arg (injected from `%spotify.auth.accessToken%` in neon). Handlers are lazily instantiated sub-objects (e.g. `->playlists()`, `->artists()`).

### Import services (`App/Infrastructure/Import/`)

Orchestrate pulling data from external clients and persisting via Doctrine. Called from commands.

## Frontend (Vue 3 + TypeScript)

Entry point: `assets/js/main.ts` → mounts `assets/vue/App.vue` on `#app`.

Output: `www/assets/build/main.js` and `www/assets/build/main.css` (loaded by Latte layout).

### Structure

- `assets/vue/App.vue` — root component, owns screen routing logic (`landing → create → room → play → results` + overlays) and the global `useGameSession` instance.
- `assets/vue/pages/` — one `.vue` per screen.
- `assets/vue/components/` — shared UI components.
- `assets/vue/composables/useGameSession.ts` — central reactive state for the active game. Persists `hash`+`token` to `localStorage` (`sb_session` key) for page-refresh recovery. Polls `/state` every 1500 ms during play.
- `assets/vue/api/client.ts` — `SongBattleApi` object wrapping all REST calls. Base path: `/api/v1/songbattle`. Player auth via `X-Player-Token` header.
- `assets/vue/composables/i18n.ts` — bilingual strings object (`cs` / `en`), passed as `:t` prop down the tree.

Game modes: `solo` | `robin` | `all`. Player roles: `master` (game creator, controls playback) | `player`.

## Database migrations

Migration files live in `database/Migrations/`. Generate via `php bin/console migrations:generate`, run via `php bin/console migrations:migrate`. Doctrine manages the schema; do not alter tables directly.
