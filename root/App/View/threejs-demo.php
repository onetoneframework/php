<?php

declare(strict_types=1);

use Clover\Enumeration\HTTPStatusCode;

$publishedPreviewPath = dirname(__DIR__)
	. DIRECTORY_SEPARATOR
	. 'Frontend'
	. DIRECTORY_SEPARATOR
	. 'typescript-platform'
	. DIRECTORY_SEPARATOR
	. 'index.html';
$previewFailureTitle = 'Three.js preview is unavailable';
$previewFailureMessage = 'Publish the browser bundle before opening this page.';
$previewFailureStatusCode = HTTPStatusCode::SERVICE_UNAVAILABLE;

if (is_file($publishedPreviewPath) && is_readable($publishedPreviewPath)) {
	$readResult = readfile($publishedPreviewPath);

	if ($readResult !== false) {
		return;
	}

	$previewFailureTitle = 'Three.js preview could not be read';
	$previewFailureMessage = 'The published preview exists, but the server could not read it. Check the server log and file permissions.';
	$previewFailureStatusCode = HTTPStatusCode::INTERNAL_SERVER_ERROR;
}

http_response_code($previewFailureStatusCode);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Three.js Preview Unavailable | Onetone</title>
	<style>
		:root {
			color-scheme: dark;
			font-family: "Segoe UI", system-ui, sans-serif;
		}

		body {
			display: grid;
			min-height: 100vh;
			margin: 0;
			place-items: center;
			background: #020712;
			color: #eef6ff;
		}

		main {
			width: min(620px, calc(100vw - 64px));
			padding: 28px;
			border: 1px solid rgba(255, 202, 92, 0.45);
			border-radius: 16px;
			background: rgba(10, 24, 43, 0.92);
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
		}

		h1 {
			margin-top: 0;
			font-size: 24px;
		}

		code {
			display: block;
			margin-top: 18px;
			padding: 14px;
			border-radius: 10px;
			background: #07101e;
			color: #9ed2ff;
		}
	</style>
</head>
<body>
	<main>
		<h1><?= htmlspecialchars($previewFailureTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
		<p><?= htmlspecialchars($previewFailureMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
		<code>cd res/Platform/Typescript<br>npm run publish:php-demo</code>
	</main>
</body>
</html>
