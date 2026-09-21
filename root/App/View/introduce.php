<?php
/**
 * Introduce view — multilingual (EN / KO / JA) with auto-detection.
 *
 * Language resolution order:
 *   1. ?lang=xx query param (also written to a 1-year cookie via JS).
 *   2. `lang` cookie set by the switcher.
 *   3. HTTP Accept-Language negotiation.
 *   4. Fallback: "en".
 */

$__supportedLocales = ['en', 'ko', 'ja'];

$__resolveLocale = static function (array $supported): string {
    $fromQuery = $_GET['lang'] ?? null;
    if (is_string($fromQuery) && in_array($fromQuery, $supported, true)) {
        return $fromQuery;
    }

    $fromCookie = $_COOKIE['lang'] ?? null;
    if (is_string($fromCookie) && in_array($fromCookie, $supported, true)) {
        return $fromCookie;
    }

    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if (is_string($accept) && $accept !== '') {
        foreach (explode(',', $accept) as $chunk) {
            $code = strtolower(substr(trim($chunk), 0, 2));
            if (in_array($code, $supported, true)) {
                return $code;
            }
        }
    }

    return 'en';
};

$locale = $__resolveLocale($__supportedLocales);

/**
 * Dictionary — values may contain trusted HTML (e.g. <code>, <em>, <strong>, <br>).
 */
$__dict = [
    'en' => [
        'lang_name'        => 'English',
        'lang_switch_aria' => 'Switch language',
        'hero_title_1'     => 'Build at the',
        'hero_title_2'     => 'Speed of Thought',
        'hero_subtitle'    => 'A high-performance PHP framework powered by C internals, annotation-driven routing, and a modern TypeScript frontend toolkit.',
        'hero_lead'        => 'From Runtime to Response in milliseconds. Onetone combines native C performance with PHP expressiveness and TypeScript modularity — so you ship faster without compromise.',
        'cta_docs'         => 'Language &amp; runtime docs',
        'cta_install'      => 'Install PHP app',
        'cta_github'       => 'GitHub',

        'slides_aria'      => 'Framework highlights',
        'slides' => [
            ['eyebrow' => 'Runtime',  'title' => 'C at the core, PHP at the surface',  'text' => 'Hot paths drop into a native interpreter while your application code stays ordinary, readable PHP.',              'href' => '/docs/architecture', 'cta' => 'Read the architecture'],
            ['eyebrow' => 'Routing',  'title' => 'Attributes are the router',          'text' => 'One #[Route] above a method is the whole registration — cached, prefixed and middleware-aware.',                  'href' => '/docs/api',          'cta' => 'Routing reference'],
            ['eyebrow' => 'Realtime', 'title' => 'Broadcast once, reach everywhere',   'text' => 'SSE, EventBus and Null drivers ship in the box, all behind a single ShouldBroadcast contract.',                     'href' => '/documentation',     'cta' => 'Event documentation'],
            ['eyebrow' => 'Tooling',  'title' => 'One container, web and CLI',         'text' => 'The same services resolve in an HTTP request and in bin/console — there is no second bootstrap to maintain.',       'href' => '/docs/components',   'cta' => 'Browse components'],
        ],

        'facts_aria'       => 'Platform facts',
        'facts' => [
            ['strong' => 'PHP 8.4',       'span' => 'Target runtime for this tree'],
            ['strong' => 'Attributes',    'span' => '<code>#[Route]</code> + <code>#[Prefix]</code> routing'],
            ['strong' => 'DI',            'span' => 'Shared container for web + CLI'],
            ['strong' => 'Layouts',       'span' => 'CSS/JS pipeline + Blade-style PHP templates'],
            ['strong' => 'Event Bus',     'span' => 'Sync &amp; async <code>EventManager</code> with middleware'],
            ['strong' => 'Broadcasting',  'span' => 'SSE, EventBus &amp; Null drivers out of the box'],
            ['strong' => 'ORM',           'span' => 'Annotation-based ActiveRecord with migrations &amp; seeders'],
            ['strong' => 'FFI',           'span' => 'Win32, ONNX Runtime, OpenCV, GLFW bindings'],
            ['strong' => 'Transformer',   'span' => 'Multi-head attention language model in pure PHP'],
            ['strong' => 'Interpreter',   'span' => 'Embedded Onetone script parser &amp; evaluator'],
        ],

        'explore_label' => 'Explore',
        'explore_title' => 'Pages shipped with this demo',
        'explore_desc'  => 'Marketing-style sections are real routes — open them from the top navigation or the cards below.',
        'explore_cards' => [
            ['title' => 'Products',              'desc' => 'Framework, cloud, analytics, and server story pages you can replace with your product copy.', 'href' => '/products',             'cta' => 'View products'],
            ['title' => 'Docs hub',              'desc' => 'Installation, architecture, components, and API reference stubs — extend with your own HTML views.', 'href' => '/docs',         'cta' => 'Open docs hub'],
            ['title' => 'Interpreter reference', 'desc' => 'Long-form markdown for the C interpreter and language surface lives on a dedicated route.', 'href' => '/documentation',        'cta' => 'Read'],
            ['title' => 'Resources',             'desc' => 'Tutorials, certification, and podcast placeholders — swap for your LMS or media links.', 'href' => '/resources/tutorials',    'cta' => 'Tutorials'],
            ['title' => 'Partners &amp; enterprise', 'desc' => 'Partner directory stubs plus enterprise support and consulting outlines.', 'href' => '/partners',                     'cta' => 'Partners'],
            ['title' => 'Community',             'desc' => 'Forum, Discord, and contribution entry points — wire to your real channels when you launch.', 'href' => '/community',         'cta' => 'Community home'],
        ],

        'quickstart_label' => 'Local dev',
        'quickstart_title' => 'Run this repository in two commands',
        'quickstart_desc'  => 'From the repo root on your machine (see <code>AGENTS.md</code> for PHPStan, PHPUnit, and route cache notes).',
        'quickstart_head'  => 'terminal · bash or PowerShell',
        'quickstart_hint'  => 'Then open <a href="/">http://localhost:8080/</a> — if routes do not refresh, clear <code>res/Platform/PHP/root/App/Cache/routes.cache.php</code> or set <code>ROUTE_CACHE=false</code> in <code>res/Platform/PHP/root/.env</code>.',

        'lifecycle_label' => 'Architecture',
        'lifecycle_title' => 'Request Lifecycle',
        'lifecycle_desc'  => 'From bootstrap to response — a deterministic, high-performance pipeline.',
        'lifecycle_steps' => [
            ['n' => '01', 'title' => 'Runtime',    'desc' => '<code>Clover\\Framework\\Component\\Runtime</code> wires the base proxy, registers <code>ErrorHandler</code>, boots <code>EventManager</code>, warms <code>MatrixOps</code>, and loads <code>.env</code> / <code>.env.local</code> through <em>Dotenv</em> immutable mode. Emits the <code>RUNTIME_BOOT</code> lifecycle event.'],
            ['n' => '02', 'title' => 'Configure',  'desc' => 'Applies display errors, timezone, and error reporting from <code>.env</code>; publishes <code>RUNTIME_CONFIGURE_STARTED</code> / <code>FINISHED</code> spans on the kernel bus. <code>PROFILER_ENABLED</code> resets the timeline so Swoole workers start clean on every request.'],
            ['n' => '03', 'title' => 'Mapper',     'desc' => 'Builds the <code>Container</code>, binds framework services (logger, event bus, <strong>BroadcastManager</strong>, session, routing), and resolves controller <em>Interceptors</em>. Publishes <code>RUNTIME_MAPPING_STARTED</code> / <code>FINISHED</code>.'],
            ['n' => '04', 'title' => 'HttpKernel', 'desc' => 'Runs the <code>StackRequestHandler</code> middleware chain — <code>SecurityHeadersMiddleware</code> → <code>MaintenanceMiddleware</code> → <code>RoutingMiddleware</code> — then dispatches to the matched controller under either FPM or <strong>Swoole</strong>.'],
            ['n' => '05', 'title' => 'Response',   'desc' => 'Fires <code>BeforeResponseSend</code>, serializes the payload through <code>JSONHandler</code> / <code>SimpleXML</code> / template output, and writes the body. Nginx, LightSpeed, and CLI each get a dedicated flush strategy.'],
            ['n' => '06', 'title' => 'Terminate',  'desc' => 'Emits <code>AfterResponseSend</code>, drains queued async events, persists dead-letter queues, and resets the profiler timeline so long-lived workers stay deterministic.'],
        ],

        'eco_label' => 'Ecosystem',
        'eco_title' => 'Everything You Need, Built In',
        'eco_desc'  => 'A vertically integrated toolkit — no third-party glue required.',
        'eco_primary_title' => 'Annotation Router',
        'eco_primary_desc'  => 'Define routes with PHP 8 attributes — #[Prefix], #[Route], #[Middleware]. Supports parameter patterns, route groups, and automatic caching for zero-overhead dispatching.',
        'eco_primary_cta'   => 'See architecture notes &rarr;',
        'eco_cards' => [
            ['title' => 'Clover ORM',        'desc' => 'Annotation-based ActiveRecord with #[Entity\\Column], #[OneToMany], #[OneToOne] relations and eager loading.'],
            ['title' => 'Interceptor System','desc' => 'Pre/post handle hooks at the boot level. Mutate handlers, arguments, or responses before they reach the client.'],
            ['title' => 'Swoole Runtime',    'desc' => 'Run your application in-memory with Swoole coroutines for 10x throughput over traditional FPM.'],
            ['title' => 'Promise Engine',    'desc' => 'Asynchronous control flow with resolve/reject semantics and chainable then() callbacks in pure PHP.'],
            ['title' => 'CLI Kernel',        'desc' => 'Same container, same DI — dual-mode execution. Build console commands that share your application logic seamlessly.'],
        ],

        'modules_label' => 'Modules',
        'modules_title' => 'Capabilities by namespace',
        'modules_desc'  => 'Every block below is a real sub-tree under <code>res/Platform/PHP/src/Classes</code> — no glue, no third-party adapter layer.',
        'modules_cards' => [
            ['title' => 'Routing',              'desc' => '<code>Classes\\Routing\\Router</code> compiles annotations into a priority-ordered trie, persists <code>routes.cache.php</code>, and supports per-route query guards, host filters, and <code>pathQueryKey</code> fallback when URL rewriting is unavailable.'],
            ['title' => 'Dependency Injection', 'desc' => '<code>Classes\\DependencyInjection\\Container</code> exposes <code>bind()</code>, <code>singleton()</code>, <code>set()</code>, <code>get()</code>, and <code>autowire()</code>. Facades (<code>EventManager</code>, <code>BroadcastManagerFacade</code>) resolve from the container first, then fall back to a local singleton.'],
            ['title' => 'Broadcasting',         'desc' => '<code>BroadcastManager</code> dispatches <code>BroadcastMessage</code> DTOs onto pluggable drivers: <code>NullBroadcaster</code>, <code>LogBroadcaster</code>, <code>SseBroadcaster</code> (via <code>SseServer</code>), and <code>EventBusBroadcaster</code> for in-process fan-out.'],
            ['title' => 'Events &amp; EventBus','desc' => '<code>EventBus</code> supports synchronous publish, async <code>queue</code>, retry with exponential backoff, dead-letter persistence, middleware, and a PSR-14-compatible <code>EventDispatcherAdapter</code>.'],
            ['title' => 'Database',             'desc' => 'Active-record entities, a fluent <code>QueryBuilder</code>, a driver layer, <code>SchemaModelGenerator</code> that reverse-engineers models from schema, plus <code>Migration</code> and <code>Seeder</code> runners.'],
            ['title' => 'Cache',                'desc' => 'Drop-in drivers for <code>Apc</code>, <code>Memcache</code>, and <code>Redis</code> with a unified key/value surface so controllers stay transport-agnostic.'],
            ['title' => 'Security',             'desc' => '<code>SecurityManager</code>, <code>SecurityAuditor</code>, and <code>SecurityTester</code> plus <code>Auth</code>, <code>Guard</code>, and middleware packages cover headers, rate limiting, CSRF, and session hardening.'],
            ['title' => 'Real-time (SSE)',      'desc' => '<code>Classes\\SSE\\SseServer</code> manages session-scoped channel subscriptions; the <code>SseBroadcaster</code> pushes broadcast messages straight to browsers without an external broker.'],
            ['title' => 'I18N',                 'desc' => '<code>Classes\\I18N\\I18n</code> handles locale negotiation, plural rules, and bilingual tokenization for transformer pipelines through <code>BilingualMorphTokenizer</code>.'],
            ['title' => 'Transformer &amp; LLM','desc' => 'Full encoder stack — <code>MultiHeadAttention</code>, <code>ScaledDotProductAttention</code>, <code>LayerNormalization</code> — plus connectors for ChatGPT, Claude, Gemini, Copilot, DeepSeek, Grok, Perplexity, and Qwen under <code>Classes\\LLM\\*</code>.'],
            ['title' => 'Interpreter',          'desc' => 'A complete Onetone-script stack (<code>Tokenizer</code> → <code>Parser</code> → <code>Interpreter</code>) embeds scripting into PHP with a pluggable <code>Environment</code> and <code>Bindings</code> layer.'],
            ['title' => 'FFI',                  'desc' => 'Typed bindings for <code>WindowsAPI</code>, <code>CustomWindow</code>, <code>GLFW</code>, <code>OnnxRuntime</code>, <code>LibOpenCVVideoIO</code>, and <code>HuggingFace</code> — all through PHP\'s FFI extension.'],
            ['title' => 'Math',                 'desc' => '40+ numerical modules covering <code>Vector</code>, <code>Matrix</code>, <code>MatrixOps</code>, <code>Calculus</code>, <code>ProbabilityDistributions</code>, <code>RungeKuttaPhysicsSimulation</code>, financial (<code>Loan</code>, <code>Credit</code>), vocal tract estimation (<code>VTL</code>, <code>Vocal</code>), and more.'],
            ['title' => 'Expert System',        'desc' => '<code>Classes\\ExpertSystem</code> ships a forward-chaining rule engine with fact bases, conflict resolution strategies, and a compact DSL for decision logic.'],
            ['title' => 'Barcode &amp; Media',  'desc' => '<code>BarcodeHandler</code> produces 1D/2D codes, and <code>LibOpenCVVideoIO</code> streams video frames for CV workloads.'],
            ['title' => 'Protocols',            'desc' => '<code>Classes\\Protocol\\Internet</code>, <code>Classes\\Protocol\\PHP</code>, <code>Classes\\XMPP\\Bosh</code>, and a <code>GraphQL</code> parser / executor round out the transport surface.'],
        ],

        'ann_label' => 'Annotations',
        'ann_title' => 'Attribute reference',
        'ann_desc'  => 'All real <code>#[Attribute]</code> classes shipped in <code>src/Annotation/</code> — copy-paste ready.',
        'ann_primary_title' => 'HTTP routing attributes',
        'ann_primary_desc'  => '<code>#[Prefix]</code>, <code>#[Route(method, pattern)]</code>, <code>#[GetMapping]</code>, <code>#[PostMapping]</code>, <code>#[PutMapping]</code>, <code>#[PatchMapping]</code>, <code>#[DeleteMapping]</code>, <code>#[RequestMapping]</code>. Each route declares <em>method</em>, <em>pattern</em>, <em>host</em>, <em>priority</em>, required <em>query</em> pairs, and a <em>pathQueryKey</em> fallback.',
        'ann_primary_cta'   => 'Architecture notes &rarr;',
        'ann_cards' => [
            ['title' => 'Entity attributes',   'desc' => '<code>#[Entity\\Column(name, type, nullable)]</code>, <code>#[Entity\\Id]</code>, <code>#[Entity\\JoinColumn]</code>, <code>#[Entity\\JoinTable]</code>, <code>#[Entity\\OneToOne]</code>, <code>#[Entity\\OneToMany]</code>, <code>#[Entity\\ManyToOne]</code>, <code>#[Entity\\ManyToMany]</code>.'],
            ['title' => 'Pipeline attributes', 'desc' => '<code>#[Middleware]</code> stacks handlers before/after a route. <code>#[ContentType]</code> locks response format, <code>#[NotFound]</code> installs a 404 handler, <code>#[Autowire]</code> marks constructor dependencies, and <code>#[Value]</code> injects configuration values.'],
            ['title' => 'Controller kinds',    'desc' => '<code>#[Controller]</code> marks MVC endpoints that render views, <code>#[RestController]</code> returns serialized payloads automatically, and <code>#[Query]</code> documents expected query parameters at the method signature.'],
            ['title' => 'MCP tool surface',    'desc' => '<code>#[McpTool]</code> exposes any method as a Model Context Protocol tool — one attribute flips the method into the <code>res/Platform/PHP/mcp-server</code> registry so LLMs can call it.'],
            ['title' => 'Interceptors',        'desc' => 'Extend <code>Clover\\Abstract\\Interceptor</code> to mutate handlers, arguments, and responses around the route target — the framework resolves them from the DI container for free.'],
        ],

        'dx_label' => 'Developer Experience',
        'dx_title' => 'Write Code That Reads Like Intent',
        'dx_desc'  => 'Expressive, type-safe, and zero boilerplate.',

        'realtime_label' => 'Realtime &amp; Events',
        'realtime_title' => 'Broadcast Once, Reach Everywhere',
        'realtime_desc'  => 'Events declare their own channels; drivers handle the wire.',

        'caps_label' => 'Capabilities',
        'caps_title' => 'Built for the Demanding',
        'caps_desc'  => 'A robust foundation for systems that can\'t afford downtime.',
        'caps_cards' => [
            ['title' => 'C-Powered Core',       'desc' => 'Custom C-based template engine and interpreter deliver native-speed rendering with minimal memory overhead.'],
            ['title' => 'Dependency Injection', 'desc' => 'Constructor and method injection resolved automatically from the container. Configure bindings in a single file.'],
            ['title' => 'Middleware Pipeline',  'desc' => 'Apply middleware at the controller or method level with #[Middleware] attributes. Stack them, chain them, compose them.'],
            ['title' => 'Entity Relations',     'desc' => '#[OneToOne], #[OneToMany] with eager loading. ActiveRecord models that map directly to your schema.'],
            ['title' => 'Polyglot FFI',         'desc' => 'Call Windows API, Rust, Go, Python, and C# directly through PHP\'s Foreign Function Interface.'],
            ['title' => 'TypeScript Frontend',  'desc' => 'Built-in component system with Three.js integration, interactive maps, and media players — no webpack required.'],
        ],

        'cli_label' => 'CLI',
        'cli_title' => 'One container, two entry points',
        'cli_desc'  => 'Every command shares the same DI container, event bus, and broadcaster — run them from <code>php bin/console &lt;name&gt;</code>.',
        'cli_cards' => [
            ['title' => 'Database',              'desc' => '<code>DatabaseMigrateCommand</code> runs schema migrations; <code>DatabaseSeedCommand</code> hydrates fixtures; the <code>SchemaModelGenerator</code> reverse-engineers entity classes directly from the live schema.'],
            ['title' => 'Routing introspection', 'desc' => '<code>RouteListCommand</code> dumps every compiled route, <code>RouteFlowCommand</code> animates the dispatch flow, and <code>RouteDiagramCommand</code> renders an attribute-driven topology diagram.'],
            ['title' => 'LLM integrations',      'desc' => '<code>ChatGPTCommand</code>, <code>ClaudeCommand</code>, <code>GoogleGeminiCommand</code>, and <code>PapagoCommand</code> invoke provider clients from the shared <code>Classes\\LLM\\*</code> tree — useful for batch summarization, evals, and translation jobs.'],
            ['title' => 'MCP bridge',            'desc' => '<code>McpCommand</code> starts the Model Context Protocol server from <code>res/Platform/PHP/mcp-server</code>, exposing every <code>#[McpTool]</code>-annotated method to compatible agents.'],
            ['title' => 'Transformer jobs',      'desc' => '<code>TransformerTrainingCommand</code> trains the bundled encoder stack; <code>TransformerPredictCommand</code> runs inference — both reuse <code>Classes\\Transformer\\*</code>.'],
            ['title' => 'Developer tooling',     'desc' => '<code>WizardCommand</code> scaffolds controllers, entities, and migrations interactively; <code>GithubCommand</code>, <code>HarnessCommand</code>, and <code>ExchangeRateCommand</code> automate day-to-day integrations.'],
            ['title' => 'Interactive samples',   'desc' => 'Fully-playable terminal games — <code>SokobanCommand</code>, <code>MinesweeperCommand</code>, <code>MazeCommand</code>, <code>SquidCommand</code> — demonstrate the console renderer, input loop, and keyboard-driven FSM patterns.'],
            ['title' => 'Domain examples',       'desc' => '<code>MedicalReportCommand</code>, <code>RandomDronePathCommand</code>, and <code>QueryCommand</code> showcase the Math and Expert-System modules in a single <code>php bin/console</code> invocation.'],
        ],

        'config_label' => 'Configuration',
        'config_title' => 'One <code>.env</code> to tune the whole stack',
        'config_desc'  => 'Environment variables are read by <code>Runtime::overrideOptionsFromEnv()</code>, <code>Profiler</code>, <code>Mapper</code>, and individual drivers.',
        'config_head'  => 'res/Platform/PHP/root/.env',
        'config_hint'  => 'Place host-specific overrides in <code>.env.local</code>; Dotenv loads it in mutable mode after <code>.env</code>, unless <code>RUNNING_IN_DOCKER=1</code> or <code>/.dockerenv</code> exists.',

        'perf_label' => 'Performance',
        'perf_title' => 'Numbers That Matter',
        'perf_desc'  => 'Illustrative targets from lab-style setups — always benchmark on <em>your</em> hardware and workload.',
        'perf_cards' => [
            ['value' => '0.4ms', 'tone' => 'accent',  'unit' => 'Average Response',  'desc' => 'Cached route resolution with in-memory Swoole runtime.'],
            ['value' => '12k',   'tone' => 'emerald', 'unit' => 'Requests / Second', 'desc' => 'Sustained throughput on a single 2-core instance.'],
            ['value' => '8MB',   'tone' => 'amber',   'unit' => 'Memory Footprint',  'desc' => 'Base memory consumption with full ORM and router loaded.'],
        ],

        'stack_title' => 'Designed to sit beside your stack',
        'stack_lead'  => 'No fictional customer logos — just the kinds of tools this repo is tested alongside.',
        'stack_items' => [
            ['icon' => 'fa-brands fa-php',              'text' => 'PHP 8.4 language features &amp; extensions'],
            ['icon' => 'fa-solid fa-box-open',          'text' => 'Composer for <code>res/Platform/PHP</code> dependencies'],
            ['icon' => 'fa-solid fa-server',            'text' => 'Built-in server or your own FPM / reverse proxy'],
            ['icon' => 'fa-solid fa-flask',             'text' => 'PHPUnit + PHPStan workflows documented in-repo'],
            ['icon' => 'fa-solid fa-bolt',              'text' => 'Swoole coroutines for long-running workers'],
            ['icon' => 'fa-solid fa-database',          'text' => 'MySQL / PostgreSQL / SQLite via the driver layer'],
            ['icon' => 'fa-solid fa-broadcast-tower',   'text' => 'SSE out of the box, WebSocket / MQTT by plug-in driver'],
            ['icon' => 'fa-solid fa-robot',             'text' => 'Model Context Protocol for AI-agent tooling'],
        ],

        'quality_label' => 'Quality',
        'quality_title' => 'Testing, static analysis, and observability',
        'quality_desc'  => 'The same tree that runs production is the tree you test and profile.',
        'quality_cards' => [
            ['title' => 'PHPUnit',            'desc' => 'Suite definition in <code>phpunit.xml.dist</code>, tests colocated under <code>tests/Classes/*</code>. The broadcasting module ships dedicated coverage for manager, facade, and container wiring.'],
            ['title' => 'PHPStan',            'desc' => 'Strict-level static analysis with strong type inference; the classmap autoloader keeps analysis accurate even for attribute-only classes.'],
            ['title' => 'Kernel profiler',    'desc' => '<code>Classes\\Debug\\Profiler</code> emits <code>KernelSpanStarted</code> / <code>KernelSpanFinished</code> spans around every lifecycle phase — enable with <code>PROFILER_ENABLED=true</code> and pipe to any listener.'],
            ['title' => 'Structured logging', 'desc' => '<code>Classes\\Logging\\StructuredKernelLogger</code> outputs machine-readable events; the <code>LogBroadcaster</code> driver serializes broadcasts as JSON lines for offline replay.'],
            ['title' => 'Error handler',      'desc' => '<code>Classes\\Debug\\ErrorHandler</code> registers a unified exception-to-response pipeline with tailored templates under <code>src/Template/</code> (Error, Exception, Shutdown).'],
            ['title' => 'Security tooling',   'desc' => '<code>SecurityAuditor</code> enumerates misconfigurations; <code>SecurityTester</code> exercises auth and CSRF paths; <code>SecurityHeadersMiddleware</code> hardens every response.'],
        ],

        'map_label' => 'Map',
        'map_title' => 'Directory cheatsheet',
        'map_desc'  => 'A mental model of where things live inside <code>res/Platform/PHP</code>.',
        'map_head'  => 'res/Platform/PHP/',

        'footer_framework'        => 'Framework',
        'footer_product'          => 'Product',
        'footer_resources'        => 'Resources',
        'footer_ecosystem'        => 'Ecosystem',
        'footer_subscribe'        => 'Subscribe',
        'footer_subscribe_lead'   => 'Stay updated on releases and ecosystem news.',
        'footer_subscribe_ph'     => 'Email address',
        'footer_copy'             => '&copy; 2026 Onetone Framework. All rights reserved.',
        'footer_links' => [
            'framework' => [
                ['href' => '/about/team',    'text' => 'Team'],
                ['href' => '/about/careers', 'text' => 'Careers'],
                ['href' => '/about/brand',   'text' => 'Brand assets'],
                ['href' => '/about/story',   'text' => 'Story'],
            ],
            'product' => [
                ['href' => '/news/releases',  'text' => 'Release notes'],
                ['href' => '/news/blog',      'text' => 'Blog'],
                ['href' => '/products/cloud', 'text' => 'Onetone Cloud'],
                ['href' => '/enterprise',     'text' => 'Enterprise'],
            ],
            'resources' => [
                ['href' => '/documentation',      'text' => 'Interpreter documentation'],
                ['href' => '/docs',               'text' => 'Documentation hub'],
                ['href' => '/docs/api',           'text' => 'API reference'],
                ['href' => '/resources/tutorials','text' => 'Tutorials'],
                ['href' => '/community',          'text' => 'Community'],
            ],
            'ecosystem' => [
                ['href' => '/ecosystem/packages',   'text' => 'Packages'],
                ['href' => '/ecosystem/themes',     'text' => 'Themes'],
                ['href' => '/ecosystem/extensions', 'text' => 'Extensions'],
                ['href' => '/shop',                 'text' => 'Shop'],
            ],
        ],
    ],

    'ko' => [
        'lang_name'        => '한국어',
        'lang_switch_aria' => '언어 전환',
        'hero_title_1'     => '생각의 속도로',
        'hero_title_2'     => '빌드하세요',
        'hero_subtitle'    => 'C 내부 엔진, 어노테이션 기반 라우팅, 현대적인 TypeScript 프론트엔드 툴킷을 탑재한 고성능 PHP 프레임워크입니다.',
        'hero_lead'        => '런타임에서 응답까지 단 몇 밀리초. Onetone은 네이티브 C의 성능, PHP의 표현력, TypeScript의 모듈성을 하나로 묶어 타협 없이 빠르게 배포할 수 있게 해 줍니다.',
        'cta_docs'         => '언어 &amp; 런타임 문서',
        'cta_install'      => 'PHP 앱 설치',
        'cta_github'       => 'GitHub',

        'slides_aria'      => '핵심 특징 슬라이드',
        'slides' => [
            ['eyebrow' => '런타임',   'title' => 'C를 심장에, PHP를 표면에',      'text' => '무거운 경로는 네이티브 인터프리터가 맡고, 애플리케이션 코드는 평범하고 읽기 쉬운 PHP 그대로 남습니다.',       'href' => '/docs/architecture', 'cta' => '아키텍처 살펴보기'],
            ['eyebrow' => '라우팅',   'title' => '어트리뷰트가 곧 라우터입니다',   'text' => '메서드 위의 #[Route] 한 줄이 등록의 전부입니다. 캐시·프리픽스·미들웨어까지 함께 처리됩니다.',            'href' => '/docs/api',          'cta' => '라우팅 레퍼런스'],
            ['eyebrow' => '실시간',   'title' => '한 번의 브로드캐스트, 모든 곳에', 'text' => 'SSE·EventBus·Null 드라이버가 기본 제공되며, 모두 하나의 ShouldBroadcast 계약 뒤에 있습니다.',           'href' => '/documentation',     'cta' => '이벤트 문서 보기'],
            ['eyebrow' => '도구',     'title' => '하나의 컨테이너, 웹과 CLI',      'text' => '같은 서비스가 HTTP 요청에서도 bin/console 에서도 그대로 해석됩니다. 두 번째 부트스트랩은 필요 없습니다.', 'href' => '/docs/components',   'cta' => '컴포넌트 둘러보기'],
        ],

        'facts_aria'       => '플랫폼 요약',
        'facts' => [
            ['strong' => 'PHP 8.4',       'span' => '이 트리의 기준 런타임'],
            ['strong' => '어트리뷰트',     'span' => '<code>#[Route]</code> + <code>#[Prefix]</code> 라우팅'],
            ['strong' => 'DI',            'span' => '웹과 CLI가 공유하는 단일 컨테이너'],
            ['strong' => '레이아웃',       'span' => 'CSS/JS 파이프라인 + Blade 스타일 PHP 템플릿'],
            ['strong' => '이벤트 버스',    'span' => '동기·비동기 <code>EventManager</code> + 미들웨어'],
            ['strong' => '브로드캐스팅',   'span' => 'SSE·EventBus·Null 드라이버 기본 제공'],
            ['strong' => 'ORM',           'span' => '마이그레이션·시더 내장 어노테이션 ActiveRecord'],
            ['strong' => 'FFI',           'span' => 'Win32·ONNX Runtime·OpenCV·GLFW 바인딩'],
            ['strong' => 'Transformer',   'span' => '순수 PHP로 구현한 Multi-head Attention 언어 모델'],
            ['strong' => 'Interpreter',   'span' => '내장형 Onetone 스크립트 파서·인터프리터'],
        ],

        'explore_label' => '둘러보기',
        'explore_title' => '이 데모가 포함한 페이지들',
        'explore_desc'  => '마케팅 스타일 섹션은 모두 실제 라우트입니다. 상단 내비게이션이나 아래 카드에서 바로 열어 보세요.',
        'explore_cards' => [
            ['title' => '제품',              'desc' => '프레임워크·클라우드·분석·서버 소개 페이지. 자사 카피로 자유롭게 교체하세요.', 'href' => '/products',             'cta' => '제품 보기'],
            ['title' => '문서 허브',          'desc' => '설치·아키텍처·컴포넌트·API 레퍼런스 스텁을 자체 HTML 뷰로 확장하세요.',    'href' => '/docs',                 'cta' => '문서 허브 열기'],
            ['title' => '인터프리터 레퍼런스', 'desc' => 'C 인터프리터와 언어 표면에 대한 상세 문서가 별도 라우트에 있습니다.',       'href' => '/documentation',        'cta' => '읽기'],
            ['title' => '리소스',             'desc' => '튜토리얼·자격증·팟캐스트 자리. LMS나 미디어 링크로 교체하세요.',           'href' => '/resources/tutorials',  'cta' => '튜토리얼'],
            ['title' => '파트너 &amp; 엔터프라이즈', 'desc' => '파트너 디렉터리 스텁과 엔터프라이즈 지원·컨설팅 개요.',                    'href' => '/partners',             'cta' => '파트너'],
            ['title' => '커뮤니티',            'desc' => '포럼·Discord·기여 진입점. 실제 런칭 시 실제 채널로 연결하세요.',         'href' => '/community',            'cta' => '커뮤니티 홈'],
        ],

        'quickstart_label' => '로컬 개발',
        'quickstart_title' => '두 줄로 이 저장소 실행하기',
        'quickstart_desc'  => '저장소 루트에서 실행합니다 (PHPStan·PHPUnit·라우트 캐시 관련 주의 사항은 <code>AGENTS.md</code> 참고).',
        'quickstart_head'  => '터미널 · bash 또는 PowerShell',
        'quickstart_hint'  => '<a href="/">http://localhost:8080/</a>을 열어 보세요. 라우트가 갱신되지 않으면 <code>res/Platform/PHP/root/App/Cache/routes.cache.php</code>를 삭제하거나 <code>res/Platform/PHP/root/.env</code>에 <code>ROUTE_CACHE=false</code>를 지정하세요.',

        'lifecycle_label' => '아키텍처',
        'lifecycle_title' => '요청 라이프사이클',
        'lifecycle_desc'  => '부트스트랩에서 응답까지 — 결정적이고 고성능인 파이프라인.',
        'lifecycle_steps' => [
            ['n' => '01', 'title' => 'Runtime',    'desc' => '<code>Clover\\Framework\\Component\\Runtime</code>가 베이스 프록시를 연결하고 <code>ErrorHandler</code> 등록, <code>EventManager</code> 기동, <code>MatrixOps</code> 워밍업, <em>Dotenv</em> 불변 모드로 <code>.env</code> / <code>.env.local</code>을 로드합니다. <code>RUNTIME_BOOT</code> 라이프사이클 이벤트를 발행합니다.'],
            ['n' => '02', 'title' => 'Configure',  'desc' => '<code>.env</code>의 표시 오류·타임존·에러 리포팅을 적용하고 커널 버스에 <code>RUNTIME_CONFIGURE_STARTED</code> / <code>FINISHED</code> 스팬을 발행합니다. <code>PROFILER_ENABLED</code>는 요청마다 타임라인을 초기화해 Swoole 워커도 깨끗한 상태로 시작하게 합니다.'],
            ['n' => '03', 'title' => 'Mapper',     'desc' => '<code>Container</code>를 구성해 프레임워크 서비스(로거·이벤트 버스·<strong>BroadcastManager</strong>·세션·라우팅)를 바인딩하고, 컨트롤러 <em>Interceptor</em>를 해석합니다. <code>RUNTIME_MAPPING_STARTED</code> / <code>FINISHED</code>를 발행합니다.'],
            ['n' => '04', 'title' => 'HttpKernel', 'desc' => '<code>StackRequestHandler</code>의 미들웨어 체인 (<code>SecurityHeadersMiddleware</code> → <code>MaintenanceMiddleware</code> → <code>RoutingMiddleware</code>)을 실행하고 매칭된 컨트롤러를 FPM 또는 <strong>Swoole</strong>에서 호출합니다.'],
            ['n' => '05', 'title' => 'Response',   'desc' => '<code>BeforeResponseSend</code>를 발행하고 <code>JSONHandler</code>·<code>SimpleXML</code>·템플릿 출력으로 페이로드를 직렬화하여 응답 본문을 작성합니다. Nginx·LightSpeed·CLI 각각에 맞는 플러시 전략이 적용됩니다.'],
            ['n' => '06', 'title' => 'Terminate',  'desc' => '<code>AfterResponseSend</code>를 발행하고, 대기 중인 비동기 이벤트를 비우고 데드레터 큐를 영속화하며 프로파일러 타임라인을 리셋해 장기 실행 워커도 결정적 상태를 유지합니다.'],
        ],

        'eco_label' => '에코시스템',
        'eco_title' => '필요한 모든 것, 기본 탑재',
        'eco_desc'  => '서드파티 글루 없이 수직 통합된 툴킷.',
        'eco_primary_title' => '어노테이션 라우터',
        'eco_primary_desc'  => 'PHP 8 어트리뷰트 (#[Prefix], #[Route], #[Middleware]) 만으로 라우트 정의. 파라미터 패턴·그룹·자동 캐싱까지 지원해 디스패칭 오버헤드가 0에 수렴합니다.',
        'eco_primary_cta'   => '아키텍처 노트 보기 &rarr;',
        'eco_cards' => [
            ['title' => 'Clover ORM',        'desc' => '어노테이션 기반 ActiveRecord — #[Entity\\Column], #[OneToMany], #[OneToOne] 관계와 Eager Loading 지원.'],
            ['title' => '인터셉터 시스템',    'desc' => '부트 레벨의 pre/post 훅. 클라이언트에 도달하기 전에 핸들러·인자·응답을 자유롭게 가공합니다.'],
            ['title' => 'Swoole 런타임',      'desc' => 'Swoole 코루틴으로 애플리케이션을 인메모리 실행 — 전통적인 FPM 대비 10배 처리량.'],
            ['title' => 'Promise 엔진',       'desc' => '순수 PHP에서 resolve/reject 시맨틱과 체이닝 가능한 then() 콜백으로 비동기 흐름을 제어합니다.'],
            ['title' => 'CLI 커널',           'desc' => '같은 컨테이너, 같은 DI — 이중 모드 실행. 애플리케이션 로직을 공유하는 콘솔 명령을 매끄럽게 작성하세요.'],
        ],

        'modules_label' => '모듈',
        'modules_title' => '네임스페이스로 보는 기능',
        'modules_desc'  => '아래 블록은 모두 <code>res/Platform/PHP/src/Classes</code> 하위의 실제 서브트리입니다. 글루 코드나 서드파티 어댑터 계층이 없습니다.',
        'modules_cards' => [
            ['title' => 'Routing',              'desc' => '<code>Classes\\Routing\\Router</code>는 어노테이션을 우선순위 트라이로 컴파일해 <code>routes.cache.php</code>로 영속화하고, 라우트별 쿼리 가드·호스트 필터·URL 리라이트가 없을 때의 <code>pathQueryKey</code> 폴백을 지원합니다.'],
            ['title' => 'Dependency Injection', 'desc' => '<code>Classes\\DependencyInjection\\Container</code>는 <code>bind()</code>·<code>singleton()</code>·<code>set()</code>·<code>get()</code>·<code>autowire()</code>를 제공합니다. 파사드(<code>EventManager</code>·<code>BroadcastManagerFacade</code>)는 컨테이너 우선, 이후 로컬 싱글턴으로 폴백합니다.'],
            ['title' => 'Broadcasting',         'desc' => '<code>BroadcastManager</code>가 <code>BroadcastMessage</code> DTO를 플러그러블 드라이버로 디스패치 — <code>NullBroadcaster</code>·<code>LogBroadcaster</code>·<code>SseBroadcaster</code>(<code>SseServer</code> 경유)·<code>EventBusBroadcaster</code>를 통한 인프로세스 팬아웃을 지원합니다.'],
            ['title' => 'Events &amp; EventBus','desc' => '<code>EventBus</code>는 동기 퍼블리시, 비동기 <code>queue</code>, 지수 백오프 재시도, 데드레터 영속화, 미들웨어, PSR-14 호환 <code>EventDispatcherAdapter</code>를 제공합니다.'],
            ['title' => 'Database',             'desc' => 'ActiveRecord 엔티티, 플루언트 <code>QueryBuilder</code>, 드라이버 레이어, 스키마로부터 모델을 역공학하는 <code>SchemaModelGenerator</code>, <code>Migration</code>·<code>Seeder</code> 러너까지.'],
            ['title' => 'Cache',                'desc' => '<code>Apc</code>·<code>Memcache</code>·<code>Redis</code> 드롭인 드라이버가 통일된 키/값 인터페이스를 제공해 컨트롤러는 전송 계층에 독립적으로 유지됩니다.'],
            ['title' => 'Security',             'desc' => '<code>SecurityManager</code>·<code>SecurityAuditor</code>·<code>SecurityTester</code>와 <code>Auth</code>·<code>Guard</code>·미들웨어 패키지가 헤더·레이트 리밋·CSRF·세션 하드닝을 모두 커버합니다.'],
            ['title' => 'Real-time (SSE)',      'desc' => '<code>Classes\\SSE\\SseServer</code>가 세션 범위 채널 구독을 관리하고, <code>SseBroadcaster</code>가 외부 브로커 없이 브로드캐스트 메시지를 브라우저로 바로 푸시합니다.'],
            ['title' => 'I18N',                 'desc' => '<code>Classes\\I18N\\I18n</code>은 로케일 협상·복수 규칙을 처리하고, <code>BilingualMorphTokenizer</code>로 Transformer 파이프라인용 이중 언어 토크나이징도 지원합니다.'],
            ['title' => 'Transformer &amp; LLM','desc' => '완성된 인코더 스택(<code>MultiHeadAttention</code>·<code>ScaledDotProductAttention</code>·<code>LayerNormalization</code>)에 더해 <code>Classes\\LLM\\*</code>에서 ChatGPT·Claude·Gemini·Copilot·DeepSeek·Grok·Perplexity·Qwen 커넥터를 제공합니다.'],
            ['title' => 'Interpreter',          'desc' => '<code>Tokenizer</code> → <code>Parser</code> → <code>Interpreter</code>로 이어지는 Onetone 스크립트 스택. 플러그러블 <code>Environment</code>·<code>Bindings</code>로 PHP에 스크립팅을 내장합니다.'],
            ['title' => 'FFI',                  'desc' => 'PHP FFI 확장으로 <code>WindowsAPI</code>·<code>CustomWindow</code>·<code>GLFW</code>·<code>OnnxRuntime</code>·<code>LibOpenCVVideoIO</code>·<code>HuggingFace</code>에 타입이 지정된 바인딩을 제공합니다.'],
            ['title' => 'Math',                 'desc' => '<code>Vector</code>·<code>Matrix</code>·<code>MatrixOps</code>·<code>Calculus</code>·<code>ProbabilityDistributions</code>·<code>RungeKuttaPhysicsSimulation</code>·금융(<code>Loan</code>·<code>Credit</code>)·성도 길이 추정(<code>VTL</code>·<code>Vocal</code>) 등 40개 이상의 수치 모듈.'],
            ['title' => 'Expert System',        'desc' => '<code>Classes\\ExpertSystem</code>은 팩트 베이스와 충돌 해결 전략을 갖춘 전방향 추론 규칙 엔진, 그리고 결정 로직을 위한 컴팩트 DSL을 제공합니다.'],
            ['title' => '바코드 &amp; 미디어',   'desc' => '<code>BarcodeHandler</code>로 1D/2D 바코드를 생성하고, <code>LibOpenCVVideoIO</code>는 CV 워크로드를 위한 비디오 프레임을 스트리밍합니다.'],
            ['title' => '프로토콜',              'desc' => '<code>Classes\\Protocol\\Internet</code>·<code>Classes\\Protocol\\PHP</code>·<code>Classes\\XMPP\\Bosh</code>, 그리고 <code>GraphQL</code> 파서/실행기가 전송 계층을 마무리합니다.'],
        ],

        'ann_label' => '어노테이션',
        'ann_title' => '어트리뷰트 레퍼런스',
        'ann_desc'  => '<code>src/Annotation/</code>에 실제 포함된 모든 <code>#[Attribute]</code> 클래스 — 복붙 즉시 사용 가능합니다.',
        'ann_primary_title' => 'HTTP 라우팅 어트리뷰트',
        'ann_primary_desc'  => '<code>#[Prefix]</code>·<code>#[Route(method, pattern)]</code>·<code>#[GetMapping]</code>·<code>#[PostMapping]</code>·<code>#[PutMapping]</code>·<code>#[PatchMapping]</code>·<code>#[DeleteMapping]</code>·<code>#[RequestMapping]</code>. 각 라우트는 <em>method</em>·<em>pattern</em>·<em>host</em>·<em>priority</em>, 필수 <em>query</em> 쌍과 <em>pathQueryKey</em> 폴백을 선언합니다.',
        'ann_primary_cta'   => '아키텍처 노트 &rarr;',
        'ann_cards' => [
            ['title' => '엔티티 어트리뷰트',    'desc' => '<code>#[Entity\\Column(name, type, nullable)]</code>·<code>#[Entity\\Id]</code>·<code>#[Entity\\JoinColumn]</code>·<code>#[Entity\\JoinTable]</code>·<code>#[Entity\\OneToOne]</code>·<code>#[Entity\\OneToMany]</code>·<code>#[Entity\\ManyToOne]</code>·<code>#[Entity\\ManyToMany]</code>.'],
            ['title' => '파이프라인 어트리뷰트', 'desc' => '<code>#[Middleware]</code>로 라우트 앞뒤에 핸들러를 스택하고, <code>#[ContentType]</code>으로 응답 포맷 고정, <code>#[NotFound]</code>로 404 핸들러 설치, <code>#[Autowire]</code>로 생성자 의존성 표시, <code>#[Value]</code>로 설정값을 주입합니다.'],
            ['title' => '컨트롤러 종류',        'desc' => '<code>#[Controller]</code>는 뷰를 렌더링하는 MVC 엔드포인트, <code>#[RestController]</code>는 페이로드를 자동 직렬화, <code>#[Query]</code>는 메서드 시그니처에 기대 쿼리 파라미터를 문서화합니다.'],
            ['title' => 'MCP 툴 표면',          'desc' => '<code>#[McpTool]</code>로 아무 메서드나 Model Context Protocol 툴로 노출 — 어트리뷰트 하나면 <code>res/Platform/PHP/mcp-server</code> 레지스트리에 등록되어 LLM이 호출할 수 있습니다.'],
            ['title' => '인터셉터',              'desc' => '<code>Clover\\Abstract\\Interceptor</code>를 상속해 라우트 대상 주위에서 핸들러·인자·응답을 가공 — DI 컨테이너가 자동으로 해석합니다.'],
        ],

        'dx_label' => '개발자 경험',
        'dx_title' => '의도가 바로 읽히는 코드',
        'dx_desc'  => '표현력 있고 타입 안전하며 보일러플레이트 0.',

        'realtime_label' => '실시간 &amp; 이벤트',
        'realtime_title' => '한 번의 브로드캐스트, 모든 곳에 도달',
        'realtime_desc'  => '이벤트가 자신의 채널을 선언하고, 드라이버는 전송만 담당합니다.',

        'caps_label' => '역량',
        'caps_title' => '까다로운 현장을 위한 설계',
        'caps_desc'  => '다운타임이 허용되지 않는 시스템을 위한 견고한 기반.',
        'caps_cards' => [
            ['title' => 'C 기반 코어',      'desc' => '자체 제작한 C 기반 템플릿 엔진과 인터프리터가 최소 메모리로 네이티브 속도 렌더링을 제공합니다.'],
            ['title' => '의존성 주입',      'desc' => '컨테이너에서 생성자·메서드 주입이 자동으로 해석됩니다. 바인딩은 단일 파일에서 설정하세요.'],
            ['title' => '미들웨어 파이프라인', 'desc' => '컨트롤러 또는 메서드 수준에서 #[Middleware] 어트리뷰트로 미들웨어를 스택·체인·컴포지트할 수 있습니다.'],
            ['title' => '엔티티 관계',      'desc' => 'Eager Loading이 되는 #[OneToOne]·#[OneToMany]. 스키마에 바로 매핑되는 ActiveRecord 모델.'],
            ['title' => '다언어 FFI',       'desc' => 'PHP의 Foreign Function Interface로 Windows API·Rust·Go·Python·C#을 직접 호출합니다.'],
            ['title' => 'TypeScript 프론트엔드', 'desc' => 'Three.js 통합·인터랙티브 지도·미디어 플레이어를 포함한 컴포넌트 시스템을 webpack 없이 제공합니다.'],
        ],

        'cli_label' => 'CLI',
        'cli_title' => '하나의 컨테이너, 두 개의 진입점',
        'cli_desc'  => '모든 명령은 동일한 DI 컨테이너·이벤트 버스·브로드캐스터를 공유합니다 — <code>php bin/console &lt;name&gt;</code>으로 실행하세요.',
        'cli_cards' => [
            ['title' => '데이터베이스',         'desc' => '<code>DatabaseMigrateCommand</code>가 스키마 마이그레이션을 수행하고, <code>DatabaseSeedCommand</code>가 픽스처를 채우며, <code>SchemaModelGenerator</code>는 라이브 스키마에서 엔티티 클래스를 역공학합니다.'],
            ['title' => '라우트 인트로스펙션',   'desc' => '<code>RouteListCommand</code>가 컴파일된 모든 라우트를 덤프하고, <code>RouteFlowCommand</code>가 디스패치 흐름을 시각화하며, <code>RouteDiagramCommand</code>가 어트리뷰트 기반 토폴로지 다이어그램을 그립니다.'],
            ['title' => 'LLM 통합',              'desc' => '<code>ChatGPTCommand</code>·<code>ClaudeCommand</code>·<code>GoogleGeminiCommand</code>·<code>PapagoCommand</code>가 공유 <code>Classes\\LLM\\*</code> 트리의 프로바이더 클라이언트를 호출합니다 — 배치 요약·평가·번역 작업에 유용합니다.'],
            ['title' => 'MCP 브리지',            'desc' => '<code>McpCommand</code>가 <code>res/Platform/PHP/mcp-server</code>에서 Model Context Protocol 서버를 기동하여 <code>#[McpTool]</code>이 붙은 모든 메서드를 호환 에이전트에게 노출합니다.'],
            ['title' => 'Transformer 작업',      'desc' => '<code>TransformerTrainingCommand</code>는 번들된 인코더 스택을 학습하고, <code>TransformerPredictCommand</code>는 추론을 수행합니다 — 둘 다 <code>Classes\\Transformer\\*</code>를 재사용합니다.'],
            ['title' => '개발자 툴링',            'desc' => '<code>WizardCommand</code>가 컨트롤러·엔티티·마이그레이션을 대화식으로 스캐폴딩하고, <code>GithubCommand</code>·<code>HarnessCommand</code>·<code>ExchangeRateCommand</code>가 일상적인 통합을 자동화합니다.'],
            ['title' => '인터랙티브 샘플',        'desc' => '완주 가능한 터미널 게임 — <code>SokobanCommand</code>·<code>MinesweeperCommand</code>·<code>MazeCommand</code>·<code>SquidCommand</code> — 이 콘솔 렌더러·입력 루프·키보드 기반 FSM 패턴을 보여 줍니다.'],
            ['title' => '도메인 예제',            'desc' => '<code>MedicalReportCommand</code>·<code>RandomDronePathCommand</code>·<code>QueryCommand</code>가 단일 <code>php bin/console</code> 호출로 Math와 Expert-System 모듈을 시연합니다.'],
        ],

        'config_label' => '설정',
        'config_title' => '한 개의 <code>.env</code>로 스택 전체를 조율',
        'config_desc'  => '환경 변수는 <code>Runtime::overrideOptionsFromEnv()</code>·<code>Profiler</code>·<code>Mapper</code>와 각 드라이버가 읽어 갑니다.',
        'config_head'  => 'res/Platform/PHP/root/.env',
        'config_hint'  => '호스트별 오버라이드는 <code>.env.local</code>에 넣으세요. Dotenv가 <code>.env</code> 이후 가변 모드로 로드합니다. 단, <code>RUNNING_IN_DOCKER=1</code>이거나 <code>/.dockerenv</code>가 존재하면 로드하지 않습니다.',

        'perf_label' => '성능',
        'perf_title' => '의미 있는 수치',
        'perf_desc'  => '실험실 수준 세팅의 예시 목표치입니다 — 반드시 <em>직접</em> 하드웨어와 워크로드에서 벤치마크하세요.',
        'perf_cards' => [
            ['value' => '0.4ms', 'tone' => 'accent',  'unit' => '평균 응답 시간',     'desc' => 'Swoole 인메모리 런타임에서의 캐시된 라우트 해석.'],
            ['value' => '12k',   'tone' => 'emerald', 'unit' => '초당 요청 수',       'desc' => '2-코어 단일 인스턴스에서 지속 가능한 처리량.'],
            ['value' => '8MB',   'tone' => 'amber',   'unit' => '메모리 풋프린트',    'desc' => 'ORM과 라우터까지 로드된 상태의 기준 메모리 사용량.'],
        ],

        'stack_title' => '기존 스택 옆에 자연스럽게 놓이도록 설계',
        'stack_lead'  => '가상의 고객 로고 없이 — 이 저장소가 함께 테스트되는 도구들만 나열합니다.',
        'stack_items' => [
            ['icon' => 'fa-brands fa-php',              'text' => 'PHP 8.4 언어 기능과 확장'],
            ['icon' => 'fa-solid fa-box-open',          'text' => '<code>res/Platform/PHP</code> 의존성을 위한 Composer'],
            ['icon' => 'fa-solid fa-server',            'text' => '내장 서버 또는 기존 FPM / 리버스 프록시'],
            ['icon' => 'fa-solid fa-flask',             'text' => '저장소에 문서화된 PHPUnit + PHPStan 워크플로'],
            ['icon' => 'fa-solid fa-bolt',              'text' => '장시간 실행 워커를 위한 Swoole 코루틴'],
            ['icon' => 'fa-solid fa-database',          'text' => '드라이버 레이어를 통한 MySQL / PostgreSQL / SQLite'],
            ['icon' => 'fa-solid fa-broadcast-tower',   'text' => '기본 SSE, 플러그인 드라이버로 WebSocket / MQTT'],
            ['icon' => 'fa-solid fa-robot',             'text' => 'AI 에이전트 도구용 Model Context Protocol'],
        ],

        'quality_label' => '품질',
        'quality_title' => '테스트·정적 분석·관측 가능성',
        'quality_desc'  => '프로덕션이 실행되는 바로 그 트리에서 테스트하고 프로파일링합니다.',
        'quality_cards' => [
            ['title' => 'PHPUnit',            'desc' => '<code>phpunit.xml.dist</code>에 스위트 정의, 테스트는 <code>tests/Classes/*</code>에 공존합니다. 브로드캐스팅 모듈도 매니저·파사드·컨테이너 연결을 커버하는 전용 테스트를 제공합니다.'],
            ['title' => 'PHPStan',            'desc' => '강력한 타입 추론 기반 최고 수준 정적 분석 — 어트리뷰트 전용 클래스까지 정확히 분석할 수 있도록 classmap 오토로더가 보조합니다.'],
            ['title' => '커널 프로파일러',     'desc' => '<code>Classes\\Debug\\Profiler</code>가 라이프사이클 각 단계에서 <code>KernelSpanStarted</code> / <code>KernelSpanFinished</code> 스팬을 발행합니다 — <code>PROFILER_ENABLED=true</code>로 활성화하고 원하는 리스너로 파이프하세요.'],
            ['title' => '구조화된 로깅',       'desc' => '<code>Classes\\Logging\\StructuredKernelLogger</code>가 기계 가독 이벤트를 출력하고, <code>LogBroadcaster</code> 드라이버는 오프라인 재생을 위해 브로드캐스트를 JSON 라인으로 직렬화합니다.'],
            ['title' => '에러 핸들러',         'desc' => '<code>Classes\\Debug\\ErrorHandler</code>가 <code>src/Template/</code>의 전용 템플릿(Error·Exception·Shutdown)으로 예외→응답 파이프라인을 통합합니다.'],
            ['title' => '보안 툴링',           'desc' => '<code>SecurityAuditor</code>가 설정 오류를 열거하고, <code>SecurityTester</code>가 인증·CSRF 경로를 점검하며, <code>SecurityHeadersMiddleware</code>가 모든 응답을 하드닝합니다.'],
        ],

        'map_label' => '지도',
        'map_title' => '디렉터리 치트시트',
        'map_desc'  => '<code>res/Platform/PHP</code> 내부에 무엇이 어디에 있는지에 대한 멘탈 모델.',
        'map_head'  => 'res/Platform/PHP/',

        'footer_framework'        => '프레임워크',
        'footer_product'          => '제품',
        'footer_resources'        => '리소스',
        'footer_ecosystem'        => '에코시스템',
        'footer_subscribe'        => '구독',
        'footer_subscribe_lead'   => '릴리스와 에코시스템 소식을 받아 보세요.',
        'footer_subscribe_ph'     => '이메일 주소',
        'footer_copy'             => '&copy; 2026 Onetone Framework. All rights reserved.',
        'footer_links' => [
            'framework' => [
                ['href' => '/about/team',    'text' => '팀'],
                ['href' => '/about/careers', 'text' => '채용'],
                ['href' => '/about/brand',   'text' => '브랜드 자산'],
                ['href' => '/about/story',   'text' => '스토리'],
            ],
            'product' => [
                ['href' => '/news/releases',  'text' => '릴리스 노트'],
                ['href' => '/news/blog',      'text' => '블로그'],
                ['href' => '/products/cloud', 'text' => 'Onetone Cloud'],
                ['href' => '/enterprise',     'text' => '엔터프라이즈'],
            ],
            'resources' => [
                ['href' => '/documentation',      'text' => '인터프리터 문서'],
                ['href' => '/docs',               'text' => '문서 허브'],
                ['href' => '/docs/api',           'text' => 'API 레퍼런스'],
                ['href' => '/resources/tutorials','text' => '튜토리얼'],
                ['href' => '/community',          'text' => '커뮤니티'],
            ],
            'ecosystem' => [
                ['href' => '/ecosystem/packages',   'text' => '패키지'],
                ['href' => '/ecosystem/themes',     'text' => '테마'],
                ['href' => '/ecosystem/extensions', 'text' => '확장'],
                ['href' => '/shop',                 'text' => '샵'],
            ],
        ],
    ],

    'ja' => [
        'lang_name'        => '日本語',
        'lang_switch_aria' => '言語切り替え',
        'hero_title_1'     => '思考の速度で',
        'hero_title_2'     => 'ビルドする',
        'hero_subtitle'    => 'C 製の内部エンジン、アノテーション駆動のルーティング、モダンな TypeScript フロントエンド ツールキットを備えた高性能 PHP フレームワーク。',
        'hero_lead'        => 'ランタイムからレスポンスまでわずか数ミリ秒。Onetone はネイティブ C の性能、PHP の表現力、TypeScript のモジュール性を一つにまとめ、妥協なく素早く出荷できるようにします。',
        'cta_docs'         => '言語 &amp; ランタイムのドキュメント',
        'cta_install'      => 'PHP アプリをインストール',
        'cta_github'       => 'GitHub',

        'slides_aria'      => '主な特徴のスライド',
        'slides' => [
            ['eyebrow' => 'ランタイム',   'title' => 'コアは C、表層は PHP',            'text' => 'ホットパスはネイティブ インタープリタに委ね、アプリケーション コードは読みやすい普通の PHP のままです。',      'href' => '/docs/architecture', 'cta' => 'アーキテクチャを読む'],
            ['eyebrow' => 'ルーティング', 'title' => 'アトリビュートがそのままルーターに', 'text' => 'メソッドに付けた #[Route] 一行が登録のすべて。キャッシュ・プリフィックス・ミドルウェアまで面倒を見ます。', 'href' => '/docs/api',          'cta' => 'ルーティング リファレンス'],
            ['eyebrow' => 'リアルタイム', 'title' => '一度の配信で、あらゆる場所へ',      'text' => 'SSE・EventBus・Null ドライバを標準搭載し、すべて単一の ShouldBroadcast 契約の背後にあります。',            'href' => '/documentation',     'cta' => 'イベント ドキュメント'],
            ['eyebrow' => 'ツール',       'title' => '一つのコンテナで、Web と CLI',     'text' => '同じサービスが HTTP リクエストでも bin/console でも解決されます。二つ目のブートストラップは不要です。',        'href' => '/docs/components',   'cta' => 'コンポーネントを見る'],
        ],

        'facts_aria'       => 'プラットフォーム概要',
        'facts' => [
            ['strong' => 'PHP 8.4',       'span' => 'このツリーのターゲット ランタイム'],
            ['strong' => 'アトリビュート', 'span' => '<code>#[Route]</code> + <code>#[Prefix]</code> によるルーティング'],
            ['strong' => 'DI',            'span' => 'Web と CLI で共有する単一コンテナ'],
            ['strong' => 'レイアウト',     'span' => 'CSS/JS パイプライン + Blade 風 PHP テンプレート'],
            ['strong' => 'イベント バス', 'span' => '同期・非同期の <code>EventManager</code> とミドルウェア'],
            ['strong' => 'ブロードキャスト','span' => 'SSE・EventBus・Null ドライバを標準搭載'],
            ['strong' => 'ORM',           'span' => 'マイグレーションとシーダを備えたアノテーション ActiveRecord'],
            ['strong' => 'FFI',           'span' => 'Win32・ONNX Runtime・OpenCV・GLFW バインディング'],
            ['strong' => 'Transformer',   'span' => '純粋な PHP 実装の Multi-head Attention 言語モデル'],
            ['strong' => 'Interpreter',   'span' => '組み込み型 Onetone スクリプト パーサとインタプリタ'],
        ],

        'explore_label' => 'エクスプローラ',
        'explore_title' => 'このデモが収録するページ',
        'explore_desc'  => 'マーケティング風のセクションはすべて実際のルートです。上部ナビゲーションか下のカードから開いてみてください。',
        'explore_cards' => [
            ['title' => 'プロダクト',               'desc' => 'フレームワーク・クラウド・アナリティクス・サーバー紹介ページ。自社のコピーで自由に差し替えられます。', 'href' => '/products',             'cta' => 'プロダクトを見る'],
            ['title' => 'ドキュメント ハブ',          'desc' => 'インストール・アーキテクチャ・コンポーネント・API リファレンスのスタブ。独自の HTML ビューで拡張できます。',          'href' => '/docs',                 'cta' => 'ハブを開く'],
            ['title' => 'インタプリタ リファレンス',   'desc' => 'C インタプリタと言語面に関する長文ドキュメントは専用ルートにあります。',                                      'href' => '/documentation',        'cta' => '読む'],
            ['title' => 'リソース',                  'desc' => 'チュートリアル・認定・ポッドキャストのプレースホルダ。LMS やメディアのリンクに差し替えてください。',          'href' => '/resources/tutorials',  'cta' => 'チュートリアル'],
            ['title' => 'パートナー &amp; エンタープライズ', 'desc' => 'パートナー ディレクトリのスタブと、エンタープライズ サポート・コンサルティングの概要。',               'href' => '/partners',             'cta' => 'パートナー'],
            ['title' => 'コミュニティ',               'desc' => 'フォーラム・Discord・コントリビュートの入り口。本番公開時は実チャネルに接続してください。',                  'href' => '/community',            'cta' => 'コミュニティ ホーム'],
        ],

        'quickstart_label' => 'ローカル開発',
        'quickstart_title' => '2 行でこのリポジトリを起動',
        'quickstart_desc'  => 'リポジトリのルートで実行します (PHPStan・PHPUnit・ルート キャッシュの注意点は <code>AGENTS.md</code> を参照)。',
        'quickstart_head'  => 'ターミナル · bash または PowerShell',
        'quickstart_hint'  => '<a href="/">http://localhost:8080/</a> を開きます。ルートが更新されない場合は <code>res/Platform/PHP/root/App/Cache/routes.cache.php</code> を削除するか、<code>res/Platform/PHP/root/.env</code> に <code>ROUTE_CACHE=false</code> を設定してください。',

        'lifecycle_label' => 'アーキテクチャ',
        'lifecycle_title' => 'リクエスト ライフサイクル',
        'lifecycle_desc'  => 'ブートストラップからレスポンスまで — 決定論的で高性能なパイプライン。',
        'lifecycle_steps' => [
            ['n' => '01', 'title' => 'Runtime',    'desc' => '<code>Clover\\Framework\\Component\\Runtime</code> がベース プロキシを接続し、<code>ErrorHandler</code> を登録、<code>EventManager</code> を起動、<code>MatrixOps</code> をウォーム、<em>Dotenv</em> の不変モードで <code>.env</code> / <code>.env.local</code> をロードします。<code>RUNTIME_BOOT</code> ライフサイクル イベントを発行します。'],
            ['n' => '02', 'title' => 'Configure',  'desc' => '<code>.env</code> から display_errors・タイムゾーン・エラー レポートを適用し、カーネル バスに <code>RUNTIME_CONFIGURE_STARTED</code> / <code>FINISHED</code> スパンを発行します。<code>PROFILER_ENABLED</code> はリクエストごとにタイムラインをリセットするので、Swoole ワーカーも毎回クリーンな状態で始まります。'],
            ['n' => '03', 'title' => 'Mapper',     'desc' => '<code>Container</code> を構築してフレームワーク サービス(ロガー・イベント バス・<strong>BroadcastManager</strong>・セッション・ルーティング)をバインドし、コントローラーの <em>Interceptor</em> を解決します。<code>RUNTIME_MAPPING_STARTED</code> / <code>FINISHED</code> を発行します。'],
            ['n' => '04', 'title' => 'HttpKernel', 'desc' => '<code>StackRequestHandler</code> のミドルウェア チェーン (<code>SecurityHeadersMiddleware</code> → <code>MaintenanceMiddleware</code> → <code>RoutingMiddleware</code>) を実行し、マッチしたコントローラーを FPM または <strong>Swoole</strong> 上で呼び出します。'],
            ['n' => '05', 'title' => 'Response',   'desc' => '<code>BeforeResponseSend</code> を発火し、<code>JSONHandler</code>・<code>SimpleXML</code>・テンプレート出力でペイロードをシリアライズしてボディを書き出します。Nginx・LightSpeed・CLI のそれぞれに専用のフラッシュ戦略があります。'],
            ['n' => '06', 'title' => 'Terminate',  'desc' => '<code>AfterResponseSend</code> を発行し、キュー内の非同期イベントを排出、デッドレター キューを永続化、プロファイラ タイムラインをリセットして長寿命ワーカーも決定論的な状態を維持します。'],
        ],

        'eco_label' => 'エコシステム',
        'eco_title' => '必要なすべてを標準搭載',
        'eco_desc'  => 'サードパーティの糊付けコード不要の縦統合トールキット。',
        'eco_primary_title' => 'アノテーション ルーター',
        'eco_primary_desc'  => 'PHP 8 アトリビュート (#[Prefix], #[Route], #[Middleware]) だけでルートを定義。パラメータ パターン・ルート グループ・自動キャッシュを備え、ディスパッチのオーバーヘッドをゼロに抑えます。',
        'eco_primary_cta'   => 'アーキテクチャ メモ &rarr;',
        'eco_cards' => [
            ['title' => 'Clover ORM',        'desc' => 'アノテーション ベースの ActiveRecord — #[Entity\\Column]・#[OneToMany]・#[OneToOne] の関係と Eager Loading をサポート。'],
            ['title' => 'インターセプタ システム','desc' => 'ブート レベルの pre/post フック。クライアントに到達する前にハンドラ・引数・レスポンスを自由に変換します。'],
            ['title' => 'Swoole ランタイム',    'desc' => 'Swoole コルーチンでアプリをインメモリ実行 — 従来の FPM 比 10 倍のスループット。'],
            ['title' => 'Promise エンジン',     'desc' => '純粋な PHP で resolve/reject セマンティクスとチェーン可能な then() コールバックによる非同期制御フロー。'],
            ['title' => 'CLI カーネル',         'desc' => '同じコンテナ、同じ DI — デュアル モード実行。アプリ ロジックを共有するコンソール コマンドをシームレスに構築。'],
        ],

        'modules_label' => 'モジュール',
        'modules_title' => '名前空間で見る機能',
        'modules_desc'  => '以下のブロックはすべて <code>res/Platform/PHP/src/Classes</code> 配下の実サブツリーです。糊付けコードもサードパーティ アダプタ層もありません。',
        'modules_cards' => [
            ['title' => 'Routing',              'desc' => '<code>Classes\\Routing\\Router</code> はアノテーションを優先度付きトライにコンパイルし <code>routes.cache.php</code> に永続化、ルートごとのクエリ ガード・ホスト フィルタ・URL 書き換えが無い場合の <code>pathQueryKey</code> フォールバックに対応します。'],
            ['title' => 'Dependency Injection', 'desc' => '<code>Classes\\DependencyInjection\\Container</code> は <code>bind()</code>・<code>singleton()</code>・<code>set()</code>・<code>get()</code>・<code>autowire()</code> を提供します。ファサード (<code>EventManager</code>・<code>BroadcastManagerFacade</code>) はまずコンテナから解決し、無ければローカル シングルトンにフォールバックします。'],
            ['title' => 'Broadcasting',         'desc' => '<code>BroadcastManager</code> が <code>BroadcastMessage</code> DTO をプラガブル ドライバにディスパッチします: <code>NullBroadcaster</code>・<code>LogBroadcaster</code>・<code>SseBroadcaster</code> (<code>SseServer</code> 経由)・プロセス内ファンアウトの <code>EventBusBroadcaster</code>。'],
            ['title' => 'Events &amp; EventBus','desc' => '<code>EventBus</code> は同期パブリッシュ、非同期 <code>queue</code>、指数バックオフ再試行、デッドレター永続化、ミドルウェア、PSR-14 互換の <code>EventDispatcherAdapter</code> をサポートします。'],
            ['title' => 'Database',             'desc' => 'ActiveRecord エンティティ、流暢な <code>QueryBuilder</code>、ドライバ層、スキーマからモデルを逆生成する <code>SchemaModelGenerator</code>、<code>Migration</code>・<code>Seeder</code> ランナー。'],
            ['title' => 'Cache',                'desc' => '<code>Apc</code>・<code>Memcache</code>・<code>Redis</code> のドロップイン ドライバが統一された KV インターフェイスを提供し、コントローラは転送層に非依存のままです。'],
            ['title' => 'Security',             'desc' => '<code>SecurityManager</code>・<code>SecurityAuditor</code>・<code>SecurityTester</code> と <code>Auth</code>・<code>Guard</code>・ミドルウェア パッケージがヘッダ・レート制限・CSRF・セッション強化を網羅します。'],
            ['title' => 'Real-time (SSE)',      'desc' => '<code>Classes\\SSE\\SseServer</code> がセッション スコープのチャネル購読を管理し、<code>SseBroadcaster</code> は外部ブローカー無しでブロードキャストをブラウザへ直接プッシュします。'],
            ['title' => 'I18N',                 'desc' => '<code>Classes\\I18N\\I18n</code> がロケール ネゴシエーション・複数形規則を扱い、<code>BilingualMorphTokenizer</code> で Transformer パイプライン用の二言語トークナイズにも対応します。'],
            ['title' => 'Transformer &amp; LLM','desc' => '完全なエンコーダ スタック(<code>MultiHeadAttention</code>・<code>ScaledDotProductAttention</code>・<code>LayerNormalization</code>)に加え、<code>Classes\\LLM\\*</code> に ChatGPT・Claude・Gemini・Copilot・DeepSeek・Grok・Perplexity・Qwen のコネクタを収録。'],
            ['title' => 'Interpreter',          'desc' => '<code>Tokenizer</code> → <code>Parser</code> → <code>Interpreter</code> の Onetone スクリプト スタック。プラガブルな <code>Environment</code>・<code>Bindings</code> 層で PHP にスクリプティングを組み込みます。'],
            ['title' => 'FFI',                  'desc' => 'PHP の FFI 拡張を通じて <code>WindowsAPI</code>・<code>CustomWindow</code>・<code>GLFW</code>・<code>OnnxRuntime</code>・<code>LibOpenCVVideoIO</code>・<code>HuggingFace</code> への型付きバインディングを提供します。'],
            ['title' => 'Math',                 'desc' => '<code>Vector</code>・<code>Matrix</code>・<code>MatrixOps</code>・<code>Calculus</code>・<code>ProbabilityDistributions</code>・<code>RungeKuttaPhysicsSimulation</code>・金融 (<code>Loan</code>・<code>Credit</code>)・声道長推定 (<code>VTL</code>・<code>Vocal</code>) など 40 以上の数値モジュール。'],
            ['title' => 'Expert System',        'desc' => '<code>Classes\\ExpertSystem</code> はファクト ベースと競合解決戦略を備えた前向き推論の規則エンジンと、決定ロジック用のコンパクトな DSL を提供します。'],
            ['title' => 'バーコード &amp; メディア', 'desc' => '<code>BarcodeHandler</code> が 1D/2D コードを生成し、<code>LibOpenCVVideoIO</code> は CV ワークロード向けに映像フレームをストリーミングします。'],
            ['title' => 'プロトコル',               'desc' => '<code>Classes\\Protocol\\Internet</code>・<code>Classes\\Protocol\\PHP</code>・<code>Classes\\XMPP\\Bosh</code>、そして <code>GraphQL</code> パーサ / エグゼキュータが転送層を仕上げます。'],
        ],

        'ann_label' => 'アノテーション',
        'ann_title' => 'アトリビュート リファレンス',
        'ann_desc'  => '<code>src/Annotation/</code> に実在するすべての <code>#[Attribute]</code> クラス — コピペですぐに使えます。',
        'ann_primary_title' => 'HTTP ルーティング アトリビュート',
        'ann_primary_desc'  => '<code>#[Prefix]</code>・<code>#[Route(method, pattern)]</code>・<code>#[GetMapping]</code>・<code>#[PostMapping]</code>・<code>#[PutMapping]</code>・<code>#[PatchMapping]</code>・<code>#[DeleteMapping]</code>・<code>#[RequestMapping]</code>。各ルートは <em>method</em>・<em>pattern</em>・<em>host</em>・<em>priority</em>、必須の <em>query</em> ペア、<em>pathQueryKey</em> フォールバックを宣言します。',
        'ann_primary_cta'   => 'アーキテクチャ メモ &rarr;',
        'ann_cards' => [
            ['title' => 'エンティティ属性',       'desc' => '<code>#[Entity\\Column(name, type, nullable)]</code>・<code>#[Entity\\Id]</code>・<code>#[Entity\\JoinColumn]</code>・<code>#[Entity\\JoinTable]</code>・<code>#[Entity\\OneToOne]</code>・<code>#[Entity\\OneToMany]</code>・<code>#[Entity\\ManyToOne]</code>・<code>#[Entity\\ManyToMany]</code>。'],
            ['title' => 'パイプライン属性',       'desc' => '<code>#[Middleware]</code> はルート前後にハンドラをスタックし、<code>#[ContentType]</code> はレスポンス形式を固定、<code>#[NotFound]</code> は 404 ハンドラを設置、<code>#[Autowire]</code> はコンストラクタ依存を明示、<code>#[Value]</code> は設定値を注入します。'],
            ['title' => 'コントローラの種類',      'desc' => '<code>#[Controller]</code> はビューを描画する MVC エンドポイントを示し、<code>#[RestController]</code> はペイロードを自動シリアライズ、<code>#[Query]</code> はメソッド シグネチャに想定クエリを文書化します。'],
            ['title' => 'MCP ツール面',           'desc' => '<code>#[McpTool]</code> はメソッドを Model Context Protocol ツールとして公開 — アトリビュート 1 つでメソッドを <code>res/Platform/PHP/mcp-server</code> レジストリに登録し、LLM から呼び出せるようにします。'],
            ['title' => 'インターセプタ',          'desc' => '<code>Clover\\Abstract\\Interceptor</code> を継承し、ルート対象の前後でハンドラ・引数・レスポンスを操作します — DI コンテナが自動的に解決します。'],
        ],

        'dx_label' => '開発者体験',
        'dx_title' => '意図がそのまま読めるコード',
        'dx_desc'  => '表現力があり、型安全で、ボイラープレートゼロ。',

        'realtime_label' => 'リアルタイム &amp; イベント',
        'realtime_title' => '一度のブロードキャストですべてに届ける',
        'realtime_desc'  => 'イベントは自分のチャネルを宣言し、ドライバは配送だけを担当します。',

        'caps_label' => 'ケイパビリティ',
        'caps_title' => '厳しい要件のために設計',
        'caps_desc'  => 'ダウンタイムが許されないシステムのための堅牢な基盤。',
        'caps_cards' => [
            ['title' => 'C パワードコア',       'desc' => '独自の C ベース テンプレート エンジンとインタプリタが、最小のメモリ オーバーヘッドでネイティブ速度の描画を提供します。'],
            ['title' => '依存性注入',            'desc' => 'コンストラクタ・メソッド注入がコンテナから自動解決されます。バインディングは 1 つのファイルで構成します。'],
            ['title' => 'ミドルウェア パイプライン', 'desc' => 'コントローラ単位・メソッド単位で #[Middleware] アトリビュートを適用し、積み重ね・連結・合成が自在です。'],
            ['title' => 'エンティティ関係',        'desc' => 'Eager Loading 対応の #[OneToOne]・#[OneToMany]。スキーマに直接マップされる ActiveRecord モデル。'],
            ['title' => 'ポリグロット FFI',        'desc' => 'PHP の Foreign Function Interface で Windows API・Rust・Go・Python・C# を直接呼び出します。'],
            ['title' => 'TypeScript フロントエンド','desc' => 'Three.js 連携・インタラクティブ マップ・メディア プレイヤーを含むコンポーネント システムを webpack 不要で提供します。'],
        ],

        'cli_label' => 'CLI',
        'cli_title' => '1 つのコンテナ、2 つのエントリ ポイント',
        'cli_desc'  => 'すべてのコマンドは同じ DI コンテナ・イベント バス・ブロードキャスタを共有します — <code>php bin/console &lt;name&gt;</code> で実行してください。',
        'cli_cards' => [
            ['title' => 'データベース',            'desc' => '<code>DatabaseMigrateCommand</code> がスキーマ マイグレーションを実行し、<code>DatabaseSeedCommand</code> がフィクスチャを投入、<code>SchemaModelGenerator</code> がライブ スキーマからエンティティ クラスを逆生成します。'],
            ['title' => 'ルート内省',              'desc' => '<code>RouteListCommand</code> がコンパイル済みの全ルートをダンプ、<code>RouteFlowCommand</code> がディスパッチ フローを可視化、<code>RouteDiagramCommand</code> がアトリビュート駆動のトポロジ図を描画します。'],
            ['title' => 'LLM 連携',                'desc' => '<code>ChatGPTCommand</code>・<code>ClaudeCommand</code>・<code>GoogleGeminiCommand</code>・<code>PapagoCommand</code> が共有 <code>Classes\\LLM\\*</code> ツリーのプロバイダ クライアントを呼び出します — バッチ要約・評価・翻訳ジョブに有用です。'],
            ['title' => 'MCP ブリッジ',            'desc' => '<code>McpCommand</code> が <code>res/Platform/PHP/mcp-server</code> から Model Context Protocol サーバを起動し、<code>#[McpTool]</code> を付けたメソッドを互換エージェントに公開します。'],
            ['title' => 'Transformer ジョブ',      'desc' => '<code>TransformerTrainingCommand</code> は同梱のエンコーダ スタックを学習し、<code>TransformerPredictCommand</code> は推論を実行します — どちらも <code>Classes\\Transformer\\*</code> を再利用します。'],
            ['title' => '開発者ツール',             'desc' => '<code>WizardCommand</code> がコントローラ・エンティティ・マイグレーションを対話的にスキャフォールドし、<code>GithubCommand</code>・<code>HarnessCommand</code>・<code>ExchangeRateCommand</code> が日常の連携を自動化します。'],
            ['title' => 'インタラクティブ サンプル','desc' => 'プレイ可能なターミナル ゲーム — <code>SokobanCommand</code>・<code>MinesweeperCommand</code>・<code>MazeCommand</code>・<code>SquidCommand</code> — がコンソール レンダラ・入力ループ・キーボード駆動 FSM パターンを示します。'],
            ['title' => 'ドメイン例',               'desc' => '<code>MedicalReportCommand</code>・<code>RandomDronePathCommand</code>・<code>QueryCommand</code> が、たった 1 つの <code>php bin/console</code> 呼び出しで Math と Expert-System モジュールをデモします。'],
        ],

        'config_label' => '設定',
        'config_title' => '1 つの <code>.env</code> でスタック全体を調整',
        'config_desc'  => '環境変数は <code>Runtime::overrideOptionsFromEnv()</code>・<code>Profiler</code>・<code>Mapper</code>、および各ドライバが読み取ります。',
        'config_head'  => 'res/Platform/PHP/root/.env',
        'config_hint'  => 'ホスト固有のオーバーライドは <code>.env.local</code> に置きます。Dotenv は <code>.env</code> の後に可変モードでロードしますが、<code>RUNNING_IN_DOCKER=1</code> が設定されているか <code>/.dockerenv</code> が存在する場合はロードされません。',

        'perf_label' => 'パフォーマンス',
        'perf_title' => '意味のある数字',
        'perf_desc'  => '実験室水準の構成における例示的な目標値 — 必ず <em>ご自身の</em> ハードウェアとワークロードでベンチマークしてください。',
        'perf_cards' => [
            ['value' => '0.4ms', 'tone' => 'accent',  'unit' => '平均応答時間',        'desc' => 'Swoole インメモリ ランタイムでキャッシュ済みルートを解決した場合。'],
            ['value' => '12k',   'tone' => 'emerald', 'unit' => '秒間リクエスト',       'desc' => '2 コアのシングル インスタンスでの持続スループット。'],
            ['value' => '8MB',   'tone' => 'amber',   'unit' => 'メモリ フットプリント', 'desc' => 'ORM とルーターをロードしたベースライン メモリ使用量。'],
        ],

        'stack_title' => '既存スタックの横に自然に収まる設計',
        'stack_lead'  => '架空の顧客ロゴは使いません — このリポジトリと一緒にテストされているツールだけを列挙します。',
        'stack_items' => [
            ['icon' => 'fa-brands fa-php',              'text' => 'PHP 8.4 の言語機能と拡張'],
            ['icon' => 'fa-solid fa-box-open',          'text' => '<code>res/Platform/PHP</code> 依存の Composer'],
            ['icon' => 'fa-solid fa-server',            'text' => '組み込みサーバ、または既存の FPM / リバース プロキシ'],
            ['icon' => 'fa-solid fa-flask',             'text' => 'リポジトリ内に文書化された PHPUnit + PHPStan ワークフロー'],
            ['icon' => 'fa-solid fa-bolt',              'text' => '長寿命ワーカー向けの Swoole コルーチン'],
            ['icon' => 'fa-solid fa-database',          'text' => 'ドライバ層を介した MySQL / PostgreSQL / SQLite'],
            ['icon' => 'fa-solid fa-broadcast-tower',   'text' => 'SSE を標準搭載、プラグイン ドライバで WebSocket / MQTT'],
            ['icon' => 'fa-solid fa-robot',             'text' => 'AI エージェント ツール向け Model Context Protocol'],
        ],

        'quality_label' => '品質',
        'quality_title' => 'テスト・静的解析・可観測性',
        'quality_desc'  => '本番を動かすそのツリーでテストとプロファイリングも行います。',
        'quality_cards' => [
            ['title' => 'PHPUnit',               'desc' => '<code>phpunit.xml.dist</code> にスイート定義、テストは <code>tests/Classes/*</code> にコロケーション。ブロードキャスト モジュールもマネージャ・ファサード・コンテナ配線の専用テストを備えています。'],
            ['title' => 'PHPStan',               'desc' => '強力な型推論に基づく最高水準の静的解析 — classmap オートローダがアトリビュート専用クラスでも正確な解析を支えます。'],
            ['title' => 'カーネル プロファイラ',  'desc' => '<code>Classes\\Debug\\Profiler</code> が各ライフサイクル段階で <code>KernelSpanStarted</code> / <code>KernelSpanFinished</code> スパンを発行します — <code>PROFILER_ENABLED=true</code> で有効化し、好きなリスナーにパイプできます。'],
            ['title' => '構造化ロギング',         'desc' => '<code>Classes\\Logging\\StructuredKernelLogger</code> が機械可読イベントを出力し、<code>LogBroadcaster</code> ドライバはオフライン再生のためブロードキャストを JSON 行にシリアライズします。'],
            ['title' => 'エラー ハンドラ',         'desc' => '<code>Classes\\Debug\\ErrorHandler</code> は <code>src/Template/</code> 配下の専用テンプレート (Error・Exception・Shutdown) で例外→レスポンスのパイプラインを統合します。'],
            ['title' => 'セキュリティ ツール',     'desc' => '<code>SecurityAuditor</code> が誤設定を列挙し、<code>SecurityTester</code> が認証・CSRF の経路を検査し、<code>SecurityHeadersMiddleware</code> がすべてのレスポンスを強化します。'],
        ],

        'map_label' => 'マップ',
        'map_title' => 'ディレクトリ チートシート',
        'map_desc'  => '<code>res/Platform/PHP</code> 内で何がどこにあるかのメンタル モデル。',
        'map_head'  => 'res/Platform/PHP/',

        'footer_framework'        => 'フレームワーク',
        'footer_product'          => 'プロダクト',
        'footer_resources'        => 'リソース',
        'footer_ecosystem'        => 'エコシステム',
        'footer_subscribe'        => '購読',
        'footer_subscribe_lead'   => 'リリースとエコシステムのニュースをお届けします。',
        'footer_subscribe_ph'     => 'メール アドレス',
        'footer_copy'             => '&copy; 2026 Onetone Framework. All rights reserved.',
        'footer_links' => [
            'framework' => [
                ['href' => '/about/team',    'text' => 'チーム'],
                ['href' => '/about/careers', 'text' => '採用'],
                ['href' => '/about/brand',   'text' => 'ブランド アセット'],
                ['href' => '/about/story',   'text' => 'ストーリー'],
            ],
            'product' => [
                ['href' => '/news/releases',  'text' => 'リリース ノート'],
                ['href' => '/news/blog',      'text' => 'ブログ'],
                ['href' => '/products/cloud', 'text' => 'Onetone Cloud'],
                ['href' => '/enterprise',     'text' => 'エンタープライズ'],
            ],
            'resources' => [
                ['href' => '/documentation',      'text' => 'インタプリタ ドキュメント'],
                ['href' => '/docs',               'text' => 'ドキュメント ハブ'],
                ['href' => '/docs/api',           'text' => 'API リファレンス'],
                ['href' => '/resources/tutorials','text' => 'チュートリアル'],
                ['href' => '/community',          'text' => 'コミュニティ'],
            ],
            'ecosystem' => [
                ['href' => '/ecosystem/packages',   'text' => 'パッケージ'],
                ['href' => '/ecosystem/themes',     'text' => 'テーマ'],
                ['href' => '/ecosystem/extensions', 'text' => '拡張'],
                ['href' => '/shop',                 'text' => 'ショップ'],
            ],
        ],
    ],
];

$L = $__dict[$locale];

$__langLinks = [
    'en' => 'English',
    'ko' => '한국어',
    'ja' => '日本語',
];
?>
<style>
.lang-switcher{position:absolute;top:84px;right:var(--page-gutter);z-index:5;display:inline-flex;gap:2px}
.lang-switcher a{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:26px;padding:0 8px;border-radius:980px;color:rgba(255,255,255,.45);font-size:11px;font-weight:500;letter-spacing:.06em;text-decoration:none;transition:color .2s ease,background-color .2s ease}
.lang-switcher a:hover{color:#fff}
.lang-switcher a.is-active{color:#fff;background:var(--brand)}
@media (max-width:640px){.lang-switcher{top:72px}}
</style>
<div id="header" data-lang="<?= $locale ?>">
    <nav class="lang-switcher" role="navigation" aria-label="<?= htmlspecialchars($L['lang_switch_aria'], ENT_QUOTES, 'UTF-8') ?>">
        <?php foreach ($__langLinks as $code => $label): ?>
            <a href="?lang=<?= $code ?>"
               class="<?= $code === $locale ? 'is-active' : '' ?>"
               hreflang="<?= $code ?>"
               aria-current="<?= $code === $locale ? 'true' : 'false' ?>"><?= htmlspecialchars(strtoupper($code), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach; ?>
    </nav>

    <div id="content">
        <h1 class="fade-in-up delay-1"><?= $L['hero_title_1'] ?><br><span class="text-gradient"><?= $L['hero_title_2'] ?></span></h1>
        <h3 class="fade-in-up delay-2"><?= $L['hero_subtitle'] ?></h3>
        <h5 class="fade-in-up delay-3">
            <?= $L['hero_lead'] ?>
        </h5>

        <div class="cta-group fade-in-up delay-3">
            <button type="button" id="document" onclick="location.href='/documentation'"><?= $L['cta_docs'] ?></button>
            <button type="button" id="install" onclick="location.href='/docs/installation'"><?= $L['cta_install'] ?></button>
            <button type="button" id="github" onclick="window.open('https://github.com/onetonetech/onetone', '_blank')"><?= $L['cta_github'] ?></button>
        </div>
    </div>
</div>

<main class="container">

    <section class="slider" data-slider aria-roledescription="carousel"
             aria-label="<?= htmlspecialchars($L['slides_aria'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="slider__track" data-slider-track tabindex="0">
            <?php foreach ($L['slides'] as $index => $slide): ?>
                <article class="slide" role="group" aria-roledescription="slide"
                         aria-label="<?= $index + 1 ?> / <?= count($L['slides']) ?>">
                    <div class="slide__body">
                        <span class="slide__eyebrow"><?= $slide['eyebrow'] ?></span>
                        <h2 class="slide__title"><?= $slide['title'] ?></h2>
                        <p class="slide__text"><?= $slide['text'] ?></p>
                        <a class="slide__link" href="<?= $slide['href'] ?>"><?= $slide['cta'] ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="facts-bar fade-in-up delay-4" role="region" aria-label="<?= htmlspecialchars($L['facts_aria'], ENT_QUOTES, 'UTF-8') ?>">
        <?php foreach ($L['facts'] as $fact): ?>
            <div class="fact-chip">
                <strong><?= $fact['strong'] ?></strong>
                <span><?= $fact['span'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-title fade-in-up">
        <span class="section-label"><?= $L['explore_label'] ?></span>
        <h2><?= $L['explore_title'] ?></h2>
        <p><?= $L['explore_desc'] ?></p>
    </div>

    <?php
    $exploreIcons = ['fa-cube', 'fa-book', 'fa-scroll', 'fa-graduation-cap', 'fa-handshake', 'fa-comments'];
    ?>
    <div class="features-grid intro-hub-grid fade-in-up">
        <?php foreach ($L['explore_cards'] as $idx => $card): ?>
            <article class="card">
                <div class="icon-wrapper" aria-hidden="true"><i class="fa-solid <?= $exploreIcons[$idx] ?? 'fa-cube' ?>"></i></div>
                <h3><?= $card['title'] ?></h3>
                <p><?= $card['desc'] ?></p>
                <a class="card-link" href="<?= $card['href'] ?>"><?= $card['cta'] ?> <span aria-hidden="true">&rarr;</span></a>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="quickstart-wrap fade-in-up">
        <div class="section-title" style="margin-top:0;margin-bottom:32px;">
            <span class="section-label"><?= $L['quickstart_label'] ?></span>
            <h2><?= $L['quickstart_title'] ?></h2>
            <p><?= $L['quickstart_desc'] ?></p>
        </div>
        <div class="quickstart-panel">
            <div class="quickstart-panel__head"><span class="quickstart-dot"></span> <?= $L['quickstart_head'] ?></div>
            <pre class="quickstart-pre"><code>composer install --working-dir=./res/Platform/PHP
php -S localhost:8080 -t res/Platform/PHP/root</code></pre>
            <p class="quickstart-hint"><?= $L['quickstart_hint'] ?></p>
        </div>
    </div>

    <div class="section-title fade-in-up">
        <span class="section-label"><?= $L['lifecycle_label'] ?></span>
        <h2><?= $L['lifecycle_title'] ?></h2>
        <p><?= $L['lifecycle_desc'] ?></p>
    </div>

    <div class="lifecycle-section fade-in-up">
        <div class="lifecycle-flow">
            <?php foreach ($L['lifecycle_steps'] as $step): ?>
                <div class="lifecycle-step">
                    <span class="step-num"><?= $step['n'] ?></span>
                    <h4><?= $step['title'] ?></h4>
                    <p><?= $step['desc'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section-title fade-in-up">
        <span class="section-label"><?= $L['eco_label'] ?></span>
        <h2><?= $L['eco_title'] ?></h2>
        <p><?= $L['eco_desc'] ?></p>
    </div>

    <?php $ecoIcons = ['fa-database', 'fa-shield-halved', 'fa-bolt', 'fa-code-branch', 'fa-terminal']; ?>
    <div class="ecosystem-grid fade-in-up">
        <div class="eco-card primary">
            <div class="primary-content">
                <div class="icon-box"><i class="fa-solid fa-route"></i></div>
                <h3><?= $L['eco_primary_title'] ?></h3>
                <p><?= $L['eco_primary_desc'] ?></p>
                <a href="/docs/architecture" class="learn-more"><?= $L['eco_primary_cta'] ?></a>
            </div>
            <div class="primary-visual">
                <code><span class="hl">#[Prefix</span>(<span class="str">'/api'</span>)<span class="hl">]</span>
class UserController
{
    <span class="hl">#[Route</span>(<span class="str">'GET'</span>, <span class="str">'/users'</span>)<span class="hl">]</span>
    public function list()
    {
        return <span class="hl">$this</span>->responseJson([]);
    }
}</code>
            </div>
        </div>
        <?php foreach ($L['eco_cards'] as $idx => $card): ?>
            <div class="eco-card">
                <div class="icon-box"><i class="fa-solid <?= $ecoIcons[$idx] ?? 'fa-puzzle-piece' ?>"></i></div>
                <h3><?= $card['title'] ?></h3>
                <p><?= $card['desc'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-title fade-in-up">
        <span class="section-label"><?= $L['modules_label'] ?></span>
        <h2><?= $L['modules_title'] ?></h2>
        <p><?= $L['modules_desc'] ?></p>
    </div>

    <?php
    $modulesIcons = [
        'fa-diagram-project', 'fa-plug', 'fa-tower-broadcast', 'fa-tower-observation',
        'fa-database', 'fa-memory', 'fa-lock', 'fa-wave-square',
        'fa-earth-asia', 'fa-brain', 'fa-code', 'fa-puzzle-piece',
        'fa-square-root-variable', 'fa-gears', 'fa-barcode', 'fa-share-nodes',
    ];
    ?>
    <div class="features-grid fade-in-up">
        <?php foreach ($L['modules_cards'] as $idx => $card): ?>
            <article class="card">
                <div class="icon-wrapper"><i class="fa-solid <?= $modulesIcons[$idx] ?? 'fa-cube' ?>"></i></div>
                <h3><?= $card['title'] ?></h3>
                <p><?= $card['desc'] ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="section-title fade-in-up">
        <span class="section-label"><?= $L['ann_label'] ?></span>
        <h2><?= $L['ann_title'] ?></h2>
        <p><?= $L['ann_desc'] ?></p>
    </div>

    <?php $annIcons = ['fa-database', 'fa-shield-halved', 'fa-wand-magic-sparkles', 'fa-plug-circle-bolt', 'fa-sitemap']; ?>
    <div class="ecosystem-grid fade-in-up">
        <div class="eco-card primary">
            <div class="primary-content">
                <div class="icon-box"><i class="fa-solid fa-route"></i></div>
                <h3><?= $L['ann_primary_title'] ?></h3>
                <p><?= $L['ann_primary_desc'] ?></p>
                <a href="/docs/architecture" class="learn-more"><?= $L['ann_primary_cta'] ?></a>
            </div>
            <div class="primary-visual">
                <code><span class="hl">#[Prefix</span>(<span class="str">'/api/v1'</span>)<span class="hl">]</span>
<span class="hl">#[Middleware</span>(AuthMiddleware::<span class="str">class</span>)<span class="hl">]</span>
<span class="hl">#[RestController]</span>
class OrderController
{
    <span class="hl">#[GetMapping</span>(<span class="str">'/orders/{id}?:(\d+)'</span>,
        priority: <span class="str">10</span>,
        query: [<span class="str">'format'</span> =&gt; <span class="str">'json'</span>])<span class="hl">]</span>
    public function show(string $id) { /* ... */ }
}</code>
            </div>
        </div>
        <?php foreach ($L['ann_cards'] as $idx => $card): ?>
            <div class="eco-card">
                <div class="icon-box"><i class="fa-solid <?= $annIcons[$idx] ?? 'fa-puzzle-piece' ?>"></i></div>
                <h3><?= $card['title'] ?></h3>
                <p><?= $card['desc'] ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="showcase">
        <div class="section-title">
            <span class="section-label"><?= $L['dx_label'] ?></span>
            <h2><?= $L['dx_title'] ?></h2>
            <p><?= $L['dx_desc'] ?></p>
        </div>

        <div class="code-window">
            <div class="window-header">
                <span class="title">App/Controller/ApiController.php</span>
            </div>
            <pre><code class="language-php">namespace App\Controller;

use Clover\Annotation\{Prefix, Route, Middleware};
use Clover\Classes\HTTP\Request;
use Clover\Framework\Component\LayoutComponentController;

#[Prefix('/api')]
#[Middleware(AuthMiddleware::class)]
class ApiController extends LayoutComponentController
{
    #[Route('GET', '/users/{id}?:(\d+)')]
    public function getUser(Request $request, string $id)
    {
        $user = new User();
        $user = $user->findWith($id, [], ['profile', 'posts']);

        return $this->responseJson([
            'user'  => $user->toArray(),
            'posts' => $user->posts
        ]);
    }

    #[Route('POST', '/users')]
    public function createUser(Request $request)
    {
        $user = new User();
        $user->name  = $request->getPostParameter('name');
        $user->email = $request->getPostParameter('email');
        $user = $user->save();

        return $this->responseJson($user)->setStatusCode(201);
    }
}</code></pre>
        </div>
    </div>

    <div class="showcase">
        <div class="section-title">
            <span class="section-label"><?= $L['realtime_label'] ?></span>
            <h2><?= $L['realtime_title'] ?></h2>
            <p><?= $L['realtime_desc'] ?></p>
        </div>

        <div class="code-window">
            <div class="window-header">
                <span class="title">App/Event/OrderShipped.php &amp; dispatch</span>
            </div>
            <pre><code class="language-php">use Clover\Implement\ShouldBroadcastInterface;
use Clover\Classes\Broadcasting\{PrivateChannel, BroadcastManagerFacade};

final class OrderShipped implements ShouldBroadcastInterface
{
    public function __construct(private readonly int $orderId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("orders.{$this->orderId}")];
    }

    public function broadcastAs(): string     { return 'order.shipped'; }
    public function broadcastPayload(): array { return ['id' =&gt; $this->orderId]; }
}

// Fluent dispatch — pick a driver, override channels, attach headers.
BroadcastManagerFacade::event(new OrderShipped(42))
    -&gt;via('sse')
    -&gt;withHeader('trace-id', $request-&gt;getHeader('x-request-id'))
    -&gt;dispatch();</code></pre>
        </div>
    </div>

    <?php $capsIcons = ['fa-microchip', 'fa-cubes', 'fa-layer-group', 'fa-diagram-project', 'fa-puzzle-piece', 'fa-brands fa-js']; ?>
    <div class="features-section">
        <div class="section-title" style="margin-top:0;">
            <span class="section-label"><?= $L['caps_label'] ?></span>
            <h2><?= $L['caps_title'] ?></h2>
            <p><?= $L['caps_desc'] ?></p>
        </div>

        <div class="features-grid">
            <?php foreach ($L['caps_cards'] as $idx => $card): ?>
                <?php $icon = $capsIcons[$idx] ?? 'fa-solid fa-cube'; $iconClass = str_contains($icon, 'fa-brands') ? $icon : "fa-solid {$icon}"; ?>
                <div class="card fade-in-up">
                    <div class="icon-wrapper"><i class="<?= $iconClass ?>"></i></div>
                    <h3><?= $card['title'] ?></h3>
                    <p><?= $card['desc'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section-title fade-in-up">
        <span class="section-label"><?= $L['cli_label'] ?></span>
        <h2><?= $L['cli_title'] ?></h2>
        <p><?= $L['cli_desc'] ?></p>
    </div>

    <?php
    $cliIcons = ['fa-database', 'fa-route', 'fa-robot', 'fa-microchip', 'fa-brain', 'fa-hat-wizard', 'fa-gamepad', 'fa-helicopter'];
    ?>
    <div class="features-grid fade-in-up">
        <?php foreach ($L['cli_cards'] as $idx => $card): ?>
            <article class="card">
                <div class="icon-wrapper"><i class="fa-solid <?= $cliIcons[$idx] ?? 'fa-cube' ?>"></i></div>
                <h3><?= $card['title'] ?></h3>
                <p><?= $card['desc'] ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="section-title fade-in-up">
        <span class="section-label"><?= $L['config_label'] ?></span>
        <h2><?= $L['config_title'] ?></h2>
        <p><?= $L['config_desc'] ?></p>
    </div>

    <div class="quickstart-wrap fade-in-up">
        <div class="quickstart-panel">
            <div class="quickstart-panel__head"><span class="quickstart-dot"></span> <?= $L['config_head'] ?></div>
            <pre class="quickstart-pre"><code>APP_DEBUG=true
APP_TIMEZONE=Asia/Seoul
APP_ERROR_REPORTING=32767
IS_DEBUGGABLE=true
PROFILER_ENABLED=true
ROUTE_CACHE=true
RUNNING_IN_DOCKER=0
SERVER_DRIVER=fpm              # fpm | swoole
BROADCAST_DRIVER=event-bus     # null | log | event-bus | sse
SSE_HEARTBEAT_MS=15000
CACHE_DRIVER=redis             # apc | memcache | redis</code></pre>
            <p class="quickstart-hint"><?= $L['config_hint'] ?></p>
        </div>
    </div>

    <div class="section-title fade-in-up">
        <span class="section-label"><?= $L['perf_label'] ?></span>
        <h2><?= $L['perf_title'] ?></h2>
        <p><?= $L['perf_desc'] ?></p>
    </div>

    <div class="benchmark-section fade-in-up">
        <div class="benchmark-grid">
            <?php foreach ($L['perf_cards'] as $card): ?>
                <div class="bench-card">
                    <div class="bench-value <?= $card['tone'] ?>"><?= $card['value'] ?></div>
                    <span class="bench-unit"><?= $card['unit'] ?></span>
                    <p class="bench-desc"><?= $card['desc'] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="stack-compatibility fade-in-up">
        <h3><?= $L['stack_title'] ?></h3>
        <p class="stack-compatibility__lead"><?= $L['stack_lead'] ?></p>
        <ul class="stack-list">
            <?php foreach ($L['stack_items'] as $item): ?>
                <li><i class="<?= $item['icon'] ?>" aria-hidden="true"></i><span><?= $item['text'] ?></span></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="section-title fade-in-up">
        <span class="section-label"><?= $L['quality_label'] ?></span>
        <h2><?= $L['quality_title'] ?></h2>
        <p><?= $L['quality_desc'] ?></p>
    </div>

    <?php $qualityIcons = ['fa-vial', 'fa-magnifying-glass-chart', 'fa-stopwatch', 'fa-triangle-exclamation', 'fa-bug', 'fa-helmet-safety']; ?>
    <div class="features-grid fade-in-up">
        <?php foreach ($L['quality_cards'] as $idx => $card): ?>
            <article class="card">
                <div class="icon-wrapper"><i class="fa-solid <?= $qualityIcons[$idx] ?? 'fa-cube' ?>"></i></div>
                <h3><?= $card['title'] ?></h3>
                <p><?= $card['desc'] ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="section-title fade-in-up">
        <span class="section-label"><?= $L['map_label'] ?></span>
        <h2><?= $L['map_title'] ?></h2>
        <p><?= $L['map_desc'] ?></p>
    </div>

    <div class="quickstart-wrap fade-in-up">
        <div class="quickstart-panel">
            <div class="quickstart-panel__head"><span class="quickstart-dot"></span> <?= $L['map_head'] ?></div>
            <pre class="quickstart-pre"><code>src/
  Abstract/          # HTTPAdaptor, Interceptor, Singleton base classes
  Annotation/        # #[Route], #[Prefix], #[Middleware], Entity\*, #[McpTool]
  Bridge/            # Application, Javascript, UnityWeb embed bridges
  Classes/
    Broadcasting/    # BroadcastManager, Channel, drivers (SSE, EventBus, ...)
    Cache/           # Apc, Memcache, Redis drivers
    Database/        # ActiveRecord, QueryBuilder, Migration, Seeder
    DependencyInjection/ # Container + autowiring
    Event/           # Dispatcher, EventBus, EventManager, adapters
    Debug/           # Profiler, ErrorHandler, DebugSubscriberProvider
    FFI/             # WindowsAPI, OnnxRuntime, GLFW, CustomWindow
    GraphQL/         # Parser, Executor, Schema
    HTTP/            # Request, Response, Session, Cookie, Adaptor\{FPM,Swoole}
    I18N/            # i18n engine + language packs
    Interpreter/     # Tokenizer, Parser, Interpreter, Bindings
    LLM/             # ChatGPT, Claude, Gemini, Copilot, Grok, Qwen ...
    Logging/         # Logger, StructuredKernelLogger
    Math/            # 40+ numerical modules (Matrix, VTL, Transformer...)
    Routing/         # Router, RouteAnnotationReader, RouteExecutor
    Security/        # Auth, Guard, Middleware, Auditor, Tester
    SSE/             # SseServer (session-scoped channels)
    Transformer/     # Encoder stack, tokenizers, attention, job summary
  Command/           # Console commands (bin/console targets)
  Exception/         # Typed exception tree (including Broadcasting/*)
  Framework/
    Component/       # Runtime, Mapper, HttpKernel, LayoutComponentController
    Middleware/      # Stack handler + Security / Maintenance / Routing
    Event/           # KernelBoot, KernelLifecycleEvent, Before/After send
  Interface/         # Contract interfaces (Broadcasting, EventBus, Session...)
  Service/           # Service providers (RoutingServiceProvider)
  Support/           # ServiceProvider base
  Template/          # Error.php, Exception.php, Shutdown.php, Layout/, CLI/
tests/               # PHPUnit suites mirrored under Classes/*
root/App/            # Application surface (Controllers, Views, Cache, .env)
mcp-server/          # MCP bridge exposing #[McpTool] methods
bin/phar/            # phpunit.phar and other bundled tools</code></pre>
        </div>
    </div>
</main>

<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-col">
            <h4><?= $L['footer_framework'] ?></h4>
            <ul>
                <?php foreach ($L['footer_links']['framework'] as $link): ?>
                    <li><a href="<?= $link['href'] ?>"><?= $link['text'] ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="footer-col">
            <h4><?= $L['footer_product'] ?></h4>
            <ul>
                <?php foreach ($L['footer_links']['product'] as $link): ?>
                    <li><a href="<?= $link['href'] ?>"><?= $link['text'] ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="footer-col">
            <h4><?= $L['footer_resources'] ?></h4>
            <ul>
                <?php foreach ($L['footer_links']['resources'] as $link): ?>
                    <li><a href="<?= $link['href'] ?>"><?= $link['text'] ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="footer-col">
            <h4><?= $L['footer_ecosystem'] ?></h4>
            <ul>
                <?php foreach ($L['footer_links']['ecosystem'] as $link): ?>
                    <li><a href="<?= $link['href'] ?>"><?= $link['text'] ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="footer-col newsletter">
            <h4><?= $L['footer_subscribe'] ?></h4>
            <p><?= $L['footer_subscribe_lead'] ?></p>
            <div class="input-group">
                <input type="email" placeholder="<?= htmlspecialchars($L['footer_subscribe_ph'], ENT_QUOTES, 'UTF-8') ?>">
                <button><i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p><?= $L['footer_copy'] ?></p>
        <div class="socials">
            <a href="https://github.com/onetonetech" rel="noopener noreferrer" aria-label="GitHub"><i class="fa-brands fa-github"></i></a>
            <a href="/community/discord" aria-label="Discord"><i class="fa-brands fa-discord"></i></a>
            <a href="/news/events" aria-label="Events"><i class="fa-regular fa-calendar"></i></a>
        </div>
    </div>
</footer>

<script>
(function () {
    var supported = ['en', 'ko', 'ja'];
    try { document.documentElement.lang = '<?= $locale ?>'; } catch (_) {}

    var params = new URLSearchParams(window.location.search);
    var chosen = params.get('lang');
    if (chosen && supported.indexOf(chosen) !== -1) {
        document.cookie = 'lang=' + encodeURIComponent(chosen) +
            '; path=/; max-age=31536000; samesite=lax';
        params.delete('lang');
        var qs = params.toString();
        var clean = window.location.pathname + (qs ? ('?' + qs) : '') + window.location.hash;
        if (clean !== window.location.pathname + window.location.search + window.location.hash) {
            window.history.replaceState({}, document.title, clean);
        }
    }
})();
</script>
