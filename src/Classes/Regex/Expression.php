<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Regex;

use function sprintf;

/*
 * Class Expression
 *
 * Provides methods to generate various regex patterns for common use cases, such as validating variable names, matching URLs, and more.
 */
class Expression
{
	/**
	 * Get a regex pattern for validating PHP variable names
	 * 
	 * http://vs-shop.cloudsite.ir/manual/kr/language.variables.basics.php
	 *
	 * @return string The regex pattern for validating PHP variable names
	 */
	public function validPHPVariableName(): string
	{
		return "[a-zA-Z_\x7f-\xff]";
	}

	/**
	 * Get a regex pattern for a negated character set
	 *
	 * @param string $expression The expression to negate in the character set
	 * @return string The regex pattern for the negated character set
	 */
	public function negativeSet(string $expression): string
	{
		return "[^{$expression}]";
	}

	/** 
	 * Get a regex pattern for matching URLs
	 *
	 * @return string The regex pattern for matching URLs
	 */
	public function url(): string
	{
		return "({$this->positiveLookbehind()}(([\<a href])))?(?2)?({$this->atomic()})(?1)({$this->positiveLookahead()}[http|https]).{4,5}[A-Za-z]\:\/\/\b[0-9a-zA-Z\?\=.\/\_\-]{1,}\b";
	}

	/** 
	 * Get a regex pattern for matching a specific number of repetitions of an expression
	 *
	 * @param string $expression The expression to repeat
	 * @param int $repeat The number of repetitions
	 * @return string The regex pattern for matching the specified number of repetitions of the expression
	 */
	public function repetition(string $expression, int $repeat): string
	{
		return "\\b{$expression}\{{$repeat}\}\b";
	}

	/** 
	 * Get a regex pattern for a comment
	 *
	 * @param string $content The content of the comment
	 * @return string The regex pattern for the comment
	 */
	public function setComment(string $content): string
	{
		return "(?#{$content})";
	}

	/** 
	 * Recursion and Subroutine
	 * 
	 * @return string
	 */
	public function recursion(): string
	{
		return "(?R)";
	}

	/** 
	 * Relative subroutine call
	 * 
	 * @return string
	 */
	public function relativeSubroutineCall(): string
	{
		return "(?-1)";
	}

	// Subroutine

	/** 
	 * Get a regex pattern for a named capturing group with a specific expression
	 *
	 * @param string $name The name of the capturing group
	 * @param string $expression The expression to include in the capturing group
	 * @return string The regex pattern for the named capturing group with the specified expression
	 */
	public function namedCapturing(string $name, string $expression): string
	{
		return "(?P<{$name}{$expression}>";
	}

	/** 
	 * Get a regex pattern for a named subroutine call
	 *
	 * @param string $name The name of the subroutine to call
	 * @return string The regex pattern for the named subroutine call
	 */
	public function namedSubroutine(string $name): string
	{
		return "(?P>\${$name})";
	}

	/** 
	 * Get a regex pattern for a numeric subroutine call
	 *
	 * @param int $number The number of the subroutine to call
	 * @return string The regex pattern for the numeric subroutine call
	 */
	public function numericSubroutine($number): string
	{
		return "(?\${$number})"; // isEqual \g'${$number}'
	}

	/** 
	 * Get a regex pattern for a numeric preceding subroutine call
	 *
	 * @param int $number The number of the subroutine to call
	 * @return string The regex pattern for the numeric preceding subroutine call
	 */
	public function numericPrecedingSubroutine($number): string
	{
		return "(?-\${$number})";
	}

	/** 
	 * Get a regex pattern for a numeric next subroutine call
	 *
	 * @param int $number The number of the subroutine to call
	 * @return string The regex pattern for the numeric next subroutine call
	 */
	public function numericNextSubroutine($number): string
	{
		return "(?+\${$number})";
	}

	/** 
	 * Get a regex pattern for a backreference to a named capturing group
	 *
	 * @param string $name The name of the capturing group to reference
	 * @return string The regex pattern for the backreference to the named capturing group
	 */
	public function backreferenceNumericSubroutine($expression, $number): string
	{
		return "({$expression})\g<{$number}>";
	}

	// Condition

	/**
	 * Get a regex pattern for a conditional expression based on the presence of a specific expression
	 *
	 * @param string $expression The expression to check for in the condition
	 * @param string $then The regex pattern to use if the condition is true
	 * @param string $else The regex pattern to use if the condition is false
	 * @return string The regex pattern for the conditional expression
	 */
	public function condition(string $expression, string $then, string $else): string
	{
		return "(?({$this->positiveLookahead()}{$expression}){$then}|{$else})";
	}

	/**
	 * Get a regex pattern for a negative lookbehind assertion that checks for the absence of a specific word before the current position
	 *
	 * @param string $word The word to check for in the negative lookbehind assertion
	 * @return string The regex pattern for the negative lookbehind assertion
	 */
	public function negativeLookbehindAsset(string $word): string
	{
		return sprintf("(?<!\%s)$", $word);
	}

	/**
	 * Get a regex pattern for named condition grouping when the specified expression is valid
	 *
	 * @param string $name The name of the capturing group to use in the condition
	 * @param string $expression The expression to check for in the condition
	 * @param string $condition The condition to evaluate (e.g., positive lookahead, negative lookahead, etc.)
	 * @param string $then The regex pattern to use if the condition is true
	 * @param string $else The regex pattern to use if the condition is false
	 * @return string The regex pattern for the named condition grouping when the specified expression is valid
	 */
	public function namedConditionGroupingWhenValid(string $name, string $expression, string $condition, string $then, string $else): string
	{
		return "(?<{$name}>{$expression})?{$condition}(?({$name}){$then}|{$else})";
	}

	/**
	 * Get a regex pattern for condition grouping based on the presence of a specific expression
	 *
	 * @param string $expression The expression to check for in the condition
	 * @param string $condition The condition to evaluate (e.g., positive lookahead, negative lookahead, etc.)
	 * @param string $then The regex pattern to use if the condition is true
	 * @param string $else The regex pattern to use if the condition is false
	 * @return string The regex pattern for the condition grouping
	 */
	public function conditionGrouping(string $expression, string $condition, string $then, string $else): string
	{
		return "({$expression})?{$condition}(?(1){$then}|{$else})";
	}

	// Mode

	/**
	 * Ignore whitespace and allow comments
	 */
	public function turnOnFreeSpacingMode(): string
	{
		return "(?x)";
	}

	/**
	 * Turn on case-insensitive matching
	 *
	 * @return string The regex pattern for turning on case-insensitive matching
	 */
	public function caseInsensitive(): string
	{
		return "(?i)";
	}

	/**
	 * Turn on case-sensitive matching
	 *
	 * @return string The regex pattern for turning on case-sensitive matching
	 */
	public function caseSensitive(): string
	{
		return "(?c)";
	}

	// Only supported by Tcl

	/**
	 * Turn off free-spacing mode
	 *
	 * @return string The regex pattern for turning off free-spacing mode
	 */
	public function turnOffFreeSpacingMode(): string
	{
		return "(?t)";
	}

	/**
	 * Treats the dot as matching any characters, including newline
	 * 
	 * @return string
	 */
	public function dotAll(): string
	{
		return "(?s)";
	}

	// String

	/**
	 * Get a regex pattern for keeping the text matched so far out of the overall regex match
	 *
	 * @return string The regex pattern for keeping the text matched so far out of the overall regex match
	 */
	public function keepOut(): string
	{
		return "\K";
	}

	/**
	 * Get a regex pattern for matching any Unicode letter character
	 *
	 * @return string The regex pattern for matching any Unicode letter character
	 */
	public function unicodeCategory(): string
	{
		return "\p{L}";
	}

	/**
	 * Get a regex pattern for matching any word character (alphanumeric or underscore) one or more times
	 *
	 * @return string The regex pattern for matching any word character one or more times
	 */
	public function getAnyWordMoreThanOne(): string
	{
		return "\w+";
	}

	/**
	 * Get a regex pattern for matching any sequence of non-whitespace characters
	 *
	 * @return string The regex pattern for matching any sequence of non-whitespace characters
	 */
	public function getAnyWordMoreThanOneWithoutBlank(): string
	{
		return "\S+";
	}

	/**
	 * Get a regex pattern for matching alphanumeric characters
	 *
	 * @return string The regex pattern for matching alphanumeric characters
	 */
	public function alphanumericCharacters(): string
	{
		return "\w";
	}

	/**
	 * Get a regex pattern for matching non-alphanumeric characters
	 *
	 * @return string The regex pattern for matching non-alphanumeric characters
	 */
	public function nonAlphanumericCharacters(): string
	{
		return "\W";
	}

	/**
	 * Get a regex pattern for matching a word boundary
	 *
	 * @return string The regex pattern for matching a word boundary
	 */
	public function wordBoundary(): string
	{
		return "\b";
	}

	/**
	 * Get a regex pattern for matching a word boundary at the start of a string
	 *
	 * @return string The regex pattern for matching a word boundary at the start of a string
	 */
	public function digits(): string
	{
		return "\d";
	}

	/**
	 * Get a regex pattern for matching non-digit characters
	 *
	 * @return string The regex pattern for matching non-digit characters
	 */
	public function nonDigits(): string
	{
		return "\D";
	}

	/**
	 * Get a regex pattern for matching whitespace characters
	 *
	 * @return string The regex pattern for matching whitespace characters
	 */
	public function whiteSpaceCharacters(): string
	{
		return "\s";
	}

	/**
	 * Get a regex pattern for matching non-whitespace characters
	 *
	 * @return string The regex pattern for matching non-whitespace characters
	 */
	public function nonWhiteSpaceCharacters(): string
	{
		return "\S";
	}

	// Groupping

	/**
	 * Get a regex pattern for a balancing group
	 *
	 * @param string $captureSubtract The name of the capturing group to subtract from the balancing group
	 * @param string $expression The expression to include in the balancing group
	 * @return string The regex pattern for the balancing group
	 */
	public function balancingGroup(string $captureSubtract, string $expression): string
	{
		return "(?<{$captureSubtract}>{$expression})";
	}

	/**
	 * Get a regex pattern for a branch reset group
	 *
	 * @param string $subexpression The subexpression to include in the branch reset group
	 * @return string The regex pattern for the branch reset group
	 */
	public function branchResetGroup(string $subexpression): string
	{
		return "(?|{$subexpression})";
	}

	// Atomic
	public function atomic(): string
	{
		return "?>";
	}

	/**
	 * Get a regex pattern for an atomic group
	 *
	 * @param string $subexpression The subexpression to include in the atomic group
	 * @return string The regex pattern for the atomic group
	 */
	public function atomicGroup(string $subexpression): string
	{
		return "({$this->atomic()}{$subexpression})";
	}

	/**
	 * Get a regex pattern for a named capturing group
	 *
	 * @param string $name The name of the capturing group
	 * @param string $subexpression The subexpression to include in the capturing group
	 * @return string The regex pattern for the named capturing group
	 */
	public function namedCapturingGroup(string $name, string $subexpression): string
	{
		return "(?P<{$name}>{$subexpression})";
	}

	/**
	 * Get a regex pattern for a non-capturing group
	 *
	 * @param string $subexpression The subexpression to include in the non-capturing group
	 * @return string The regex pattern for the non-capturing group
	 */
	public function noneCapturingGroup(string $subexpression): string
	{
		return "(?:{$subexpression})";
	}

	// Expression

	/**
	 * Get a regex pattern for matching an HTML node that does not have a specific attribute, without specifying the position of the attribute
	 *
	 * @param string $node The name of the HTML node to match
	 * @param string $attribute The name of the attribute that should not be present in the node
	 * @return string The regex pattern for matching the specified HTML node without the specified attribute, regardless of the position of the attribute
	 */
	public function nodeWithoutAfterSpecifyAttributes(string $node, string $attribute): string
	{
		return "(<{$node} .*?)" . $this->noneCapturingGroup("{$attribute}=\".*\"") . "?(.*?\/>)";
	}

	/**
	 * Get a regex pattern for matching an HTML node that does not have a specific attribute
	 *
	 * @param string $node The name of the HTML node to match
	 * @param string $attribute The name of the attribute that should not be present in the node
	 * @return string The regex pattern for matching the specified HTML node without the specified attribute
	 */
	public function nodeWithoutSpecifyAttributes(string $node, string $attribute): string
	{
		return "(<{$node} .*?)" . $this->noneCapturingGroup("{$attribute}=\".*\"") . "(.*?\/>)";
	}

	/**
	 * Regex expression of positive lookbehind
	 * 
	 * @return string
	 */
	public function positiveLookbehind(): string
	{
		return "?<=";
	}

	/**
	 * Get a regex pattern for performing a regex match operation
	 *
	 * @param string $expression The expression to use in the regex match operation
	 * @return string The regex pattern for performing the regex match operation
	 */
	public function regularExpression(string $expression): string
	{
		return "REGEXP {$expression}";
	}

	/**
	 * Get a regex pattern for performing a regex instruction operation
	 *
	 * @param string $expression The expression to use in the regex instruction operation
	 * @return string The regex pattern for performing the regex instruction operation
	 */
	public function regularExpressionInstring(string $expression): string
	{
		return "REGEXP_INSTR ({$expression})";
	}

	/**
	 * Get a regex pattern for performing a regex match operation
	 *
	 * @param string $expression The expression to use in the regex match operation
	 * @return string The regex pattern for performing the regex match operation
	 */
	public function regularExpressionLike(string $expression): string
	{
		return "REGEXP_LIKE ({$expression})";
	}

	/**
	 * Get a regex pattern for performing a regex substring operation
	 *
	 * @param string $expression The expression to use in the regex substring operation
	 * @return string The regex pattern for performing the regex substring operation
	 */
	public function regularExpressionSubstring(string $expression): string
	{
		return "REGEXP_SUBSTR ({$expression})";
	}

	/**
	 * Get a regex pattern for performing a regex replacement
	 *
	 * @param string $expression The expression to use in the regex replacement
	 * @return string The regex pattern for performing the regex replacement
	 */
	public function regularExpressionReplace(string $expression): string
	{
		return "REGEXP_REPLACE ({$expression})";
	}

	/**
	 * Regex expression of negative lookbehind
	 * 
	 * @return string
	 */
	public function negativeLookbehind(): string
	{
		return "?<!";
	}

	/**
	 * Regex expression of positive lookahead
	 * 
	 * @return string
	 */
	public function positiveLookahead(): string
	{
		return "?=";
	}

	/**
	 * Regex expression of negative lookahead
	 * 
	 * @return string
	 */
	public function negativeLookahead(): string
	{
		return "?!";
	}

	// Block

	/**
	 * Get a regex pattern for matching the content of a block tag
	 *
	 * @param string $name The name of the block tag to match
	 * @return string The regex pattern for matching the content of the specified block tag
	 */
	public function blockTag(string $name): string
	{
		return "<{$name}>.*?<\/{$name}>";
	}

	// Et greta

	/**
	 * Get a regex pattern for matching a specific number of repetitions of an expression
	 *
	 * @param string $expression The expression to repeat
	 * @param int $minimum The minimum number of repetitions
	 * @param int $maximum The maximum number of repetitions
	 * @return string The regex pattern for matching the specified number of repetitions of the expression
	 */

	public function braces(int $minimum, int $maximum): string
	{
		return "\{$minimum,$maximum\}";
	}

	/**
	 * Get a regex pattern for a negated character set
	 *
	 * @return string The regex pattern for a negated character set
	 */
	public function negatedCharacterSet(): string
	{
		return "[^ ]";
	}

	/**
	 * Get a regex pattern for matching zero or more occurrences of an expression
	 *
	 * @param string $expression The expression to match zero or more times
	 * @return string The regex pattern for matching zero or more occurrences of the expression
	 */
	public function zeroOrMoreQuantifier(string $expression): string
	{
		return "{$expression}*";
	}

	/**
	 * Get a regex pattern for matching an optional expression (zero or one occurrence)
	 *
	 * @param string $expression The expression to make optional
	 * @return string The regex pattern for matching the optional expression
	 */
	public function zeroOrOneQuantifier(string $expression): string
	{
		return "{$expression}?";
	}

	// One or more quantifier
	public function oneOrMoreQuantifier(): string
	{
		return "+";
	}

	// Carriage return character
	public function carriageReturn(): string
	{
		return "\r";
	}

	// Horizontal whitespace character
	public function horizontalWhitespace(): string
	{
		return "\h";
	}

	// Non-horizontal whitespace character
	public function nonHexadecimalDigit(): string
	{
		return "\H";
	}

	// Null character
	public function nullCharacter(): string
	{
		return "\h";
	}

	/**
	 * Get a regex pattern for validating email addresses according to RFC 2822
	 *
	 * @return string The regex pattern for validating email addresses according to RFC 2822
	 */
	public function rfc2822Mail(): string
	{
		return "^((?>[a-zA-Z\d!#$%&'*+\-/=?^_`{|}~]+\x20*|\"((?=[\x01-\x7f])[^\"\\]|\\[\x01-\x7f])*\"\x20*)*(?<angle><))?((?!\.)(?>\.?[a-zA-Z\d!#$%&'*+\-/=?^_`{|}~]+)+|\"((?=[\x01-\x7f])[^\"\\]|\\[\x01-\x7f])*\")@(((?!-)[a-zA-Z\d\-]+(?<!-)\.)+[a-zA-Z]{2,}|\[(((?(?<!\[)\.)(25[0-5]|2[0-4]\d|[01]?\d?\d)){4}|[a-zA-Z\d\-]*[a-zA-Z\d]:((?=[\x01-\x7f])[^\\\[\]]|\\[\x01-\x7f])+)\])(?(angle)>)$";
	}

	// Line feed character
	public function lineFeed(): string
	{
		return "\n";
	}

	// Form feed character
	public function formFeed(): string
	{
		return "\f";
	}

	// Vertical tab character
	public function verticalTab(): string
	{
		return "\v";
	}

	// Tab character
	public function horizontalTab(): string
	{
		return "\t";
	}

	public function questionMarkLiteral(): string
	{
		return $this->zeroOrOneQuantifier("?");
	}

	/**
	 * Get a regex pattern for matching CSS media queries
	 *
	 * @return string The regex pattern for matching CSS media queries
	 */
	public function getCSSMediaQueries(): string
	{
		return "@media\b[^{]*({((?:[^{}]+|(?1))*)})";
	}

	/**
	 * Get a regex pattern for matching a filename with an optional extension
	 *
	 * @return string The regex pattern for matching a filename with an optional extension
	 */
	public function getFileName(): string
	{
		return "([^.\/]+)\.?[^.\/]*$";
	}

	/**
	 * Get a regex pattern for matching a SHA-256 hash
	 *
	 * @return string The regex pattern for matching a SHA-256 hash
	 */
	public function isSha256(): string
	{
		return "^(sha256:|)[A-Fa-z0-9]{64}\\n$";
	}

	/**
	 * Get a regex pattern for matching the content of an HTML title tag
	 *
	 * @return string The regex pattern for matching the content of an HTML title tag
	 */
	public function getTitleFromHtmlNode(): string
	{
		return "<title>(.+)</title>";
	}

	/**
	 * Get a regex pattern for matching a VCS repository URL
	 *
	 * @return string The regex pattern for matching a VCS repository URL
	 */
	public function getVSCRepositoryPath(): string
	{
		return "^(?P<root>([a-z0-9.\-]+\.)+[a-z0-9.\-]+(:[0-9]+)?[A-Za-z0-9_.\-\/\~]*?\.(?P<vcs>bzr|git|hg|svn))((?:[A-Za-z0-9_.\-]+)*)$";
	}

	/**
	 * Get a regex pattern for matching an XML CDATA tag
	 *
	 * @return string The regex pattern for matching an XML CDATA tag
	 */
	public function getXMLCDataTag(): string
	{
		return '^<(!\[CDATA\[[\s\S]*?\]\]|[-a-zA-Z:0-9_.]+|\{[^{}]*\})\s*([-a-zA-Z:0-9_.]+=(\{[^{}]*\}|"[^"]*"|\'[^\']*\')\s*)*\/?\s*>';
	}

	/**
	 * Get a regex pattern for validating email addresses
	 *
	 * @return string The regex pattern for validating email addresses
	 */
	public function getCronArguments(): string
	{
		return "^[a-zA-Z0-9.!#$%&'*+\/\=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$";
	}

	/**
	 * Get a regex pattern for matching Go module paths
	 *
	 * @return string The regex pattern for matching Go module paths
	 */
	public function getGoModulePath(): string
	{
		return "^(?:([a-z0-9][-a-z0-9]+))?((?:v0|v[1-9][0-9]*)(?:\.0|\.[1-9][0-9]*){0,2}(-unstable)?)([a-zA-Z][-a-zA-Z0-9]*)(?:\.git)?((?:[a-zA-Z][-a-zA-Z0-9]*)*)$";
	}

	/**
	 * Get a regex pattern for matching Traefik route configuration entries
	 *
	 * @return string The regex pattern for matching Traefik route configuration entries
	 */
	public function getTraefikRouteConfig(): string
	{
		return "(?:Name:(?P<Name>\\S*))\\s*(?:Address:(?P<Address>\\S*))?\\s*(?:TLS:(?P<TLS>\\S*))?\\s*((?P<TLSACME>TLS))?\\s*(?:CA:(?P<CA>\\S*))?\\s*(?:Redirect.EntryPoint:(?P<RedirectEntryPoint>\\S*))?\\s*(?:Redirect.Regex:(?P<RedirectRegex>\\S*))?\\s*(?:Redirect.Replacement:(?P<RedirectReplacement>\\S*))?\\s*(?:Compress:(?P<Compress>\\S*))?";
	}

	/**
	 * Get a regex pattern for matching a GitHub reference log entry
	 *
	 * @return string The regex pattern for matching a GitHub reference log entry
	 */
	public function getGithubReferenceLogEntry(): string
	{
		return "^(refs[^/]+\S+)\s+([0-9A-Za-z]{40})\s+(\d{4}-\d{2}-\d{2}\s+\d{2}\:\d{2}\:\d{2}\s+[\+\-]\d{4})";
	}

	/**
	 * Get a regex pattern for matching Kubernetes resource names
	 *
	 * @return string The regex pattern for matching Kubernetes resource names
	 */
	public function getKubernetesResourceName(): string
	{
		return "^k8s_(?P<kubernetes_container_name>[^_\.]+)[^_]+_(?P<kubernetes_pod_name>[^_]+)_(?P<kubernetes_namespace>[^_]+)";
	}

	/**
	 * Get a regex pattern for matching a semantic version string
	 *
	 * @return string The regex pattern for matching a semantic version string
	 */
	public function getSegmentVersion(): string
	{
		return "^v\d+\.\d+\.\d+(-[a-z0-9]+)*(\.\d+)*(\+\d+-g[0-9a-f]+)?(-[^\s]+)?$";
	}

	/**
	 * Get a regex pattern for matching a UUID version 4
	 *
	 * @return string The regex pattern for matching a UUID version 4
	 */
	public function isUUIDv4(): string
	{
		return "^(\d{1,9})\.(\d{1,9})(\.|-(\w+))(\d{1,9})(\.\d{1,9})?-([^-]+)-([^-]+)$";
	}

	/**
	 * Get a regex pattern for matching a hexadecimal range with an optional label
	 *
	 * @return string The regex pattern for matching a hexadecimal range with an optional label
	 */
	public function isHexRangeWithLabel(): string
	{
		return "^([0-9A-F]+)(\.\.[0-9A-F]+)? *; ([A-Za-z_]+)$";
	}

	/**
	 * Get a regex pattern for validating email addresses
	 *
	 * @return string The regex pattern for validating email addresses
	 */
	public function isValidEmail(): string
	{
		return "^[\w!#$%&'*+\/\=?^_`{|}~-]+(?:\\.[\w!#$%&'*+\/\=?^_`{|}~-]+)*@(?:[\w](?:[\w-]*[\w])?\.)+[a-zA-Z0-9](?:[\w\-]*[\w])?$";
	}

	/**
	 * Get a regex pattern for matching a comma-separated list of numbers
	 *
	 * @return string The regex pattern for matching a comma-separated list of numbers
	 */
	public function isCommaSeparated(): string
	{
		return "^\d+(?:,\d+)*$";
	}

	/**
	 * Get a regex pattern for matching a number format with optional grouping and lookarounds
	 *
	 * @return string The regex pattern for matching a number format
	 */
	public function numberFormat(): string
	{
		return "({$this->positiveLookbehind()}\d)({$this->positiveLookahead()}(\d\d\d)+({$this->negativeLookahead()}\d))";
	}
}
