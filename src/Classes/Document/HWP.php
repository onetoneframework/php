<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Document;

use RuntimeException;
use ZipArchive;
use SimpleXMLElement;
use function file_exists;
use function filesize;
use function filemtime;
use function strtolower;
use function pathinfo;
use function fread;
use function fopen;
use function fclose;
use function simplexml_load_string;
use function str_starts_with;
use function str_ends_with;
use function trim;
use function count;
use function strlen;
use function substr;
use function str_word_count;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function array_unique;
use function array_values;
use function array_merge;
use function is_readable;
use function round;
use function min;
use function md5_file;
use function sha1_file;
use function basename;
use function copy;
use function mkdir;
use function dirname;
use function is_dir;
use function file_put_contents;

/**
 * HWP Document Utility Class
 *
 * Provides advanced utilities for extracting text, metadata, images,
 * tables, styles, and structural information from Hangul Word Processor
 * (.hwp, .hwpx) files.
 *
 * The modern HWPX format (ZIP + XML) is fully supported. Legacy binary
 * HWP (OLE compound) has limited support due to the need for an external
 * OLE parser — fallback methods throw descriptive exceptions.
 */
class HWP
{
    /** @var string OLE Compound File magic bytes */
    private const OLE_MAGIC = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

    /** @var string ZIP magic bytes (PK header) */
    private const ZIP_MAGIC = "PK\x03\x04";

    /** @var string HWPX paragraph XML namespace */
    private const NS_PARAGRAPH = 'http://www.hancom.co.kr/hwpml/2011/paragraph';

    /** @var string HWPX head XML namespace */
    private const NS_HEAD = 'http://www.hancom.co.kr/hwpml/2011/head';

    /** @var string HWPX master-page XML namespace */
    private const NS_MASTER = 'http://www.hancom.co.kr/hwpml/2011/master-page';

    /** @var string Dublin Core elements namespace */
    private const NS_DC = 'http://purl.org/dc/elements/1.1/';

    /** @var string Dublin Core terms namespace */
    private const NS_DCTERMS = 'http://purl.org/dc/terms/';

    // ── Format Detection ────────────────────────────────────────────────

    /**
     * Check if the file is an HWPX (modern XML-based ZIP) format.
     *
     * Verifies the ZIP magic bytes and looks for the HWPX-specific
     * section XML files inside the Contents directory.
     *
     * @param string $file Path to the document.
     * @return bool True if the file is HWPX format.
     */
    public function isHwpx(string $file): bool
    {
        if (!file_exists($file)) {
            return false;
        }

        // Check magic number for ZIP (PK\x03\x04)
        $handle = @fopen($file, 'rb');
        if (!$handle) {
            return false;
        }
        $magic = fread($handle, 4);
        fclose($handle);
        if ($magic !== self::ZIP_MAGIC) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return false;
        }

        $isHwpx = $zip->getFromName('Contents/section0.xml') !== false
            || $zip->locateName('Contents/section0.xml') !== false
            || strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'hwpx';

        $zip->close();
        return $isHwpx;
    }

    /**
     * Check if the file is a legacy HWP (OLE compound binary) format.
     *
     * @param string $file Path to the document.
     * @return bool True if the file has the OLE magic signature.
     */
    public function isHwp(string $file): bool
    {
        if (!file_exists($file)) {
            return false;
        }

        $handle = @fopen($file, 'rb');
        if (!$handle) {
            return false;
        }
        $magic = fread($handle, 8);
        fclose($handle);

        return $magic === self::OLE_MAGIC;
    }

    /**
     * Detect the document format type.
     *
     * @param string $file Path to the document.
     * @return string One of: 'hwpx', 'hwp', or 'unknown'.
     */
    public function detectFormat(string $file): string
    {
        if ($this->isHwpx($file)) {
            return 'hwpx';
        }
        if ($this->isHwp($file)) {
            return 'hwp';
        }
        return 'unknown';
    }

    // ── Text Extraction ─────────────────────────────────────────────────

    /**
     * Extract plain text from an HWP/HWPX file.
     *
     * Automatically detects the format and delegates to the appropriate
     * extraction method.
     *
     * @param string $file Path to the document.
     * @return string Extracted plain text.
     * @throws RuntimeException If the format is unsupported.
     */
    public function extractText(string $file): string
    {
        if (!file_exists($file)) {
            throw new RuntimeException("HWP/HWPX file not found: $file");
        }

        if ($this->isHwpx($file)) {
            return $this->extractTextFromHwpx($file);
        }

        if ($this->isHwp($file)) {
            return $this->extractTextFromHwpFallback($file);
        }

        throw new RuntimeException("Unsupported file format or invalid HWP/HWPX signature: $file");
    }

    /**
     * Extract text from a specific section (page) of an HWPX file.
     *
     * Section indices start from 0 (section0.xml, section1.xml, ...).
     *
     * @param string $file         Path to the HWPX file.
     * @param int    $sectionIndex Zero-based section index.
     * @return string Extracted text from the specified section.
     * @throws RuntimeException If the section does not exist.
     */
    public function extractTextFromSection(string $file, int $sectionIndex): string
    {
        $this->ensureHwpxFormat($file);

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException("Failed to open HWPX: $file");
        }

        $sectionName = "Contents/section{$sectionIndex}.xml";
        $xmlData = $zip->getFromName($sectionName);
        $zip->close();

        if ($xmlData === false) {
            throw new RuntimeException("Section not found: $sectionName");
        }

        return $this->extractTextFromXml($xmlData);
    }

    /**
     * Get the approximate word count of the extracted text.
     *
     * @param string $file Path to the document.
     * @return int Word count.
     */
    public function getWordCount(string $file): int
    {
        $text = $this->extractText($file);
        if (trim($text) === '') {
            return 0;
        }

        // For Korean/CJK text, count characters as approximate word units
        $cjkCount = 0;
        if (preg_match_all('/[\x{AC00}-\x{D7AF}\x{4E00}-\x{9FFF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $text, $m)) {
            $cjkCount = count($m[0]);
        }

        // Count Latin/ASCII words normally
        $stripped = preg_replace('/[\x{AC00}-\x{D7AF}\x{4E00}-\x{9FFF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', ' ', $text);
        $latinCount = str_word_count(trim($stripped));

        return $cjkCount + $latinCount;
    }

    /**
     * Get the character count (excluding whitespace).
     *
     * @param string $file Path to the document.
     * @return int Character count.
     */
    public function getCharacterCount(string $file): int
    {
        $text = $this->extractText($file);
        return mb_strlen(preg_replace('/\s/', '', $text), 'UTF-8');
    }

    /**
     * Get the number of paragraphs in the document.
     *
     * @param string $file Path to the document.
     * @return int Paragraph count.
     */
    public function getParagraphCount(string $file): int
    {
        $text = $this->extractText($file);
        if (trim($text) === '') {
            return 0;
        }
        $lines = array_filter(explode("\n", $text), fn(string $l) => trim($l) !== '');
        return count($lines);
    }

    /**
     * Search for a keyword in the document text (case-insensitive).
     *
     * @param string $file    Path to the document.
     * @param string $keyword Search term.
     * @return bool True if the keyword is found.
     */
    public function containsText(string $file, string $keyword): bool
    {
        return mb_stripos($this->extractText($file), $keyword, 0, 'UTF-8') !== false;
    }

    /**
     * Count occurrences of a keyword in the document (case-insensitive).
     *
     * @param string $file    Path to the document.
     * @param string $keyword Search term.
     * @return int Occurrence count.
     */
    public function countTextOccurrences(string $file, string $keyword): int
    {
        if ($keyword === '') {
            return 0;
        }
        $text = mb_strtolower($this->extractText($file), 'UTF-8');
        $keyword = mb_strtolower($keyword, 'UTF-8');
        return mb_substr_count($text, $keyword);
    }

    // ── Section & Structure ─────────────────────────────────────────────

    /**
     * Get the number of sections (approximate page count) in an HWPX file.
     *
     * @param string $file Path to the HWPX file.
     * @return int Section count.
     */
    public function getSectionCount(string $file): int
    {
        $this->ensureHwpxFormat($file);

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return 0;
        }

        $count = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name && str_starts_with($name, 'Contents/section') && str_ends_with($name, '.xml')) {
                $count++;
            }
        }

        $zip->close();
        return $count;
    }

    /**
     * List all internal file entries in the HWPX ZIP archive.
     *
     * Useful for debugging or understanding the document's structure.
     *
     * @param string $file Path to the HWPX file.
     * @return array<int, string> List of internal paths.
     */
    public function listInternalFiles(string $file): array
    {
        $this->ensureHwpxFormat($file);

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return [];
        }

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== false) {
                $files[] = $name;
            }
        }

        $zip->close();
        return $files;
    }

    // ── Tables ──────────────────────────────────────────────────────────

    /**
     * Count the number of tables in the HWPX document.
     *
     * @param string $file Path to the HWPX file.
     * @return int Table count.
     */
    public function getTableCount(string $file): int
    {
        $this->ensureHwpxFormat($file);

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return 0;
        }

        $tableCount = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (!$filename || !str_starts_with($filename, 'Contents/section') || !str_ends_with($filename, '.xml')) {
                continue;
            }

            $xmlData = $zip->getFromIndex($i);
            if ($xmlData === false) {
                continue;
            }

            // Count <hp:tbl> elements
            $tableCount += substr_count($xmlData, '<hp:tbl');
        }

        $zip->close();
        return $tableCount;
    }

    /**
     * Extract table data from the HWPX document as arrays of rows.
     *
     * Each table is returned as an array of rows, where each row is an
     * array of cell text values.
     *
     * @param string $file Path to the HWPX file.
     * @return array<int, array<int, array<int, string>>> Tables > Rows > Cells.
     */
    public function extractTables(string $file): array
    {
        $this->ensureHwpxFormat($file);

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return [];
        }

        $tables = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (!$filename || !str_starts_with($filename, 'Contents/section') || !str_ends_with($filename, '.xml')) {
                continue;
            }

            $xmlData = $zip->getFromIndex($i);
            if ($xmlData === false) {
                continue;
            }

            $xml = @simplexml_load_string($xmlData);
            if ($xml === false) {
                continue;
            }

            $xml->registerXPathNamespace('hp', self::NS_PARAGRAPH);

            foreach ($xml->xpath('//hp:tbl') ?: [] as $tbl) {
                $tbl->registerXPathNamespace('hp', self::NS_PARAGRAPH);
                $table = [];

                foreach ($tbl->xpath('.//hp:tr') ?: [] as $tr) {
                    $tr->registerXPathNamespace('hp', self::NS_PARAGRAPH);
                    $row = [];

                    foreach ($tr->xpath('.//hp:tc') ?: [] as $tc) {
                        $tc->registerXPathNamespace('hp', self::NS_PARAGRAPH);
                        $cellText = '';
                        foreach ($tc->xpath('.//hp:t') ?: [] as $t) {
                            $cellText .= (string) $t;
                        }
                        $row[] = $cellText;
                    }

                    if (!empty($row)) {
                        $table[] = $row;
                    }
                }

                if (!empty($table)) {
                    $tables[] = $table;
                }
            }
        }

        $zip->close();
        return $tables;
    }

    // ── Images ──────────────────────────────────────────────────────────

    /**
     * Check if the HWPX document contains embedded images.
     *
     * @param string $file Path to the HWPX file.
     * @return bool True if any image binaries are found.
     */
    public function hasImages(string $file): bool
    {
        return count($this->listImageFiles($file)) > 0;
    }

    /**
     * Count the number of embedded images.
     *
     * @param string $file Path to the HWPX file.
     * @return int Image count.
     */
    public function getImageCount(string $file): int
    {
        return count($this->listImageFiles($file));
    }

    /**
     * List the internal paths of all embedded image files.
     *
     * @param string $file Path to the HWPX file.
     * @return array<int, string> Internal image paths.
     */
    public function listImageFiles(string $file): array
    {
        $this->ensureHwpxFormat($file);

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return [];
        }

        $imageExts = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'tiff', 'tif', 'svg', 'emf', 'wmf'];
        $images = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }

            // Images are typically in BinData/ or Contents/BinData/
            if (!str_starts_with($name, 'BinData/') && !str_starts_with($name, 'Contents/BinData/')) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, $imageExts, true)) {
                $images[] = $name;
            }
        }

        $zip->close();
        return $images;
    }

    /**
     * Extract all embedded images to the specified output directory.
     *
     * @param string $file   Path to the HWPX file.
     * @param string $outDir Destination directory for extracted images.
     * @return array<int, string> List of saved image file paths.
     */
    public function extractImages(string $file, string $outDir): array
    {
        $this->ensureHwpxFormat($file);
        $this->ensureDirectory($outDir);

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return [];
        }

        $saved = [];
        $internalImages = $this->listImageFiles($file);

        foreach ($internalImages as $imagePath) {
            $data = $zip->getFromName($imagePath);
            if ($data === false) {
                continue;
            }

            $outPath = $outDir . '/' . basename($imagePath);
            if (file_put_contents($outPath, $data) !== false) {
                $saved[] = $outPath;
            }
        }

        $zip->close();
        return $saved;
    }

    // ── Metadata ────────────────────────────────────────────────────────

    /**
     * Extract metadata from the document.
     *
     * Returns OPC core properties for HWPX files. Legacy HWP returns
     * an empty array.
     *
     * @param string $file Path to the document.
     * @return array<string, string> Metadata key-value pairs.
     */
    public function getMetadata(string $file): array
    {
        if (!file_exists($file)) {
            throw new RuntimeException("HWP/HWPX file not found: $file");
        }

        if ($this->isHwpx($file)) {
            return $this->getHwpxMetadata($file);
        }

        return [];
    }

    /**
     * Get the document title from metadata.
     *
     * @param string $file Path to the document.
     * @return string|null Title, or null if not set.
     */
    public function getTitle(string $file): ?string
    {
        return $this->getMetadata($file)['Title'] ?? null;
    }

    /**
     * Get the document author from metadata.
     *
     * @param string $file Path to the document.
     * @return string|null Author, or null if not set.
     */
    public function getAuthor(string $file): ?string
    {
        return $this->getMetadata($file)['Author'] ?? null;
    }

    /**
     * Get the document subject from metadata.
     *
     * @param string $file Path to the document.
     * @return string|null Subject, or null if not set.
     */
    public function getSubject(string $file): ?string
    {
        return $this->getMetadata($file)['Subject'] ?? null;
    }

    /**
     * Get the file size in bytes.
     *
     * @param string $file Path to the document.
     * @return int File size in bytes.
     * @throws RuntimeException If the file does not exist.
     */
    public function getFileSize(string $file): int
    {
        if (!file_exists($file)) {
            throw new RuntimeException("File not found: $file");
        }
        return filesize($file);
    }

    /**
     * Get a human-readable file size string.
     *
     * @param string $file Path to the document.
     * @return string Formatted size (e.g. "1.24 MB").
     */
    public function getFileSizeFormatted(string $file): string
    {
        return $this->formatBytes($this->getFileSize($file));
    }

    /**
     * Get the last-modified timestamp of the file on disk.
     *
     * @param string $file Path to the document.
     * @return int Unix timestamp.
     */
    public function getFileModifiedTime(string $file): int
    {
        if (!file_exists($file)) {
            throw new RuntimeException("File not found: $file");
        }
        return filemtime($file);
    }

    /**
     * Compute a hash digest for the file.
     *
     * @param string $file Path to the document.
     * @param string $algo Hash algorithm: 'md5' or 'sha1'.
     * @return string Hexadecimal hash.
     */
    public function getFileHash(string $file, string $algo = 'md5'): string
    {
        if (!file_exists($file)) {
            throw new RuntimeException("File not found: $file");
        }
        return match ($algo) {
            'md5' => md5_file($file),
            'sha1' => sha1_file($file),
            default => throw new \InvalidArgumentException("Unsupported algorithm: $algo"),
        };
    }

    /**
     * Produce a comprehensive summary of the document.
     *
     * @param string $file Path to the document.
     * @return array<string, mixed> Summary information.
     */
    public function getSummary(string $file): array
    {
        $format = $this->detectFormat($file);
        $meta = $this->getMetadata($file);
        $text = '';

        try {
            $text = $this->extractText($file);
        } catch (RuntimeException) {
            // Binary HWP — text extraction may not be available
        }

        $summary = [
            'format' => $format,
            'fileSize' => $this->getFileSizeFormatted($file),
            'fileSizeBytes' => $this->getFileSize($file),
            'metadata' => $meta,
            'wordCount' => $text !== '' ? $this->getWordCount($file) : null,
            'characterCount' => $text !== '' ? $this->getCharacterCount($file) : null,
            'paragraphCount' => $text !== '' ? $this->getParagraphCount($file) : null,
        ];

        if ($format === 'hwpx') {
            $summary['sectionCount'] = $this->getSectionCount($file);
            $summary['tableCount'] = $this->getTableCount($file);
            $summary['imageCount'] = $this->getImageCount($file);
        }

        return $summary;
    }

    // ── File Operations ─────────────────────────────────────────────────

    /**
     * Save a copy of the document to another path.
     *
     * @param string $file Source file path.
     * @param string $dest Destination path.
     * @return bool True on success.
     * @throws RuntimeException If the source is not readable.
     */
    public function saveAs(string $file, string $dest): bool
    {
        if (!is_readable($file)) {
            throw new RuntimeException("File not readable: $file");
        }
        $this->ensureDirectory(dirname($dest));
        return copy($file, $dest);
    }

    // ── Styles & Fonts (HWPX) ───────────────────────────────────────────

    /**
     * List font face declarations in the HWPX header.
     *
     * @param string $file Path to the HWPX file.
     * @return array<int, string> List of font face names.
     */
    public function listFonts(string $file): array
    {
        $this->ensureHwpxFormat($file);

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return [];
        }

        $fonts = [];
        $headerXml = $zip->getFromName('Contents/header.xml');
        $zip->close();

        if ($headerXml === false) {
            return [];
        }

        $xml = @simplexml_load_string($headerXml);
        if ($xml === false) {
            return [];
        }

        $xml->registerXPathNamespace('hh', self::NS_HEAD);
        foreach ($xml->xpath('//hh:fontface') ?: [] as $ff) {
            $name = (string) ($ff['name'] ?? '');
            if ($name !== '') {
                $fonts[] = $name;
            }
        }

        // Alternative: look for face attribute in font elements
        foreach ($xml->xpath('//*[@face]') ?: [] as $el) {
            $face = (string) ($el['face'] ?? '');
            if ($face !== '') {
                $fonts[] = $face;
            }
        }

        return array_values(array_unique($fonts));
    }

    /**
     * List paragraph style names defined in the HWPX header.
     *
     * @param string $file Path to the HWPX file.
     * @return array<int, string> Style names.
     */
    public function listStyles(string $file): array
    {
        $this->ensureHwpxFormat($file);

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return [];
        }

        $styles = [];
        $headerXml = $zip->getFromName('Contents/header.xml');
        $zip->close();

        if ($headerXml === false) {
            return [];
        }

        $xml = @simplexml_load_string($headerXml);
        if ($xml === false) {
            return [];
        }

        $xml->registerXPathNamespace('hh', self::NS_HEAD);

        foreach ($xml->xpath('//hh:style') ?: [] as $style) {
            $name = (string) ($style['name'] ?? $style['localName'] ?? '');
            if ($name !== '') {
                $styles[] = $name;
            }
        }

        return array_values(array_unique($styles));
    }

    // ── Headers & Footers (HWPX) ────────────────────────────────────────

    /**
     * Extract header text from the HWPX master-page definitions.
     *
     * @param string $file Path to the HWPX file.
     * @return array<int, string> Header text blocks.
     */
    public function getHeaders(string $file): array
    {
        return $this->extractMasterPageElement($file, 'header');
    }

    /**
     * Extract footer text from the HWPX master-page definitions.
     *
     * @param string $file Path to the HWPX file.
     * @return array<int, string> Footer text blocks.
     */
    public function getFooters(string $file): array
    {
        return $this->extractMasterPageElement($file, 'footer');
    }

    // ── Private Helpers ─────────────────────────────────────────────────

    /**
     * Extract text from modern HWPX (ZIP + XML) section files.
     *
     * @param string $file Path to the HWPX file.
     * @return string Extracted text.
     */
    private function extractTextFromHwpx(string $file): string
    {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException("Failed to open HWPX as ZIP archive: $file");
        }

        $text = '';

        // HWPX content is usually located in Contents/sectionX.xml
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Look for XML files inside the Contents directory
            if (!$filename || !str_starts_with($filename, 'Contents/section') || !str_ends_with($filename, '.xml')) {
                continue;
            }

            $xmlData = $zip->getFromIndex($i);
            if ($xmlData === false) {
                continue;
            }

            $text .= $this->extractTextFromXml($xmlData);
        }

        $zip->close();
        return trim($text);
    }

    /**
     * Extract text from a single section XML string.
     *
     * @param string $xmlData Raw XML content.
     * @return string Extracted text with newlines.
     */
    private function extractTextFromXml(string $xmlData): string
    {
        $xml = @simplexml_load_string($xmlData);
        if ($xml === false) {
            return '';
        }

        $text = '';
        $xml->registerXPathNamespace('hp', self::NS_PARAGRAPH);

        foreach ($xml->xpath('//hp:p') ?: [] as $para) {
            $para->registerXPathNamespace('hp', self::NS_PARAGRAPH);
            $runs = $para->xpath('.//hp:t');
            $line = '';
            foreach ($runs ?: [] as $run) {
                $line .= (string) $run;
            }
            if ($line !== '') {
                $text .= $line . "\n";
            }
        }

        return $text;
    }

    /**
     * Extract metadata from HWPX OPC core properties.
     *
     * @param string $file Path to the HWPX file.
     * @return array<string, string> Metadata key-value pairs.
     */
    private function getHwpxMetadata(string $file): array
    {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return [];
        }

        $corePath = $this->resolveCorePath($zip);
        if ($corePath === null) {
            $zip->close();
            return [];
        }

        $xmlData = $zip->getFromName($corePath);
        $zip->close();

        if ($xmlData === false) {
            return [];
        }

        $xml = @simplexml_load_string($xmlData);
        if ($xml === false) {
            return [];
        }

        $xml->registerXPathNamespace('dc', self::NS_DC);
        $xml->registerXPathNamespace('dcterms', self::NS_DCTERMS);

        $get = static function (array $nodes): string {
            return isset($nodes[0]) ? trim((string) $nodes[0]) : '';
        };

        $meta = [];
        if (($v = $get($xml->xpath('//dc:title') ?: [])) !== '') {
            $meta['Title'] = $v;
        }
        if (($v = $get($xml->xpath('//dc:creator') ?: [])) !== '') {
            $meta['Author'] = $v;
        }
        if (($v = $get($xml->xpath('//dc:subject') ?: [])) !== '') {
            $meta['Subject'] = $v;
        }
        if (($v = $get($xml->xpath('//dc:description') ?: [])) !== '') {
            $meta['Description'] = $v;
        }
        if (($v = $get($xml->xpath('//dcterms:created') ?: [])) !== '') {
            $meta['CreationDate'] = $v;
        }
        if (($v = $get($xml->xpath('//dcterms:modified') ?: [])) !== '') {
            $meta['ModDate'] = $v;
        }

        return $meta;
    }

    /**
     * Resolve the OPC core properties file path from the package relationships.
     *
     * @param ZipArchive $zip Opened ZIP archive handle.
     * @return string|null Core properties path, or null if not found.
     */
    private function resolveCorePath(ZipArchive $zip): ?string
    {
        $relsData = $zip->getFromName('_rels/.rels');
        if ($relsData !== false) {
            $xml = @simplexml_load_string($relsData);
            if ($xml !== false) {
                $xml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
                foreach ($xml->xpath('//r:Relationship') ?: [] as $rel) {
                    $type = (string) ($rel['Type'] ?? '');
                    if (str_ends_with($type, '/metadata/core-properties')) {
                        $target = ltrim((string) ($rel['Target'] ?? ''), '/');
                        if ($target !== '') {
                            return $target;
                        }
                    }
                }
            }
        }

        foreach (['docProps/core.xml', 'meta/core.xml'] as $candidate) {
            if ($zip->getFromName($candidate) !== false) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Extract header or footer text from HWPX master-page XML.
     *
     * @param string $file    Path to the HWPX file.
     * @param string $element Element name: 'header' or 'footer'.
     * @return array<int, string> Extracted text blocks.
     */
    private function extractMasterPageElement(string $file, string $element): array
    {
        $this->ensureHwpxFormat($file);

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return [];
        }

        $results = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            // Master pages are typically in Contents/masterPage*.xml or similar
            if ($name === false || !str_starts_with($name, 'Contents/')) {
                continue;
            }
            if (!str_contains($name, 'master') && !str_contains($name, 'header') && !str_contains($name, 'footer')) {
                continue;
            }

            $xmlData = $zip->getFromIndex($i);
            if ($xmlData === false) {
                continue;
            }

            $xml = @simplexml_load_string($xmlData);
            if ($xml === false) {
                continue;
            }

            $xml->registerXPathNamespace('hp', self::NS_PARAGRAPH);
            $xml->registerXPathNamespace('mp', self::NS_MASTER);

            $xpath = "//{$element}//hp:t | //mp:{$element}//hp:t | //hp:{$element}//hp:t";
            foreach ($xml->xpath($xpath) ?: [] as $t) {
                $text = trim((string) $t);
                if ($text !== '') {
                    $results[] = $text;
                }
            }
        }

        $zip->close();
        return $results;
    }

    /**
     * Contextual fallback for OLE-based HWP binary files.
     *
     * @param string $file Path to the HWP file.
     * @return string Never returns — always throws.
     * @throws RuntimeException Always.
     */
    private function extractTextFromHwpFallback(string $file): string
    {
        throw new RuntimeException(
            "Native PHP extraction for binary .hwp (OLE compound) format requires " .
            "an external OLE/zlib parser extension. Please convert the file to .hwpx " .
            "or implement an OLE wrapper."
        );
    }

    /**
     * Assert that the given file is HWPX format.
     *
     * @param string $file Path to the file.
     * @throws RuntimeException If the file is not HWPX.
     */
    private function ensureHwpxFormat(string $file): void
    {
        if (!$this->isHwpx($file)) {
            throw new RuntimeException("File is not in HWPX format: $file");
        }
    }

    /**
     * Format a byte count into a human-readable string.
     *
     * @param int $bytes Byte count.
     * @return string Formatted string (e.g. "2.1 MB").
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $idx = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $idx < count($units) - 1) {
            $size /= 1024;
            $idx++;
        }
        return round($size, 2) . ' ' . $units[$idx];
    }

    /**
     * Ensure a directory exists, creating it recursively if needed.
     *
     * @param string $dir Directory path.
     * @throws RuntimeException If creation fails.
     */
    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: $dir");
        }
    }
}
