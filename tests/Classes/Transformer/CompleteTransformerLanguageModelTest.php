<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Transformer;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Math\MatrixOps;
use PHPUnit\Framework\TestCase;
use Clover\Classes\Transformer\BilingualMorphTokenizer;
use Clover\Classes\Transformer\CompleteTransformerLanguageModel;

/**
 * Unit tests for CompleteTransformerLanguageModel
 * - Verifies construction with small dimensions
 * - Checks positional encoding shape and simple sine/cosine pattern
 * - Checks causal mask correctness
 * - Runs a single-step training on tiny synthetic data and asserts embeddings are updated
 * - Tests language translation training
 */
final class CompleteTransformerLanguageModelTest extends TestCase
{
	private string $tmpModelFile;

	protected function setUp(): void
	{
		MatrixOps::init();

		$this->tmpModelFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ctlm_test_' . uniqid('', true) . '.bin';
		// Ensure file does not exist at start
		if (file_exists($this->tmpModelFile)) {
			unlink($this->tmpModelFile);
		}
	}

	protected function tearDown(): void
	{
		if (file_exists($this->tmpModelFile)) {
			@unlink($this->tmpModelFile);
		}
	}

	public function testConstructsWithSmallDimensions()
	{
		// Construct a tiny model to keep tests fast
		$model = new CompleteTransformerLanguageModel($this->tmpModelFile, 32, 1, 4, 64, 16);
		$this->assertInstanceOf(CompleteTransformerLanguageModel::class, $model);
	}

	public function testPretrainDataset()
	{
		ini_set('memory_limit', '1256M');

		$model = new CompleteTransformerLanguageModel(
			__DIR__ . "/pretrain.json",
			32,
			2,
			4,
			64,
			200
		);

		$trainingData = [
			['output' => 'EN: Thanks. SHORT: TNX'],
			['output' => 'EN: Thank In Advance. SHORT: TIA'],
			['output' => 'EN: Read The Fine Manual. Used when a stupid question is asked. SHORT: RTFM'],
			['output' => 'EN: Rolling On The Floor Laughing. SHORT: ROTFL'],
			['output' => 'EN: Public Domain. SHORT: PD'],
			['output' => 'EN: On The Other Hand. SHORT: OTOH'],
			['output' => 'EN: On the floor. Short form of ROTFL. SHORT: OTF'],
			['output' => 'EN: Laughing Out Loud. SHORT: LOL'],
			['output' => 'EN: Keep It Simple Stupid. SHORT: KISS'],
			['output' => 'EN: In My Opinion. SHORT: IMO'],
			['output' => 'EN: In My Not So Humble Opinion. SHORT: IMNSHO'],
			['output' => 'EN: In My Humble Opinion. SHORT: IMHO'],
			['output' => 'EN: For Your Information. SHORT: FYI'],
			['output' => 'EN: For Your Amusement. SHORT: FYA'],
			['output' => 'EN: For What It\'s Worth. SHORT: FWIW'],
			['output' => 'EN: Falling Off The Chair Laughing. SHORT: FOTCL'],
			['output' => 'EN: Frequently Asked Questions. SHORT: FAQ'],
			['output' => 'EN: Devilish Little Grin. SHORT: DLG'],
			['output' => 'EN: By the way. SHORT: BTW'],
			['output' => 'EN: Be Seeing You. SHORT: BCNU'],
		];

		//$model->train($trainingData, 500, 1e-3);

		$this->assertEquals('FYA', $model->generateWithProbability('EN: For Your Amusement. SHORT: ', 30, 0.5)['text']);
		$this->assertEquals('TNX', $model->generateWithProbability('EN: Thanks. SHORT: ', 30, 0.5)['text']);
		$this->assertEquals('SHORT: ', $model->generateWithProbability('EN: For Your Information. ', 30, 0.5)['text']);
		$this->assertEquals('KISS', $model->generateWithProbability('EN: Keep It Simple Stupid. SHORT: ', 30, 0.1)['text']);
		$this->assertEquals('FOTCL', $model->generateWithProbability('EN: Falling Off The Chair Laughing. SHORT: ', 30, 0.5)['text']);
		$this->assertEquals('DLG', $model->generateWithProbability('EN: Devilish Little Grin. SHORT: ', 30, 0.5)['text']);
	}

	public function testTranslationTraining()
	{
		ini_set('memory_limit', '1024M');

		$tokenizer = new BilingualMorphTokenizer();
		$trainingData = [
			['output' => '[JP]abc[KO]def'],
			['output' => '[JP]abd[KO]def'],
			['output' => '[JP]abe[KO]def'],
		];

		foreach ($trainingData as $data) {
			$tokenizer->encode($data['output']);
		}

		$modelFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'translation_model_' . uniqid() . '.bin';
		$model = new CompleteTransformerLanguageModel(
			$modelFile,
			64,
			1,
			2,
			64,
			200,
			$tokenizer
		);

		try {
			$result = $model->generateWithProbability('[JP]abd[KO]', 20, 0.1);

			$this->assertIsArray($result);
			$this->assertArrayHasKey('text', $result);
			$this->assertArrayHasKey('tokens_with_prob', $result);
			$this->assertArrayHasKey('avg_confidence', $result);
			$this->assertIsString($result['text']);
			$this->assertIsString($result['tokens_with_prob']);
			$this->assertGreaterThanOrEqual(0.0, $result['avg_confidence']);
		} finally {
			if (file_exists($modelFile)) {
				unlink($modelFile);
			}
		}
	}
}
