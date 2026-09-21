<?php
declare(strict_types=1);
$siteKicker = 'Community';
$siteHeading = 'Discord';
$siteLede = 'Invite policy, moderation rules, and code of conduct.';
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
    'label' => 'Discord',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Invite policy</h2>
            <p>Publish whether the server is public invite, gated behind a short form, or sponsor-only. Rotate invite links regularly and document how abuse reports are handled.</p>
        </section>
        <section class="site-section">
            <h2>Channels map</h2>
            <ul>
                <li><strong>#announcements</strong> — release notes mirroring <a href="/news/releases">/news/releases</a>.</li>
                <li><strong>#help-php</strong> — framework usage questions with thread mode enabled.</li>
                <li><strong>#internals</strong> — design discussions for contributors working on <code>src/</code>.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Moderation toolkit</h2>
            <p>List moderator coverage hours, escalation path for harassment, and bots used for CAPTCHA or logging.</p>
        </section>
<?php
$siteCtaText = "Discover other community channels.";
$siteCtaHref = "/community";
$siteCtaLabel = "Community home";
require __DIR__ . '/../partials/footer_cta.php';
