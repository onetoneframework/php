<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Debug;

use function array_slice;
use function is_object;
use function method_exists;

final class ProfilerTraceFlowNormalizer
{
	/**
	 * @param array $traces
	 * @return array<int, array{step:int, call:string, location:string}>
	 */
	public function normalize(array $traces, int $maxSteps = 4096): array
	{
		$flow = [];
		foreach ($traces as $index => $trace) {
			if (!is_object($trace)) {
				continue;
			}

			$call = '';
			if (method_exists($trace, 'hasText') && method_exists($trace, 'getText') && $trace->hasText()) {
				$call = (string) $trace->getText();
			}

			$location = '';
			if (method_exists($trace, 'hasFile') && method_exists($trace, 'getFile') && $trace->hasFile()) {
				$location = (string) $trace->getFile();
				if (method_exists($trace, 'hasLine') && method_exists($trace, 'getLine') && $trace->hasLine()) {
					$location .= ':' . (string) $trace->getLine();
				}
			} elseif (method_exists($trace, 'hasClass') && method_exists($trace, 'getClass') && $trace->hasClass()) {
				$location = (string) $trace->getClass();
			}

			$flow[] = [
				'step' => (int) $index,
				'call' => $call !== '' ? $call : '(unknown call)',
				'location' => $location !== '' ? $location : '(unknown location)',
			];
		}

		return array_slice($flow, 0, $maxSteps);
	}
}
