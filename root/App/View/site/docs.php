<?php
declare(strict_types=1);
$siteKicker = 'Documentation';
$siteHeading = 'Documentation hub';
$siteLede = 'Marketing-style stubs plus a clear hand-off to the bundled interpreter reference.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Docs',
    'href' => NULL,
  ),
);
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>How this hub is wired</h2>
            <p>Each page below is a dedicated PHP view under <code>App/View/site/docs</code> with a matching <code>#[Route]</code> on <code>HomeController</code>. That pattern keeps URLs grep-friendly and avoids opaque slug routing while you grow real documentation.</p>
        </section>
        <section class="site-section">
            <h2>Routes in this section</h2>
            <p>Every docs page is a dedicated PHP view and an explicit <code>#[Route]</code> — easier to grep than a catch-all slug.</p>
            <div class="site-panels">
                <article class="site-panel">
                    <h3><i class="fa-solid fa-download" aria-hidden="true"></i> Getting started</h3>
                    <p>Composer install, built-in server, and route cache reminders.</p>
                    <a href="/docs/installation">/docs/installation <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Architecture</h3>
                    <p>How <code>src/</code> and <code>root/App</code> split responsibilities.</p>
                    <a href="/docs/architecture">/docs/architecture <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i> Components</h3>
                    <p>Layouts, responses, plugins, and extension points.</p>
                    <a href="/docs/components">/docs/components <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-code" aria-hidden="true"></i> API reference</h3>
                    <p>Placeholder for REST/RPC catalogs you publish from code.</p>
                    <a href="/docs/api">/docs/api <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
            </div>
        </section>
<?php
$siteCtaText = "The interpreter and language surface are documented separately.";
$siteCtaHref = "/documentation";
$siteCtaLabel = "Open /documentation";
require __DIR__ . '/partials/footer_cta.php';
