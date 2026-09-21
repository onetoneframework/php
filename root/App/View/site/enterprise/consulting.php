<?php
declare(strict_types=1);
$siteKicker = 'Enterprise';
$siteHeading = 'Consulting';
$siteLede = 'Fixed-scope audits or embedded staff aug engagements.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Enterprise',
    'href' => '/enterprise',
  ),
  2 => 
  array (
    'label' => 'Consulting',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Engagement types</h2>
            <ul>
                <li><strong>Architecture review</strong> — two-week assessment of routing, middleware, caching, and observability with written recommendations.</li>
                <li><strong>Performance clinic</strong> — profiling HttpKernel hot paths, database access, and asset delivery.</li>
                <li><strong>Staff augmentation</strong> — embedded engineers pairing with your team for a defined runway.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Deliverables</h2>
            <p>Clarify reporting format (written report, workshop deck, backlog tickets), code ownership after handoff, and knowledge transfer sessions.</p>
        </section>
        <section class="site-section">
            <h2>Commercials</h2>
            <p>Outline typical SOW structure, travel policy, and how change requests are handled once discovery completes.</p>
        </section>
<?php
$siteCtaText = "Compare consulting vs. support offerings.";
$siteCtaHref = "/enterprise";
$siteCtaLabel = "Enterprise home";
require __DIR__ . '/../partials/footer_cta.php';
