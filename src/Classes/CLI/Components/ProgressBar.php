<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\CLI\Component;
use function intval;

/**
 * Progress Bar Component
 */
class ProgressBar
{
    /** @var int Total steps for the progress bar */
    private int $total;
    /** @var int Current progress */
    private int $current = 0;
    /** @var int Start time for calculating elapsed time */
    private int $startTime;
    /** @var int Length of the progress bar in characters */
    private int $barLength;
    /** @var string Label for the progress bar */
    private string $label;
    /** @var array Spinner characters for indeterminate progress */
    private array $spinner = ['⠋', '⠙', '⠸', '⠴', '⠦', '⠇'];
    /** @var int Spinner index for animation */
    private int $spinnerIndex = 0;
    /** @var bool Whether to use color in the progress bar */
    private bool $useColor;

    /**
     * Constructor
     *
     * @param int    $total Total steps for the progress bar
     * @param string $label Label for the progress bar
     * @param int    $barLength Length of the progress bar in characters
     * @param bool   $useColor Whether to use color in the progress bar
     */
    public function __construct(int $total, string $label = '', int $barLength = 40, bool $useColor = true)
    {
        $this->total = $total;
        $this->barLength = $barLength;
        $this->label = $label;
        $this->startTime = time();
        $this->useColor = $useColor;
    }

    /**
     * Update the label
     * 
     * @param string|null $newLabel New label for the progress bar (optional)
     * 
     * @return void
     */
    public function label(?string $newLabel = null): void
    {
        if ($newLabel !== null) {
            $this->label = $newLabel;
        }
        $this->render();
    }

    /**
     * Advance the progress bar
     * 
     * @param int         $step Number of steps to advance
     * @param string|null $newLabel New label for the progress bar (optional)
     * 
     * @return void
     */
    public function advance(int $step = 1, ?string $newLabel = null): void
    {
        if ($newLabel !== null) {
            $this->label = $newLabel;
        }

        $this->current += $step;
        if ($this->current > $this->total) {
            $this->current = $this->total;
        }

        $this->render();
    }

    /**
     * Finish the progress bar
     * 
     * @return void
     */
    public function finish(): void
    {
        $this->current = $this->total;
        $this->render();
        echo PHP_EOL;
    }

    /**
     * Spinner loop for indeterminate progress
     * 
     * @param callable $downloadFunc Function that performs the work and updates $this->current
     * 
     * @return void
     */
    public function spinnerLoop(callable $downloadFunc): void
    {
        while ($this->current < $this->total) {
            $downloadFunc();
            $this->render();
            usleep(50_000);
        }

        $this->finish();
    }

    /**
     * Render the progress bar
     * 
     * @return void
     */
    public function render(): void
    {
        $percent = $this->total > 0 ? $this->current / $this->total : 0;
        $filledLength = (int) floor($this->barLength * $percent);
        $bar = str_repeat('█', $filledLength) . str_repeat(' ', $this->barLength - $filledLength);
        $elapsed = time() - $this->startTime;
        $rate = $this->current > 0 ? $elapsed / $this->current : 0;
        $remaining = ($this->total - $this->current) * $rate;

        // Spinner update
        $spinnerChar = $this->spinner[$this->spinnerIndex];
        $this->spinnerIndex = ($this->spinnerIndex + 1) % count($this->spinner);

        // Color helpers
        $green = $this->useColor ? "\033[32m" : '';
        $blue = $this->useColor ? "\033[34m" : '';
        $cyan = $this->useColor ? "\033[36m" : '';
        $reset = $this->useColor ? "\033[0m" : '';

        $labelText = $this->label ? "$this->label$reset " : '';

        $time = $remaining;
        $format = "";
        $hours = round($time / (60 * 60));
        if ($hours > 0) {
            $format .= "{$hours}h ";
        }
        $time = $time - ($hours * (60 * 60));
        $minutes = round($time / 60);
        if ($minutes > 0) {
            $format .= "{$minutes}m ";
        }
        $time = intval($time - ($minutes * (60)));
        if ($time > 0) {
            $format .= "{$time}s ";
        }

        printf("\r\033[2K%s%s [%s] %3d%% (%d/%d) ⏱ %ds ETA: %s %s", $labelText, $green, $bar, $percent * 100, $this->current, $this->total, $elapsed, $format, "$cyan$spinnerChar$reset");

        ob_flush();
        flush();
    }
}
