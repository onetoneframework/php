<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use InvalidArgumentException;
use function array_key_exists;
use function count;
use function in_array;
use function intval;
use function is_array;
use function is_int;
use function is_string;

/**
 * VTL - PHP port of selected VTL functions:
 *  - estimateVTL
 *  - schwa
 *  - getFormantDispersion
 *
 * Notes:
 *  - NA in R is represented as null in PHP arrays.
 *  - Plotting and external datasets are omitted.
 *  - reformatFormants implements a minimal conversion:
 *      numeric vector -> ['f1'=>['freq'=>[...]], 'f2'=>...]
 *      associative with keys f1,f2... -> normalized to same structure
 *  - Regression uses ordinary least squares; interceptZero option supported.
 *
 * Usage examples:
 *   $sg = new VTL();
 *   $vtl = $sg->estimateVTL([600,1850,2800,3600,5000], 'regression', true, 'closed-open', 35400, true, 'simple', false);
 *   $s = $sg->schwa([860,1430,2900,null,5200], null, null, 8, true, 'closed-open', 35400, false);
 */
class VTL
{
    /**
     * Estimate vocal tract length (VTL) from formants.
     *
     * @param mixed $formants numeric array, associative array like ['f1'=>[...], ...], or null
     * @param string $method 'regression'|'meanDispersion'|'meanFormant'
     * @param bool $interceptZero
     * @param string $tube 'closed-open'|'open-open'
     * @param float $speedSound in cm/s (default 35400)
     * @param bool $checkFormat if true, reformat input
     * @param string $output 'simple'|'detailed'
     * @param bool $plot ignored (no plotting in PHP)
     * 
     * @return array[]|array{
     *  formantDispersion: mixed, 
     *  vocalTract: float|int|null, 
     *  regressionInfo: mixed, 
     *  vtlPerFormant: mixed,
     *  vocalTract_95CI: mixed,
     *  formantDispersion_95CI: mixed
     * }|float|int|null VTL in cm or detailed array
     */
    public function estimateVTL($formants, $method = 'regression', $interceptZero = true, $tube = 'closed-open', $speedSound = 35400.0, $checkFormat = true, $output = 'simple', $plot = false): array|float|int|null
    {
        $validMethods = ['meanFormant', 'meanDispersion', 'regression'];
        if (!in_array($method, $validMethods, true)) {
            throw new InvalidArgumentException('Invalid method; valid methods are: meanFormant, meanDispersion, regression');
        }

        if ($checkFormat) {
            $formants = $this->reformatFormants($formants);
            if (!is_array($formants)) {
                return null;
            }
        }

        if ($method === 'meanFormant') {
            // compute mean frequency per formant
            $formant_freqs = [];
            foreach ($formants as $f) {
                $freqs = array_filter($f['freq'], function ($v) {
                    return $v !== null;
                });
                if (count($freqs) === 0) {
                    $formant_freqs[] = null;
                } else {
                    $formant_freqs[] = array_sum($freqs) / count($freqs);
                }
            }
            $nf = count($formant_freqs);
            $vtls = [];
            for ($i = 0; $i < $nf; $i++) {
                $ff = $formant_freqs[$i];
                if ($ff === null || $ff == 0) {
                    $vtls[] = null;
                    continue;
                }
                $n = $i + 1;
                if (in_array($tube, ['closed-open', 'open-closed'], true)) {
                    $vtls[] = (2 * $n - 1) * $speedSound / 4.0 / $ff;
                } elseif (in_array($tube, ['open-open', 'closed-closed'], true)) {
                    $vtls[] = $n * $speedSound / 2.0 / $ff;
                } else {
                    throw new InvalidArgumentException('the tube can be closed-open or open-open');
                }
            }
            // average ignoring nulls
            $valid = array_filter($vtls, function ($v) {
                return $v !== null;
            });
            $vocalTract = count($valid) ? array_sum($valid) / count($valid) : null;
            $formantDispersion = null;
            $fd = null;
        } else {
            // meanDispersion or regression
            $fd = $this->getFormantDispersion($formants, $method, $tube, $interceptZero, $speedSound, $plot, true, $output);
            if (is_array($fd) && isset($fd['formantDispersion'])) {
                $formantDispersion = $fd['formantDispersion'];
            } else {
                $formantDispersion = $fd;
            }
            if ($formantDispersion === null || $formantDispersion == 0) {
                $vocalTract = null;
            } else {
                $vocalTract = $speedSound / 2.0 / $formantDispersion;
            }
        }

        if ($output === 'detailed') {
            $result = [
                'vocalTract' => $vocalTract,
                'formantDispersion' => $formantDispersion
            ];

            if (is_array($fd) && isset($fd['formantDispersion_95CI'])) {
                // convert dispersion CI to VTL CI (reverse order)
                $ci = $fd['formantDispersion_95CI'];
                $result['vocalTract_95CI'] = [
                    $speedSound / 2.0 / $ci[1],
                    $speedSound / 2.0 / $ci[0]
                ];
                $result['formantDispersion_95CI'] = $ci;
            }

            if (is_array($fd) && isset($fd['regressionInfo'])) {
                $result['regressionInfo'] = $fd['regressionInfo'];
            }
            if (is_array($fd) && isset($fd['vtlPerFormant'])) {
                $result['vtlPerFormant'] = $fd['vtlPerFormant'];
            }

            return $result;
        } else {
            return $vocalTract;
        }
    }

    /**
     * Schwa-related conversions.
     *
     * @param mixed $formants numeric array or null
     * @param float|null $vocalTract measured VTL in cm or null
     * @param array|null $formants_relative percent deviations or null
     * @param int $nForm number of formants to estimate
     * @param bool $interceptZero
     * @param string $tube
     * @param float $speedSound
     * @param bool $plot ignored
     * 
     * @return array{
     *  ff_measured: array<float|int|null>|null, 
     *  ff_relative: array|null, 
     *  ff_relative_dF: array<float|int>|null, 
     *  ff_relative_semitones: float[]|null, 
     *  ff_schwa: array<float|int>|null, 
     *  ff_theoretical: array<float|int>|null, 
     *  formantDispersion: mixed, 
     *  vtl_apparent: float|int|null, 
     *  vtl_measured: float|null}|array{
     *  ff_measured: array<float|int|null>|null, 
     *  ff_relative: array|null, 
     *  ff_relative_dF: array<float|int|null>|null, 
     *  ff_relative_semitones: array<float|null>|null, 
     *  ff_schwa: array<float|int>|null, 
     *  ff_theoretical: array<float|int>|null, 
     *  formantDispersion: mixed, 
     *  vtl_apparent: float|int|null, 
     *  vtl_measured: float|null}|array{
     *  ff_measured: array|null, 
     *  ff_relative: array|null, 
     *  ff_relative_dF: array|null, 
     *  ff_relative_semitones: array|null, 
     *  ff_schwa: array<float|int>|null, 
     *  ff_theoretical: array<float|int>|null, 
     *  formantDispersion: mixed, 
     *  vtl_apparent: float|int|null, 
     *  vtl_measured: float|null}|array{
     *  ff_measured: null, 
     *  ff_relative: array|null, 
     *  ff_relative_dF: array|null, 
     *  ff_relative_semitones: array|null, 
     *  ff_schwa: array|null, 
     *  ff_theoretical: array|null, 
     *  formantDispersion: float|int|null, 
     *  vtl_apparent: null, 
     *  vtl_measured: float|null
     * }
     */
    public function schwa($formants = null, $vocalTract = null, $formants_relative = null, $nForm = 8, $interceptZero = true, $tube = 'closed-open', $speedSound = 35400.0, $plot = false): array
    {
        if ($formants === null && $vocalTract === null) {
            throw new InvalidArgumentException('Please pecify formant frequencies and/or vocal tract length');
        }
        if ($formants_relative !== null && !is_array($formants_relative) && !is_numeric($formants_relative)) {
            throw new InvalidArgumentException('formants_relative must be numeric');
        }
        if ($vocalTract !== null && (!is_numeric($vocalTract) || $vocalTract <= 0)) {
            throw new InvalidArgumentException('vocalTract must be a positive number (cm)');
        }
        if ($formants_relative !== null && $vocalTract === null) {
            throw new InvalidArgumentException('vocalTract must be specified to convert relative formants to Hz');
        }

        // Normalize formants if provided
        $ff_measured = null;
        if ($formants !== null) {
            $ff_struct = $this->reformatFormants($formants);
            // extract mean per formant
            $ff_measured = [];
            foreach ($ff_struct as $f) {
                $vals = array_filter($f['freq'], function ($v) {
                    return $v !== null;
                });
                $ff_measured[] = count($vals) ? array_sum($vals) / count($vals) : null;
            }
        }

        if ($formants_relative === null) {
            // we want to calculate relative formants
            if ($formants === null) {
                // we know VTL only
                $formantDispersion = $speedSound / (2.0 * $vocalTract);
                $vocalTract_apparent = null;
            } else {
                if ($vocalTract === null) {
                    $fd = $this->getFormantDispersion($formants, 'regression', $tube, $interceptZero, $speedSound, false, true, 'simple');
                    $formantDispersion = is_array($fd) && isset($fd['formantDispersion']) ? $fd['formantDispersion'] : $fd;
                    $vocalTract_apparent = $formantDispersion ? $speedSound / (2.0 * $formantDispersion) : null;
                } else {
                    $fd_apparent = $this->getFormantDispersion($formants, 'regression', $tube, $interceptZero, $speedSound, false, true, 'simple');
                    $formantDispersion_apparent = is_array($fd_apparent) && isset($fd_apparent['formantDispersion']) ? $fd_apparent['formantDispersion'] : $fd_apparent;
                    $formantDispersion = $speedSound / (2.0 * $vocalTract);
                    $vocalTract_apparent = $formantDispersion_apparent ? $speedSound / (2.0 * $formantDispersion_apparent) : null;
                }
            }

            // determine indices
            if ($formants === null) {
                $idx = range(1, $nForm);
            } else {
                $idx = range(1, count($ff_measured));
            }

            if (in_array($tube, ['closed-open', 'open-closed'], true)) {
                $ff_schwa = [];
                foreach ($idx as $i) {
                    $ff_schwa[] = (2 * $i - 1) / 2.0 * $formantDispersion;
                }
            } elseif (in_array($tube, ['open-open', 'closed-closed'], true)) {
                $ff_schwa = [];
                foreach ($idx as $i) {
                    $ff_schwa[] = $i * $formantDispersion;
                }
            } else {
                throw new InvalidArgumentException('the tube can be closed-open or open-open');
            }

            // compute relative metrics
            $ff_relative = [];
            $ff_relative_semitones = [];
            $ff_relative_dF = [];
            if ($formants !== null) {
                for ($i = 0; $i < count($ff_measured); $i++) {
                    $obs = $ff_measured[$i];
                    $exp = $ff_schwa[$i] ?? null;
                    if ($obs === null || $exp === null || $exp == 0) {
                        $ff_relative[] = null;
                        $ff_relative_semitones[] = null;
                        $ff_relative_dF[] = null;
                    } else {
                        $ff_relative[] = ($obs / $exp - 1.0) * 100.0;
                        $ff_relative_semitones[] = $this->HzToSemitones($obs) - $this->HzToSemitones($exp);
                        $ff_relative_dF[] = ($obs - $exp) / $formantDispersion;
                    }
                }
                $ff_theoretical = null;
            } else {
                // no measured formants
                $ff_relative = null;
                $ff_relative_semitones = null;
                $ff_relative_dF = null;
                $ff_theoretical = null;
            }
        } else {
            // convert relative to absolute using provided vocalTract
            if (!is_array($formants_relative)) {
                $formants_relative = [$formants_relative];
            }
            // ensure length at least nForm
            if (count($formants_relative) < $nForm) {
                $formants_relative = array_merge($formants_relative, array_fill(0, $nForm - count($formants_relative), 0));
            }

            $formantDispersion = $speedSound / (2.0 * $vocalTract);
            $idx = range(1, count($formants_relative));
            if (in_array($tube, ['closed-open', 'open-closed'], true)) {
                $ff_schwa = [];
                foreach ($idx as $i) {
                    $ff_schwa[] = (2 * $i - 1) / 2.0 * $formantDispersion;
                }
            } elseif (in_array($tube, ['open-open', 'closed-closed'], true)) {
                $ff_schwa = [];
                foreach ($idx as $i) {
                    $ff_schwa[] = $i * $formantDispersion;
                }
            } else {
                throw new InvalidArgumentException('the tube can be closed-open or open-open');
            }

            $ff_theoretical = [];
            foreach ($idx as $i) {
                $ff_theoretical[] = $ff_schwa[$i - 1] * (1.0 + $formants_relative[$i - 1] / 100.0);
            }
            $vocalTract_apparent = null;
            $ff_relative = $formants_relative;
            $ff_relative_semitones = [];
            $ff_relative_dF = [];
            foreach ($ff_theoretical as $i => $val) {
                $ff_relative_semitones[] = 12.0 * log($val / $ff_schwa[$i], 2);
                $ff_relative_dF[] = ($val - $ff_schwa[$i]) / $formantDispersion;
            }

            $ff_measured = null;
        }

        $out = [
            'vtl_measured' => $vocalTract,
            'vtl_apparent' => $vocalTract_apparent ?? null,
            'formantDispersion' => $formantDispersion ?? null,
            'ff_measured' => $ff_measured,
            'ff_schwa' => $ff_schwa ?? null,
            'ff_theoretical' => $ff_theoretical ?? null,
            'ff_relative' => $ff_relative ?? null,
            'ff_relative_semitones' => $ff_relative_semitones ?? null,
            'ff_relative_dF' => $ff_relative_dF ?? null
        ];

        // remove empty elements (length 0)
        foreach ($out as $k => $v) {
            if (is_array($v) && count($v) === 0) {
                unset($out[$k]);
            }
        }

        return $out;
    }

    /**
     * Get formant dispersion.
     *
     * @param mixed $formants
     * @param string $method 'meanDispersion'|'regression'
     * @param string $tube
     * @param bool $interceptZero
     * @param float $speedSound
     * @param bool $plot ignored
     * @param bool $checkFormat
     * @param string $output 'simple'|'detailed'
     * 
     * @return array[]|array{
     *  formantDispersion: float|int, 
     *  formantDispersion_95CI: array<float|int>, 
     *  regressionInfo: array{
     *      formant: string, 
     *      formantSpacing: float|int, 
     *      formant_idx: int, 
     *      freq: mixed, 
     *      infl: null
     *  }, 
     *  vtlPerFormant: array<
     *      array|array{
     *          nFormants: int, 
     *          vtl: float|int|null
     *      }|array{
     *          nFormants: int, 
     *          vtl: null
     *      }>
     *  }|float|int|null
     * }
     */
    public function getFormantDispersion($formants, $method = 'regression', $tube = 'closed-open', $interceptZero = true, $speedSound = 35400.0, $plot = false, $checkFormat = true, $output = 'simple'): array|float|int|null
    {
        if ($plot) {
            $output = 'detailed';
        }

        if ($checkFormat) {
            $formants = $this->reformatFormants($formants);
        }

        if (!is_array($formants) || count($formants) < 1) {
            return null;
        }

        // check at least one non-null
        $hasAny = false;
        foreach ($formants as $f) {
            foreach ($f['freq'] as $v) {
                if ($v !== null) {
                    $hasAny = true;
                    break 2;
                }
            }
        }
        if (!$hasAny) {
            return null;
        }

        if ($method === 'meanDispersion') {
            // mean of differences between mean formant freqs
            $formant_freqs = [];
            foreach ($formants as $f) {
                $vals = array_filter($f['freq'], function ($v) {
                    return $v !== null;
                });
                $formant_freqs[] = count($vals) ? array_sum($vals) / count($vals) : null;
            }
            $valid = array_values(array_filter($formant_freqs, function ($v) {
                return $v !== null;
            }));
            if (count($valid) > 1) {
                $diffs = [];
                for ($i = 1; $i < count($valid); $i++) {
                    $diffs[] = $valid[$i] - $valid[$i - 1];
                }
                $formantDispersion = array_sum($diffs) / count($diffs);
            } else {
                // single formant: dispersion = 2 * f1
                if (isset($formant_freqs[0]) && $formant_freqs[0] !== null) {
                    $formantDispersion = 2.0 * $formant_freqs[0];
                } else {
                    $formantDispersion = null;
                }
            }
            return $formantDispersion;
        } elseif ($method === 'regression') {
            // build data frame fdf: formantSpacing and freq rows
            $fdf = [];
            $i = 0;
            foreach ($formants as $idx => $f) {
                $i = $idx + 1;
                $spacing = in_array($tube, ['closed-open', 'open-closed'], true) ? ($i - 0.5) : $i;
                foreach ($f['freq'] as $freqVal) {
                    $fdf[] = [
                        'formant_idx' => $i,
                        'formant' => 'F' . $i,
                        'formantSpacing' => $spacing,
                        'freq' => $freqVal,
                        'infl' => null
                    ];
                }
            }
            // filter out null freq rows
            $rows = array_values(array_filter($fdf, function ($r) {
                return $r['freq'] !== null;
            }));

            if (count($rows) === 0) {
                return null;
            }

            // prepare arrays for regression
            $x = array_column($rows, 'formantSpacing');
            $y = array_column($rows, 'freq');

            if ($interceptZero) {
                // slope = sum(x*y)/sum(x^2)
                $num = 0.0;
                $den = 0.0;
                for ($k = 0; $k < count($x); $k++) {
                    $num += $x[$k] * $y[$k];
                    $den += $x[$k] * $x[$k];
                }
                if ($den == 0) {
                    return null;
                }

                $slope = $num / $den;
                $formantDispersion = $slope;
                // compute residuals and standard error of slope
                $residSumSq = 0.0;
                for ($k = 0; $k < count($x); $k++) {
                    $pred = $slope * $x[$k];
                    $residSumSq += ($y[$k] - $pred) * ($y[$k] - $pred);
                }
                $sigma2 = $residSumSq / max(1, count($x) - 1);
                // var(slope) = sigma2 / sum(x^2)
                $se_slope = sqrt($sigma2 / $den);
            } else {
                // ordinary least squares with intercept
                $n = count($x);
                $meanX = array_sum($x) / $n;
                $meanY = array_sum($y) / $n;
                $num = 0.0;
                $den = 0.0;
                for ($k = 0; $k < $n; $k++) {
                    $num += ($x[$k] - $meanX) * ($y[$k] - $meanY);
                    $den += ($x[$k] - $meanX) * ($x[$k] - $meanX);
                }
                if ($den == 0) {
                    return null;
                }
                $slope = $num / $den;
                $intercept = $meanY - $slope * $meanX;
                $formantDispersion = $slope;
                // residuals
                $residSumSq = 0.0;
                for ($k = 0; $k < $n; $k++) {
                    $pred = $intercept + $slope * $x[$k];
                    $residSumSq += ($y[$k] - $pred) * ($y[$k] - $pred);
                }
                $sigma2 = $residSumSq / max(1, $n - 2);
                $se_slope = sqrt($sigma2 / $den);
            }

            // 95% CI for slope
            $ci = [$formantDispersion - 1.96 * $se_slope, $formantDispersion + 1.96 * $se_slope];

            $result = [
                'formantDispersion' => $formantDispersion,
                'formantDispersion_95CI' => $ci
            ];

            if ($output === 'detailed') {
                // vtl full
                $vtl_full = $speedSound / 2.0 / $formantDispersion;
                // vtl per first n formants
                // determine unique formant indices
                $uniqueFormants = array_unique(array_column($rows, 'formant'));
                $nUnique = count($uniqueFormants);
                $vf = [];
                for ($m = 1; $m <= $nUnique; $m++) {
                    // select rows with formant_idx <= m
                    $sel = array_values(array_filter($rows, function ($r) use ($m) {
                        return $r['formant_idx'] <= $m;
                    }));
                    if (count($sel) === 0) {
                        $vf[] = ['nFormants' => $m, 'vtl' => null];
                        continue;
                    }
                    $xs = array_column($sel, 'formantSpacing');
                    $ys = array_column($sel, 'freq');
                    if ($interceptZero) {
                        $num = 0.0;
                        $den = 0.0;
                        for ($k = 0; $k < count($xs); $k++) {
                            $num += $xs[$k] * $ys[$k];
                            $den += $xs[$k] * $xs[$k];
                        }
                        if ($den == 0) {
                            $vtl_i = null;
                        } else {
                            $slope_i = $num / $den;
                            $vtl_i = $speedSound / 2.0 / $slope_i;
                        }
                    } else {
                        $n2 = count($xs);
                        $meanX = array_sum($xs) / $n2;
                        $meanY = array_sum($ys) / $n2;
                        $num = 0.0;
                        $den = 0.0;
                        for ($k = 0; $k < $n2; $k++) {
                            $num += ($xs[$k] - $meanX) * ($ys[$k] - $meanY);
                            $den += ($xs[$k] - $meanX) * ($xs[$k] - $meanX);
                        }
                        if ($den == 0) {
                            $vtl_i = null;
                        } else {
                            $slope_i = $num / $den;
                            $vtl_i = $speedSound / 2.0 / $slope_i;
                        }
                    }
                    $vf[] = ['nFormants' => $m, 'vtl' => $vtl_i];
                }

                // influence of each observation: remove each row and recompute vtl
                $infl = [];
                for ($r = 0; $r < count($rows); $r++) {
                    $rows_i = $rows;
                    array_splice($rows_i, $r, 1);
                    if (count($rows_i) === 0) {
                        $infl[] = null;
                        continue;
                    }
                    $xs = array_column($rows_i, 'formantSpacing');
                    $ys = array_column($rows_i, 'freq');
                    if ($interceptZero) {
                        $num = 0.0;
                        $den = 0.0;
                        for ($k = 0; $k < count($xs); $k++) {
                            $num += $xs[$k] * $ys[$k];
                            $den += $xs[$k] * $xs[$k];
                        }
                        if ($den == 0) {
                            $vtl_i = null;
                        } else {
                            $slope_i = $num / $den;
                            $vtl_i = $speedSound / 2.0 / $slope_i;
                        }
                    } else {
                        $n2 = count($xs);
                        $meanX = array_sum($xs) / $n2;
                        $meanY = array_sum($ys) / $n2;
                        $num = 0.0;
                        $den = 0.0;
                        for ($k = 0; $k < $n2; $k++) {
                            $num += ($xs[$k] - $meanX) * ($ys[$k] - $meanY);
                            $den += ($xs[$k] - $meanX) * ($xs[$k] - $meanX);
                        }
                        if ($den == 0) {
                            $vtl_i = null;
                        } else {
                            $slope_i = $num / $den;
                            $vtl_i = $speedSound / 2.0 / $slope_i;
                        }
                    }
                    if ($vtl_i === null || $vtl_full === 0) {
                        $infl[] = null;
                    } else {
                        $infl[] = abs($vtl_full - $vtl_i) / $vtl_full * 10.0 + 1.0;
                    }
                }

                // attach regressionInfo and vtlPerFormant
                $regressionInfo = [];
                // reconstruct fdf with infl values aligned to original rows
                $k = 0;
                foreach ($fdf as $row) {
                    if ($row['freq'] === null) {
                        $row['infl'] = null;
                    } else {
                        $row['infl'] = $infl[$k] ?? null;
                        $k++;
                    }
                    $regressionInfo[] = $row;
                }

                $result['regressionInfo'] = $regressionInfo;
                $result['vtlPerFormant'] = $vf;
            }

            return $result;
        } else {
            throw new InvalidArgumentException('Invalid method for getFormantDispersion');
        }
    }

    /**
     * Minimal reformatFormants: normalize input into list of formants where each
     * element is ['freq' => [values...]].
     *
     * Accepts:
     *  - numeric indexed array [f1, f2, f3, ...]
     *  - associative array ['f1'=>[...], 'f2'=>...]
     *
     * @param mixed $formants
     * @return array|array{freq: array}[]|null
     */
    protected function reformatFormants(mixed $formants): array|null
    {
        if ($formants === null) {
            return null;
        }

        // If numeric indexed array of numbers
        if ($this->isNumericArray($formants)) {
            $out = [];
            foreach ($formants as $i => $val) {
                // convert NaN or non-finite to null
                if (!is_numeric($val) || !is_finite($val)) {
                    $val = null;
                }
                $out[] = ['freq' => [$val]];
            }
            return $out;
        }

        // If associative with keys f1,f2...
        if (is_array($formants)) {
            // detect keys like 'f1','f2' or numeric keys with arrays
            $keys = array_keys($formants);
            $isAssocF = false;
            foreach ($keys as $k) {
                if (is_string($k) && preg_match('/^f\d+$/i', $k)) {
                    $isAssocF = true;
                    break;
                }
            }
            if ($isAssocF) {
                // sort by numeric index
                $pairs = [];
                foreach ($formants as $k => $v) {
                    if (preg_match('/^f(\d+)$/i', $k, $m)) {
                        $idx = intval($m[1]);
                        $pairs[$idx] = $v;
                    }
                }
                ksort($pairs);
                $out = [];
                foreach ($pairs as $v) {
                    if (is_array($v)) {
                        // flatten numeric values, convert non-finite to null
                        $vals = array_map(function ($x) {
                            if (!is_numeric($x) || !is_finite($x)) {
                                return null;
                            }
                            return $x;
                        }, array_values($v));
                        $out[] = ['freq' => $vals];
                    } else {
                        // single numeric
                        $val = (is_numeric($v) && is_finite($v)) ? $v : null;
                        $out[] = ['freq' => [$val]];
                    }
                }
                return $out;
            } else {
                // If it's an indexed array of arrays like [ ['freq'=>[...]], ... ]
                $allHaveFreq = true;
                foreach ($formants as $el) {
                    if (!is_array($el) || !array_key_exists('freq', $el)) {
                        $allHaveFreq = false;
                        break;
                    }
                }
                if ($allHaveFreq) {
                    // normalize freq arrays
                    $out = [];
                    foreach ($formants as $el) {
                        $vals = array_map(function ($x) {
                            if (!is_numeric($x) || !is_finite($x)) {
                                return null;
                            }
                            return $x;
                        }, array_values($el['freq']));
                        $out[] = ['freq' => $vals];
                    }
                    return $out;
                }
            }
        }

        // unsupported format
        return null;
    }

    /**
     * Convert Hz to semitones (reference 1 Hz).
     *
     * @param float $hz
     * 
     * @return float|null
     */
    protected function HzToSemitones($hz): float|null
    {
        if ($hz === null || $hz <= 0) {
            return null;
        }

        return 12.0 * log($hz, 2.0);
    }

    /**
     * Helper: check if array is numeric-indexed and contains only scalar numbers or nulls.
     *
     * @param mixed $arr
     * 
     * @return bool
     */
    protected function isNumericArray($arr): bool
    {
        if (!is_array($arr)) {
            return false;
        }

        $keys = array_keys($arr);
        foreach ($keys as $k) {
            if (!is_int($k)) {
                return false;
            }
        }

        // allow numeric or null values
        foreach ($arr as $v) {
            if (!is_numeric($v) && $v !== null) {
                return false;
            }
        }
        return true;
    }
}

// Example quick test (uncomment to run):
// $vtl = new VTL();
// var_dump($sg->estimateVTL([600,1850,2800,3600,5000], 'regression', true, 'closed-open', 35400, true, 'detailed', false));
