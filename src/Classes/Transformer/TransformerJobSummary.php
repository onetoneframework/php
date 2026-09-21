<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Transformer;

use Clover\Classes\CLI\Component\ProgressBar;

class TransformerJobSummary
{
    private $currentJob = '';
    private $label = '';
    private $jobCount;
    private $jobTick = 0;
    private $totalBatches;
    private $epochs;
    private $epoch;
    private $avg_loss;
    private $epoch_time;
    private $progressBar;
    private $jobBeginTime;
    private $jobEndTime;
    private $batchesBeginTime;
    private $batchesEndTime;
    private $currentBatches = 0;

    public function __construct($epochs, float $totalBatches)
    {
        $this->totalBatches = $totalBatches - 1;

        $this->progressBar = new ProgressBar((int) ($epochs * (int) ($totalBatches)));
    }

    public function setEpoch($epoch)
    {
        $this->epoch = $epoch;
    }


    public function setJobCount($jobCount)
    {
        $this->jobCount = $jobCount;
    }

    public function nextJobTick()
    {
        //$this->jobTick += 1;
    }

    public function setCurrentJob($currentJob)
    {
        $this->jobBeginTime = microtime(true);
        $this->currentJob = $currentJob;
    }

    public function setEndJob()
    {
        $this->jobEndTime = microtime(true) - $this->jobBeginTime;
        $this->nextTick();
        $this->jobTick = 0;
    }

    public function setEpochs($epochs)
    {
        $this->epochs = $epochs;
    }

    public function setAvgLoss($avg_loss)
    {
        $this->avg_loss = $avg_loss;
    }
    public function setEpochTime($epoch_time)
    {
        $this->epoch_time = $epoch_time;
    }

    public function startBatches()
    {
        $this->batchesBeginTime = microtime(true);
    }

    public function nextBatches()
    {
        $this->currentBatches++;
    }

    public function clearBatches()
    {
        $this->currentBatches = 0;
    }

    public function nextTick($next = false)
    {
        $this->label = sprintf(
            "\r\033[31mEpoch\033[0m: %3d/%d | \033[31mLoss\033[0m: %.7f | \033[31mTime\033[0m: %.6fs",
            $this->epoch,
            $this->epochs,
            $this->avg_loss,
            $this->epoch_time
        );

        if ($this->currentJob) {
            $this->label .= sprintf(" \033[31mTime\033[0m: %.6fs | \033[31mJob\033[0m: %s", $this->jobEndTime, $this->currentJob);
        }

        if ($this->totalBatches) {
            $this->label .= sprintf(" \033[31mBatches\033[0m: %d/%d %.6fs", $this->currentBatches, $this->totalBatches, microtime(true) - $this->batchesBeginTime);
        }

        if ($this->jobCount > 0) {
            $this->label .= sprintf(" \033[31mJob\033[0m: %d/%d %.6fs", $this->jobTick, $this->jobCount, microtime(true) - $this->batchesBeginTime);
        }

        if ($next) {
            $this->progressBar->advance(1, $this->label);
        } else {
            $this->progressBar->label($this->label);
            $this->progressBar->render();
        }
    }
}