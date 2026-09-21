<?php
declare(strict_types=1);
$siteKicker = 'Resources';
$siteHeading = 'Resources';
$siteLede = 'Tutorials, certification, and podcast placeholders.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Resources',
    'href' => NULL,
  ),
);
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>Learning paths</h2>
            <p>Use this hub to steer newcomers from installation through tutorials, structured certification, and long-form audio. Each child route is explicit so instructors can deep-link without slug guessing.</p>
        </section>
        <section class="site-section"><h2>In this section</h2><div class="site-panels"><article class="site-panel"><h3><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Tutorials</h3><p>Hands-on lessons for routing, DI, and tests.</p><a href="/resources/tutorials">/resources/tutorials <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-certificate" aria-hidden="true"></i> Certification</h3><p>Structured learning paths for teams.</p><a href="/resources/certification">/resources/certification <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-podcast" aria-hidden="true"></i> Podcast</h3><p>Audio updates and guest interviews.</p><a href="/resources/podcast">/resources/podcast <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article></div></section>
<?php
$siteCtaText = "Clone this repository, run Composer, and boot the built-in server to explore the full stack.";
$siteCtaHref = "/docs/installation";
$siteCtaLabel = "Install guide";
require __DIR__ . '/partials/footer_cta.php';
