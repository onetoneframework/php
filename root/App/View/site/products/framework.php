<?php
declare(strict_types=1);
$siteKicker = 'Products';
$siteHeading = 'Onetone Framework';
$siteLede = 'The PHP stack you are browsing: annotation routes, layout controllers, and the Clover service container.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Products',
    'href' => '/products',
  ),
  2 => 
  array (
    'label' => 'Onetone Framework',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>What you get in this repository</h2>
            <p>The PHP platform pairs a public web root with a Clover-namespaced framework library: annotation-based routes, a middleware pipeline, layout controllers, and an event dispatcher wired for kernel and routing lifecycle hooks.</p>
            <h3>Request path</h3>
            <ul>
                <li><strong>Runtime</strong> loads environment, applies options, then hands off to <strong>Mapper</strong> to resolve the runner (HTTP or CLI).</li>
                <li><strong>HttpKernel</strong> builds <code>MaintenanceMiddleware</code> and <code>RoutingMiddleware</code>, runs the stack, then renders or streams the <code>Response</code>.</li>
                <li>Structured logs and trace identifiers can follow the same path when enabled in your environment.</li>
            </ul>
            <h3>Where to look in the tree</h3>
            <ul>
                <li><code>res/Platform/PHP/root/App/Controller</code> — HTTP entrypoints such as <code>HomeController</code> and explicit <code>#[Route]</code> actions.</li>
                <li><code>res/Platform/PHP/src</code> — framework libraries (HTTP, routing, DI, events).</li>
                <li><code>res/Platform/PHP/root/App/View/site</code> — static marketing shells like this page (no catch-all slug router).</li>
            </ul>
            <p class="site-notice">Language and interpreter documentation lives under <a href="/documentation">/documentation</a>. PHP routing and dev-loop notes are summarized in <code>docs/README_PHP_EN.md</code> at repository root.</p>
        </section>
        <section class="site-section">
            <h2>Next steps</h2>
            <div class="site-panels">
                <article class="site-panel">
                    <h3><i class="fa-solid fa-download" aria-hidden="true"></i> Install locally</h3>
                    <p>Composer, built-in server, PHPUnit, and PHPStan commands used by maintainers.</p>
                    <a href="/docs/installation">Getting started <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Architecture</h3>
                    <p>How <code>src/</code> and <code>root/App</code> split responsibilities across layers.</p>
                    <a href="/docs/architecture">Architecture <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
            </div>
        </section>
<?php
$siteCtaText = "Return to the product index or jump into the documentation hub.";
$siteCtaHref = "/products";
$siteCtaLabel = "All products";
require __DIR__ . '/../partials/footer_cta.php';
