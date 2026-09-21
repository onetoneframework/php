<?php
declare(strict_types=1);
$siteKicker = 'Error';
$siteHeading = 'Page not found';
$siteLede = 'The URL you requested is not registered on this demo application.';
$siteCrumbs = [['label' => 'Home', 'href' => '/'], ['label' => 'Not found', 'href' => null]];
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>Troubleshooting</h2>
            <p>If you are developing locally, clear <code>res/Platform/PHP/root/App/Cache/routes.cache.php</code> or set <code>ROUTE_CACHE=false</code> in <code>res/Platform/PHP/root/.env</code>, then reload.</p>
        </section>
        <section class="site-section">
            <h2>Popular destinations</h2>
            <div class="site-panels">
                <article class="site-panel">
                    <h3><i class="fa-solid fa-cube" aria-hidden="true"></i> Framework product</h3>
                    <p>Architecture map and repository pointers.</p>
                    <a href="/products/framework">Open <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-book" aria-hidden="true"></i> Docs hub</h3>
                    <p>Installation, architecture, components, API stubs.</p>
                    <a href="/docs">Open <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-users" aria-hidden="true"></i> Community</h3>
                    <p>Forums, chat, and contribution expectations.</p>
                    <a href="/community">Open <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
            </div>
        </section>
<?php
$siteCtaText = 'Return to the landing page and browse the navigation.';
$siteCtaHref = '/';
$siteCtaLabel = 'Home';
require __DIR__ . '/partials/footer_cta.php';
