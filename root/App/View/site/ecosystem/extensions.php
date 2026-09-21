<?php
declare(strict_types=1);
$siteKicker = 'Ecosystem';
$siteHeading = 'Extensions';
$siteLede = 'VS Code / PhpStorm plugins and CLI companions.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Ecosystem',
    'href' => '/ecosystem',
  ),
  2 => 
  array (
    'label' => 'Extensions',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Editor integrations</h2>
            <p>Link VS Code / PhpStorm plugins that add snippets for annotations, route navigation, and PHPUnit templates. Include install counts and compatibility matrix per IDE version.</p>
        </section>
        <section class="site-section">
            <h2>CLI companions</h2>
            <ul>
                <li>Wrappers around <code>php_console</code> for repetitive maintenance tasks.</li>
                <li>Codegen tools that scaffold controllers, views, and tests with project conventions.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Deploy integrations</h2>
            <p>Document GitHub Actions, GitLab CI, and Docker snippets that cache Composer dependencies and run static analysis before deploy.</p>
        </section>
<?php
$siteCtaText = "Return to ecosystem overview.";
$siteCtaHref = "/ecosystem";
$siteCtaLabel = "Ecosystem home";
require __DIR__ . '/../partials/footer_cta.php';
