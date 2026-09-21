<?php
declare(strict_types=1);
$siteKicker = 'Ecosystem';
$siteHeading = 'Ecosystem';
$siteLede = 'Packages, themes, and extensions that orbit the core framework.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Ecosystem',
    'href' => NULL,
  ),
);
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>Extending the platform</h2>
            <p>The ecosystem hub groups reusable packages, visual themes, and editor or deploy extensions. Everything here is optional glue around the internal-first PHP core.</p>
        </section>
        <section class="site-section"><h2>In this section</h2><div class="site-panels"><article class="site-panel"><h3><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i> Packages</h3><p>Composer packages and first-party modules.</p><a href="/ecosystem/packages">/ecosystem/packages <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-palette" aria-hidden="true"></i> Themes</h3><p>Alternate MenuShadow-style skins and tokens.</p><a href="/ecosystem/themes">/ecosystem/themes <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-plug" aria-hidden="true"></i> Extensions</h3><p>Editor tooling, codegen, and deploy integrations.</p><a href="/ecosystem/extensions">/ecosystem/extensions <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article></div></section>
<?php
$siteCtaText = "Clone this repository, run Composer, and boot the built-in server to explore the full stack.";
$siteCtaHref = "/docs/installation";
$siteCtaLabel = "Install guide";
require __DIR__ . '/partials/footer_cta.php';
