<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Controller;

use Clover\Annotation\Prefix;
use Clover\Annotation\Route;
use Clover\Framework\Component\BaseController;
use Clover\Framework\Component\Response;

#[Prefix('/audio-analysis')]
final class AudioAnalysisPageController extends BaseController
{
	private const ANALYSIS_ENDPOINT = '/api/audio-analysis/analyses';
	private const COMPARISON_ENDPOINT = '/api/audio-analysis/comparisons';
	private const MAXIMUM_UPLOAD_BYTES = 1_073_741_824;
	private const VIEW_PATH = '/App/Modules/AudioAnalysis/View/index.php';
	private const STYLE_PATH = '/App/Modules/AudioAnalysis/View/audio-analysis.css';
	private const SCRIPT_PATH = '/App/Modules/AudioAnalysis/View/audio-analysis.js';
	private const VOICE_TRANSFORMER_WORKLET_PATH = '/App/Modules/AudioAnalysis/View/voice-transformer-worklet.js';

	#[Route('GET', '')]
	public function index(): Response
	{
		$this->setTitle('Voice Acoustic Workbench | Onetone');
		$this->addMetaTag([
			'name' => 'viewport',
			'content' => 'width=device-width, initial-scale=1',
		]);
		$this->addCssFileToHead(self::STYLE_PATH);
		$this->addJsFileToHead(self::SCRIPT_PATH);

		return $this->render(self::VIEW_PATH, [
			'analysisEndpoint' => self::ANALYSIS_ENDPOINT,
			'comparisonEndpoint' => self::COMPARISON_ENDPOINT,
			'maximumUploadBytes' => self::MAXIMUM_UPLOAD_BYTES,
			'voiceTransformerWorkletPath' => self::VOICE_TRANSFORMER_WORKLET_PATH,
		]);
	}
}
