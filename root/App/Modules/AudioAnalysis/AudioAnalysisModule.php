<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis;

use App\Modules\AudioAnalysis\Contract\AudioAnalysisGatewayInterface;
use App\Modules\AudioAnalysis\Contract\UploadedAudioProviderInterface;
use Clover\Framework\Module\AbstractModule;

final class AudioAnalysisModule extends AbstractModule
{
	public function getName(): string
	{
		return 'audio-analysis';
	}

	/**
	 * @return string[]
	 */
	public function getExportedServiceIdentifiers(): array
	{
		return [
			AudioAnalysisGatewayInterface::class,
			UploadedAudioProviderInterface::class,
		];
	}
}
