<?php
declare(strict_types=1);
$siteKicker = 'Resources';
$siteHeading = 'Podcast';
$siteLede = 'RSS embed or transcript archive for maintainers and community voices.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Resources',
    'href' => '/resources',
  ),
  2 => 
  array (
    'label' => 'Podcast',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Show format</h2>
            <p>Publish episode length, release cadence, and whether transcripts ship alongside audio. Highlight guests from core, extension authors, and teams running Onetone in production.</p>
        </section>
        <section class="site-section">
            <h2>Distribution</h2>
            <ul>
                <li>RSS feed URL and supported podcast clients.</li>
                <li>Video simulcast links if you record on YouTube or similar platforms.</li>
                <li>Chapter markers for deep dives (routing cache, Swoole mode, polyglot roadmap).</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Community call-ins</h2>
            <p>Explain how listeners submit questions and how moderation handles spoiler-sensitive roadmap topics.</p>
        </section>
<?php
$siteCtaText = "Back to the resources index.";
$siteCtaHref = "/resources";
$siteCtaLabel = "Resources home";
require __DIR__ . '/../partials/footer_cta.php';
