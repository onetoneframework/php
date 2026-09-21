<?php
declare(strict_types=1);
$siteKicker = 'News';
$siteHeading = 'Events';
$siteLede = 'Embed Luma, Meetup, or Conference links here.';
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
    'label' => 'Events',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Upcoming events</h2>
            <p>Embed calendars from Luma, Meetup, or Conference tools. For each event include timezone-aware start time, venue or streaming link, language, and accessibility accommodations.</p>
        </section>
        <section class="site-section">
            <h2>Call for proposals</h2>
            <p>Explain how speakers submit workshops on Onetone internals, performance clinics, or migration stories. Provide a single intake email or form URL.</p>
        </section>
        <section class="site-section">
            <h2>Booth and swag</h2>
            <p>List trade show appearances with booth numbers, demo focus, and whether <a href="/shop/merch">merch</a> will be available on site.</p>
        </section>
<?php
$siteCtaText = "More updates on the news index.";
$siteCtaHref = "/news";
$siteCtaLabel = "News home";
require __DIR__ . '/../partials/footer_cta.php';
