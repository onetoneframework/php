<?php

declare(strict_types=1);

namespace Clover\Tests\Command;

use Clover\Classes\CLI\Input;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CommandConfigurationTest extends TestCase
{
	#[DataProvider('commandNameProvider')]
	public function testCommandNamesRemainStable(string $className, string $expectedName): void
	{
		$command = new $className();

		$this->assertSame($expectedName, $command->getName());
		$this->assertNotSame('', trim($command->getDescription()));
	}

	public function testSurveyedCommandNamesAreUnique(): void
	{
		$names = [];
		foreach (self::commandNameProvider() as [$className]) {
			$command = new $className();
			$names[] = $command->getName();
		}

		$this->assertSame($names, array_values(array_unique($names)));
	}

	#[DataProvider('configurationProvider')]
	public function testConfigurationDefaultsBindIntoInput(
		string $className,
		array $expectedArguments,
		array $expectedOptions
	): void {
		$command = new $className();
		$command->configure();

		$input = new Input(
			['php_console', $command->getName()],
			$command->arguments ?? [],
			$command->options ?? []
		);

		foreach ($expectedArguments as $name => $default) {
			$this->assertSame($default, $input->getArgument($name));
		}

		foreach ($expectedOptions as $name => $default) {
			$this->assertSame($default, $input->getOption($name));
		}
	}

	public static function commandNameProvider(): array
	{
		return [
			'chatgpt' => [\Command\ChatGPTCommand::class, 'llm:chatgpt'],
			'claude' => [\ClaudeCommand::class, 'llm:claude'],
			'exchange rates' => [\Command\ExchangeRateCommand::class, 'exchange:rates'],
			'gemini' => [\GoogleGeminiCommand::class, 'llm:gemini'],
			'github' => [\GithubCommand::class, 'github'],
			'harness' => [\Command\HarnessCommand::class, 'harness'],
			'maze' => [\Command\MazeCommand::class, 'game:maze'],
			'mcp' => [\Command\McpCommand::class, 'boost:mcp'],
			'medical report' => [\MedicalReportCommand::class, 'medical:report'],
			'minesweeper' => [\Command\MinesweeperCommand::class, 'game:minesweeper'],
			'papago' => [\Command\PapagoCommand::class, 'translate:papago'],
			'query' => [\QueryCommand::class, 'database:query'],
			'qr code' => [\QRCodeGenerateCommand::class, 'qr:generate'],
			'random drone path' => [\Command\RandomDronePathCommand::class, 'drone'],
			'route diagram' => [\RouteDiagramCommand::class, 'route:diagram'],
			'route flow' => [\RouteFlowCommand::class, 'route:flow'],
			'route list' => [\RouteListCommand::class, 'route:list'],
			'sokoban' => [\Command\SokobanCommand::class, 'game:sokoban'],
			'squid' => [\Command\SquidCommand::class, 'game:squid'],
			'transformer predict' => [\TransformerPredictCommand::class, 'transformer:predict'],
			'transformer training' => [\TransformerTrainingCommand::class, 'transformer:train'],
			'wizard' => [\Command\WizardCommand::class, 'framework:wizard'],
		];
	}

	public static function configurationProvider(): array
	{
		return [
			'chatgpt prompt' => [\Command\ChatGPTCommand::class, [], ['prompt' => 'How are you?']],
			'claude prompt' => [\ClaudeCommand::class, [], ['prompt' => 'How are you?']],
			'gemini prompt' => [\GoogleGeminiCommand::class, [], ['prompt' => 'How are you?']],
			'github action' => [\GithubCommand::class, [], ['command' => 'usage']],
			'harness' => [
				\Command\HarnessCommand::class,
				['task' => 'list'],
				['php' => 'php', 'quiet' => false],
			],
			'maze' => [
				\Command\MazeCommand::class,
				[],
				['width' => '21', 'height' => '21', 'seed' => '0'],
			],
			'medical report prompt' => [\MedicalReportCommand::class, [], ['prompt' => 'How are you?']],
			'minesweeper' => [
				\Command\MinesweeperCommand::class,
				[],
				['difficulty' => 'normal', 'width' => '16', 'height' => '16', 'mines' => '40'],
			],
			'papago prompt' => [\Command\PapagoCommand::class, [], ['prompt' => 'How are you?']],
			'query' => [
				\QueryCommand::class,
				['table' => 'table'],
				['command' => 'table:list'],
			],
			'qr code data' => [\QRCodeGenerateCommand::class, [], ['data' => 'testHelloWorld']],
			'random drone path' => [\Command\RandomDronePathCommand::class, [], ['prompt' => 'How are you?']],
			'sokoban level' => [\Command\SokobanCommand::class, [], ['level' => '1']],
			'squid' => [\Command\SquidCommand::class, [], ['size' => 'normal', 'squids' => '3']],
			'transformer predict prompt' => [\TransformerPredictCommand::class, [], ['prompt' => 'How are you?']],
			'transformer training prompt' => [\TransformerTrainingCommand::class, [], ['prompt' => 'How are you?']],
		];
	}
}
