# Onetone Framework — PHP Platform

A self-contained PHP web framework and standard library, packaged together with the demo
application that exercises it. Everything needed to serve HTTP, run console commands, talk to a
database, and render views is in this repository; the only production dependency is
`vlucas/phpdotenv`.

- **Package name:** `onetone/framework`
- **Version:** `v1.0.0`
- **Root namespace:** `Clover\` → `src/`, `App\` → `root/App/`
- **License:** AGPL-3.0-only

The `Clover\` prefix is historical: the framework was originally developed under the name Clover,
and the namespace was kept so existing application code keeps resolving.

---

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Running the application](#running-the-application)
- [Directory layout](#directory-layout)
- [Entry points](#entry-points)
- [Request lifecycle](#request-lifecycle)
- [Routing](#routing)
- [Middleware](#middleware)
- [Console commands](#console-commands)
- [Application modules](#application-modules)
- [Database access](#database-access)
- [Task scheduling](#task-scheduling)
- [Standard library](#standard-library)
- [Configuration reference](#configuration-reference)
- [Quality tooling](#quality-tooling)
- [Code style](#code-style)
- [Deployment](#deployment)
- [Known limitations](#known-limitations)
- [License](#license)

---

## Requirements

| Item | Value |
| --- | --- |
| PHP | `>=8.0.0` declared in `composer.json`; Composer platform is pinned to `8.3`, so resolution behaves as if 8.3 were installed |
| Composer | 2.x |
| Runtime dependency | `vlucas/phpdotenv ^5.7` |
| Dev dependencies | `phpunit/phpunit ^10.5`, `phpstan/phpstan ^2.1`, `friendsofphp/php-cs-fixer ^3.64` |
| Database | MySQL-compatible server, reached through PDO. Optional — the site routes do not open a connection |

Optional extensions: `pcov` or `xdebug` for test coverage; `ffi` for the header bindings under
`src/FFI`. Neither is required to boot the application.

## Installation

```bash
composer install
cp root/.env.example root/.env
```

`make dev` performs both steps. The autoloader is written to `vendor/autoload.php` at the package
root, which is the path both `root/index.php` and `root/frankenphp-worker.php` resolve.

`root/.env` is required: `Runtime::run()` throws `RuntimeException` when `DotenvLoader::load()`
cannot read an environment file. A machine-local override may be placed in `root/.env.local`; it is
skipped inside containers via `RUNNING_IN_DOCKER`.

## Running the application

```bash
# PHP built-in server (composer script "dev")
composer dev                       # serves root/ on localhost:8000

# or directly, with a custom bind
php -S localhost:8080 -t root
```

Under `cli-server`, `root/index.php` returns `false` for any request that resolves to an existing
file inside `root/`, so the built-in server streams static assets itself instead of routing them.
The resolved path is verified to still live under the document root before it is served, which is
what stops `..` segments from escaping.

Console entry point:

```bash
php bin/console route:list
```

`bin/console` is a two-line shim that requires `root/index.php`; the same bootstrap serves HTTP and
CLI, and `Mapper::matchRunner()` decides which kernel runs based on the SAPI.

## Directory layout

```text
.
├── bin/console                  CLI entry point (requires root/index.php)
├── root/                        Web root — document root in every deployment
│   ├── index.php                HTTP + CLI bootstrap, entry-point selection
│   ├── frankenphp-worker.php    FrankenPHP worker-mode loop
│   ├── .env.example             Environment template
│   ├── .htaccess                Apache front-controller rules
│   └── App/                     The bundled application
│       ├── Attachment/          Uploaded-file storage
│       ├── Cache/               Compiled route cache target
│       ├── Command/             Application console commands
│       ├── Configure/           dependencies, kernel, providers, routes, modules, schedule
│       ├── Controller/          HomeController — the marketing site and probes
│       ├── DataTransfer/        DTOs
│       ├── Database/Seeders/    DatabaseSeeder
│       ├── Entity/              ActiveRecord entities
│       ├── File/                Runtime file storage (FILE_PATH)
│       ├── Frontend/            Layout skins, jQuery, built bundles, TypeScript platform output
│       ├── Http/Kernel.php      HTTP kernel for the `application` entry point
│       ├── Livewire/            Livewire-style components
│       ├── Middleware/          DefaultMiddleware, TrimStringsMiddleware
│       ├── Modules/             Feature modules: AudioAnalysis, Board, Comment
│       ├── Provider/            GraphQLRouteProvider, ProfilerRouteProvider
│       ├── Resource/            CSS, JS, fonts, images
│       ├── Routes/              Route fragment directory
│       ├── Table/               Table schema definitions
│       └── View/                PHP templates, markdown documentation, site pages
├── src/                         The framework and standard library (Clover\)
├── tests/                       PHPUnit suite (353 test files)
├── composer.json                Autoload map and the script aliases documented below
├── phpunit.xml.dist             Test suite, coverage reports, PHP ini overrides
├── phpstan.neon.dist            Level 5, src only, baselined
├── phpstan-baseline.neon        Generated baseline
├── .php-cs-fixer.dist.php       PSR-12 + tabs + strict_types
├── .htaccess                    Rewrites requests into root/
└── Makefile                     dev / env targets
```

`src/` holds 2,482 PHP files, of which 1,343 are generated data tables under `src/Defaults`
(54 MB of literal `return [...]` arrays: emoji, locale, international, statistical and MIME data).
Every tool in this repository excludes that directory, so the hand-written framework surface is
1,139 files.

## Entry points

The framework ships two kernel stacks. `APP_ENTRY_POINT` selects one; an unset or empty value
means `runtime`, and an unrecognised value raises `InvalidArgumentException` at startup rather than
silently booting the wrong kernel.

| | `runtime` (default) | `application` |
| --- | --- | --- |
| Bootstrap | `Clover\Framework\Component\Runtime` | `Clover\Component\Foundation\Application` |
| Dispatch | `Mapper::matchRunner()` → `Framework\Component\HttpKernel` or `CliKernel` | `Component\Kernel\HttpKernel` resolved from the container |
| Services | `root/App/Configure/dependencies.php` | `src/Service/*ServiceProvider.php` listed in `root/App/Configure/providers.php` |
| Routes | discovered from controller attributes by `Framework\Routing\RouteRegistry` | declared in `root/App/Configure/routes.php` |
| Kernel binding | fixed | `root/App/Configure/kernel.php` binds `KernelInterface` |

The bundled application is written against `runtime`. The two stacks maintain separate route
tables and do not share route definitions — a route added to `routes.php` is invisible to the
default entry point, and a controller attribute is invisible to `application`.

Under `application`, `Application::configure()` runs `kernel.php` before any kernel is resolved, so
an application can replace the HTTP kernel or bind its own `ExceptionHandlerInterface`.
`HandleExceptions` installs the framework default only when that slot is empty. `RegisterProviders`
calls `register()` on each provider in listed order — a later provider overwrites an earlier
binding — and `BootProviders` then calls every `boot()` against the finished container.

## Request lifecycle

Under the default `runtime` entry point:

1. `root/index.php` defines `BASE_PATH`, `FILE_PATH` and `VENDOR_PATH`, includes the autoloader,
   and calls `DotenvLoader::load()`. The loader is idempotent, so the later call inside `Runtime`
   reuses the result.
2. `EntryPoint::fromEnvironment()` reads `APP_ENTRY_POINT`. Dotenv populates `$_ENV` rather than
   `putenv()`, so `$_ENV` is consulted first and `getenv()` second — a real OS-level variable can
   still select the entry point where no `.env` file exists.
3. `Runtime::run()` registers `ErrorHandler`, registers debug subscribers when `IS_DEBUGGABLE=true`,
   and publishes a profiler timeline reset for non-CLI requests when the profiler is enabled.
4. Configuration phase: defaults are applied, the PHP and server environment is probed
   (version, post/upload limits, short-open-tag, session cookie policy, integer size, server
   software, home path), `APP_DEBUG` / `APP_TIMEZONE` / `APP_ERROR_REPORTING` override them, and
   the result is pushed into `ini_set` equivalents through `OperationSystem`.
5. Mapping phase: `Mapper::matchRunner()` returns an HTTP or CLI runner, `run()` produces a
   `Response`, the response is sent, and output is flushed through the path that matches the
   detected server software — `fastcgi_finish_request` for nginx, the LiteSpeed equivalent for
   LiteSpeed, buffered output otherwise. `Mapper::terminate()` runs in a `finally` block.

Each phase dispatches `KernelLifecycleEvent` markers (`RUNTIME_BOOT`,
`RUNTIME_CONFIGURE_STARTED` / `_FINISHED`, `RUNTIME_MAPPING_STARTED` / `_FINISHED`), and emits
`KernelSpanStarted` / `KernelSpanFinished` pairs when `PROFILER_ENABLED=true`.

FrankenPHP worker mode uses `root/frankenphp-worker.php`, which loops on
`frankenphp_handle_request()` and builds a fresh `Runtime(['server' => 'frankenphp'])` per request.
When the function is absent the file falls back to a single direct invocation, so it stays runnable
under a normal SAPI.

## Routing

### Attribute routing (`runtime`)

Controllers declare routes with PHP attributes from the `Clover\Annotation` namespace:

```php
#[Annotation\Prefix('/')]
class HomeController extends LayoutComponentController
{
    #[Annotation\Route(method: 'GET', pattern: '/health')]
    public function health(Request $request): Response
    {
        return $this->responseJson(['status' => 'ok', 'time' => gmdate('c')]);
    }
}
```

The complete attribute set in `src/Annotation`: `Autowire`, `ContentType`, `Controller`,
`DeleteMapping`, `Deprecated`, `GetMapping`, `McpTool`, `Middleware`, `NotFound`, `PatchMapping`,
`PostMapping`, `Prefix`, `PutMapping`, `Query`, `RequestMapping`, `RestController`, `Route`,
`Value`, plus the `Entity` subdirectory for entity mapping.

`RouteRegistry::load()` scans, in order, `root/App/Controller`, then `root/App/Command` — only
under CLI — then `src/Command`. It then registers `App\Provider\ProfilerRouteProvider`,
`App\Provider\GraphQLRouteProvider`, `Clover\Framework\Livewire\LivewireRouteProvider`, and finally
calls `registerRoutes()` on every enabled module.

Route caching is off by default. With `ROUTE_CACHE=true` the compiled table is written to
`root/App/Cache/routes.cache.php` (override with `ROUTE_CACHE_PATH`). The cache payload carries a
format version and the enabled-module list; a mismatch in either, a malformed payload, or a
payload that predates the metadata format while modules are registered, all count as a cache miss
and force rediscovery. If route changes do not appear, either set `ROUTE_CACHE=false` or delete the
cache file.

### Router routing (`application`)

`root/App/Configure/routes.php` returns a closure receiving `Router` and `Application`.
`RoutingServiceProvider::boot()` runs it once the container is complete, so an action may name a
controller with constructor dependencies and the router resolves it through the container:

```php
$router->get('/echo/{value}', static fn(Request $request, string $value): array => [
    'value' => $value,
])->middleware('trim')->name('echo');
```

### Bundled routes

`HomeController` serves the marketing site (products, docs, resources, ecosystem, news, partners,
enterprise, shop, community, about), markdown-rendered platform documentation for interpreter, PHP,
Frida, TypeScript, Android, Java and Python, Kakao and Naver OAuth redirect/callback pairs, an XML
response sample, and two probes: `GET /health` (liveness) and `GET /ready` (readiness). Both probes
are also the default `APP_MAINTENANCE_BYPASS_PATHS`.

## Middleware

Framework middleware in `src/Framework/Middleware`:

| Class | Responsibility |
| --- | --- |
| `ExceptionHandlingMiddleware` | Converts thrown exceptions into responses |
| `MaintenanceMiddleware` | Serves the maintenance response while `APP_MAINTENANCE=true`, except for bypass paths |
| `RoutingMiddleware` | Matches the request against the route table and invokes the action |
| `SecurityHeadersMiddleware` | Emits HSTS and Content-Security-Policy per configuration |
| `StackRequestHandler` | Runs the middleware stack and terminates at the innermost handler |

Application middleware lives in `root/App/Middleware`: `DefaultMiddleware` and
`TrimStringsMiddleware`, the latter publishing its result under
`TrimStringsMiddleware::ATTRIBUTE` for downstream handlers.

HSTS is only emitted when `APP_HSTS_ENABLED=true` **and** the request is HTTPS — either directly,
or via `X-Forwarded-Proto: https` from a peer listed in `APP_TRUSTED_PROXIES`. With no trusted
proxies configured the forwarded header is ignored entirely, which is the correct default for a
directly exposed server.

## Console commands

`CliKernel` builds a `CLIRouter` over two directories, in this order:
`root/App/Command` and `src/Command`. Each command implements
`Clover\Implement\CommandInterface` and reports its own name from `getName()`, so a command's file
name and its invocation are independent.

Framework commands (`src/Command`):

| Command | Class | Purpose |
| --- | --- | --- |
| `boost:mcp` | `McpCommand` | MCP server over SSE on `tcp://127.0.0.1:8002`; tools are registered from `#[McpTool]` attributes by reflection |
| `database:migrate` | `DatabaseMigrateCommand` | Manage database migrations |
| `database:query` | `QueryCommand` | Query tables and display schema or table list |
| `database:seed` | `DatabaseSeedCommand` | Run database seeders |
| `drone` | `RandomDronePathCommand` | Generate and visualize a random drone flight path |
| `exchange:rates` | `ExchangeRateCommand` | Fetch or convert exchange rates through the Frankfurter API |
| `framework:wizard` | `WizardCommand` | Interactive wizard for composer, docker, PHP and frontend operations; prompts are translated through `Translator` |
| `game:maze` | `MazeCommand` | Randomly generated maze |
| `game:minesweeper` | `MinesweeperCommand` | Minesweeper |
| `game:sokoban` | `SokobanCommand` | Sokoban |
| `game:squid` | `SquidCommand` | Lair of Squid, a first-person maze |
| `github` | `GithubCommand` | Git status |
| `harness` | `HarnessCommand` | Orchestrate migrations, tests, static analysis and route listing |
| `llm:chatgpt` | `ChatGPTCommand` | Prompt request to ChatGPT |
| `llm:claude` | `ClaudeCommand` | Prompt request to Claude |
| `llm:gemini` | `GoogleGeminiCommand` | Prompt request to Google Gemini |
| `medical:report` | `MedicalReportCommand` | Generate a medical health report from patient data |
| `qr:generate` | `QRCodeGenerateCommand` | Generate a QR code |
| `route:diagram` | `RouteDiagramCommand` | Controller function flow diagram |
| `route:flow` | `RouteFlowCommand` | Control flow diagram of route controller methods |
| `route:list` | `RouteListCommand` | Tabular list of every registered route |
| `schedule:list` | `ScheduleListCommand` | List registered scheduled tasks |
| `schedule:run` | `ScheduleRunCommand` | Run every task that is due now |
| `transformer:predict` | `TransformerPredictCommand` | Prediction with the transformer language model |
| `transformer:train` | `TransformerTrainingCommand` | Train the transformer language model |
| `translate:papago` | `PapagoCommand` | Translate text through Naver Papago |

Application commands (`root/App/Command`):

| Command | Class | Purpose |
| --- | --- | --- |
| `interpreter` | `InterpreterCommand` | Run a sample script through the Onetone interpreter runtime |
| `weather:status` | `WeatherCommand` | Nearby air-quality station data from Korean public APIs |
| `windows:style` | `WindowsStyleCommand` | Windows API style experiments |

`qr:generate` currently returns a `getDescription()` string copied from the Gemini command; the
behaviour is unaffected, but the built-in help text for that one command is wrong.

## Application modules

A module groups one feature — a board, a payment system, a catalog — into a single directory under
`root/App/Modules`. Direct child directories are discovered during bootstrap.

```text
App/Modules/Payment/
├── PaymentModule.php          App\Modules\Payment\PaymentModule, implements ModuleInterface
├── Configure/dependencies.php
├── Contract/
├── Controller/
├── Service/
└── Command/
```

Extending `AbstractModule` supplies conventional dependency, controller-route and CLI-command
loading. State is configured in `root/App/Configure/modules.php`:

```php
return [
    'enabled'  => null,             // null enables every discovered module
    'disabled' => ['payment'],      // always overrides `enabled`
    'order'    => ['board', 'comment'],
];
```

Declared dependencies always load before the preferred `order`. Disabling a module that an enabled
module requires stops bootstrap with a dependency error rather than booting into a broken graph.

Cross-module calls go through the manager, and only through exported contracts:

```php
$paymentGateway = $moduleManager->service('payment', PaymentGatewayInterface::class);
```

`ModuleManager` rejects disabled, missing, unregistered and non-exported services. Modules shipped
in this package: `AudioAnalysis`, `Board`, `Comment`.

## Database access

`root/App/Configure/dependencies.php` registers `PHPDataObject` as a container factory, applies
`DatabaseConfig::fromEnv()`, opens the connection and hands it to `ActiveRecord`.

Because entities reach the database through a static handle rather than through the container, the
factory is additionally installed as an `ActiveRecord` connection resolver:

```php
ActiveRecord::setConnectionResolver(static function () use ($container): void {
    $container->get(PHPDataObject::class);
});
```

Registering a resolver instead of resolving eagerly means a request that touches no entity never
opens a connection, and container boot stays free of I/O. The container aliases `db`,
`ai.agents`, `ai.orchestrator`, `kakao.login`, `naver.login` and `scheduler` are bound in the same
file.

## Task scheduling

A single `Scheduler` instance is shared across the request lifecycle so CLI commands and HTTP
handlers observe the same task set. Its timezone comes from `APP_TIMEZONE`, falling back to UTC
when the name does not parse.

Tasks are declared in `root/App/Configure/schedule.php`, which must return a callable receiving the
scheduler and the container. `schedule:list` prints the registered tasks; `schedule:run` executes
those that are due.

## Standard library

`src/Classes` is a general-purpose library, independent of the HTTP stack. The complete set of
top-level namespaces:

`AI`, `Array`, `Audio`, `Barcode`, `Base`, `Broadcasting`, `CLI`, `COM`, `Cache`, `Chord`,
`Chrome`, `Class`, `Client`, `ClientURL`, `Compression`, `Crawler`, `Crypt`, `Data`,
`DataStructor`, `Database`, `Date`, `Debug`, `Delivery`, `DependencyInjection`, `Device`,
`Directory`, `Document`, `Dom`, `Emulator`, `Encode`, `Event`, `Exception`, `FFI`, `File`,
`FileSystem`, `Format`, `GraphQL`, `HTML`, `HTTP`, `Hash`, `Header`, `I18N`, `Image`,
`Interpreter`, `Iterator`, `LLM`, `Layout`, `Linker`, `Logging`, `Mail`, `Markdown`, `Math`,
`Maya`, `Medical`, `NLP`, `OperationSystem`, `Pagination`, `Permission`, `Protocol`, `Proxy`,
`Queue`, `Recursive`, `Reflection`, `Regex`, `Repository`, `Routing`, `SSE`, `Scheduler`,
`Security`, `Service`, `Socket`, `Sort`, `StandardPHPLibrary`, `Storage`, `Streaming`, `System`,
`Text`, `Token`, `Transformer`, `UUID`, `Upload`, `Video`, `Web`, `XML`, `XMPP`.

Other top-level trees under `src/`:

| Directory | Contents |
| --- | --- |
| `Abstract` | Abstract base classes |
| `Annotation` | Routing, injection and MCP attributes |
| `Bridge` | `Application`, `Javascript`, `UnityWeb` host bridges |
| `Command` | Framework console commands |
| `Component` | The `application` entry-point stack: Bootstrap, Container, Contract, Exception, Foundation, Http, Kernel, Log, Pipeline, Routing |
| `Constant` | Shared constants |
| `Constraints` | Validation constraints |
| `Contract` | Interfaces consumed across layers |
| `Defaults` | Generated data tables (emoji, international, locale, GeoIP region, MIME, chi-squared, z-score, alpha tables) |
| `Enumeration` | 42 enumerations |
| `Exception` | Exception hierarchy |
| `FFI` | C header bindings |
| `Framework` | The `runtime` entry-point stack: Component, Configure, Context, Contract, Enumeration, Event, Events, Livewire, Middleware, Module, Routing, Template |
| `Interface` | Public interfaces |
| `Message` | Message objects |
| `Plugins` | `API` integrations and OAuth plugins |
| `Service` | Service providers for the `application` entry point |
| `Support` | Support helpers |
| `Template` | CLI, error, exception and shutdown templates |
| `Traits` | Reusable traits |
| `Validation` | `FileValidation`, `PHPValidation` |

`src/Plugins/API` holds ready-made clients for third-party services, including exchange rates,
lyrics providers, Korean public data, messaging and login providers, and media services.

## Configuration reference

Every variable below is read from `root/.env` (or the process environment). Defaults are the
behaviour when the key is unset or empty.

### Application

| Variable | Default | Effect |
| --- | --- | --- |
| `APP_ENTRY_POINT` | `runtime` | Kernel stack; `runtime` or `application`, anything else is refused at startup |
| `APP_TIMEZONE` | `UTC` | Default timezone for PHP and the scheduler |
| `APP_DEBUG` | `false` | When true: display errors, display startup errors, `E_ALL`, and an `error_log` path |
| `APP_ERROR_REPORTING` | `E_ALL & ~E_NOTICE` | Numeric error-reporting level override |
| `APP_MAINTENANCE` | `false` | Serve the maintenance response |
| `APP_MAINTENANCE_BYPASS_PATHS` | `/health,/ready` | Comma-separated paths that stay reachable during maintenance |
| `APP_PUBLIC_URL` | `http://localhost:8080` | Public origin for OAuth redirects and absolute URLs, no trailing slash |
| `APP_SERVER` | unset | Bind for the PHP built-in server, wired in `root/index.php` |

### Security

| Variable | Default | Effect |
| --- | --- | --- |
| `APP_HSTS_ENABLED` | `false` | Send `Strict-Transport-Security` on HTTPS requests |
| `APP_HSTS_VALUE` | `max-age=31536000; includeSubDomains` | HSTS header value |
| `APP_SECURITY_CSP` | unset | Full `Content-Security-Policy` value; leave empty when loading third-party fonts or CDNs |
| `APP_TRUSTED_PROXIES` | unset | Comma-separated IPv4/IPv6 addresses or CIDRs whose `X-Forwarded-Proto` is believed; empty means no proxy is trusted |

### Routing and diagnostics

| Variable | Default | Effect |
| --- | --- | --- |
| `ROUTE_CACHE` | `false` | Compile and reuse the route table |
| `ROUTE_CACHE_PATH` | `App/Cache/routes.cache.php` | Route cache output path |
| `IS_DEBUGGABLE` | `false` | Register debug event subscribers |
| `PROFILER_ENABLED` | `false` | Emit kernel spans on the profiler timeline |
| `USE_PROXY` | `true` | Proxy usage flag |
| `STRUCTURED_LOG_ENABLED` | `true` | Structured JSON kernel logging |

### Internationalisation

`APP_LOCALE`, `APP_LANGUAGE`, `APP_LANG`, `APP_FALLBACK_LOCALE`, `APP_FALLBACK_LANGUAGE`.
`Translator` prefers `APP_LOCALE`, then `APP_LANGUAGE`, then `APP_LANG`.

### Database

`MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USERNAME`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`. The template
targets Docker (`host.docker.internal:3306`); copy `root/.env.local.example` to `root/.env.local`
for a host PHP install. Containers skip `.env.local` through `RUNNING_IN_DOCKER`.

### Third-party credentials

All are unset by default and each is read only by the integration that needs it:
`NAVER_CLIENT_ID`, `NAVER_CLIENT_SECRET`, `NAVER_REDIRECT_URL`, `KAKAO_REDIRECT_URL`,
`KAKAO_NATIVE_APP_KEY`, `KAKAO_RESTFUL_API_KEY`, `KAKAO_JAVASCRIPT_KEY`, `KAKAO_ADMIN_KEY`,
`KAKAO_LOGIN_CLIENT_SECRET`, `CLAUDE_API_KEY`, `CHATGPT_API_KEY`, `GEMINI_API_KEY`,
`KOREA_PUBLIC_DATA_API_KEY`, `NASA_API_KEY`, `YOUTUBE_API_KEY`, `BLOCKIO_API_KEY`,
`OPEN_DART_API_KEY`, `JYOGAKUEN_LOGIN_TOKEN`, `EXHENTAI_IPB_MEMBER_ID`, `EXHENTAI_IPB_PASS_HASH`,
`EXHENTAI_IGNEOUS`, `EXHENTAI_S`.

`.htaccess` denies direct access to any file matching `\.(ini|sh|log|env)`, so `root/.env` is not
served by Apache. Confirm the equivalent rule exists before deploying behind nginx.

## Quality tooling

| Command | What it runs |
| --- | --- |
| `composer test` | PHPUnit with `--no-coverage` |
| `composer test:coverage` | PHPUnit with text, Clover and HTML reports into `build/coverage/` |
| `composer analyse` | PHPStan level 5, `--memory-limit 2G` |
| `composer analyse:baseline` | Regenerate `phpstan-baseline.neon` |
| `composer cs` | php-cs-fixer in dry-run mode, with a diff |
| `composer cs:fix` | php-cs-fixer, writing changes |
| `composer lint` | `cs` then `analyse` |
| `composer check` | `lint` then `test` |

The PHPUnit suite is `tests/` (353 test files). Coverage sources are `src/` and `root/App/`,
excluding `src/Defaults`, `root/App/Resource`, `root/App/Attachment` and `root/App/Cache`. Tests
run with `date.timezone=UTC`, `intl.default_locale=C.UTF-8` and a 1 GB memory limit.

Coverage requires `pcov` or `xdebug`; `composer test` passes `--no-coverage` so it never needs one.
PHPUnit 10.5 has no minimum-coverage option, and no line-coverage threshold is enforced anywhere.

PHPStan analyses `src` only, at level 5, with `src/Defaults` excluded from both analysis and
scanning. `reportUnmatchedIgnoredErrors` is off because `src` carries 101 inline
`@phpstan-ignore` comments that no longer match at level 5, and that diagnostic cannot be
baselined. Two error patterns are ignored globally: the missing
`preg_last_error_constant` function and unsafe `new static` usage.

When regenerating the baseline, comment out the `includes` block first — otherwise PHPStan reads
the existing baseline, finds nothing left to report, and writes an empty one.

## Code style

`.php-cs-fixer.dist.php` is the only place code style is defined. It covers `src` and `tests` and
excludes `src/Defaults`.

- `@PSR12`, with **tab** indentation — `@PSR12` alone would impose four spaces
- LF line endings
- Short array syntax
- `declare(strict_types=1)` inserted where missing (risky rule)
- Explicit strict `true` appended to `in_array` / `array_search` / `array_keys` (risky rule)
- Unused imports removed
- Vertically aligned PHPDoc

Both risky rules change behaviour, not only formatting. The first `cs:fix` run over a tree that has
not been fixed before touches roughly 1,175 files; land it as its own commit so the diff stays
reviewable.

## Deployment

### Apache

The root `.htaccess` makes `root/` the front controller from a parent document root: it denies
`vendor/*.php|rb|py`, denies dotfiles and `composer.*`, denies `.ini`, `.sh`, `.log` and `.env`,
disables MultiViews, serves existing files and directories under `root/` directly, and rewrites
everything else to `root/index.php` with the query string preserved.

Pointing the virtual host at `root/` directly is the cleaner arrangement; the rewrite set exists
for shared hosting where the document root cannot be moved.

### nginx / PHP-FPM

Set the document root to `root/`, route unmatched paths to `index.php`, and deny `.env` and
dotfiles explicitly — none of the `.htaccess` protections apply. `Runtime` detects nginx and
finishes the FastCGI response before running post-response work.

### FrankenPHP

Use `root/frankenphp-worker.php` as the worker script. `Runtime` is constructed with
`['server' => 'frankenphp']` on every iteration.

### LiteSpeed

Detected by name; the LiteSpeed response-flush path is selected automatically.

## Known limitations

These are properties of the current code, stated so they are not discovered in production:

- The `runtime` and `application` entry points maintain **separate route tables**. A route defined
  for one is invisible to the other.
- `root/.env` is mandatory. Without it `Runtime::run()` throws rather than falling back to
  defaults.
- `vendor/` is not committed. `composer install` is required before anything runs.
- No line-coverage threshold is enforced by any configuration in this repository.
- PHPStan's `reportUnmatchedIgnoredErrors` is disabled, so stale `@phpstan-ignore` comments are
  not reported.
- `src/Defaults` is excluded from analysis, coverage and style fixing. Changes there are checked by
  nothing.
- `qr:generate` reports a description belonging to another command.

## License

AGPL-3.0-only. Copyright (C) Onetoneframework.

Author: onetone — <tactics6655@gmail.com>
