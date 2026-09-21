<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\HTML\Handler as HTMLHandler;

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
<?php if (isset($metaTags)) : ?>
    <?php foreach ($metaTags as $resource) : ?>
<?php echo HTMLHandler::generateElement('meta', '', $resource, false); ?>

    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($scriptMap)) : ?>
    <?php foreach ($scriptMap as $resource) : ?>
<?php echo HTMLHandler::generateElement('script', '', $resource); ?>

    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($cssMap)) : ?>
    <?php foreach ($cssMap as $resource) : ?>
<?php echo HTMLHandler::generateElement('link', '', $resource, true); ?>

    <?php endforeach; ?>
<?php endif; ?>

    <title><?= $title ?? ""; ?></title>
</head>

<body>
