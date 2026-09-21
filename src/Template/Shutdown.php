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
    <title><?= Translator::trans('template_messages.web.fatal_error', [], 'Fatal Error') ?></title>
</head>

<div id="debugger">
    <div class="title">
        <?= $error['message'] ?>
    </div>
    <div class="content">
        <?php if (!empty($vscodeOpenUri)): ?>
            <div id="vscode-open">
                <a class="vscode-link" href="<?= htmlspecialchars($vscodeOpenUri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= Translator::trans('template_messages.web.open_in_vscode', [], 'Open in VS Code') ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    * {
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
    }

    body {
        margin: 0px;
        background-color: #313131;
        justify-content: center;
        align-items: center;
        display: flex;
    }

    #debugger {
        overflow-wrap: break-word;
        background-color: #fff;
        width: 600px;
        margin: auto;
        margin-top: auto;
        margin-bottom: auto;
        padding: 15px;
        border-radius: 6px;
        display: flex;
        flex-direction: column;
    }

    .title {
        font-size: 16px;
        color: #a61b4d;
        border-bottom: 1px solid #929292;
        padding-bottom: 14px;
    }

    .content {
        margin-top: 10px;
    }

    #vscode-open:hover {
        background-color: #e6eff6;
    }

    #vscode-open a {
        color: #007acc;
        text-decoration: none;
    }
    #vscode-open a:hover {
        text-decoration: underline;
    }
    #vscode-open {
        margin-top: 10px;
        font-size: 14px;
        padding: 6px 10px;
        background-color: #bedfff;
        display: inline-block;
        border-radius: 3px;
        float: right;
    }

    .vscode-link {
        color: #007acc;
        text-decoration: none;
    }

    .vscode-link:hover {
        text-decoration: underline;
    }
</style>
