<!-- License - Onetone Framework (AGPL-3.0) template -->
<div id="debugger-bar">
    <div class="debugger-bar-group debugger-bar-left">
        <div class="debugger-bar-item" id="db-memory">
            <svg class="debugger-bar-icon" viewBox="0 0 16 16" fill="none"><path d="M2 4.5A2.5 2.5 0 014.5 2h7A2.5 2.5 0 0114 4.5v7a2.5 2.5 0 01-2.5 2.5h-7A2.5 2.5 0 012 11.5v-7z" stroke="currentColor" stroke-width="1.2"/><path d="M5 6h6M5 8h6M5 10h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <span class="debugger-bar-label">Memory</span>
            <span class="debugger-bar-value"><?= $memoryUsage ?></span>
        </div>
        <div class="debugger-bar-item" id="db-disk">
            <svg class="debugger-bar-icon" viewBox="0 0 16 16" fill="none"><path d="M3 3.5A1.5 1.5 0 014.5 2h5.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V12.5A1.5 1.5 0 0112 14H4.5A1.5 1.5 0 013 12.5v-9z" stroke="currentColor" stroke-width="1.2"/><path d="M5 2v3.5a.5.5 0 00.5.5h4a.5.5 0 00.5-.5V2" stroke="currentColor" stroke-width="1.2"/><path d="M5 10h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
            <span class="debugger-bar-label">Disk</span>
            <span class="debugger-bar-value"><?= $freeSpace ?></span>
        </div>
    </div>
    <div class="debugger-bar-group debugger-bar-right">
        <?php if (($profilerEnabled ?? false) === true): ?>
            <div class="debugger-bar-item debugger-bar-clickable" id="db-tools-toggle" onclick="toggleDebugToolsMenu()">
                <svg class="debugger-bar-icon" viewBox="0 0 16 16" fill="none"><path d="M8 1.8l1.2 2.3 2.5.4-1.8 1.9.4 2.6L8 8l-2.3 1.2.4-2.6L4.3 4.5l2.5-.4L8 1.8z" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/></svg>
                <span class="debugger-bar-label">Tools</span>
                <span class="debugger-bar-value">Profiler</span>
            </div>
        <?php endif; ?>
        <div class="debugger-bar-item" id="db-locale">
            <svg class="debugger-bar-icon" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.2"/><path d="M2 8h12M8 2c-2 2.4-2 9.6 0 12M8 2c2 2.4 2 9.6 0 12" stroke="currentColor" stroke-width="1.2"/></svg>
            <span class="debugger-bar-value"><?= $environment['acceptLanguage']; ?></span>
        </div>
        <div class="debugger-bar-item" id="db-php">
            <svg class="debugger-bar-icon" viewBox="0 0 16 16" fill="none"><path d="M5.5 3L2 8l3.5 5M10.5 3L14 8l-3.5 5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="debugger-bar-value">PHP <?= $phpVersion ?></span>
        </div>
        <div class="debugger-bar-item" id="db-server">
            <svg class="debugger-bar-icon" viewBox="0 0 16 16" fill="none"><rect x="2" y="3" width="12" height="4" rx="1" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="9" width="12" height="4" rx="1" stroke="currentColor" stroke-width="1.2"/><circle cx="4.5" cy="5" r=".7" fill="currentColor"/><circle cx="4.5" cy="11" r=".7" fill="currentColor"/></svg>
            <span class="debugger-bar-value"><?= $builtOperationSystem ?> / <?= $serverSoftware ?></span>
        </div>
    </div>
</div>
<?php if (($profilerEnabled ?? false) === true): ?>
    <div id="debugger-tools-menu" style="display:none;">
        <a href="<?= $profilerUrl ?? '/profiler' ?>" target="_blank" rel="noopener noreferrer">Open Profiler</a>
    </div>
<?php endif; ?>

<script>
    function toggleDebugToolsMenu() {
        var menu = document.getElementById('debugger-tools-menu');
        if (!menu) {
            return;
        }
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    document.addEventListener('click', function (e) {
        var toggle = document.getElementById('db-tools-toggle');
        var menu = document.getElementById('debugger-tools-menu');
        if (!toggle || !menu) {
            return;
        }
        if (!toggle.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
        }
    });
</script>

<style>
    #debugger-bar {
        z-index: 999999999999;
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #16161e;
        border-top: 1px solid #23232b;
        font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.35);
    }

    .debugger-bar-group {
        display: flex;
        align-items: center;
        height: 100%;
    }

    .debugger-bar-item {
        display: flex;
        align-items: center;
        gap: 6px;
        height: 100%;
        padding: 0 16px;
        font-size: 11px;
        color: #8888a0;
        border-right: 1px solid #23232b;
        transition: background-color 0.15s ease, color 0.15s ease;
        white-space: nowrap;
        cursor: default;
    }

    .debugger-bar-clickable {
        cursor: pointer;
    }

    .debugger-bar-right .debugger-bar-item {
        border-right: none;
        border-left: 1px solid #23232b;
    }

    .debugger-bar-item:hover {
        background-color: #1e1e28;
        color: #c0c0d4;
    }

    .debugger-bar-icon {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
        color: #555568;
    }

    .debugger-bar-item:hover .debugger-bar-icon {
        color: #7a7a90;
    }

    .debugger-bar-label {
        color: #555568;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 9px;
        letter-spacing: 0.8px;
    }

    .debugger-bar-value {
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        font-size: 11px;
        color: #a0a0b8;
    }

    .debugger-bar-item:hover .debugger-bar-value {
        color: #d0d0df;
    }

    #db-memory .debugger-bar-value {
        color: #4ade80;
    }

    #db-memory:hover .debugger-bar-value {
        color: #6ee7a0;
    }

    #db-disk .debugger-bar-value {
        color: #60a5fa;
    }

    #db-disk:hover .debugger-bar-value {
        color: #93c5fd;
    }

    #db-php .debugger-bar-value {
        color: #a78bfa;
    }

    #db-php:hover .debugger-bar-value {
        color: #c4b5fd;
    }

    #db-server .debugger-bar-value {
        color: #f87171;
    }

    #db-server:hover .debugger-bar-value {
        color: #fca5a5;
    }

    #debugger-tools-menu {
        position: fixed;
        right: 14px;
        bottom: 44px;
        z-index: 1000000000000;
        min-width: 170px;
        background: #141822;
        border: 1px solid #2d364b;
        border-radius: 8px;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.35);
        overflow: hidden;
        font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    #debugger-tools-menu a {
        display: block;
        padding: 10px 12px;
        font-size: 12px;
        color: #cad5f0;
        text-decoration: none;
    }

    #debugger-tools-menu a:hover {
        background: #20283a;
        color: #ffffff;
    }
</style>