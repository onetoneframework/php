<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Document\Excel;

/**
 * Immutable style definition applied to one or more cells.
 *
 * All setters return a new instance (copy-on-write), so a base style can be
 * reused and varied cheaply:
 *
 *   $header = (new ExcelStyle())->bold()->bgColor('FF4472C4')->fontColor('FFFFFFFF')->alignH('center');
 *   $money  = (new ExcelStyle())->numberFmt('#,##0.00')->alignH('right');
 */
final class ExcelStyle
{
    public function __construct(
        // ── Font ──────────────────────────────────────────────────────────
        public readonly string $fontName = 'Arial',
        public readonly int $fontSize = 11,
        public readonly bool $bold = false,
        public readonly bool $italic = false,
        public readonly bool $underline = false,
        /** Font color as 8-char ARGB hex, e.g. 'FF000000' (opaque black). */
        public readonly ?string $fontColor = null,

        // ── Fill ──────────────────────────────────────────────────────────
        /** Background fill color as 8-char ARGB hex, e.g. 'FFFFFF00' (yellow). */
        public readonly ?string $bgColor = null,

        // ── Alignment ─────────────────────────────────────────────────────
        /** Horizontal alignment: 'left' | 'center' | 'right' | 'fill' | 'justify' | 'general'. */
        public readonly ?string $alignH = null,
        /** Vertical alignment: 'top' | 'center' | 'bottom' | 'justify'. */
        public readonly ?string $alignV = null,
        public readonly bool $wrapText = false,

        // ── Number format ─────────────────────────────────────────────────
        /**
         * Excel number-format string, e.g. '#,##0.00', '0.00%', 'yyyy-mm-dd'.
         * Pass null to use the default (General) format.
         */
        public readonly ?string $numberFmt = null,

        // ── Borders ───────────────────────────────────────────────────────
        /**
         * Border style applied to all four sides:
         *   'thin' | 'medium' | 'thick' | 'dashed' | 'dotted' | 'double'
         */
        public readonly ?string $borderStyle = null,
        /** Border color as 8-char ARGB hex. Defaults to opaque black when borderStyle is set. */
        public readonly ?string $borderColor = null,
    ) {
    }

    // ── Fluent setters (return new instance) ──────────────────────────────────

    public function bold(bool $v = true): self
    {
        return $this->with('bold', $v);
    }

    public function italic(bool $v = true): self
    {
        return $this->with('italic', $v);
    }

    public function underline(bool $v = true): self
    {
        return $this->with('underline', $v);
    }

    public function fontSize(int $size): self
    {
        return $this->with('fontSize', $size);
    }

    public function fontName(string $name): self
    {
        return $this->with('fontName', $name);
    }

    /** @param string $argb 8-char ARGB hex, e.g. 'FFFF0000' for opaque red. */
    public function fontColor(string $argb): self
    {
        return $this->with('fontColor', $argb);
    }

    /** @param string $argb 8-char ARGB hex, e.g. 'FFFFFF00' for yellow background. */
    public function bgColor(string $argb): self
    {
        return $this->with('bgColor', $argb);
    }

    /** @param string $h 'left' | 'center' | 'right' | 'fill' | 'justify' | 'general' */
    public function alignH(string $h): self
    {
        return $this->with('alignH', $h);
    }

    /** @param string $v 'top' | 'center' | 'bottom' | 'justify' */
    public function alignV(string $v): self
    {
        return $this->with('alignV', $v);
    }

    public function wrapText(bool $v = true): self
    {
        return $this->with('wrapText', $v);
    }

    public function numberFmt(string $fmt): self
    {
        return $this->with('numberFmt', $fmt);
    }

    /**
     * Apply a border to all four sides.
     *
     * @param string $style 'thin' | 'medium' | 'thick' | 'dashed' | 'dotted' | 'double'
     * @param string $color 8-char ARGB hex (default: 'FF000000')
     * 
     * @return self
     */
    public function border(string $style = 'thin', string $color = 'FF000000'): self
    {
        return $this->with('borderStyle', $style)->with('borderColor', $color);
    }

    /**
     * Return a stable serialized key used for deduplication inside the style registry.
     * Two ExcelStyle instances with identical properties produce the same key.
     */
    public function key(): string
    {
        return implode('|', [
            $this->fontName,
            $this->fontSize,
            (int) $this->bold,
            (int) $this->italic,
            (int) $this->underline,
            $this->fontColor ?? '',
            $this->bgColor ?? '',
            $this->alignH ?? '',
            $this->alignV ?? '',
            (int) $this->wrapText,
            $this->numberFmt ?? '',
            $this->borderStyle ?? '',
            $this->borderColor ?? '',
        ]);
    }

    /** 
     * Copy-on-write helper. 
     * 
     * @return self
     **/
    private function with(string $prop, mixed $value): self
    {
        $args = [
            'fontName' => $this->fontName,
            'fontSize' => $this->fontSize,
            'bold' => $this->bold,
            'italic' => $this->italic,
            'underline' => $this->underline,
            'fontColor' => $this->fontColor,
            'bgColor' => $this->bgColor,
            'alignH' => $this->alignH,
            'alignV' => $this->alignV,
            'wrapText' => $this->wrapText,
            'numberFmt' => $this->numberFmt,
            'borderStyle' => $this->borderStyle,
            'borderColor' => $this->borderColor,
        ];
        $args[$prop] = $value;
        return new self(...$args);
    }
}
