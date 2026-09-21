<?php
declare(strict_types=1);
$siteKicker = 'About';
$siteHeading = 'Team';
$siteLede = 'Photos, bios, and time zones for maintainers.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'About',
    'href' => '/about',
  ),
  2 => 
  array (
    'label' => 'Team',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Maintainers and reviewers</h2>
            <p>List core committers with short bios, focus areas (routing, DI, frontend toolchain), and preferred contact channels. Include time zones so global contributors know when to expect async reviews.</p>
        </section>
        <section class="site-section">
            <h2>Alumni and advisors</h2>
            <p>Recognize people who shaped earlier releases even if they have stepped back. Link to talks, papers, or conference sessions that explain architectural decisions.</p>
        </section>
        <section class="site-section">
            <h2>Placeholder grid</h2>
            <div class="site-panels">
                <article class="site-panel">
                    <h3><i class="fa-solid fa-user" aria-hidden="true"></i> Role: Platform lead</h3>
                    <p>Owns HttpKernel, middleware contracts, and release cadence.</p>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-user" aria-hidden="true"></i> Role: Tooling</h3>
                    <p>Owns PHPUnit/PHPStan baselines and developer bootstrap scripts.</p>
                </article>
                <article class="site-panel">
                    <h3><i class="fa-solid fa-user" aria-hidden="true"></i> Role: Community</h3>
                    <p>Coordinates forums, Discord moderation, and contributor onboarding.</p>
                </article>
            </div>
        </section>
<?php
$siteCtaText = "More company context on the about index.";
$siteCtaHref = "/about";
$siteCtaLabel = "About home";
require __DIR__ . '/../partials/footer_cta.php';
