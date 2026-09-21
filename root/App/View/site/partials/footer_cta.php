<?php
declare(strict_types=1);
/** @var string $siteCtaText */
/** @var string $siteCtaHref */
/** @var string $siteCtaLabel */
?>
        <div class="site-cta-bar">
            <p><?= htmlspecialchars($siteCtaText, ENT_QUOTES, 'UTF-8') ?></p>
            <a class="site-btn site-btn--primary" href="<?= htmlspecialchars($siteCtaHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($siteCtaLabel, ENT_QUOTES, 'UTF-8') ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
    </div>
</div>
