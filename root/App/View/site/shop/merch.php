<?php
declare(strict_types=1);
$siteKicker = 'Shop';
$siteHeading = 'Merch';
$siteLede = 'Product grid, sizing charts, and fulfillment partners.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Shop',
    'href' => '/shop',
  ),
  2 => 
  array (
    'label' => 'Merch',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Catalog outline</h2>
            <p>Describe apparel cuts, materials, sizing charts, and fulfillment partners. Include expected ship regions, duties disclaimer, and return policy.</p>
        </section>
        <section class="site-section">
            <h2>Merchandising blocks</h2>
            <ul>
                <li>Hero SKU with limited drops and inventory countdown.</li>
                <li>Bundles that pair stickers + notebooks for conference booths.</li>
                <li>Charity SKU where proceeds fund <a href="/shop/donate">open-source maintenance</a>.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Operational checklist</h2>
            <p>Wire tax collection, fraud rules, and warehouse webhooks. Document how customer service accesses order history.</p>
        </section>
<?php
$siteCtaText = "Browse other shop sections.";
$siteCtaHref = "/shop";
$siteCtaLabel = "Shop home";
require __DIR__ . '/../partials/footer_cta.php';
