<?php
declare(strict_types=1);
$siteKicker = 'Products';
$siteHeading = 'Analytics';
$siteLede = 'Productized telemetry: canonical events, sampling rules, and export to your warehouse.';
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
    'label' => 'Analytics',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Product analytics surface</h2>
            <p>Analytics covers first-party telemetry for how your Onetone deployment behaves in production: canonical event names, sampling, privacy boundaries, and export to a warehouse or lake.</p>
            <h3>Event model</h3>
            <ul>
                <li>Define a small set of stable event types (request lifecycle, auth outcomes, business milestones) and version them explicitly.</li>
                <li>Use sampling for high-volume paths while keeping error and security signals at full fidelity.</li>
                <li>Attach trace identifiers where available so support can correlate logs, metrics, and user sessions.</li>
            </ul>
            <h3>Delivery and governance</h3>
            <ul>
                <li>Batch export to S3/BigQuery/Snowflake or stream to Kafka/Pub/Sub depending on your stack.</li>
                <li>Document retention, deletion requests, and regional processing to satisfy security review.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Placeholder assets</h2>
            <p>Replace this section with example dashboards, a schema registry link, and screenshots of explorer tools your team actually ships.</p>
        </section>
<?php
$siteCtaText = "Return to the product index or jump into the documentation hub.";
$siteCtaHref = "/products";
$siteCtaLabel = "All products";
require __DIR__ . '/../partials/footer_cta.php';
