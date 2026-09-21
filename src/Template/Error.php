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
    <title><?= Translator::trans('template_messages.web.error_500_title', [], '500 Error') ?></title>
</head>

<body>
    <div id="fatal">
        <div class="title">
            <?= Translator::trans('template_messages.web.error', [], 'Error') ?>
        </div>
        <div class="message">
            <?= $error_message; ?>
        </div>
        <div class="file">
            <?= $filename; ?>:<?= $linenumber; ?>
        </div>
        <?php if (!empty($vscodeOpenUri)): ?>
            <div id="vscode-open">
                <a class="vscode-link" href="<?= htmlspecialchars($vscodeOpenUri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= Translator::trans('template_messages.web.open_in_vscode', [], 'Open in VS Code') ?></a>
            </div>
        <?php endif; ?>
    </div>
</body>

<style>
    * {
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
    }

    body {
        background-color: #eee;
    }

    #fatal {
        background-color: #fff;
        width: 600px;
        margin: 0 auto;
        height: 200px;
        margin-top: auto;
        margin-bottom: auto;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0px 0px 8px 2px #dbdbdb;
        display: flex;
        flex-direction: column;
    }

    .title {
        font-size: 19px;
        color: #a61b4d;
        font-weight: bold;
        flex: 1;
    }

    .message {
        margin-top: 15px;
        font-size: 14px;
        flex: 1;
        font-weight: bold;
        color: red;
    }

    .file {
        margin-top: 15px;
        font-size: 12px;
    }

    #vscode-open {
        margin-top: 12px;
    }

    #vscode-open .vscode-link {
        font-size: 12px;
        font-weight: 600;
        color: #1a5fbd;
        text-decoration: none;
    }

    #vscode-open .vscode-link:hover {
        text-decoration: underline;
    }
</style>