<?php
declare(strict_types=1);
$siteKicker = 'Enterprise';
$siteHeading = 'Enterprise support';
$siteLede = 'Hotline, escalation matrix, and maintenance windows.';
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
    'label' => 'Enterprise support',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Service levels</h2>
            <p>Publish severity definitions (production down, degraded, general guidance) with initial response and resolution targets. Include regional coverage hours and holiday schedules.</p>
        </section>
        <section class="site-section">
            <h2>Channels</h2>
            <ul>
                <li>Private Slack or Teams connect for engineers.</li>
                <li>Encrypted ticketing with attachment support for logs and traces.</li>
                <li>Quarterly health reviews summarizing incidents, upgrades, and roadmap alignment.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Escalation</h2>
            <p>Document the escalation matrix from support engineer to platform architect, including pager policies for Sev1 incidents.</p>
        </section>
<?php
$siteCtaText = "Compare consulting vs. support offerings.";
$siteCtaHref = "/enterprise";
$siteCtaLabel = "Enterprise home";
require __DIR__ . '/../partials/footer_cta.php';
