<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Debug\ControlFlow;

use Clover\Classes\Debug\ControlFlow\AsciiFlowRenderer;
use Clover\Classes\Debug\ControlFlow\FlowNode;
use PHPUnit\Framework\TestCase;

final class FlowNodeRendererTest extends TestCase
{
	public function testFlowNodeStoresReadonlyIdentityAndMutableChildCollections(): void
	{
		$root = new FlowNode('function', 'run()');
		$body = new FlowNode('call', 'work()');
		$alternate = new FlowNode('else', 'fallback');

		$root->body[] = $body;
		$root->alternate[] = $alternate;
		$root->cases[] = ['label' => 'ready', 'body' => [$body]];

		$this->assertSame('function', $root->type);
		$this->assertSame('run()', $root->label);
		$this->assertSame([$body], $root->body);
		$this->assertSame([$alternate], $root->alternate);
		$this->assertSame([['label' => 'ready', 'body' => [$body]]], $root->cases);
	}

	public function testRendererBuildsABoxedRootAndKnownTypeLabels(): void
	{
		$root = new FlowNode('function', 'run()');
		$root->body[] = new FlowNode('if', '$ready');
		$root->body[] = new FlowNode('return', '$value');

		$output = (new AsciiFlowRenderer())->render($root);

		$this->assertStringContainsString('run()', $output);
		$this->assertStringContainsString('[IF] $ready', $output);
		$this->assertStringContainsString('[RETURN] $value', $output);
		$this->assertStringContainsString('╔', $output);
		$this->assertStringContainsString('╚', $output);
	}

	public function testRendererUsesUppercaseFallbackForUnknownNodeTypes(): void
	{
		$root = new FlowNode('root', 'entry');
		$root->body[] = new FlowNode('custom-step', 'payload');

		$output = (new AsciiFlowRenderer())->render($root);

		$this->assertStringContainsString('[CUSTOM-STEP] payload', $output);
	}

	public function testRendererExpandsBodyCasesAndAlternatesInThatOrder(): void
	{
		$root = new FlowNode('root', 'entry');
		$branch = new FlowNode('switch', '$state');
		$branch->body[] = new FlowNode('call', 'beforeCase()');
		$branch->cases[] = [
			'label' => 'ready',
			'body' => [new FlowNode('echo', 'ok')],
		];
		$branch->alternate[] = new FlowNode('else', 'fallback');
		$root->body[] = $branch;

		$output = (new AsciiFlowRenderer())->render($root);
		$bodyPosition = strpos($output, '[CALL] beforeCase()');
		$casePosition = strpos($output, '[CASE] ready');
		$alternatePosition = strpos($output, '[ELSE] fallback');

		$this->assertIsInt($bodyPosition);
		$this->assertIsInt($casePosition);
		$this->assertIsInt($alternatePosition);
		$this->assertLessThan($casePosition, $bodyPosition);
		$this->assertLessThan($alternatePosition, $casePosition);
		$this->assertStringContainsString('[ECHO] ok', $output);
	}

	public function testRendererCanBeReusedWithoutLeakingPreviousLines(): void
	{
		$renderer = new AsciiFlowRenderer();
		$first = new FlowNode('root', 'first');
		$first->body[] = new FlowNode('call', 'old()');
		$renderer->render($first);

		$second = new FlowNode('root', 'second');
		$output = $renderer->render($second);

		$this->assertStringContainsString('second', $output);
		$this->assertStringNotContainsString('first', $output);
		$this->assertStringNotContainsString('old()', $output);
	}
}
