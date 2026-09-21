<?php
declare(strict_types=1);
$siteKicker = 'Ecosystem';
$siteHeading = 'Packages';
$siteLede = 'Index your Packagist namespace or private feed here.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Ecosystem',
    'href' => '/ecosystem',
  ),
  2 => 
  array (
    'label' => 'Packages',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Composer landscape</h2>
            <p>List first-party packages with their Packagist names, minimum PHP version, and stability (beta, stable). Separate internal modules from community-maintained add-ons.</p>
        </section>
        <section class="site-section">
            <h2>Versioning policy</h2>
            <ul>
                <li>Semantic versioning for libraries; document breaking changes per major.</li>
                <li>Security-only branches and end-of-life dates for older majors.</li>
                <li>Link to changelog files or GitHub releases for each package.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Private feeds</h2>
            <p>If you mirror packages internally, document authentication (<code>auth.json</code>), Composer repository URLs, and how CI validates package integrity.</p>
        </section>
<?php
$siteCtaText = "Return to ecosystem overview.";
$siteCtaHref = "/ecosystem";
$siteCtaLabel = "Ecosystem home";
require __DIR__ . '/../partials/footer_cta.php';
