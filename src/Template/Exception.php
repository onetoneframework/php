<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Framework\Component\Translator;
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title><?= Translator::trans('template_messages.web.error_500_title', [], '500 Error') ?></title>
    <link media="all" rel="stylesheet" href="/App/Resource/fonts.css" />
</head>

<body>
    <div id="debugger">
        <div id="header">
            <div id="exception-badge"><?= htmlspecialchars((string) $className, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
            <div class="meta-label"><?= Translator::trans('template_messages.web.message', [], 'Message') ?></div>
            <div id="message"><?= htmlspecialchars((string) $message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
            <div id="file-info">
                <span class="file-label"><?= Translator::trans('template_messages.web.file', [], 'File') ?></span>
                <span class="file-path"><?= htmlspecialchars((string) $file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                <span class="file-sep">:</span>
                <span class="file-line"><?= htmlspecialchars((string) $line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                <span class="file-size">
                    (<?= Translator::trans('template_messages.web.size', [], 'Size') ?>: <?= htmlspecialchars((string) $fileSize, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)
                </span>
                <span class="file-size">Code: <?= htmlspecialchars((string) $code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            </div>
            <?php if (!empty($vscodeOpenUri)): ?>
                <div id="vscode-open">
                    <a class="vscode-link" href="<?= htmlspecialchars($vscodeOpenUri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= Translator::trans('template_messages.web.open_in_vscode', [], 'Open in VS Code') ?></a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($exceptionCode)): ?>
            <div id="source-section">
                <div class="section-title" onclick="toggleSection('source-code')">
                    <span class="section-icon" id="source-code-icon">&#9660;</span>
                    <?= Translator::trans('template_messages.web.source', [], 'Source') ?>
                </div>
                <div class="code-container" id="source-code"><?= $exceptionCode ?></div>
            </div>
        <?php endif; ?>

        <?php if (count($exceptionChain) > 1): ?>
            <div id="exception-chain-section">
                <div class="section-title" onclick="toggleSection('exception-chain')">
                    <span class="section-icon" id="exception-chain-icon">&#9654;</span>
                    Exception chain (<?= count($exceptionChain) ?>)
                </div>
                <div id="exception-chain" class="exception-chain" style="display:none;">
                    <?php foreach ($exceptionChain as $exceptionIndex => $exceptionDetails): ?>
                        <div class="exception-chain-item">
                            <div class="exception-chain-heading">
                                #<?= $exceptionIndex ?>
                                <?= htmlspecialchars((string) $exceptionDetails['class'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </div>
                            <div class="exception-chain-message"><?= htmlspecialchars((string) $exceptionDetails['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                            <div class="exception-chain-location">
                                <?= htmlspecialchars((string) $exceptionDetails['file'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:<?= htmlspecialchars((string) $exceptionDetails['line'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                &middot; Code <?= htmlspecialchars((string) $exceptionDetails['code'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </div>
                            <pre class="diagnostic-content"><?= htmlspecialchars((string) $exceptionDetails['trace'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($troubleshootingHints) && isset($troubleshootingHints['items'])): ?>
            <div id="help-section">
                <div class="section-title trace-title">
                    <?= Translator::trans($troubleshootingHints['titleKey'], [], $troubleshootingHints['titleDefault']) ?>
                </div>
                <div class="help-card">
                    <ul class="help-list">
                        <?php foreach ($troubleshootingHints['items'] as $hint): ?>
                            <li><?= Translator::trans($hint['key'], $hint['replacements'] ?? [], $hint['default']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div id="trace">
            <div class="section-title trace-title"><?= Translator::trans('template_messages.web.stack_trace', [], 'Stack Trace') ?></div>
            <? /** @var Clover\Classes\Debug\TraceObject $traces */ ?>
            <?php foreach ($traces as $traceIndex => $trace): ?>
                <div class="item" id="trace-<?= $traceIndex ?>">
                    <div class="item-header" onclick="toggleTrace(<?= $traceIndex ?>)">
                        <div class="item-index">#<?= $traceIndex ?></div>
                        <div class="item-summary">
                            <?php if ($trace->hasText()): ?>
                                <span class="item-function"><?= htmlspecialchars((string) $trace->getText(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <span class="item-location">
                                <?php if ($trace->hasFile()): ?>
                                    <?= htmlspecialchars((string) $trace->getFile(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                <?php else: ?>
                                    <?= Translator::trans('template_messages.web.class', [], 'Class') ?>: <?= htmlspecialchars((string) $trace->getClass(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                <?php endif; ?>
                                <?php if ($trace->hasLine()): ?>
                                    :<span class="item-line"><?= htmlspecialchars((string) $trace->getLine(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="item-toggle" id="toggle-icon-<?= $traceIndex ?>">&#9654;</div>
                    </div>

                    <div class="item-body" id="trace-body-<?= $traceIndex ?>" style="display:none;">
                        <?php if ($trace->hasAnnotation()): ?>
                            <div class="annotation"><?= htmlspecialchars((string) $trace->getAnnotation(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <?php if ($trace->hasComment()): ?>
                            <div class="comment"><?= htmlspecialchars((string) $trace->getCommentTag(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <?php if ($trace->hasClass()): ?>
                            <div class="class-bar"><?= htmlspecialchars((string) $trace->getClass(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <?php if ($trace->hasCode()): ?>
                            <div class="code-container"><?= $trace->getCode() ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="diagnostics">
            <div class="section-title trace-title">Diagnostic context</div>
            <div class="diagnostic-grid">
                <?php foreach ($debugSections as $sectionIndex => $debugSection): ?>
                    <div class="diagnostic-panel">
                        <div class="diagnostic-header" onclick="toggleSection('diagnostic-<?= $sectionIndex ?>')">
                            <span class="section-icon" id="diagnostic-<?= $sectionIndex ?>-icon">&#9654;</span>
                            <span><?= htmlspecialchars((string) $debugSection['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            <span class="diagnostic-count"><?= htmlspecialchars((string) $debugSection['count'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        </div>
                        <pre class="diagnostic-content" id="diagnostic-<?= $sectionIndex ?>" style="display:none;"><?= htmlspecialchars((string) $debugSection['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function scrollToHighlight(container) {
            var el = container.querySelector('[data-highlight="true"]');
            if (!el) {
                return;
            }

            container.scrollTop = el.offsetTop - container.offsetTop - (container.clientHeight / 2) + (el.clientHeight / 2);
        }

        function toggleSection(id) {
            var el = document.getElementById(id);
            var icon = document.getElementById(id + '-icon');
            if (el.style.display === 'none') {
                el.style.display = 'block';
                icon.innerHTML = '&#9660;';
                scrollToHighlight(el);
            } else {
                el.style.display = 'none';
                icon.innerHTML = '&#9654;';
            }
        }

        function toggleTrace(index) {
            var body = document.getElementById('trace-body-' + index);
            var icon = document.getElementById('toggle-icon-' + index);
            if (body.style.display === 'none') {
                body.style.display = 'block';
                icon.innerHTML = '&#9660;';
                var codeContainer = body.querySelector('.code-container');
                if (codeContainer) {
                    scrollToHighlight(codeContainer);
                }
            } else {
                body.style.display = 'none';
                icon.innerHTML = '&#9654;';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var sourceCode = document.getElementById('source-code');
            if (sourceCode) {
                scrollToHighlight(sourceCode);
            }
        });
    </script>
</body>

<style>
    :root {
        --bg: #f8f7f4;
        --border: #e8e4da;
        --border-strong: #d6cebd;
        --text-primary: #17161c;
        --text-message: #17161c;
        --text-muted: #9a958a;
        --text-file-label: #a89f92;
        --text-file-path: #302d27;
        --text-file-info: #5b574f;
        --text-file-size: #aca397;

        --accent: #8c2e2e;
        --accent-strong: #6e2020;
        --accent-soft: #a44b4b;
        --accent-badge-text: #fdf7f5;
        --accent-badge-shadow: rgba(140, 46, 46, 0.38);
        --accent-line-glow: rgba(140, 46, 46, 0.1);

        --link: #3e5872;
        --link-hover: #2a3f52;

        --code-bg: #f4f2ed;
        --code-text: #4a463e;
        --code-line-number: #bcb4a5;
        --code-highlight-text: #1d1c22;
        --code-scrollbar-thumb: #d6cebd;

        --help-bg: #f6f8f9;
        --help-border: #dde5ea;
        --help-text: #3e5872;
        --help-marker: #7f9bb0;

        --item-bg: #ffffff;
        --item-hover-shadow: rgba(32, 31, 38, 0.09);
        --item-index-bg: #f4f2ed;
        --item-index-text: #93897a;
        --item-function-text: #24222a;
        --item-location-text: #9c9384;
        --item-line-color: #a3661f;
        --item-toggle: #cac2b2;
        --item-toggle-hover: #8c2e2e;

        --class-bar-bg: #f4f2ed;
        --class-bar-text: #6b665b;

        --annotation-bg: #eef2f4;
        --annotation-text: #3e5872;

        --comment-bg: #f8ece9;
        --comment-text: #7a3d3a;
    }

    @media (prefers-color-scheme: dark) {
        :root {
            --bg: #0a0a0e;
            --border: rgba(255, 255, 255, 0.07);
            --border-strong: rgba(255, 255, 255, 0.13);
            --text-primary: #f5f5fa;
            --text-message: #f5f5fa;
            --text-muted: #83839c;
            --text-file-label: #6e6e88;
            --text-file-path: #b0b0c4;
            --text-file-info: #90909e;
            --text-file-size: #52526a;

            --accent: #e64d4d;
            --accent-strong: #b0272f;
            --accent-soft: #ec6060;
            --accent-badge-text: #ffffff;
            --accent-badge-shadow: rgba(224, 51, 51, 0.28);
            --accent-line-glow: rgba(230, 51, 51, 0.11);

            --link: #82aeff;
            --link-hover: #a7c5ff;

            --code-bg: #121218;
            --code-text: #a8a8ba;
            --code-line-number: #45455a;
            --code-highlight-text: #f5f5fa;
            --code-scrollbar-thumb: #34344a;

            --help-bg: #171b28;
            --help-border: rgba(115, 145, 220, 0.18);
            --help-text: #c2cde6;
            --help-marker: #6f8fd6;

            --item-bg: #15151d;
            --item-hover-shadow: rgba(0, 0, 0, 0.2);
            --item-index-bg: #1a1a26;
            --item-index-text: #4c4c62;
            --item-function-text: #d6d6e4;
            --item-location-text: #565672;
            --item-line-color: #e6a545;
            --item-toggle: #4c4c62;
            --item-toggle-hover: #8080a0;

            --class-bar-bg: #191924;
            --class-bar-text: #8b8bab;

            --annotation-bg: #141827;
            --annotation-text: #7c9bd4;

            --comment-bg: #1a1420;
            --comment-text: #c8a8ab;
        }
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    body {
        background-color: var(--bg);
        color: var(--text-primary);
        min-height: 100vh;
        letter-spacing: 0.1px;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    #debugger {
        max-width: 1180px;
        margin: 0 auto;
        padding: 0 24px 72px 24px;
    }

    #header {
        padding: 52px 0 38px 0;
        border-bottom: 1px solid var(--border);
        position: relative;
    }

    #header::after {
        content: "";
        position: absolute;
        left: 0;
        bottom: -1px;
        width: 130px;
        height: 2px;
        background: linear-gradient(90deg, var(--accent), rgba(140, 46, 46, 0));
    }

    #exception-badge {
        display: inline-block;
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%);
        color: var(--accent-badge-text);
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 1.1px;
        text-transform: uppercase;
        padding: 6px 13px;
        border-radius: 5px;
        margin-bottom: 22px;
        box-shadow: 0 10px 24px -6px var(--accent-badge-shadow), inset 0 1px 0 rgba(255, 255, 255, 0.14);
    }

    #message {
        font-size: 27px;
        font-weight: 600;
        color: var(--text-message);
        line-height: 1.42;
        margin-bottom: 18px;
        word-break: break-word;
        letter-spacing: -0.3px;
    }

    .meta-label {
        font-size: 10.5px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1.4px;
        margin-bottom: 9px;
    }

    #file-info {
        font-size: 12.5px;
        color: var(--text-file-info);
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 2px;
    }

    #file-info .file-path {
        color: var(--text-file-path);
    }

    #file-info .file-label {
        color: var(--text-file-label);
        margin-right: 9px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 10.5px;
        font-weight: 600;
    }

    #file-info .file-sep {
        color: var(--border-strong);
    }

    #file-info .file-line {
        color: var(--accent);
        font-weight: 700;
    }

    #file-info .file-size {
        color: var(--text-file-size);
        margin-left: 14px;
    }

    #vscode-open {
        margin-top: 16px;
    }

    #vscode-open .vscode-link {
        font-size: 12px;
        font-weight: 600;
        color: var(--link);
        text-decoration: none;
        letter-spacing: 0.2px;
        padding-bottom: 1px;
        border-bottom: 1px solid var(--link);
        opacity: 1;
        transition: border-color 0.15s ease, color 0.15s ease;
    }

    #vscode-open .vscode-link:hover {
        color: var(--link-hover);
    }

    .section-title {
        font-size: 11.5px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1.4px;
        padding: 30px 0 14px 0;
        cursor: pointer;
        user-select: none;
        display: flex;
        align-items: center;
    }

    .section-title.trace-title {
        cursor: default;
    }

    .section-icon {
        font-size: 9px;
        margin-right: 7px;
        display: inline-block;
        color: var(--border-strong);
    }

    .code-container {
        background-color: var(--code-bg);
        border: 1px solid var(--border);
        border-radius: 9px;
        overflow-y: auto;
        max-height: 520px;
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        font-size: 12px;
        line-height: 1.75;
        scrollbar-width: thin;
        scrollbar-color: var(--code-scrollbar-thumb) var(--code-bg);
    }

    .code-container::-webkit-scrollbar {
        width: 6px;
    }

    .code-container::-webkit-scrollbar-track {
        background: var(--code-bg);
    }

    .code-container::-webkit-scrollbar-thumb {
        background: var(--code-scrollbar-thumb);
        border-radius: 3px;
    }

    .code-container>div {
        display: flex;
        padding: 0 16px 0 0;
        white-space: pre;
    }

    .code-container>div:first-child {
        padding-top: 13px;
    }

    .code-container>div:last-child {
        padding-bottom: 13px;
    }

    .code-line-number {
        display: inline-block;
        min-width: 56px;
        padding-right: 16px;
        text-align: right;
        color: var(--code-line-number);
        user-select: none;
        flex-shrink: 0;
    }

    .code-line-content {
        flex: 1;
        color: var(--code-text);
    }

    .code-line-highlight {
        background: linear-gradient(90deg, var(--accent-line-glow), rgba(140, 46, 46, 0) 68%);
        border-left: 3px solid var(--accent);
    }

    .code-line-highlight .code-line-number {
        color: var(--accent-soft);
        font-weight: 700;
    }

    .code-line-highlight .code-line-content {
        color: var(--code-highlight-text);
        font-weight: 600;
    }

    #trace {
        margin-top: 8px;
    }

    #help-section {
        margin-top: 6px;
    }

    .help-card {
        background-color: var(--help-bg);
        border: 1px solid var(--help-border);
        border-radius: 9px;
        padding: 15px 19px;
    }

    .help-list {
        margin: 0;
        padding-left: 18px;
    }

    .help-list li {
        color: var(--help-text);
        line-height: 1.65;
        font-size: 13px;
        margin: 5px 0;
    }

    .help-list li::marker {
        color: var(--help-marker);
    }

    .item {
        background-color: var(--item-bg);
        border: 1px solid var(--border);
        border-radius: 9px;
        margin-bottom: 7px;
        overflow: hidden;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .item:hover {
        border-color: var(--border-strong);
        box-shadow: 0 6px 20px -4px var(--item-hover-shadow);
        transform: translateY(-1px);
    }

    .item-header {
        display: flex;
        align-items: center;
        padding: 14px 16px;
        cursor: pointer;
        user-select: none;
        gap: 14px;
    }

    .item-index {
        font-size: 10.5px;
        font-weight: 700;
        color: var(--item-index-text);
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        flex-shrink: 0;
        min-width: 26px;
        text-align: center;
        background-color: var(--item-index-bg);
        border-radius: 5px;
        padding: 4px 0;
    }

    .item-summary {
        flex: 1;
        min-width: 0;
        overflow: hidden;
    }

    .item-function {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--item-function-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 4px;
        letter-spacing: -0.1px;
    }

    .item-location {
        display: block;
        font-size: 11px;
        color: var(--item-location-text);
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .item-line {
        color: var(--item-line-color);
    }

    .item-toggle {
        font-size: 9px;
        color: var(--item-toggle);
        flex-shrink: 0;
        transition: color 0.15s ease;
    }

    .item:hover .item-toggle {
        color: var(--item-toggle-hover);
    }

    .item-body {
        border-top: 1px solid var(--border);
    }

    .item-body .code-container {
        border-radius: 0;
        border: none;
        border-top: none;
        max-height: 400px;
    }

    .class-bar {
        background-color: var(--class-bar-bg);
        color: var(--class-bar-text);
        font-size: 12px;
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        padding: 10px 16px;
        border-bottom: 1px solid var(--border);
        letter-spacing: 0.1px;
    }

    .annotation {
        background-color: var(--annotation-bg);
        color: var(--annotation-text);
        font-size: 11px;
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        line-height: 1.6;
        padding: 12px 16px;
        white-space: pre-wrap;
        border-bottom: 1px solid var(--border);
    }

    .comment {
        background-color: var(--comment-bg);
        color: var(--comment-text);
        font-size: 12px;
        line-height: 1.6;
        padding: 12px 16px;
        border-left: 3px solid var(--accent);
        border-bottom: 1px solid var(--border);
    }

    #diagnostics {
        margin-top: 8px;
    }

    .diagnostic-grid,
    .exception-chain {
        display: grid;
        gap: 8px;
    }

    .diagnostic-panel,
    .exception-chain-item {
        background-color: var(--item-bg);
        border: 1px solid var(--border);
        border-radius: 9px;
        overflow: hidden;
    }

    .diagnostic-header {
        display: flex;
        align-items: center;
        padding: 13px 16px;
        color: var(--item-function-text);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
    }

    .diagnostic-count {
        margin-left: auto;
        min-width: 24px;
        padding: 3px 7px;
        color: var(--item-index-text);
        background-color: var(--item-index-bg);
        border-radius: 999px;
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        font-size: 10px;
        text-align: center;
    }

    .diagnostic-content {
        max-height: 480px;
        padding: 15px 17px;
        overflow: auto;
        border-top: 1px solid var(--border);
        color: var(--code-text);
        background-color: var(--code-bg);
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        font-size: 11.5px;
        line-height: 1.65;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .exception-chain-item .diagnostic-content {
        margin-top: 12px;
    }

    .exception-chain-heading {
        padding: 15px 17px 5px;
        color: var(--item-function-text);
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        font-size: 12px;
        font-weight: 700;
    }

    .exception-chain-message {
        padding: 3px 17px;
        color: var(--text-primary);
        font-size: 13px;
        overflow-wrap: anywhere;
    }

    .exception-chain-location {
        padding: 4px 17px 12px;
        color: var(--item-location-text);
        font-family: "Geist Mono", ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        font-size: 10.5px;
        overflow-wrap: anywhere;
    }

    a.highlight {
        color: var(--accent);
        font-weight: bold;
    }
</style>
