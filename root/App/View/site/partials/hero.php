<?php
declare(strict_types=1);
/** @var string $siteKicker */
/** @var string $siteHeading */
/** @var string $siteLede */
/** @var list<array{label:string, href:?string}> $siteCrumbs */
?>
<div class="site-page">
    <header class="site-hero">
        <div class="site-hero__inner">
            <nav class="site-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <?php foreach ($siteCrumbs as $i => $crumb): ?>
                        <li>
                            <?php if ($crumb['href'] !== null && $crumb['href'] !== ''): ?>
                                <a href="<?= htmlspecialchars($crumb['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></a>
                            <?php else: ?>
                                <span aria-current="page"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
            <p class="site-kicker"><?= htmlspecialchars($siteKicker, ENT_QUOTES, 'UTF-8') ?></p>
            <h1 class="site-heading"><?= htmlspecialchars($siteHeading, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="site-lede"><?= htmlspecialchars($siteLede, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </header>
    <div class="site-body">
