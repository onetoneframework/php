<?php
declare(strict_types=1);
$siteKicker = 'Documentation';
$siteHeading = 'Getting started';
$siteLede = 'Install PHP dependencies, boot the built-in server, and learn the commands maintainers run every day.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Docs',
    'href' => '/docs',
  ),
  2 => 
  array (
    'label' => 'Getting started',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Install dependencies</h2>
            <p>From the repository root, install Composer packages for the PHP platform (vendor tree lives next to <code>root/</code> and <code>src/</code>).</p>
            <div class="site-code">composer install --working-dir=./res/Platform/PHP</div>
            <h2>Run locally</h2>
            <p>Use PHP’s built-in server with the public web root under <code>res/Platform/PHP/root</code> so <code>index.php</code> and static assets resolve correctly.</p>
            <div class="site-code">php -S localhost:8080 -t res/Platform/PHP/root</div>
            <h2>Environment and route cache</h2>
            <ul>
                <li>Copy <code>res/Platform/PHP/root/.env.example</code> to <code>.env</code> and tune flags such as <code>ROUTE_CACHE</code> while iterating on routes.</li>
                <li>If routes appear stale, delete <code>res/Platform/PHP/root/App/Cache/routes.cache.php</code> or disable caching in <code>.env</code>.</li>
            </ul>
            <h2>Quality loop</h2>
            <p>Repository <code>AGENTS.md</code> documents PHPUnit, PHPStan, and interpreter smoke commands. Project skills under <code>.claude/skills/project/</code> mirror those workflows for automation-friendly agents.</p>
        </section>
<?php
$siteCtaText = "Browse sibling pages from the docs hub.";
$siteCtaHref = "/docs";
$siteCtaLabel = "Docs hub";
require __DIR__ . '/../partials/footer_cta.php';
