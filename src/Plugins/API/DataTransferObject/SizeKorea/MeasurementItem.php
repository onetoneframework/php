<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\SizeKorea;

use Clover\Plugin\API\PublicDataInterface;

class MeasurementItem implements PublicDataInterface
{
    private int $measItemSeq;
    private string $measItemCd;
    private ?string $etcMeasItemCd;
    private string $measItemNm;
    private string $measItemUnitCcd;
    private int $lastMeasDegree;
    private int $measCount;
    private float $means;
    private float $min;
    private float $max;
    private float $stdDev;
    private float $p1;
    private float $p5;
    private float $p25;
    private float $p50;
    private float $p75;
    private float $p95;
    private float $p99;

    /** @var int[] */
    private array $measDegreeSet;

    /** @var string[] */
    private array $labels;

    /** @var int[] */
    private array $dataset;

    public function __construct(int $measItemSeq, string $measItemCd, ?string $etcMeasItemCd, string $measItemNm, string $measItemUnitCcd, int $lastMeasDegree, int $measCount, float $means, float $min, float $max, float $stdDev, float $p1, float $p5, float $p25, float $p50, float $p75, float $p95, float $p99, array $measDegreeSet, array $labels, array $dataset, )
    {
        $this->measItemSeq = $measItemSeq;
        $this->measItemCd = $measItemCd;
        $this->etcMeasItemCd = $etcMeasItemCd;
        $this->measItemNm = $measItemNm;
        $this->measItemUnitCcd = $measItemUnitCcd;
        $this->lastMeasDegree = $lastMeasDegree;
        $this->measCount = $measCount;
        $this->means = $means;
        $this->min = $min;
        $this->max = $max;
        $this->stdDev = $stdDev;
        $this->p1 = $p1;
        $this->p5 = $p5;
        $this->p25 = $p25;
        $this->p50 = $p50;
        $this->p75 = $p75;
        $this->p95 = $p95;
        $this->p99 = $p99;
        $this->measDegreeSet = $measDegreeSet;
        $this->labels = $labels;
        $this->dataset = $dataset;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self(
            measItemSeq: (int) $data->measItemSeq,
            measItemCd: $data->measItemCd,
            etcMeasItemCd: $data->etcMeasItemCd ?? null,
            measItemNm: $data->measItemNm,
            measItemUnitCcd: $data->measItemUnitCcd,
            lastMeasDegree: (int) $data->lastMeasDegree,
            measCount: (int) $data->measCount,
            means: (float) $data->means,
            min: (float) $data->min,
            max: (float) $data->max,
            stdDev: (float) $data->stdDev,
            p1: (float) $data->p1,
            p5: (float) $data->p5,
            p25: (float) $data->p25,
            p50: (float) $data->p50,
            p75: (float) $data->p75,
            p95: (float) $data->p95,
            p99: (float) $data->p99,
            measDegreeSet: (array) $data->measDegreeSet,
            labels: (array) $data->labels,
            dataset: (array) $data->dataset,
        );
    }

    public function getMeasItemSeq(): int
    {
        return $this->measItemSeq;
    }
    public function getMeasItemCd(): string
    {
        return $this->measItemCd;
    }
    public function getEtcMeasItemCd(): ?string
    {
        return $this->etcMeasItemCd;
    }
    public function getMeasItemNm(): string
    {
        return $this->measItemNm;
    }
    public function getMeasItemUnitCcd(): string
    {
        return $this->measItemUnitCcd;
    }
    public function getLastMeasDegree(): int
    {
        return $this->lastMeasDegree;
    }
    public function getMeasCount(): int
    {
        return $this->measCount;
    }
    public function getMeans(): float
    {
        return $this->means;
    }
    public function getMin(): float
    {
        return $this->min;
    }
    public function getMax(): float
    {
        return $this->max;
    }
    public function getStdDev(): float
    {
        return $this->stdDev;
    }
    public function getP1(): float
    {
        return $this->p1;
    }
    public function getP5(): float
    {
        return $this->p5;
    }
    public function getP25(): float
    {
        return $this->p25;
    }
    public function getP50(): float
    {
        return $this->p50;
    }
    public function getP75(): float
    {
        return $this->p75;
    }
    public function getP95(): float
    {
        return $this->p95;
    }
    public function getP99(): float
    {
        return $this->p99;
    }

    /** @return int[] */
    public function getMeasDegreeSet(): array
    {
        return $this->measDegreeSet;
    }

    /** @return string[] */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /** @return int[] */
    public function getDataset(): array
    {
        return $this->dataset;
    }
}
