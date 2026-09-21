<?php
declare(strict_types=1);
$siteKicker = 'About';
$siteHeading = 'Brand assets';
$siteLede = 'Vector downloads and trademark guidelines.';
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
    'label' => 'Brand assets',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Logo usage</h2>
            <p>Provide primary horizontal and stacked marks in SVG and high-resolution PNG. Specify minimum clear space (for example the cap height of the logotype) and acceptable background colors (light, dark, photographic overlays).</p>
        </section>
        <section class="site-section">
            <h2>Color and typography</h2>
            <ul>
                <li>Document core brand colors with HEX, RGB, and WCAG contrast pairs for text on surfaces.</li>
                <li>List webfont files or system font fallbacks used on the marketing site (<code>MenuShadow</code> skin references display and body stacks in CSS variables).</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Trademark guidance</h2>
            <p>Clarify when third parties may use the name “Onetone” in product titles, conference talks, or merchandise. Link to a PDF brand book when legal approves it.</p>
            <p class="site-notice">Replace placeholder guidance with counsel-approved language before distributing widely.</p>
        </section>
<?php
$siteCtaText = "More company context on the about index.";
$siteCtaHref = "/about";
$siteCtaLabel = "About home";
require __DIR__ . '/../partials/footer_cta.php';
