<?php
declare(strict_types=1);
$siteKicker = 'Ecosystem';
$siteHeading = 'Themes';
$siteLede = 'Screenshots and token tables for each layout skin.';
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
    'label' => 'Themes',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>MenuShadow and beyond</h2>
            <p>The demo site uses the <code>MenuShadow</code> layout under <code>App/Frontend/Layout/MenuShadow</code>. Themes should document CSS variables for color, typography, spacing, and motion tokens.</p>
        </section>
        <section class="site-section">
            <h2>Deliverables per theme</h2>
            <ul>
                <li>Preview screenshots for desktop, tablet, and mobile breakpoints.</li>
                <li>Accessibility notes (contrast ratios, focus rings, reduced-motion behavior).</li>
                <li>Instructions for swapping the active layout in controllers or configuration.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Dark mode</h2>
            <p>Describe how tokens map between light and dark palettes and whether user preference is read from <code>prefers-color-scheme</code> or explicit toggles.</p>
        </section>
<?php
$siteCtaText = "Return to ecosystem overview.";
$siteCtaHref = "/ecosystem";
$siteCtaLabel = "Ecosystem home";
require __DIR__ . '/../partials/footer_cta.php';
