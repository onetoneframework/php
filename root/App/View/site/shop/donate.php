<?php
declare(strict_types=1);
$siteKicker = 'Shop';
$siteHeading = 'Donations';
$siteLede = 'Suggested tiers and tax receipts policy.';
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
    'label' => 'Donations',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Suggested tiers</h2>
            <ul>
                <li><strong>Sustainer</strong> — recurring monthly amount for CI minutes and security audits.</li>
                <li><strong>Advocate</strong> — annual contribution with logo placement on the supporters page.</li>
                <li><strong>Patron</strong> — custom sponsorship with roadmap input sessions.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Receipts and compliance</h2>
            <p>Explain tax-deductible status per jurisdiction, currency support, and how donors receive PDF receipts. Link privacy policy for payment data handling.</p>
        </section>
        <section class="site-section">
            <h2>Transparency</h2>
            <p>Publish how funds are allocated (hosting, events, bounties) and provide quarterly reports once donations flow.</p>
        </section>
<?php
$siteCtaText = "Browse other shop sections.";
$siteCtaHref = "/shop";
$siteCtaLabel = "Shop home";
require __DIR__ . '/../partials/footer_cta.php';
