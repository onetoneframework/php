<?php
declare(strict_types=1);
$siteKicker = 'News';
$siteHeading = 'Release notes';
$siteLede = 'Automate from CHANGELOG.md when you ship.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'News',
    'href' => '/news',
  ),
  2 => 
  array (
    'label' => 'Release notes',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Release discipline</h2>
            <p>Automate this page from <code>CHANGELOG.md</code> or GitHub Releases. Each entry should list highlights, migration notes, and security fixes with CVE references when applicable.</p>
        </section>
        <section class="site-section">
            <h2>Upgrade checklist</h2>
            <ul>
                <li>Run Composer update against the pinned PHP version.</li>
                <li>Execute PHPUnit and PHPStan baselines; capture diff for reviewers.</li>
                <li>Invalidate route cache and warm caches if your deployment relies on them.</li>
                <li>Smoke-test critical controllers and CLI commands.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Support windows</h2>
            <p>Document how long each major release receives bug fixes versus security-only updates so downstream vendors can plan.</p>
        </section>
<?php
$siteCtaText = "More updates on the news index.";
$siteCtaHref = "/news";
$siteCtaLabel = "News home";
require __DIR__ . '/../partials/footer_cta.php';
