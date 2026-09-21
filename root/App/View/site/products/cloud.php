<?php
declare(strict_types=1);
$siteKicker = 'Products';
$siteHeading = 'Onetone Cloud';
$siteLede = 'Story page for managed hosting, blue/green deploys, and observability tied to Onetone runtimes.';
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
    'label' => 'Onetone Cloud',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Managed platform storyboard</h2>
            <p>Onetone Cloud is a narrative placeholder for how teams would run this framework on a managed control plane: repeatable deploys, environment parity, and observability without giving up the internal-first PHP stack.</p>
            <h3>Deployment model</h3>
            <ul>
                <li>Blue/green or canary releases per application, with health checks tied to <code>HttpKernel</code> readiness.</li>
                <li>Secrets and configuration injected per environment (staging, production) without baking credentials into images.</li>
                <li>Optional worker runtimes for queues and scheduled tasks alongside the web tier.</li>
            </ul>
            <h3>What to publish here later</h3>
            <ul>
                <li>Region matrix, latency targets, and data residency statement.</li>
                <li>SLA table (uptime, support response) and status page link.</li>
                <li>Screenshots of dashboards, deploy timelines, and log/trace drill-down.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Observability hooks</h2>
            <p>Align marketing copy with real signals: structured request logs, trace IDs on responses, and kernel lifecycle events you can subscribe to for timelines and support bundles.</p>
        </section>
<?php
$siteCtaText = "Return to the product index or jump into the documentation hub.";
$siteCtaHref = "/products";
$siteCtaLabel = "All products";
require __DIR__ . '/../partials/footer_cta.php';
