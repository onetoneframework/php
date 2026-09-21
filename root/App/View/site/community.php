<?php
declare(strict_types=1);
$siteKicker = 'Community';
$siteHeading = 'Community';
$siteLede = 'Async forums, real-time chat, and contribution guides.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Community',
    'href' => NULL,
  ),
);
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>Where help happens</h2>
            <p>Async forums capture long debugging threads, Discord offers real-time pairing, and the contribute page encodes repository standards so reviews stay fast and kind.</p>
        </section>
        <section class="site-section"><h2>In this section</h2><div class="site-panels"><article class="site-panel"><h3><i class="fa-solid fa-comments" aria-hidden="true"></i> Forum</h3><p>Long-form debugging threads.</p><a href="/community/forum">/community/forum <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-brands fa-discord" aria-hidden="true"></i> Discord</h3><p>Live help for early adopters.</p><a href="/community/discord">/community/discord <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-code-branch" aria-hidden="true"></i> Contribute</h3><p>Coding standards and review expectations.</p><a href="/community/contribute">/community/contribute <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article></div></section>
<?php
$siteCtaText = "Clone this repository, run Composer, and boot the built-in server to explore the full stack.";
$siteCtaHref = "/docs/installation";
$siteCtaLabel = "Install guide";
require __DIR__ . '/partials/footer_cta.php';
