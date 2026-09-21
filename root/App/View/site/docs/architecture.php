<?php
declare(strict_types=1);
$siteKicker = 'Documentation';
$siteHeading = 'Architecture';
$siteLede = 'How the public app, framework libraries, and documentation layers relate in this monorepo.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Docs',
    'href' => '/docs',
  ),
  2 => 
  array (
    'label' => 'Architecture',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Repository layers</h2>
            <p>The PHP platform splits application code from reusable framework libraries so upgrades stay predictable and grep-friendly.</p>
            <ul>
                <li><code>res/Platform/PHP/root</code> — public entry (<code>index.php</code>), <code>App/Controller</code>, <code>App/View</code>, frontend layout assets, and generated caches.</li>
                <li><code>res/Platform/PHP/src</code> — Clover framework namespaces: HTTP, routing, middleware, events, and shared utilities.</li>
                <li><code>docs/</code> — human-oriented README files; marketing pages under <code>App/View/site</code> complement them for this demo skin.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Runtime flow</h2>
            <p><strong>Runtime</strong> bootstraps environment and options, then <strong>Mapper</strong> resolves the runner. HTTP requests land in <strong>HttpKernel</strong>, which applies middleware, resolves routes, and returns a typed <code>Response</code>. CLI and alternate servers reuse the same building blocks where possible.</p>
            <h3>Routing styles</h3>
            <ul>
                <li>Annotation routes on controllers (explicit methods and paths) power this marketing site.</li>
                <li>A programmatic router can coexist for modules that register routes imperatively.</li>
            </ul>
        </section>
<?php
$siteCtaText = "Browse sibling pages from the docs hub.";
$siteCtaHref = "/docs";
$siteCtaLabel = "Docs hub";
require __DIR__ . '/../partials/footer_cta.php';
