<?php
declare(strict_types=1);
$siteKicker = 'Partners';
$siteHeading = 'Find a partner';
$siteLede = 'Search partners by geography, industry, and certification tier.';
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
    'label' => 'Find a partner',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Directory filters</h2>
            <p>Let visitors filter partners by geography, industry vertical, certification tier, and languages supported. Surface response-time expectations for initial contact.</p>
        </section>
        <section class="site-section">
            <h2>Partner profiles</h2>
            <ul>
                <li>Short positioning statement and flagship customers (with permission).</li>
                <li>Links to case studies, security attestations, and team size.</li>
                <li>Preferred project sizes (MVP, migration, 24/7 operations).</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Implementation note</h2>
            <p>Wire this view to your CRM or CMS when profiles outgrow static HTML. Until then, maintain a curated list manually to avoid stale data.</p>
        </section>
<?php
$siteCtaText = "Browse other partner resources.";
$siteCtaHref = "/partners";
$siteCtaLabel = "Partners home";
require __DIR__ . '/../partials/footer_cta.php';
