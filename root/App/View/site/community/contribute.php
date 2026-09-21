<?php
declare(strict_types=1);
$siteKicker = 'Community';
$siteHeading = 'Contribute';
$siteLede = 'Issue templates, skills under .claude/skills, and AGENTS.md.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Community',
    'href' => '/community',
  ),
  2 => 
  array (
    'label' => 'Contribute',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Before you open a PR</h2>
            <ul>
                <li>Read <code>AGENTS.md</code> for coding standards, tab indentation, and testing expectations.</li>
                <li>Skim relevant skills under <code>.claude/skills/project/</code> for repeatable dev loops.</li>
                <li>Search existing issues and forums to avoid duplicate work.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Development workflow</h2>
            <p>Keep diffs surgical: prefer focused changes over drive-by refactors. Run PHPUnit and PHPStan on touched PHP paths when feasible. Never commit secrets or machine-local <code>.env</code> files.</p>
            <h3>Issue templates</h3>
            <p>Describe how to label bugs vs. features, attach repro repositories, and mention PHP/runtime versions.</p>
        </section>
        <section class="site-section">
            <h2>Recognition</h2>
            <p>Explain how contributors appear in release notes, how first-time contributors get paired with mentors, and any swag eligibility tied to <a href="/shop/merch">merch</a> programs.</p>
        </section>
<?php
$siteCtaText = "Discover other community channels.";
$siteCtaHref = "/community";
$siteCtaLabel = "Community home";
require __DIR__ . '/../partials/footer_cta.php';
