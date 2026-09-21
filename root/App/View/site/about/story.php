<?php
declare(strict_types=1);
$siteKicker = 'About';
$siteHeading = 'Our story';
$siteLede = 'Why Onetone exists and which trade-offs it optimizes for.';
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
    'label' => 'Our story',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Mission</h2>
            <p>Onetone optimizes for teams that want a batteries-included PHP framework with clear boundaries: expressive application code, predictable internals, and documentation that agents and humans can follow without guesswork.</p>
        </section>
        <section class="site-section">
            <h2>Principles</h2>
            <ul>
                <li><strong>Internal-first core</strong> — prefer framework-owned HTTP, routing, and observability paths before pulling in parallel third-party stacks.</li>
                <li><strong>Explicit wiring</strong> — dedicated routes and view files beat magic conventions when you are shipping serious systems.</li>
                <li><strong>Compatibility discipline</strong> — deprecated behavior stays reachable until maintainers intentionally remove it.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Roadmap narrative</h2>
            <p>Use this section for release themes, long-lived bets (interpreter, polyglot tooling), and how community feedback shapes priorities. Link to <a href="/news/releases">release notes</a> when you publish them.</p>
        </section>
<?php
$siteCtaText = "More company context on the about index.";
$siteCtaHref = "/about";
$siteCtaLabel = "About home";
require __DIR__ . '/../partials/footer_cta.php';
