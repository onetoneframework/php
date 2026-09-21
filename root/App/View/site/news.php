<?php
declare(strict_types=1);
$siteKicker = 'News';
$siteHeading = 'News';
$siteLede = 'Blog posts, releases, and events calendars.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'News',
    'href' => NULL,
  ),
);
require __DIR__ . '/partials/hero.php';
?>
        <section class="site-section">
            <h2>Stay current</h2>
            <p>Ship editorial updates, tagged releases, and a public events calendar without overloading the main documentation tree. Each route remains explicit for RSS or static export later.</p>
        </section>
        <section class="site-section"><h2>In this section</h2><div class="site-panels"><article class="site-panel"><h3><i class="fa-solid fa-newspaper" aria-hidden="true"></i> Blog</h3><p>Editorial content and deep dives.</p><a href="/news/blog">/news/blog <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-rocket" aria-hidden="true"></i> Release notes</h3><p>Version history with upgrade steps.</p><a href="/news/releases">/news/releases <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><article class="site-panel"><h3><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Events</h3><p>Meetups, webinars, and booth schedules.</p><a href="/news/events">/news/events <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article></div></section>
<?php
$siteCtaText = "Clone this repository, run Composer, and boot the built-in server to explore the full stack.";
$siteCtaHref = "/docs/installation";
$siteCtaLabel = "Install guide";
require __DIR__ . '/partials/footer_cta.php';
