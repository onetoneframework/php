<?php
declare(strict_types=1);
$siteKicker = 'Resources';
$siteHeading = 'Certification';
$siteLede = 'Exam blueprints, badges, and renewal policy — replace with your program.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Resources',
    'href' => '/resources',
  ),
  2 => 
  array (
    'label' => 'Certification',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Program outline</h2>
            <p>Describe certification tiers (Associate, Professional, Architect) with skills measured in each: routing, middleware, DI containers, performance profiling, and secure deployment practices.</p>
        </section>
        <section class="site-section">
            <h2>Exam format</h2>
            <ul>
                <li>Timed multiple choice on framework internals and HTTP semantics.</li>
                <li>Practical lab deploying a small feature behind feature flags with tests.</li>
                <li>Oral or written architecture review for advanced tiers.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Renewal</h2>
            <p>Explain how often professionals must recertify, which major versions trigger renewal, and how badges appear on partner directories.</p>
        </section>
<?php
$siteCtaText = "Back to the resources index.";
$siteCtaHref = "/resources";
$siteCtaLabel = "Resources home";
require __DIR__ . '/../partials/footer_cta.php';
