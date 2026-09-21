<?php
declare(strict_types=1);
$siteKicker = 'About';
$siteHeading = 'About';
$siteLede = 'Story, team, careers, and brand downloads.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'About',
    'href' => NULL,
  ),
);
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>Company snapshot</h2>
            <p>Use the About hub for mission narrative, the people behind releases, hiring, and brand governance. Each child page is a standalone PHP view you can swap for CMS-driven content later.</p>
        </section>
        <section class="site-section"><h2>In this section</h2><div class="site-panels"><article class="site-panel"><h3><i class="fa-solid fa-book-open" aria-hidden="true"></i> Story</h3><p>Mission, history, and roadmap principles.</p><a href="/about/story">/about/story <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-users" aria-hidden="true"></i> Team</h3><p>Maintainers, reviewers, alumni.</p><a href="/about/team">/about/team <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-briefcase" aria-hidden="true"></i> Careers</h3><p>Open roles and hiring process.</p><a href="/about/careers">/about/careers <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-pen-nib" aria-hidden="true"></i> Brand assets</h3><p>Logos, colors, and clear space.</p><a href="/about/brand">/about/brand <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article></div></section>
<?php
$siteCtaText = "Clone this repository, run Composer, and boot the built-in server to explore the full stack.";
$siteCtaHref = "/docs/installation";
$siteCtaLabel = "Install guide";
require __DIR__ . '/partials/footer_cta.php';
