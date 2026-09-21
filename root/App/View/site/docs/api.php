<?php
declare(strict_types=1);
$siteKicker = 'Documentation';
$siteHeading = 'API reference';
$siteLede = 'Placeholder for the HTTP and RPC contracts your product exposes to clients.';
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
    'label' => 'API reference',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>What to publish here</h2>
            <p>When your HTTP surface stabilizes, link generated artifacts instead of hand-maintaining tables:</p>
            <ul>
                <li>OpenAPI 3.x documents (versioned by release) with authentication and error schemas.</li>
                <li>gRPC package names, proto repositories, and compatibility guarantees.</li>
                <li>Webhook catalogs describing delivery guarantees, retry policies, and sample payloads.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Developer experience</h2>
            <p>Add links to Postman collections, Insomnia workspaces, or SDK repositories. Keep changelog cross-links next to each major version so integrators know what moved.</p>
            <p class="site-notice">This demo site does not ship a generated OpenAPI bundle yet; treat this page as a template until your pipeline publishes artifacts.</p>
        </section>
<?php
$siteCtaText = "Browse sibling pages from the docs hub.";
$siteCtaHref = "/docs";
$siteCtaLabel = "Docs hub";
require __DIR__ . '/../partials/footer_cta.php';
