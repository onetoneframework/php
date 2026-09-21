<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Video\H264;

use Exception;

/**
 * Class ContextInitialisation
 *
 * Holds the CABAC context initialisation values an I slice uses, as (m, n) pairs indexed by context.
 */
final class ContextInitialisation
{
	/** @var array<int, array{0:int,1:int}>|null */
	private static ?array $cache = null;

	/**
	 * Context initialisation pairs for I slices, keyed by context index.
	 *
	 * @return array<int, array{0:int,1:int}>
	 * @throws Exception                      If the packed table cannot be read.
	 */
	public static function forIntraSlices(): array
	{
		if (self::$cache !== null) {
			return self::$cache;
		}

		$decoded = base64_decode(self::PACKED_RANGES, true);
		if (!is_string($decoded)) {
			throw new Exception('Failed to decode the CABAC context initialisation data.');
		}

		$inflated = gzuncompress($decoded);
		if (!is_string($inflated)) {
			throw new Exception('Failed to decompress the CABAC context initialisation data.');
		}

		$ranges = json_decode($inflated, true, 512, JSON_THROW_ON_ERROR);
		$contexts = [];

		foreach ($ranges as $range) {
			$start = (int) ($range[0] ?? 0);
			foreach ($range[1] ?? [] as $offset => $pair) {
				$contexts[$start + (int) $offset] = [(int) ($pair[0] ?? 0), (int) ($pair[1] ?? 0)];
			}
		}

		self::$cache = $contexts;

		return $contexts;
	}

	private const PACKED_RANGES = 'eNqtV13SaykI3JCpEsS/taS+/W9jgG5O8s3UrfsyedGjiEBDY97v3t5v7e0l86e9tU3zYbQdwx+WX3qa6M7ZaNJzbbU5YhSI7jbl58cnK/T3ZuLz3tb4z/C67cRo7axcval5t60+ysDR0Zamvg19Eot+14Sim1fvMkvCLM2to9QGy8U3YPkjPOTxxh4Fp935WyyOSioZ/RGLXbitvpsnZPkabD3HbX2JPFtKl308uTDbscdgKG+7x+gGQLEHpP/rVjqznlBdSa2PhLUZsXzNj/v5rW6lzrRN+gzjdl0j7Wzev3F0C1TBZCuTlSbvQARjOHfaCvnJm93DlQgtA0KwsO0D3DpyJCHubQBGC1m3ZEDIuLpSb24GVhsaBBpCUeRjZICHfoSxYrQtw1qRlt7h+VgJS+wJpRBA9+0ag34OwTzPyoK7Z/AQAiYXhwTAdeRtxC9XT5sXeCIjy5LM9InIbtQYDrr/+R1IZJJPRCBuzDzvcK4j7p6WY1NvKrhtbsRi2kdaUn26v8J9T/UO5TfB04lik/NZjc9LgDZ05PZqesEOiPZso2cBJ2AOzfncu4G8HwWoRDXOXH5XmRusxr6i7P1qCzlP/sFwGnMmS182EsFHA3ucNG05ceVEfbLh993h98qF1PhKpz15vHJiZfgkHBrbJ/t7xUI47B+njvtWcpAVR5q014KI4PO5J5adSAYsU+UdkuKzvYya6wYaMfCZyjXhcmEcutDiiW6HyjeSYXWEL6tVgyF+Mg7hfKWUM4cxSSe4BbkSVYvUmqjAYitmJnnnslxmW6QKEikwdXmzT+VGDSUJeAmQQjpowM0A1zzFFQVYRTInt9Ijl0VJW6R9ujQ1y9itG9C+q0A728opjpbOygJheuayqt2lS0vIcrv4+FTR3fXrsNe7JLJx44H/g4cTDPcfrXB8WAhhD8dvkeijHrbZA9VOqBaJuQjo4GgHrwYW8qmyAK7z6kv2md8mGBD1oDzHoW2zuR4W2ze1R5EuZllHcSp5IAlAIB48sFCbQ0HVil0qhTJW6GUyBJl0+0T2J2sFDXMx5psQnnYmvyehnYXSSeaBV9W7BlNhYRTmc3hN78Hh3vxuBR8UfNFy9vqi8M6OXMQsiBb5Nw5n7jDUT+upZB88xcfKhP+RtmhHY4TTHuuV1Jq0tVHmESOyngwSsnAki5accl/JyMlobpoWgEYMOsekCwNHOW2oZEmRnARAawF90aFdT/JFBHSD3Isg+OzAc2OgI956lwXUyeJOvqkoJwb313X3h/N0mhq8iG5iPkljfJJtJyfzm3KDhDc5lN8dnKn4yqj5G3Wwu2Ua6AXDOhV3OJkXutOPMmg3MrGBiaNz4DN3FVi4OIVFwcxY7gjdIAR+6WAoPVfT8ZuOM/Q+DkIASs6nZa9nnShfpXyCxnNtkijJmIbW52EstvHXn9QWHhxMSWNGa70TlQyQK0+yG4L3klEtdfxqeB7HtMYQIEMKx5ch/NlU3OPKCnCSMEnZ0Zvhz0B1oHrSy6/nI9nr4g1jfMJ50PvDo/UKIqFqTU41gk6Sv8XCVixcO/LUev2nmKSWoq3JHALPeuG8sreo8TmgbOMxCnHHcJEsiWmssjAXXyddlB36b8///0HEf/8Ap5dDLQ==';
}
