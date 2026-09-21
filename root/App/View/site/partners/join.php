<?php
declare(strict_types=1);
$siteKicker = 'Partners';
$siteHeading = 'Become a partner';
$siteLede = 'Application workflow, MDF rules, and deal registration.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Partners',
    'href' => '/partners',
  ),
  2 => 
  array (
    'label' => 'Become a partner',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Why partner</h2>
            <p>Describe MDF rules, lead registration, deal desk access, and co-marketing opportunities (webinars, blog swaps, conference slots).</p>
        </section>
        <section class="site-section">
            <h2>Requirements</h2>
            <ul>
                <li>Certified engineers on staff referencing <a href="/resources/certification">certification</a> tiers.</li>
                <li>Demonstrated customer success on PHP 8.4+ stacks with automated testing discipline.</li>
                <li>Legal agreements covering IP, trademark usage, and support boundaries.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Application flow</h2>
            <p>Publish intake steps: discovery call, technical review, pilot engagement, and onboarding checklist. Link to PDF contract templates when legal approves.</p>
        </section>
<?php
$siteCtaText = "Browse other partner resources.";
$siteCtaHref = "/partners";
$siteCtaLabel = "Partners home";
require __DIR__ . '/../partials/footer_cta.php';
