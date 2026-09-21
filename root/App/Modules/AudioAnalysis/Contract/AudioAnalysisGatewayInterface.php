<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Contract;

use App\Modules\AudioAnalysis\DataTransferObject\AudioAnalysisResponse;
use App\Modules\AudioAnalysis\DataTransferObject\UploadedAudio;

interface AudioAnalysisGatewayInterface
{
	public function analyze(UploadedAudio $audio): AudioAnalysisResponse;

	public function compare(UploadedAudio $baselineAudio, UploadedAudio $currentAudio): AudioAnalysisResponse;
}
