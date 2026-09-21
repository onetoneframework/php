# Onetone PHP application layer

This page documents the Clover/Onetone stack under `res/Platform/PHP`: **boot** (`index.php` → `Runtime` → `Mapper` → `HttpKernel` / `CliKernel`), **configuration** (`.env`, route cache), **controllers and routing** (attributes and programmatic `Router`), **DI**, **database/ActiveRecord**, **scheduler**, **events**, **Livewire**, **CLI commands**, and **tests / PHPStan**. The [Interpreter](/documentation) page covers the C / VM side.

## Table of contents

- [What lives in this tree](#what-lives-in-this-tree)
- [Runtime version and golden rules](#runtime-version-and-golden-rules)
- [Requirements and Composer](#requirements-and-composer)
- [HTTP entry: request pipeline](#http-entry-request-pipeline)
- [Configuration: `.env` and route cache](#configuration-env-and-route-cache)
- [Controllers, routes, and responses](#controllers-routes-and-responses)
- [DI: `dependencies.php` and the container](#di-dependenciesphp-and-the-container)
- [Database and entities](#database-and-entities)
- [Scheduler (`schedule.php`)](#scheduler-schedulephp)
- [Events and the event bus](#events-and-the-event-bus)
- [Livewire-style components](#livewire-style-components)
- [CLI: commands and the console](#cli-commands-and-the-console)
- [Tests, PHPStan, and code style](#tests-phpstan-and-code-style)
- [Where to look when debugging](#where-to-look-when-debugging)

---

<a id="what-lives-in-this-tree"></a>
## What lives in this tree

| Area | Path | Notes |
|------|------|--------|
| Framework | `res/Platform/PHP/src/` | Namespace `Clover\` — routing, HTTP/CLI kernels, middleware, DI, scheduler, Livewire, etc. |
| Application | `res/Platform/PHP/root/App/` | Namespace `App\` — controllers, views under `App/View/`, commands, Livewire components, providers. |
| Entry | `root/index.php` | **Single front controller** for both HTTP and CLI: `Runtime` → `Mapper::matchRunner()` → `HttpKernel` or `CliKernel`. |
| Configuration | `App/Configure/` | `dependencies.php` (DI), `schedule.php` (cron tasks), `common.php`, `Interceptor/`, `EventDispatcher/` + `eventbus.*.yml`. |
| Caches | `App/Cache/` | Route cache file (path from `ROUTE_CACHE_PATH`), structured logs if enabled. |
| Tooling | `phpunit.xml.dist`, `phpstan.neon.dist`, `.php-cs-fixer.dist.php` | Run from repo root with explicit paths (see below). |

`BASE_PATH` at runtime is **`res/Platform/PHP/root`**. Views and assets are resolved from that root (e.g. `App/View/...`, `App/Frontend/Layout/...`).

**Service providers** (`src/Service/*ServiceProvider.php`) exist for an alternate bootstrap path when `Application`/`HttpKernel` mode is selected in `index.php`. In the **default** `Runtime`/`Mapper` path used by this repo, **`dependencies.php` is the source of truth** for app singletons — keep it aligned with any provider if you maintain both entry modes.

---

<a id="runtime-version-and-golden-rules"></a>
## Runtime version and golden rules

- **PHP**: **≥ 8.0**; `composer.json` may **pin the platform to 8.3** — prefer code that runs cleanly on **8.3**.
- **Composer**: always run from the **monorepo root** with `--working-dir=./res/Platform/PHP` unless a script documents otherwise.
- **Never commit or echo real `.env` values**; treat `root/.env` as secret.
- **Route not updating?** Disable route cache or delete the cached file when `ROUTE_CACHE=true`.
- **FFI / Win32**: avoid C constants that behave as pointers or callbacks in PHP FFI (native interop is easy to get wrong at ABI boundaries).
- **Dependencies**: prefer extending `Clover\Classes\*` and existing components over adding new third-party packages when possible.
- **Style**: English-only in source, **tabs**, **braces on every `if`**, per repository rules.

---

<a id="requirements-and-composer"></a>
## Requirements and Composer

```bash
composer install --working-dir=./res/Platform/PHP
```

On Windows, if `vendor/bin/` is empty, re-run `composer install` without `--no-scripts` or run PHPUnit from `vendor/phpunit/phpunit/phpunit` inside `res/Platform/PHP/vendor/`.

**Environment file** (first-time):

```text
copy res\Platform\PHP\root\.env.example res\Platform\PHP\root\.env
```

Edit `res/Platform/PHP/root/.env` for local DB, `APP_DEBUG`, `ROUTE_CACHE`, etc. — never paste secrets into chat or commits.

---

<a id="http-entry-request-pipeline"></a>
## HTTP entry: request pipeline

1. Web server document root: **`res/Platform/PHP/root`** (or FPM/FrankenPHP worker pointing at the same `index.php`).
2. Every request hits **`index.php`**, which constructs **`Clover\Framework\Component\Runtime`** and **`Mapper::matchRunner()`** to select HTTP vs CLI.
3. **HTTP** path: middleware stack (including **RoutingMiddleware**) loads or **builds the route table** (from controller annotations + providers), matches URL/method, then dispatches the **controller action**.
4. The controller returns a **`Response`** (text, JSON, layout-wrapped view, redirect, etc.).

**Local server** (from monorepo root):

```bash
php -S localhost:8080 -t res/Platform/PHP/root
```

**FrankenPHP** (if you use the bundled worker):

```bash
frankenphp php-server --worker ./res/Platform/PHP/root/frankenphp-worker.php
```

Conceptual order: **Request → global middleware → router → (route middleware) → controller → Response**.

**Runtime → Mapper → kernel**

`index.php` drives **`Runtime->run`**: error handler, `.env` / dotenv, default options, inject PHP env into `$_ENV`, then **`Mapper`**: set proxy, **`matchRunner`** (find interceptors, **`setContainer`**, boot application), detect CLI vs HTTP, then **`Mapper->run`** → **`CliKernel`** or **`HttpKernel`**.

**`HttpKernel`** path: `run` → (FPM / Swoole as configured) → **`handleRequest`** → **Router** loads routes (`fromDirectory` or **`fromCachedArray`**), **`Router->setContainer`**, **`Router->handle`**, result becomes **`Response`** → print body, status code, flush.

---

<a id="configuration-env-and-route-cache"></a>
## Configuration: `.env` and route cache

`Runtime` loads `root/.env` and exposes values via `$_ENV` for the kernel and your bindings.

| Variable | Role |
|----------|------|
| `APP_DEBUG`, `APP_TIMEZONE`, `APP_ERROR_REPORTING` | Developer/runtime behavior. |
| `APP_SERVER` | Intended SAPI: `fpm`, `swoole`, or `frankenphp` (drives integration assumptions). |
| `APP_MAINTENANCE` | When `true`, kernel can return **503** for maintenance. |
| `ROUTE_CACHE`, `ROUTE_CACHE_PATH` | If `ROUTE_CACHE=true`, routes are **cached to a PHP file** — after **any** new `#[Route]`, **delete the cache file** or set `ROUTE_CACHE=false` or changes will not appear. |
| `STRUCTURED_LOG_ENABLED` | JSON / structured request logs (paths often under `App/Cache/...` per branch). |
| `MYSQL_*` | Used by your `dependencies.php` DB setup. |

When enabled, **trace headers** (`X-Trace-Id` / W3C `traceparent`) tie browser → PHP logs to upstreams.

---

<a id="controllers-routes-and-responses"></a>
## Controllers, routes, and responses

**Conventions** (as used in `App\Controller\HomeController` and elsewhere):

- Class-level **`#[Prefix('/')]`** (or a subtree prefix) scopes all actions.
- **`#[Route(method: 'GET'|'POST'|..., pattern: '/path')]`** on a **public** method.
- Controllers that render HTML often extend **`LayoutComponentController`**, which wires layout skins, CSS/JS in `<head>`, and view paths under `App/View/`.

**Minimal example**

```php
namespace App\Controller;

use Clover\Annotation;
use Clover\Classes\HTTP\Request;
use Clover\Framework\Component\LayoutComponentController;
use Clover\Framework\Component\Response;

#[Annotation\Prefix('/')]
class ExampleController extends LayoutComponentController
{
    #[Annotation\Route(method: 'GET', pattern: '/hello')]
    public function hello(Request $request): Response
    {
        return $this->responseText('Hello from Onetone');
    }
}
```

**Response helpers** (use these instead of raw `echo`):

- `responseText`, `responseJson`, `responseXml` — as exposed on your base controller.
- `layout($skin, '/App/View/foo.php', $data)` — for themed pages.
- `redirect($url)` — 302/303 as appropriate.

**Programmatic routing** — `Clover\Classes\Routing\Router`:

```php
use Clover\Classes\Routing\Router;

$router = new Router();
$router->get('/index', function () {
    return $this->responseText('This is main page');
});

$router->get('/api/{a}?:([a-z]+)/{b}?:(\d+)/{c}?:(.*)?', function ($a, $b, $c) {
    return $this->responseJson(compact('a', 'b', 'c'));
});

$router->group('/first', function ($route) {
    $route->get('/second', function () {
        return $this->responseText('This is main page');
    });
});
```

**Middleware** — `#[Middleware(SomeMiddleware::class)]` on a class or method. **Interceptors** under `App/Configure/Interceptor/` hook routing outcomes (e.g. mutate `Response`).

---

<a id="di-dependenciesphp-and-the-container"></a>
## DI: `dependencies.php` and the container

`App/Configure/dependencies.php` must **return** a `callable` with signature `(\Clover\Classes\DependencyInjection\Container $container): void` — **not** an array. `Mapper::setContainer()` loads the file and invokes the callable. In this repository the same file registers **`KakaoLogin`**, **`NaverLogin`**, **`PHPDataObject`**, **`Scheduler`**, and string aliases (e.g. `bind('db', PHPDataObject::class)`).

**Database + ActiveRecord** — this is the same pattern as in `App/Configure/dependencies.php` (the `set(PHPDataObject::class, …)` closure):

```php
use Clover\Classes\Database\ActiveRecord;
use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\DependencyInjection\Container;
use Clover\Framework\Component\DatabaseConfig;

$container->set(PHPDataObject::class, static function (Container $container): PHPDataObject {
    $db = new PHPDataObject();
    DatabaseConfig::fromEnv()->apply($db);
    $db->createConnection();

    ActiveRecord::setDatabaseConnection($db);

    return $db;
});

$container->bind('db', PHPDataObject::class);
```

**Scheduler (same file, shortened):** `Scheduler` is built with a **`DateTimeZone`** from `APP_TIMEZONE`, then **`App/Configure/schedule.php`** is loaded if it exists: it must `return` a `callable(Scheduler, Container): void` which the bootstrap invokes with `($scheduler, $container)`.

**Resolve** via **`Clover\Framework\Context\ApplicationContext::getContainer()`** → `$container->get(PHPDataObject::class)` or `->get('db')` — do not `new` a service the container owns.

---

<a id="database-and-entities"></a>
## Database and entities

`Game` in **`App/Entity/Game.php`** matches the project’s **attribute** style: `#[Entity\ID]`, `#[Entity\Column(name: "no", type: "integer")]`-style property mapping, and relations such as `#[Entity\OneToMany(targetEntity: "App\\Entity\\Appearance", mappedBy: "game_no")]`. Use that file as the reference for new entities.

**Find / insert–update (same entity fields as in `Game.php`):**

```php
use App\Entity\Game;

$game = new Game();
$game = $game->findBy('title', 'Starcraft');

$game = new Game();
$game->title = 'Starcraft';
$game->type = 'RTS';
$game->brand_no = 1;
$game->release = '1995-05-01';
$game = $game->save();
```

Adjust column names to match your table; the entity uses `no` as the primary key via `#[Entity\ID]` on `$no`.

<a id="scheduler-schedulephp"></a>
## Scheduler (`schedule.php`)

**Scheduler** — `Clover\Classes\Scheduler\` (`CronExpression`, `ScheduledTask`, `Scheduler`).

- **`App/Configure/schedule.php`** returns `callable(Scheduler, Container): void` and registers named tasks with cron expressions and callbacks.

**`App/Configure/schedule.php` in this repo** returns the same `callable(Scheduler, Container): void` shape. A real registered task is **`system:heartbeat`** (every minute, no-op body — placeholder you can replace):

```php
return static function (Scheduler $scheduler, Container $container): void {
    $scheduler->call(
        'system:heartbeat',
        static function (): void {
            // no-op: replace with health check / metrics if needed
        },
        '* * * * *',
        'In-memory heartbeat used to verify the scheduler is alive.'
    );
};
```

**Operational use**: from the host OS, run `php index.php schedule:run` on a **cron** (e.g. every minute); the scheduler only executes tasks that are **due** at the current time. Flags: `--task=system:heartbeat` to force one task, `--now=<iso8601>` to override the clock in tests.

```bash
php index.php schedule:list
php index.php schedule:run
php index.php schedule:run --task=system:heartbeat
```

---

<a id="events-and-the-event-bus"></a>
## Events and the event bus

- Subscribers live under **`App/Configure/EventDispatcher/`** and are **auto-discovered**; **`eventbus.*.yml`** can declare bus wiring.
- Publishing: resolve **`EventBusInterface`** from the container and `->publish($event)` (see framework docs for the concrete class).

Use this for **domain decoupling** (order placed, cache invalidation) without stuffing logic into a single fat controller.

---

<a id="livewire-style-components"></a>
## Livewire-style components

- Runtime: **`Component`**, **`LivewireManager`**, **`POST /livewire/update`**, client script at **`GET /livewire/livewire.js`**.
- **App** components: **`App/Livewire/`** (auto-discovered).
- In a view: `<?= livewire('counter', [...]) ?>` and `<?= livewire_scripts() ?>` where required.

Suitable for **stateful server-driven UI** without a separate SPA for small widgets.

---

<a id="cli-commands-and-the-console"></a>
## CLI: commands and the console

Use **`php bin/console ...`** from **`res/Platform/PHP`**. The existing **`php index.php ...`** entry point from **`res/Platform/PHP/root`** remains available for compatibility.

`CLIRouter` discovers `Clover\Implement\CommandInterface` implementations under:

- `root/App/Command/`
- `src/Command/`

**Implement** `getName()`, `getDescription()`, `configure()`, `run(Input $input): bool`.  
Register options with **`new InputOption(...)`** in `configure()`.  
**Output**: `Clover\Classes\System\Output::printLine()` (not `echo`).

**Examples**

```bash
php index.php route:list
php index.php schedule:list
php index.php schedule:run
```

---

<a id="tests-phpstan-and-code-style"></a>
## Tests, PHPStan, and code style

**PHPUnit** (from monorepo root)

```bash
php ./res/Platform/PHP/vendor/bin/phpunit -c ./res/Platform/PHP/phpunit.xml.dist
```

If `vendor/bin/phpunit` is missing on Windows, run:  
`php res/Platform/PHP/vendor/phpunit/phpunit/phpunit -c res/Platform/PHP/phpunit.xml.dist` (from repository root, adjust path if your `vendor` layout differs).

- Mirror **`src/Classes/...` → `tests/Classes/...`** and **`App/...` → `tests/App/...`**.

**PHPStan**

```bash
php ./bin/phar/phpstan.phar analyse ./res/Platform/PHP/src --memory-limit=500M -c ./res/Platform/PHP/phpstan.neon.dist
```

**Code style** — `.php-cs-fixer.dist.php` — keep **tabs**; do not let a formatter switch to spaces for this tree.

**Quick syntax check** — `php -l res/Platform/PHP/path/to/File.php`.

---

<a id="where-to-look-when-debugging"></a>
## Where to look when debugging

| Symptom | Check |
|--------|--------|
| New route 404s | `ROUTE_CACHE` + delete `routes.cache` (or path in `.env`) |
| Service null / wrong instance | `dependencies.php` binding, `ApplicationContext::getContainer()` |
| Only CLI or only HTTP broken | `index.php` mode vs `Runtime` / `Application` path |
| Scheduler not firing | Cron OS schedule, `schedule:run` logs, `schedule:list` |
| Livewire 419 / no JS | `livewire_scripts()` included, path `/livewire/*` not blocked |
| Deeper HTTP flow | [HTTP entry](#http-entry-request-pipeline) and `res/Platform/PHP/src/Framework/Component/HttpKernel.php` / `Middleware/` in the framework tree |

---

**Onetone public site (outside `/documentation`):** [Getting started](/docs/installation) · [Architecture](/docs/architecture) — product/marketing pages, not the technical hub sidebar.
