<?php
declare(strict_types=1);
$siteKicker = 'Partners';
$siteHeading = 'Partners';
$siteLede = 'Find implementation partners or apply to the program.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Partners',
    'href' => NULL,
  ),
);
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>Partner motion</h2>
            <p>Implementation partners extend Onetone into regulated industries and complex integrations. Keep directory data fresh and align benefits with the <a href="/enterprise">enterprise</a> offerings.</p>
        </section>
        <section class="site-section"><h2>In this section</h2><div class="site-panels"><article class="site-panel"><h3><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Find a partner</h3><p>Directory filters for region and specialty.</p><a href="/partners/find">/partners/find <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-handshake" aria-hidden="true"></i> Become a partner</h3><p>Benefits, requirements, and onboarding.</p><a href="/partners/join">/partners/join <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article></div></section>
<?php
$siteCtaText = "Need enterprise-grade support? See the enterprise section.";
$siteCtaHref = "/enterprise";
$siteCtaLabel = "Enterprise";
require __DIR__ . '/partials/footer_cta.php';
