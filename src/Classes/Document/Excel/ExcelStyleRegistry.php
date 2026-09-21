<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Document\Excel;

use Clover\Classes\Document\Excel;
use function count;

/**
 * Collects unique ExcelStyle objects and compiles a valid OOXML styles.xml.
 *
 * OOXML styles.xml element order (all required, order matters):
 *   numFmts → fonts → fills → borders → cellStyleXfs → cellXfs → cellStyles
 *
 * Index 0 in every sub-list is always the default (no customization).
 * Custom styles get indices ≥ 1 and are referenced by <c s="N">.
 */
class ExcelStyleRegistry
{
    /**
     * Maps serialized style key → xf index (1-based; 0 = default/unstyled).
     * @var array<string, int>
     */
    private array $index = [];

    /**
     * Registered styles in insertion order, keyed by xf index.
     * @var array<int, ExcelStyle>
     */
    private array $styles = [];

    /**
     * Register a style and return its xf index (≥ 1).
     * Duplicate styles (identical key) reuse the same index.
     */
    public function register(ExcelStyle $style): int
    {
        $key = $style->key();
        if (!isset($this->index[$key])) {
            $id = count($this->styles) + 1;
            $this->index[$key] = $id;
            $this->styles[$id] = $style;
        }
        return $this->index[$key];
    }

    /**
     * Get the xf index for a previously registered style, or 0 if not registered.
     */
    public function getId(ExcelStyle $style): int
    {
        return $this->index[$style->key()] ?? 0;
    }

    /**
     * Build the complete styles.xml content string.
     */
    public function buildXml(): string
    {
        // ── Seed defaults (index 0 in every sub-table) ────────────────────
        $fontXmls = [$this->fontXml(new ExcelStyle())];   // default font
        $fillXmls = [
            '<fill><patternFill patternType="none"/></fill>',      // [0] required
            '<fill><patternFill patternType="gray125"/></fill>',   // [1] required
        ];
        $borderXmls = ['<border><left/><right/><top/><bottom/><diagonal/></border>'];
        $numFmtXmls = [];
        $xfXmls = ['<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'];

        // ── De-duplication maps ───────────────────────────────────────────
        $fontMap = ['' => 0];
        $fillMap = ['' => 0, '__gray125__' => 1];  // slot 1 is always gray125
        $borderMap = ['' => 0];
        $numFmtMap = [];  // formatCode => numFmtId
        $customFmtN = 0;   // counter for custom numFmtId (starts at 164)

        // Built-in ID lookup (format string → ID)
        $builtinIds = array_flip([
            0 => 'General',
            1 => '0',
            2 => '0.00',
            3 => '#,##0',
            4 => '#,##0.00',
            9 => '0%',
            10 => '0.00%',
            14 => 'mm-dd-yy',
            22 => 'm/d/yy h:mm',
            49 => '@',
        ]);

        foreach ($this->styles as $style) {
            // ── Font ──────────────────────────────────────────────────────
            $fKey = $this->fontKey($style);
            if (!isset($fontMap[$fKey])) {
                $fontMap[$fKey] = count($fontXmls);
                $fontXmls[] = $this->fontXml($style);
            }

            // ── Fill ──────────────────────────────────────────────────────
            $bgKey = $style->bgColor ?? '';
            if ($bgKey !== '' && !isset($fillMap[$bgKey])) {
                $fillMap[$bgKey] = count($fillXmls);
                $fillXmls[] = $this->fillXml($style);
            }

            // ── Border ────────────────────────────────────────────────────
            $bKey = ($style->borderStyle ?? '') . '|' . ($style->borderColor ?? '');
            if ($bKey !== '|' && !isset($borderMap[$bKey])) {
                $borderMap[$bKey] = count($borderXmls);
                $borderXmls[] = $this->borderXml($style);
            }

            // ── Number format ─────────────────────────────────────────────
            $numFmtId = 0;
            if ($style->numberFmt !== null) {
                $fmt = $style->numberFmt;
                if (isset($builtinIds[$fmt])) {
                    $numFmtId = $builtinIds[$fmt];
                } elseif (isset($numFmtMap[$fmt])) {
                    $numFmtId = $numFmtMap[$fmt];
                } else {
                    $numFmtId = Excel::CUSTOM_NUMFMT_BASE + $customFmtN++;
                    $numFmtMap[$fmt] = $numFmtId;
                    $escaped = htmlspecialchars($fmt, ENT_XML1, 'UTF-8');
                    $numFmtXmls[] = "<numFmt numFmtId=\"{$numFmtId}\" formatCode=\"{$escaped}\"/>";
                }
            }

            // ── xf record ─────────────────────────────────────────────────
            $fontId = $fontMap[$fKey];
            $fillId = $bgKey !== '' ? ($fillMap[$bgKey] ?? 0) : 0;
            $borderId = ($bKey !== '|') ? ($borderMap[$bKey] ?? 0) : 0;

            $apply = '';
            if ($fontId > 0) {
                $apply .= ' applyFont="1"';
            }
            if ($fillId > 0) {
                $apply .= ' applyFill="1"';
            }
            if ($borderId > 0) {
                $apply .= ' applyBorder="1"';
            }
            if ($numFmtId > 0) {
                $apply .= ' applyNumberFormat="1"';
            }

            $xfOpen = "<xf numFmtId=\"{$numFmtId}\" fontId=\"{$fontId}\" fillId=\"{$fillId}\""
                . " borderId=\"{$borderId}\" xfId=\"0\"{$apply}>";

            $alignParts = [];
            if ($style->alignH !== null) {
                $alignParts[] = "horizontal=\"{$style->alignH}\"";
            }
            if ($style->alignV !== null) {
                $alignParts[] = "vertical=\"{$style->alignV}\"";
            }
            if ($style->wrapText) {
                $alignParts[] = 'wrapText="1"';
            }

            if (!empty($alignParts)) {
                $xfOpen = str_replace('xfId="0"', 'xfId="0" applyAlignment="1"', $xfOpen);
                $xfOpen .= '<alignment ' . implode(' ', $alignParts) . '/>';
            }

            $xfXmls[] = $xfOpen . '</xf>';
        }

        // ── Assemble ──────────────────────────────────────────────────────
        $nf = count($numFmtXmls);
        $nfX = $nf > 0 ? "<numFmts count=\"{$nf}\">" . implode('', $numFmtXmls) . '</numFmts>'
            : '<numFmts count="0"/>';

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $nfX
            . '<fonts count="' . count($fontXmls) . '">' . implode('', $fontXmls) . '</fonts>'
            . '<fills count="' . count($fillXmls) . '">' . implode('', $fillXmls) . '</fills>'
            . '<borders count="' . count($borderXmls) . '">' . implode('', $borderXmls) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($xfXmls) . '">' . implode('', $xfXmls) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    // ── XML fragment builders ─────────────────────────────────────────────────

    private function fontXml(ExcelStyle $s): string
    {
        $xml = '<font>';
        $xml .= "<sz val=\"{$s->fontSize}\"/>";
        $xml .= "<name val=\"{$s->fontName}\"/>";
        if ($s->bold) {
            $xml .= '<b/>';
        }
        if ($s->italic) {
            $xml .= '<i/>';
        }
        if ($s->underline) {
            $xml .= '<u/>';
        }
        if ($s->fontColor) {
            $xml .= "<color rgb=\"{$s->fontColor}\"/>";
        }
        return $xml . '</font>';
    }

    private function fillXml(ExcelStyle $s): string
    {
        if ($s->bgColor === null) {
            return '<fill><patternFill patternType="none"/></fill>';
        }
        return '<fill><patternFill patternType="solid">'
            . "<fgColor rgb=\"{$s->bgColor}\"/>"
            . '<bgColor indexed="64"/>'
            . '</patternFill></fill>';
    }

    private function borderXml(ExcelStyle $s): string
    {
        if ($s->borderStyle === null) {
            return '<border><left/><right/><top/><bottom/><diagonal/></border>';
        }
        $st = $s->borderStyle;
        $color = $s->borderColor ?? 'FF000000';
        $c = "<color rgb=\"{$color}\"/>";
        return "<border>"
            . "<left style=\"{$st}\">{$c}</left>"
            . "<right style=\"{$st}\">{$c}</right>"
            . "<top style=\"{$st}\">{$c}</top>"
            . "<bottom style=\"{$st}\">{$c}</bottom>"
            . "<diagonal/>"
            . "</border>";
    }

    /** Returns a compact key representing only font-related properties. */
    private function fontKey(ExcelStyle $s): string
    {
        return implode('|', [$s->fontName, $s->fontSize, (int) $s->bold, (int) $s->italic, (int) $s->underline, $s->fontColor ?? '',]);
    }
}
