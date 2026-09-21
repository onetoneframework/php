<?php
declare(strict_types=1);
$siteKicker = 'Shop';
$siteHeading = 'Shop';
$siteLede = 'Merch and donation flows — connect Stripe or your vendor later.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Shop',
    'href' => NULL,
  ),
);
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>Commerce placeholders</h2>
            <p>This shop section demonstrates how you might cross-link physical goods and donation flows. Integrate Stripe, Shopify, or a regional PSP when you are ready to collect payments.</p>
        </section>
        <section class="site-section"><h2>In this section</h2><div class="site-panels"><article class="site-panel"><h3><i class="fa-solid fa-shirt" aria-hidden="true"></i> Merch</h3><p>Apparel and desk swag for teams.</p><a href="/shop/merch">/shop/merch <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-heart" aria-hidden="true"></i> Donations</h3><p>Sustain development with recurring tips.</p><a href="/shop/donate">/shop/donate <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article></div></section>
<?php
$siteCtaText = "Clone this repository, run Composer, and boot the built-in server to explore the full stack.";
$siteCtaHref = "/docs/installation";
$siteCtaLabel = "Install guide";
require __DIR__ . '/partials/footer_cta.php';
