<?php
declare(strict_types=1);
$siteKicker = 'News';
$siteHeading = 'Blog';
$siteLede = 'Wire this route to your CMS or static generator.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'News',
    'href' => '/news',
  ),
  2 => 
  array (
    'label' => 'Blog',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Editorial calendar</h2>
            <p>Publish deep dives on performance work, security advisories with mitigation steps, and guest posts from partners. Each article should link to the relevant release or documentation section.</p>
        </section>
        <section class="site-section">
            <h2>CMS integration</h2>
            <p>Wire this route to your headless CMS, static site generator, or Markdown collection. Until then, curate three evergreen stories below as HTML snippets.</p>
            <ul>
                <li><strong>Case study</strong> — latency wins after enabling structured logging and trace propagation.</li>
                <li><strong>Guide</strong> — how teams adopt PHPUnit baselines without freezing quality forever.</li>
                <li><strong>Spotlight</strong> — contributor interviews tied to <a href="/community/contribute">contribution docs</a>.</li>
            </ul>
        </section>
<?php
$siteCtaText = "More updates on the news index.";
$siteCtaHref = "/news";
$siteCtaLabel = "News home";
require __DIR__ . '/../partials/footer_cta.php';
