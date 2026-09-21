<?php
declare(strict_types=1);
$siteKicker = 'About';
$siteHeading = 'Careers';
$siteLede = 'Role descriptions and interview loops.';
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
    'label' => 'Careers',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Open roles</h2>
            <p>Publish real openings with location policy, compensation bands where legal, and stack expectations (PHP 8.4, automated tests, static analysis). Until then, use this page to describe how candidates should reach maintainers.</p>
        </section>
        <section class="site-section">
            <h2>Interview loop</h2>
            <ul>
                <li>Initial conversation focused on prior framework or platform work.</li>
                <li>Take-home or paired exercise grounded in this repository (routing change + tests).</li>
                <li>Architecture discussion referencing <code>HttpKernel</code>, middleware, and observability.</li>
                <li>Values and remote collaboration expectations.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Benefits and growth</h2>
            <p>Document learning budget, conference travel, mentorship structure, and how performance reviews connect to open-source stewardship.</p>
        </section>
<?php
$siteCtaText = "More company context on the about index.";
$siteCtaHref = "/about";
$siteCtaLabel = "About home";
require __DIR__ . '/../partials/footer_cta.php';
