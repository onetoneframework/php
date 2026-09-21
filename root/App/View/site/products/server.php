<?php
declare(strict_types=1);
$siteKicker = 'Products';
$siteHeading = 'Server management';
$siteLede = 'Provisioning, configuration drift detection, and backup policies for fleets that run this framework.';
$siteCrumbs = array (
  0 => 
  array (
    'label' => 'Home',
    'href' => '/',
  ),
  1 => 
  array (
    'label' => 'Products',
    'href' => '/products',
  ),
  2 => 
  array (
    'label' => 'Server management',
    'href' => NULL,
  ),
);
require __DIR__ . '/../partials/hero.php';
?>
        <section class="site-section">
            <h2>Fleet operations</h2>
            <p>Server management describes how operators keep many nodes healthy while applications built on Onetone stay online: patching, drift detection, backups, and safe rollback paths.</p>
            <h3>Monitoring and agents</h3>
            <ul>
                <li>Agent-based metrics (CPU, memory, disk, PHP-FPM pool saturation) or agentless checks via HTTP health endpoints.</li>
                <li>Alert routing by severity with on-call rotations and escalation policies.</li>
                <li>Integration hooks for Ansible, Terraform, or your existing config management without forking the framework.</li>
            </ul>
            <h3>Maintenance windows</h3>
            <ul>
                <li>Coordinate <code>APP_MAINTENANCE</code>-style flags with load balancers so users see a deliberate maintenance page.</li>
                <li>Document patch windows, kernel updates, and PHP minor version cadence per environment.</li>
            </ul>
        </section>
        <section class="site-section">
            <h2>Backups and recovery</h2>
            <p>Spell out RPO/RTO targets, off-site retention, and restore drills. Link runbooks that reference this application’s cache paths, upload directories, and database schemas.</p>
        </section>
<?php
$siteCtaText = "Return to the product index or jump into the documentation hub.";
$siteCtaHref = "/products";
$siteCtaLabel = "All products";
require __DIR__ . '/../partials/footer_cta.php';
