<?php
declare(strict_types=1);
$siteKicker = 'Documentation';
$siteHeading = 'Components';
$siteLede = 'Layouts, controllers, responses, plugins, and the extension points you touch most often.';
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
    'label' => 'Components',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Controllers and layouts</h2>
            <p>Extend <code>LayoutComponentController</code> when actions share chrome (navigation, meta tags, bundled CSS/JS). The demo <code>HomeController</code> registers MenuShadow assets once and reuses them across every <code>App/View/site/*.php</code> page.</p>
            <h3>Responses</h3>
            <ul>
                <li>Return framework <code>Response</code> objects with explicit type, status, and headers for JSON, HTML, redirects, and downloads.</li>
                <li>Keep view paths explicit (<code>App/View/...</code>) so static analysis and code search stay reliable.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Middleware and events</h2>
            <p>The HTTP stack composes <code>MaintenanceMiddleware</code>, <code>RoutingMiddleware</code>, and a terminal handler. Kernel and routing lifecycle events flow through the event dispatcher for logging, tracing, and custom subscribers.</p>
            <h3>Plugins</h3>
            <p>Optional integrations (for example social login plugins wired in the container) should stay behind interfaces so core controllers remain testable.</p>
        </section>
<?php
$siteCtaText = "Browse sibling pages from the docs hub.";
$siteCtaHref = "/docs";
$siteCtaLabel = "Docs hub";
require __DIR__ . '/../partials/footer_cta.php';
