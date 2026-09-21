<?php
declare(strict_types=1);
$siteKicker = 'Enterprise';
$siteHeading = 'Enterprise';
$siteLede = 'Support retainers and consulting engagements.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Enterprise',
    'href' => NULL,
  ),
);
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>Production confidence</h2>
            <p>Enterprise offerings pair named support with consulting depth so teams can ship Onetone under compliance, uptime, and security constraints.</p>
        </section>
        <section class="site-section"><h2>In this section</h2><div class="site-panels"><article class="site-panel"><h3><i class="fa-solid fa-headset" aria-hidden="true"></i> Support</h3><p>Named engineers and severity-based SLAs.</p><a href="/enterprise/support">/enterprise/support <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-people-group" aria-hidden="true"></i> Consulting</h3><p>Architecture reviews and performance tuning.</p><a href="/enterprise/consulting">/enterprise/consulting <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article></div></section>
<?php
$siteCtaText = "Clone this repository, run Composer, and boot the built-in server to explore the full stack.";
$siteCtaHref = "/docs/installation";
$siteCtaLabel = "Install guide";
require __DIR__ . '/partials/footer_cta.php';
