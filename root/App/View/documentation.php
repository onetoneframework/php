<?php
/** @var string $content */
$active = $activeDoc ?? 'interpreter';
?>
<div id="content" class="documentation-page">
    <aside class="documentation-sidebar" aria-label="Documentation">
        <p class="documentation-sidebar__title">Documentation</p>
        <nav class="documentation-sidebar__nav" aria-label="Primary">
            <a
                href="/documentation"
                class="documentation-sidebar__link<?= $active === 'interpreter' ? ' is-active' : '' ?>"
                <?= $active === 'interpreter' ? 'aria-current="page"' : '' ?>
            >Interpreter</a>
            <a
                href="/documentation/php"
                class="documentation-sidebar__link<?= $active === 'php' ? ' is-active' : '' ?>"
                <?= $active === 'php' ? 'aria-current="page"' : '' ?>
            >PHP platform</a>
            <a
                href="/documentation/frida"
                class="documentation-sidebar__link<?= $active === 'frida' ? ' is-active' : '' ?>"
                <?= $active === 'frida' ? 'aria-current="page"' : '' ?>
            >Frida</a>
            <a
                href="/documentation/typescript"
                class="documentation-sidebar__link<?= $active === 'typescript' ? ' is-active' : '' ?>"
                <?= $active === 'typescript' ? 'aria-current="page"' : '' ?>
            >TypeScript</a>
            <a
                href="/documentation/android"
                class="documentation-sidebar__link<?= $active === 'android' ? ' is-active' : '' ?>"
                <?= $active === 'android' ? 'aria-current="page"' : '' ?>
            >Android</a>
            <a
                href="/documentation/java"
                class="documentation-sidebar__link<?= $active === 'java' ? ' is-active' : '' ?>"
                <?= $active === 'java' ? 'aria-current="page"' : '' ?>
            >Java</a>
            <a
                href="/documentation/python"
                class="documentation-sidebar__link<?= $active === 'python' ? ' is-active' : '' ?>"
                <?= $active === 'python' ? 'aria-current="page"' : '' ?>
            >Python</a>
        </nav>
    </aside>
    <div class="documentation-article markdown-body">
        <?php echo $content; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', (event) => {
    hljs.highlightAll();
});
</script>
