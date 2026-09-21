<?php

declare(strict_types=1);

/**
 * Code style is owned by this file alone (see AGENTS.md § "Code style").
 *
 * Indentation: TABS. AGENTS.md and root/App/View/markdown/php.md both mandate tabs, and the
 * legacy `.php_cs` (PHP-CS-Fixer v1 config, deleted) was the only place that actually
 * configured them. @PSR12 alone would silently impose 4 spaces, so ->setIndent("\t") below
 * carries that decision forward. The working tree is LF (.gitattributes: `* text=auto eol=lf`),
 * which ->setLineEnding("\n") matches.
 */

$finder = (new PhpCsFixer\Finder())
	->in(__DIR__ . '/src')
	->in(__DIR__ . '/tests')
	// 1,343 generated files / 54 MB of literal `return [...]` data. Without this exclusion the
	// first `cs:fix` run rewrites every one of them (none has declare(strict_types=1) today).
	->exclude('Defaults');

return (new PhpCsFixer\Config())
	->setRules([
		'@PSR12' => true,
		'array_syntax' => ['syntax' => 'short'],
		// risky: appends an explicit strict `true` to in_array/array_search/array_keys
		'strict_param' => true,
		// risky: inserts declare(strict_types=1) into every file that lacks it
		'declare_strict_types' => true,
		'no_unused_imports' => true,
		'phpdoc_align' => ['align' => 'vertical'],
	])
	->setFinder($finder)
	->setIndent("\t")
	->setLineEnding("\n")
	->setRiskyAllowed(true);
