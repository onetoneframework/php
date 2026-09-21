<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Contract;

use App\Modules\AudioAnalysis\DataTransferObject\UploadedAudio;

interface UploadedAudioProviderInterface
{
	public function get(string $fieldName): UploadedAudio;
}
