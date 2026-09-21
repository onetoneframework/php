<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Contract;

use RuntimeException;

interface AudioAnalysisFailureLoggerInterface
{
	public function log(RuntimeException $exception, string $correlationIdentifier): void;
}
