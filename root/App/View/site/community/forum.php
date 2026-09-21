<?php
declare(strict_types=1);
$siteKicker = 'Community';
$siteHeading = 'Forum';
$siteLede = 'Discourse / phpBB embed or native integration.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Community',
    'href' => '/community',
  ),
  2 => 
  array (
    'label' => 'Forum',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Forum expectations</h2>
            <p>Encourage reproducible bug reports: PHP version, framework commit, minimal route or test case, and logs with secrets redacted. Pin a “read first” thread linking to <a href="/docs/installation">installation</a> and <a href="/docs/architecture">architecture</a> docs.</p>
        </section>
        <section class="site-section">
            <h2>Moderation</h2>
            <ul>
                <li>Code of conduct summary with reporting email.</li>
                <li>Spam and recruiting policies.</li>
                <li>How threads are tagged (bug, question, RFC) for triage.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Embedding</h2>
            <p>Drop Discourse or phpBB iframe snippets here once you provision hosting. Until then, link out to the live forum subdomain.</p>
        </section>
<?php
$siteCtaText = "Discover other community channels.";
$siteCtaHref = "/community";
$siteCtaLabel = "Community home";
require __DIR__ . '/../partials/footer_cta.php';
