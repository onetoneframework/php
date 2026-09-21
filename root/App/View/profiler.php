<?php
/** @var array|null $profile */
/** @var string|null $profileId */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profiler</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --bg-base:       #080c12;
            --bg-surface:    #0d1420;
            --bg-elevated:   #121c2e;
            --bg-hover:      #172035;
            --border-subtle: #000000;
            --border-mid:    #253650;
            --border-strong: #2e4770;
            --text-primary:  #e8f0fe;
            --text-secondary:#8fa8cc;
            --text-muted:    #c3cbd4;
            --accent-blue:   #3d8bff;
            --accent-cyan:   #26d0ce;
            --accent-gold:   #f0a645;
            --accent-violet: #8b73ff;
            --accent-green:  #34d399;
            --accent-red:    #f87171;
            --mono:          'DM Mono', 'Fira Code', Consolas, monospace;
            --display:       'Syne', 'Segoe UI', sans-serif;
        }

        body {
            margin: 0;
            background: var(--bg-base);
            color: var(--text-primary);
            font-family: var(--mono);
            font-size: 13px;
            line-height: 1.6;
        }

        .layout {
            max-width: 1160px;
            margin: 0 auto;
            padding: 32px 24px 64px;
        }

        /* Header */
        .header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-subtle);
            margin-bottom: 28px;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--accent-violet) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .header-icon svg { display: block; }

        .header-title {
            font-family: var(--display);
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.3px;
            color: var(--text-primary);
        }

        .header-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--text-muted);
            background: var(--bg-elevated);
            border: 1px solid var(--border-subtle);
            border-radius: 3px;
            padding: 4px 12px;
            letter-spacing: 0.3px;
        }

        .header-badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-green);
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.35; }
        }

        /* Cards */
        .card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: 5px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .card-title {
            font-family: var(--display);
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: 0.2px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title-icon {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .card-body { padding: 16px 20px; }

        /* KPI Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--border-subtle);
        }

        .kpi-cell {
            background: var(--bg-surface);
            padding: 18px 20px;
        }

        .kpi-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .kpi-value {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-primary);
            word-break: break-all;
        }

        .kpi-value.accent { color: var(--accent-blue); }

        /* Exception */
        .exception-card .card-header { border-bottom-color: rgba(248,113,113,0.25); }

        .exception-card {
            border-color: rgba(248,113,113,0.2);
            background: rgba(248,113,113,0.03);
        }

        .exception-body {
            padding: 14px 20px;
            font-size: 12px;
            color: #fca5a5;
            background: rgba(248,113,113,0.04);
        }

        .exception-class {
            color: var(--accent-red);
            font-weight: 500;
        }

        /* Waterfall toolbar */
        .waterfall-toolbar {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-subtle);
            background: var(--bg-elevated);
        }

        .waterfall-toolbar-left {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .wf-btn {
            appearance: none;
            border: 1px solid var(--border-subtle);
            background: transparent;
            color: var(--text-muted);
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 11px;
            font-family: var(--mono);
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }

        .wf-btn:hover {
            background: var(--bg-hover);
            color: var(--text-secondary);
            border-color: var(--border-mid);
        }

        .wf-btn.active {
            background: rgba(61,139,255,0.12);
            border-color: rgba(61,139,255,0.45);
            color: var(--accent-blue);
        }

        .waterfall-stat {
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 0.3px;
        }

        .waterfall-stat strong {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .waterfall-shell {
            padding: 12px;
            background: var(--bg-base);
        }

        .waterfall-scroll { overflow: auto; }

        #waterfall-canvas {
            display: block;
            background: var(--bg-base);
        }

        /* Tooltip */
        .waterfall-tooltip {
            position: fixed;
            z-index: 100;
            pointer-events: none;
            display: none;
            min-width: 200px;
            max-width: min(960px, 96vw);
            padding: 10px 14px;
            background: #0a1120;
            border: 1px solid var(--border-strong);
            border-radius: 8px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.6), 0 0 0 1px rgba(61,139,255,0.08);
            font-size: 11.5px;
            line-height: 1.6;
            color: var(--text-secondary);
            font-family: var(--mono);
            white-space: pre-wrap;
        }

        /* Flow items */
        .flow-list { display: flex; flex-direction: column; gap: 6px; padding: 14px 20px; }

        .flow-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 14px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-subtle);
            border-radius: 3px;
            transition: border-color 0.12s, background 0.12s;
        }

        .flow-item:hover {
            background: var(--bg-hover);
            border-color: var(--border-mid);
        }

        .flow-step-num {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 2px;
            background: rgba(61,139,255,0.1);
            border: 1px solid rgba(61,139,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 500;
            color: var(--accent-blue);
            align-self: center;
        }

        .flow-content {
            flex: 1;
            min-width: 0;
            position: relative;
            padding-right: 220px;
        }

        .flow-call {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-primary);
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .flow-location {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .flow-memory {
            position: absolute;
            right: 0px;
            top: 0px;
            bottom: auto;
            max-width: 210px;
            font-size: 9px;
            color: var(--text-secondary);
            text-align: right;
            white-space: normal;
            word-break: break-word;
            overflow-y: hidden;
        }

        .flow-duration {
            flex-shrink: 0;
            font-size: 11px;
            color: var(--accent-gold);
            font-weight: 500;
            white-space: nowrap;
            margin-top: 1px;
            min-width: 76px;
            text-align: center;
            align-self: center;
        }

        /* Empty state */
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 28px;
            margin-bottom: 10px;
            opacity: 0.5;
        }

        .empty-state-text {
            font-size: 13px;
            color: var(--text-muted);
        }

        .no-data {
            padding: 14px 20px;
            font-size: 12px;
            color: var(--text-muted);
        }

        @media (max-width: 720px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .header { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
    </style>
</head>
<body>
<div class="layout">

    <div class="header">
        <div class="header-brand">
            <div class="header-icon">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M3 9h3M9 3v3M12 9h3M9 12v3" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/>
                    <circle cx="9" cy="9" r="2.5" fill="#fff" fill-opacity="0.9"/>
                </svg>
            </div>
            <div>
                <div class="header-title">Request Profiler</div>
                <div class="header-sub">Flow &amp; timing snapshot</div>
            </div>
        </div>
        <div class="header-badge">
            <span class="header-badge-dot"></span>
            Timeline · <?= date('H:i:s') ?>
        </div>
    </div>

    <?php if (!is_array($profile)): ?>
        <div class="card">
            <div class="empty-state">
                <div class="empty-state-icon">⊘</div>
                <div class="empty-state-text">No profiler snapshot found. Trigger a request first, then open profiler again.</div>
            </div>
        </div>
    <?php else: ?>
        <?php
            $totalMs  = (float) ($profile['durationMs'] ?? 0);
            $flowItems = (!empty($profile['flow']) && is_array($profile['flow'])) ? $profile['flow'] : [];
            if ($totalMs <= 0 && !empty($flowItems)) {
                $sum = 0.0;
                foreach ($flowItems as $flowItem) {
                    $sum += (float) ($flowItem['durationMs'] ?? 0);
                }
                $totalMs = $sum;
            }
            $flowJson = json_encode($flowItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        ?>

        <!-- KPI -->
        <div class="card">
            <div class="kpi-grid">
                <div class="kpi-cell">
                    <div class="kpi-label">Profile ID</div>
                    <div class="kpi-value accent"><?= htmlspecialchars((string) ($profile['id'] ?? $profileId ?? 'N/A')) ?></div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-label">Duration</div>
                    <div class="kpi-value"><?= htmlspecialchars((string) ($profile['durationMs'] ?? '0')) ?> <span style="font-size:11px;color:var(--text-muted)">ms</span></div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-label">Method</div>
                    <div class="kpi-value"><?= htmlspecialchars((string) ($profile['request']['method'] ?? 'N/A')) ?></div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-label">URI</div>
                    <div class="kpi-value" style="font-size:12px;word-break:break-all;"><?= htmlspecialchars((string) ($profile['request']['uri'] ?? 'N/A')) ?></div>
                </div>
            </div>
        </div>

        <?php if (!empty($profile['exception']) && is_array($profile['exception'])): ?>
        <div class="card exception-card">
            <div class="card-header">
                <div class="card-title" style="color:var(--accent-red);">
                    <div class="card-title-icon" style="background:rgba(248,113,113,0.12);">
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                            <path d="M5 2v3M5 7.5v.5" stroke="#f87171" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="5" cy="5" r="4.25" stroke="#f87171" stroke-width="1.5"/>
                        </svg>
                    </div>
                    Exception
                </div>
            </div>
            <div class="exception-body">
                <div>
                    <span class="exception-class"><?= htmlspecialchars((string) ($profile['exception']['class'] ?? 'Exception')) ?></span>:
                    <?= htmlspecialchars((string) ($profile['exception']['message'] ?? '')) ?>
                </div>
                <div style="color:var(--text-muted);margin-top:4px;">
                    <?= htmlspecialchars((string) ($profile['exception']['file'] ?? '')) ?>:<?= htmlspecialchars((string) ($profile['exception']['line'] ?? '')) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Waterfall -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-title-icon" style="background:rgba(61,139,255,0.1);">
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                            <rect x="1" y="2" width="3" height="6" rx="1" fill="#3d8bff" fill-opacity="0.8"/>
                            <rect x="6" y="4" width="3" height="4" rx="1" fill="#3d8bff" fill-opacity="0.5"/>
                        </svg>
                    </div>
                    Waterfall
                </div>
            </div>
            <?php if (!empty($flowItems)): ?>
            <div class="waterfall-toolbar">
                <div class="waterfall-toolbar-left">
                    <button type="button" class="wf-btn active" data-zoom="all">All</button>
                    <button type="button" class="wf-btn" data-zoom="500">500ms</button>
                    <button type="button" class="wf-btn" data-zoom="100">100ms</button>
                </div>
                <div class="waterfall-stat">Total: <strong><?= htmlspecialchars((string) round($totalMs, 3)) ?> ms</strong></div>
            </div>
            <div class="waterfall-shell">
                <div id="waterfall-scroll" class="waterfall-scroll">
                    <canvas id="waterfall-canvas"></canvas>
                </div>
            </div>
            <?php else: ?>
            <div class="no-data">No waterfall data.</div>
            <?php endif; ?>
        </div>

        <!-- Execution Flow -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-title-icon" style="background:rgba(139,115,255,0.12);">
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                            <circle cx="5" cy="2" r="1.25" fill="#8b73ff"/>
                            <circle cx="5" cy="5" r="1.25" fill="#8b73ff" fill-opacity="0.7"/>
                            <circle cx="5" cy="8" r="1.25" fill="#8b73ff" fill-opacity="0.4"/>
                            <line x1="5" y1="3.25" x2="5" y2="3.75" stroke="#8b73ff" stroke-width="1"/>
                            <line x1="5" y1="6.25" x2="5" y2="6.75" stroke="#8b73ff" stroke-width="1" stroke-opacity="0.6"/>
                        </svg>
                    </div>
                    Execution Flow
                </div>
                <div class="waterfall-stat"><?= count($flowItems) ?> steps</div>
            </div>
            <?php if (!empty($flowItems)): ?>
            <div class="flow-list">
                <?php foreach ($flowItems as $step): ?>
                <div class="flow-item">
                    <div class="flow-step-num"><?= htmlspecialchars((string) ($step['step'] ?? '?')) ?></div>
                    <div class="flow-content">
                        <div class="flow-call"><?= htmlspecialchars((string) ($step['call'] ?? '(unknown)')) ?></div>
                        <?php $normalizedLocation = str_replace(['<br>', '<br/>', '<br />'], ' ', (string) ($step['location'] ?? '(unknown location)')); ?>
                        <div class="flow-location"><?= htmlspecialchars($normalizedLocation) ?></div>
                        <?php if (isset($step['memoryUsageBytes']) || isset($step['memoryDeltaBytes']) || isset($step['memoryPeakBytes'])): ?>
                        <div class="flow-memory">
                            <div>Usage : <?= htmlspecialchars((string) round(((float) ($step['memoryUsageBytes'] ?? 0)) / 1024 / 1024, 2)) ?> MB</div>
                            <?php if (isset($step['memoryDeltaBytes'])): ?><div>Delta : <?= htmlspecialchars((string) round(((float) $step['memoryDeltaBytes']) / 1024 / 1024, 2)) ?> MB</div><?php endif; ?>
                            <?php if (isset($step['memoryPeakBytes'])): ?><div>Peak : <?= htmlspecialchars((string) round(((float) $step['memoryPeakBytes']) / 1024 / 1024, 2)) ?> MB</div><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($step['durationMs'])): ?>
                    <div class="flow-duration"><?= htmlspecialchars((string) $step['durationMs']) ?> ms</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-data">No flow data.</div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<div id="waterfall-tooltip" class="waterfall-tooltip"></div>

<?php if (!empty($flowItems)): ?>
<script>
(function () {
    const flow = <?= is_string($flowJson) ? $flowJson : '[]' ?>;
    const canvas = document.getElementById('waterfall-canvas');
    const scrollHost = document.getElementById('waterfall-scroll');
    const tooltip = document.getElementById('waterfall-tooltip');
    if (!canvas || !scrollHost || !Array.isArray(flow) || flow.length === 0) return;

    const dpr = window.devicePixelRatio || 1;
    const state = { bars: [], selectedIndex: null, zoomMode: 'all' };
    const zoomButtons = Array.from(document.querySelectorAll('.wf-btn'));

    const palette = {
        database:    '#f0a645',
        http_client: '#26d0ce',
        routing:     '#8b73ff',
        kernel:      '#3d8bff',
    };

    function laneOf(call) {
        if (call.indexOf('Database') !== -1 || call.indexOf('DB ') !== -1) return 'database';
        if (call.indexOf('HttpClient') !== -1 || call.indexOf('HTTP ') !== -1) return 'http_client';
        if (call.indexOf('Routing') !== -1) return 'routing';
        return 'kernel';
    }

    function normalize(items) {
        let cursor = 0;
        return items.map((item, index) => {
            const duration = Number(item.durationMs || 0);
            const start = cursor;
            cursor += duration;
            const lane = laneOf(String(item.call ?? ''));
            return {
                index,
                step: String(item.step ?? index),
                call: String(item.call ?? '(unknown)'),
                location: String(item.location ?? '(unknown location)').replace(/<br\s*\/?>/gi, ' '),
                durationMs: duration,
                startMs: start,
                lane,
                memoryUsageBytes: Number(item.memoryUsageBytes || 0),
                memoryPeakBytes: Number(item.memoryPeakBytes || 0),
                memoryDeltaBytes: Number(item.memoryDeltaBytes || 0),
            };
        });
    }

    function formatBytes(bytes) {
        const value = Number(bytes || 0);
        if (!Number.isFinite(value) || value <= 0) {
            return '0 B';
        }
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let size = value;
        let idx = 0;
        while (size >= 1024 && idx < units.length - 1) {
            size /= 1024;
            idx++;
        }
        return size.toFixed(idx === 0 ? 0 : 2) + ' ' + units[idx];
    }

    const items = normalize(flow);

    function fitCanvas() {
        const viewportWidth = scrollHost.clientWidth || 1000;
        const rowH = 34, axisH = 30, padBottom = 12;
        const totalMs = Math.max(1, items.reduce((s, it) => s + it.durationMs, 0));
        const zoomTotal = state.zoomMode === 'all' ? totalMs : Math.min(totalMs, Number(state.zoomMode || totalMs));
        const wFactor = Math.max(1, totalMs / Math.max(1, zoomTotal));
        const cssW = Math.max(viewportWidth, Math.floor(viewportWidth * wFactor));
        const cssH = Math.max(200, axisH + items.length * rowH + padBottom);
        canvas.style.width = cssW + 'px';
        canvas.style.height = cssH + 'px';
        canvas.width = Math.floor(cssW * dpr);
        canvas.height = Math.floor(cssH * dpr);
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        draw(ctx, cssW, cssH, totalMs, zoomTotal);
    }

    function draw(ctx, width, height, totalMs, zoomTotal) {
        const axisH = 30, labelW = 580, rowH = 34, barH = 14;
        const chartX = labelW, chartW = Math.max(80, width - chartX - 20);
        const ticks = 10;

        state.bars = [];
        ctx.clearRect(0, 0, width, height);

        ctx.fillStyle = '#080c12';
        ctx.fillRect(0, 0, width, height);

        // Grid lines
        for (let i = 0; i <= ticks; i++) {
            const x = chartX + (chartW * i / ticks);
            ctx.strokeStyle = i === 0 ? '#1e2d45' : '#111827';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(x + 0.5, 0);
            ctx.lineTo(x + 0.5, height);
            ctx.stroke();
        }

        // Axis ruler
        ctx.fillStyle = '#0d1420';
        ctx.fillRect(0, 0, width, axisH);
        ctx.strokeStyle = '#1e2d45';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(0, axisH - 0.5);
        ctx.lineTo(width, axisH - 0.5);
        ctx.stroke();

        ctx.font = '10px DM Mono, Consolas, monospace';
        ctx.textAlign = 'left';
        ctx.fillStyle = '#4e6a8a';
        ctx.fillText('ms', 14, axisH - 9);

        for (let i = 0; i <= ticks; i++) {
            const x = chartX + (chartW * i / ticks);
            const ms = (zoomTotal * i / ticks).toFixed(1);
            ctx.fillStyle = '#4e6a8a';
            ctx.textAlign = 'center';
            ctx.font = '10px DM Mono, Consolas, monospace';
            ctx.fillText(ms, x, axisH - 9);
        }

        // Rows
        items.forEach((item, i) => {
            if (item.startMs > zoomTotal) return;

            const y = axisH + i * rowH;
            if (i % 2 === 0) {
                ctx.fillStyle = 'rgba(13,20,32,0.8)';
                ctx.fillRect(0, y, width, rowH);
            }

            // Label area
            ctx.font = '12px DM Mono, Consolas, monospace';
            ctx.textAlign = 'left';
            ctx.fillStyle = '#c8d8f0';
            const label = '#' + item.step + ' ' + item.call;
            const labelMax = 220;
            ctx.fillText(label.length > labelMax ? label.slice(0, labelMax - 1) + '…' : label, 14, y + 14);

            ctx.font = '10px DM Mono, Consolas, monospace';
            ctx.fillStyle = '#3a5070';
            const locMax = 220;
            const loc = item.location.length > locMax ? item.location.slice(0, locMax - 1) + '…' : item.location;
            ctx.fillText(loc, 14, y + 28);

            // Bar
            const startX = chartX + (item.startMs / zoomTotal) * chartW;
            const barW = Math.max(3, (item.durationMs / zoomTotal) * chartW);
            const barY = y + (rowH - barH) / 2;

            const color = palette[item.lane] || '#3d8bff';
            const grad = ctx.createLinearGradient(startX, barY, startX + barW, barY);
            grad.addColorStop(0, color);
            grad.addColorStop(1, color + '88');
            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.roundRect(startX, barY, barW, barH, 3);
            ctx.fill();

            if (state.selectedIndex === item.index) {
                ctx.strokeStyle = '#f0a645';
                ctx.lineWidth = 1.5;
                ctx.beginPath();
                ctx.roundRect(startX - 1, barY - 1, barW + 2, barH + 2, 4);
                ctx.stroke();
                ctx.lineWidth = 1;
            }

            state.bars.push({ x: startX, y: barY, w: barW, h: barH, item, index: item.index });
        });
    }

    function hitTest(e) {
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        return state.bars.find(b => x >= b.x && x <= b.x + b.w && y >= b.y && y <= b.y + b.h) || null;
    }

    canvas.addEventListener('mousemove', function (e) {
        const hit = hitTest(e);
        if (!hit) { tooltip.style.display = 'none'; canvas.style.cursor = 'default'; return; }
        canvas.style.cursor = 'pointer';
        const it = hit.item;
        tooltip.textContent =
            '[' + it.lane + '] #' + it.step + ' ' + it.call + '\n' +
            'Start:    ' + it.startMs.toFixed(3) + ' ms\n' +
            'Duration: ' + it.durationMs.toFixed(3) + ' ms\n' +
            'Memory:   ' + formatBytes(it.memoryUsageBytes) + '\n' +
            'Delta:    ' + (it.memoryDeltaBytes >= 0 ? '+' : '') + formatBytes(it.memoryDeltaBytes) + '\n' +
            'Peak:     ' + formatBytes(it.memoryPeakBytes) + '\n' +
            'Location: ' + it.location;
        tooltip.style.display = 'block';
        tooltip.style.left = (e.clientX + 16) + 'px';
        tooltip.style.top = (e.clientY + 16) + 'px';
    });

    canvas.addEventListener('mouseleave', function () {
        tooltip.style.display = 'none';
        canvas.style.cursor = 'default';
    });

    canvas.addEventListener('click', function (e) {
        const hit = hitTest(e);
        state.selectedIndex = hit ? hit.index : null;
        fitCanvas();
    });

    zoomButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            state.zoomMode = btn.dataset.zoom || 'all';
            zoomButtons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            fitCanvas();
        });
    });

    window.addEventListener('resize', fitCanvas);
    fitCanvas();
})();
</script>
<?php endif; ?>
</body>
</html>
