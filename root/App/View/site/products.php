<?php
declare(strict_types=1);
$siteKicker = 'Products';
$siteHeading = 'Product surface for this demo';
$siteLede = 'Each card below maps to a real GET route under /products/… — no dynamic slug routing.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Products',
    'href' => NULL,
  ),
);
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>How these pages work</h2>
            <p>Each card maps to a real GET route registered on <code>HomeController</code>. There is no opaque slug router: URLs stay explicit so operators can cache, audit, and rewrite them predictably.</p>
        </section>
        <section class="site-section">
            <h2>What lives here</h2>
            <p>These routes ship as static HTML shells you can replace with pricing, screenshots, and links to your real services.</p>
            <div class="site-panels">
                <article class="site-panel">
                    <h3><i class="fa-solid fa-cube" aria-hidden="true"></i> Framework</h3>
                    <p>PHP platform layout, routing attributes, DI, and the same MenuShadow skin you see on the home page.</p>
                    <a href="/products/framework">Open page <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-cloud" aria-hidden="true"></i> Onetone Cloud</h3>
                    <p>Placeholder story for managed runtimes, dashboards, and deployment pipelines.</p>
                    <a href="/products/cloud">Open page <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-server" aria-hidden="true"></i> Server management</h3>
                    <p>Fleet operations copy: patching, secrets rotation, and health checks around your nodes.</p>
                    <a href="/products/server">Open page <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Analytics</h3>
                    <p>Event pipelines, privacy-aware metrics, and warehouse export hooks.</p>
                    <a href="/products/analytics">Open page <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
            </div>
        </section>
<?php
$siteCtaText = "Clone this repository, run Composer, and boot the built-in server to explore the full stack.";
$siteCtaHref = "/docs/installation";
$siteCtaLabel = "Install guide";
require __DIR__ . '/partials/footer_cta.php';
