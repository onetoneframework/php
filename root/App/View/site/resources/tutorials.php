<?php
declare(strict_types=1);
$siteKicker = 'Resources';
$siteHeading = 'Tutorials';
$siteLede = 'Step-by-step guides for PHPUnit, PHPStan, and controller tests.';
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
    'label' => 'Tutorials',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Hands-on tracks</h2>
            <p>Structure lessons as short modules with a runnable repository state at each step. Favor commands that already exist in <code>AGENTS.md</code> so learners stay aligned with maintainers.</p>
            <h3>Suggested modules</h3>
            <ul>
                <li><strong>Routing</strong> — add a new <code>#[Route]</code>, clear route cache, verify with the built-in server.</li>
                <li><strong>Layouts</strong> — create a controller action that registers CSS/JS and returns a MenuShadow layout.</li>
                <li><strong>Testing</strong> — write PHPUnit coverage for a controller and run PHPStan on touched <code>src/</code> paths.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Artifacts to attach</h2>
            <p>Link sample diffs, exercise branches, and solution tags. Mention expected PHP version (8.4) and Composer workspace path (<code>res/Platform/PHP</code>).</p>
        </section>
<?php
$siteCtaText = "Back to the resources index.";
$siteCtaHref = "/resources";
$siteCtaLabel = "Resources home";
require __DIR__ . '/../partials/footer_cta.php';
