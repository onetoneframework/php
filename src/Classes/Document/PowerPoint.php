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
use InvalidArgumentException;
use ZipArchive;
use SimpleXMLElement;
use function is_readable;
use function is_dir;
use function mkdir;
use function dirname;
use function pathinfo;
use function strpos;
use function trim;
use function implode;
use function copy;
use function count;
use function strlen;
use function str_word_count;
use function strtolower;
use function stripos;
use function preg_match;
use function preg_replace;
use function file_put_contents;
use function basename;
use function filesize;
use function filemtime;
use function md5_file;
use function sha1_file;
use function round;
use function min;
use function sort;
use function natsort;
use function array_values;
use function array_unique;
use function array_keys;
use function array_filter;
use function array_merge;
use function in_array;
use function str_contains;
use function usort;

/**
 * PowerPoint Document Utility Class
 *
 * Provides comprehensive utilities for extracting text, images, metadata,
 * speaker notes, comments, slide layouts, themes, and embedded media
 * from modern PowerPoint (.pptx) Office Open XML files.
 *
 * Requires the PHP 'zip' and 'libxml' extensions.
 */
class PowerPoint
{
    /** @var string DrawingML main namespace */
    private const NS_A = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    /** @var string PresentationML namespace */
    private const NS_P = 'http://schemas.openxmlformats.org/presentationml/2006/main';

    /** @var string Dublin Core elements namespace */
    private const NS_DC = 'http://purl.org/dc/elements/1.1/';

    /** @var string Dublin Core terms namespace */
    private const NS_DCTERMS = 'http://purl.org/dc/terms/';

    /** @var string Core properties namespace */
    private const NS_CP = 'http://schemas.openxmlformats.org/package/2006/metadata/core-properties';

    /** @var string Extended properties namespace */
    private const NS_VT = 'http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes';

    /** @var string Package relationships namespace */
    private const NS_REL = 'http://schemas.openxmlformats.org/package/2006/relationships';

    // ── Slide Counting & Structure ──────────────────────────────────────

    /**
     * Get the total number of slides in the presentation.
     *
     * Reads the Slides property from the app.xml extended properties.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return int Slide count.
     * @throws RuntimeException If the file cannot be opened.
     */
    public function getSlideCount(string $pptxFile): int
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $count = 0;
        $appXml = $zip->getFromName('docProps/app.xml');

        if ($appXml !== false) {
            $xml = new SimpleXMLElement($appXml);
            if (isset($xml->Slides)) {
                $count = (int) $xml->Slides;
            }
        }

        // Fallback: count slide XML files directly
        if ($count === 0) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name && preg_match('#^ppt/slides/slide\d+\.xml$#', $name)) {
                    $count++;
                }
            }
        }

        $zip->close();
        return $count;
    }

    /**
     * List all slide XML filenames in the archive, sorted by slide number.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<int, string> Sorted list of slide filenames (e.g. "ppt/slides/slide1.xml").
     */
    public function listSlideFiles(string $pptxFile): array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $slides = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name && preg_match('#^ppt/slides/slide\d+\.xml$#', $name)) {
                $slides[] = $name;
            }
        }

        $zip->close();

        // Natural sort to handle slide1, slide2, ..., slide10 correctly
        natsort($slides);
        return array_values($slides);
    }

    /**
     * Get the number of hidden slides in the presentation.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return int Hidden slide count.
     */
    public function getHiddenSlideCount(string $pptxFile): int
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $presXml = $zip->getFromName('ppt/presentation.xml');
        $zip->close();

        if ($presXml === false) {
            return 0;
        }

        $xml = new SimpleXMLElement($presXml);
        $xml->registerXPathNamespace('p', self::NS_P);

        $count = 0;
        foreach ($xml->xpath('//p:sldIdLst/p:sldId') ?: [] as $sld) {
            // Check for show="0" attribute (hidden slide marker)
            if ((string) ($sld['show'] ?? '') === '0') {
                $count++;
            }
        }

        return $count;
    }

    // ── Text Extraction ─────────────────────────────────────────────────

    /**
     * Extract all text from the presentation slides.
     *
     * Concatenates text from all <a:t> elements across all slides.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return string Extracted text.
     * @throws RuntimeException If the file cannot be opened.
     */
    public function extractText(string $pptxFile): string
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $textBlocks = [];

        // Loop through all files in the archive to find slide XMLs
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Match slide XML files (e.g., ppt/slides/slide1.xml)
            if (strpos($filename, 'ppt/slides/slide') === 0 && strpos($filename, '.xml') !== false) {
                $slideXml = $zip->getFromName($filename);
                if ($slideXml !== false) {
                    // Extract <a:t> tags which contain the text
                    $xml = new SimpleXMLElement($slideXml);
                    $xml->registerXPathNamespace('a', self::NS_A);
                    $textNodes = $xml->xpath('//a:t');
                    foreach ($textNodes as $node) {
                        $textBlocks[] = (string) $node;
                    }
                }
            }
        }

        $zip->close();
        return trim(implode(' ', $textBlocks));
    }

    /**
     * Extract text from a specific slide by its 1-based index.
     *
     * @param string $pptxFile   Path to the PPTX file.
     * @param int    $slideNumber 1-based slide number.
     * @return string Extracted text from the specified slide.
     * @throws RuntimeException If the slide does not exist.
     */
    public function extractTextFromSlide(string $pptxFile, int $slideNumber): string
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $slideName = "ppt/slides/slide{$slideNumber}.xml";
        $slideXml = $zip->getFromName($slideName);
        $zip->close();

        if ($slideXml === false) {
            throw new RuntimeException("Slide not found: $slideName");
        }

        $xml = new SimpleXMLElement($slideXml);
        $xml->registerXPathNamespace('a', self::NS_A);

        $blocks = [];
        foreach ($xml->xpath('//a:t') as $node) {
            $blocks[] = (string) $node;
        }

        return trim(implode(' ', $blocks));
    }

    /**
     * Extract text from each slide individually, indexed by slide number.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<int, string> Slide number => text content map.
     */
    public function extractTextPerSlide(string $pptxFile): array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $result = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (!preg_match('#^ppt/slides/slide(\d+)\.xml$#', $filename, $m)) {
                continue;
            }

            $slideNum = (int) $m[1];
            $slideXml = $zip->getFromName($filename);
            if ($slideXml === false) {
                continue;
            }

            $xml = new SimpleXMLElement($slideXml);
            $xml->registerXPathNamespace('a', self::NS_A);

            $blocks = [];
            foreach ($xml->xpath('//a:t') as $node) {
                $blocks[] = (string) $node;
            }

            $result[$slideNum] = trim(implode(' ', $blocks));
        }

        $zip->close();
        ksort($result);
        return $result;
    }

    /**
     * Get the approximate word count of the entire presentation.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return int Word count.
     */
    public function getWordCount(string $pptxFile): int
    {
        $text = $this->extractText($pptxFile);
        if (trim($text) === '') {
            return 0;
        }
        return str_word_count($text);
    }

    /**
     * Get the character count (excluding spaces).
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return int Character count.
     */
    public function getCharacterCount(string $pptxFile): int
    {
        $text = $this->extractText($pptxFile);
        return strlen(preg_replace('/\s/', '', $text));
    }

    /**
     * Search for a keyword in the presentation text (case-insensitive).
     *
     * @param string $pptxFile Path to the PPTX file.
     * @param string $keyword  Search term.
     * @return bool True if the keyword is found.
     */
    public function containsText(string $pptxFile, string $keyword): bool
    {
        return stripos($this->extractText($pptxFile), $keyword) !== false;
    }

    /**
     * Search for slides containing a specific keyword, returning their numbers.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @param string $keyword  Search term (case-insensitive).
     * @return array<int, int> List of slide numbers containing the keyword.
     */
    public function searchSlides(string $pptxFile, string $keyword): array
    {
        $perSlide = $this->extractTextPerSlide($pptxFile);
        $matches = [];

        foreach ($perSlide as $slideNum => $text) {
            if (stripos($text, $keyword) !== false) {
                $matches[] = $slideNum;
            }
        }

        return $matches;
    }

    // ── Slide Titles ────────────────────────────────────────────────────

    /**
     * Extract the title text from each slide.
     *
     * Looks for shapes with a title placeholder type in each slide.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<int, string> Slide number => title text map.
     */
    public function getSlideTitles(string $pptxFile): array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $titles = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (!preg_match('#^ppt/slides/slide(\d+)\.xml$#', $filename, $m)) {
                continue;
            }

            $slideNum = (int) $m[1];
            $slideXml = $zip->getFromName($filename);
            if ($slideXml === false) {
                continue;
            }

            $xml = new SimpleXMLElement($slideXml);
            $xml->registerXPathNamespace('a', self::NS_A);
            $xml->registerXPathNamespace('p', self::NS_P);

            // Look for title/ctrTitle placeholder types
            $titleNodes = $xml->xpath('//p:sp[.//p:ph[@type="title" or @type="ctrTitle"]]//a:t');
            $titleText = '';
            foreach ($titleNodes ?: [] as $node) {
                $titleText .= (string) $node;
            }

            $titles[$slideNum] = trim($titleText);
        }

        $zip->close();
        ksort($titles);
        return $titles;
    }

    // ── Speaker Notes ───────────────────────────────────────────────────

    /**
     * Check if the presentation contains speaker notes.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return bool True if any notes slides exist.
     * @throws RuntimeException If the file cannot be opened.
     */
    public function hasNotes(string $pptxFile): bool
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $hasNotes = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (strpos($filename, 'ppt/notesSlides/') === 0) {
                $hasNotes = true;
                break;
            }
        }

        $zip->close();
        return $hasNotes;
    }

    /**
     * Extract speaker notes from all slides.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<int, string> Slide number => notes text map.
     */
    public function extractNotes(string $pptxFile): array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $notes = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (!preg_match('#^ppt/notesSlides/notesSlide(\d+)\.xml$#', $filename, $m)) {
                continue;
            }

            $slideNum = (int) $m[1];
            $noteXml = $zip->getFromName($filename);
            if ($noteXml === false) {
                continue;
            }

            $xml = new SimpleXMLElement($noteXml);
            $xml->registerXPathNamespace('a', self::NS_A);
            $xml->registerXPathNamespace('p', self::NS_P);

            // Notes text is in body placeholders (type="body")
            $textNodes = $xml->xpath('//p:sp[.//p:ph[@type="body"]]//a:t');
            $blocks = [];
            foreach ($textNodes ?: [] as $node) {
                $blocks[] = (string) $node;
            }

            // Fallback: get all text if no body placeholder found
            if (empty($blocks)) {
                $allNodes = $xml->xpath('//a:t');
                foreach ($allNodes ?: [] as $node) {
                    $blocks[] = (string) $node;
                }
            }

            $text = trim(implode(' ', $blocks));
            if ($text !== '') {
                $notes[$slideNum] = $text;
            }
        }

        $zip->close();
        ksort($notes);
        return $notes;
    }

    /**
     * Extract speaker notes from a specific slide.
     *
     * @param string $pptxFile   Path to the PPTX file.
     * @param int    $slideNumber 1-based slide number.
     * @return string|null Notes text, or null if no notes exist for the slide.
     */
    public function extractNotesFromSlide(string $pptxFile, int $slideNumber): ?string
    {
        $allNotes = $this->extractNotes($pptxFile);
        return $allNotes[$slideNumber] ?? null;
    }

    // ── Comments ────────────────────────────────────────────────────────

    /**
     * Check if the presentation contains comments.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return bool True if any comment XML files exist.
     */
    public function hasComments(string $pptxFile): bool
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            return false;
        }

        $hasComments = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name && str_contains($name, 'ppt/comments/')) {
                $hasComments = true;
                break;
            }
        }

        $zip->close();
        return $hasComments;
    }

    /**
     * Extract all comments from the presentation.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<int, array{author: string, text: string, date: string}>
     */
    public function getComments(string $pptxFile): array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            return [];
        }

        // First, build author map from commentAuthors.xml
        $authors = [];
        $authorsXml = $zip->getFromName('ppt/commentAuthors.xml');
        if ($authorsXml !== false) {
            $xml = new SimpleXMLElement($authorsXml);
            $xml->registerXPathNamespace('p', self::NS_P);
            foreach ($xml->xpath('//p:cmAuthor') ?: [] as $a) {
                $id = (string) ($a['id'] ?? '');
                $name = (string) ($a['name'] ?? 'Unknown');
                if ($id !== '') {
                    $authors[$id] = $name;
                }
            }
        }

        $comments = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (!$filename || !str_contains($filename, 'ppt/comments/')) {
                continue;
            }

            $commentXml = $zip->getFromName($filename);
            if ($commentXml === false) {
                continue;
            }

            $xml = new SimpleXMLElement($commentXml);
            $xml->registerXPathNamespace('p', self::NS_P);

            foreach ($xml->xpath('//p:cm') ?: [] as $cm) {
                $authorId = (string) ($cm['authorId'] ?? '');
                $date = (string) ($cm['dt'] ?? '');
                $textNodes = $cm->xpath('.//a:t') ?: [];

                // Register namespace on child element
                $cm->registerXPathNamespace('a', self::NS_A);
                $textNodes = $cm->xpath('.//a:t') ?: [];

                $text = '';
                foreach ($textNodes as $t) {
                    $text .= (string) $t;
                }

                // Fallback: try direct p:text element
                if ($text === '') {
                    $pText = $cm->xpath('.//p:text') ?: [];
                    foreach ($pText as $pt) {
                        $text .= (string) $pt;
                    }
                }

                $comments[] = [
                    'author' => $authors[$authorId] ?? 'Unknown',
                    'text' => trim($text),
                    'date' => $date,
                ];
            }
        }

        $zip->close();
        return $comments;
    }

    // ── Metadata ────────────────────────────────────────────────────────

    /**
     * Extract basic metadata from the presentation core properties.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<string, string> Associative array of metadata fields.
     * @throws RuntimeException If the file cannot be opened.
     */
    public function getMetadata(string $pptxFile): array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $meta = [];
        $coreXml = $zip->getFromName('docProps/core.xml');

        if ($coreXml !== false) {
            $xml = new SimpleXMLElement($coreXml);
            $xml->registerXPathNamespace('dc', self::NS_DC);
            $xml->registerXPathNamespace('dcterms', self::NS_DCTERMS);
            $xml->registerXPathNamespace('cp', self::NS_CP);

            $title = $xml->xpath('//dc:title');
            $creator = $xml->xpath('//dc:creator');
            $description = $xml->xpath('//dc:description');
            $subject = $xml->xpath('//dc:subject');
            $created = $xml->xpath('//dcterms:created');
            $modified = $xml->xpath('//dcterms:modified');
            $revision = $xml->xpath('//cp:revision');
            $lastModifiedBy = $xml->xpath('//cp:lastModifiedBy');
            $category = $xml->xpath('//cp:category');

            $meta['Title'] = !empty($title) ? (string) $title[0] : '';
            $meta['Creator'] = !empty($creator) ? (string) $creator[0] : '';
            $meta['Description'] = !empty($description) ? (string) $description[0] : '';
            $meta['Subject'] = !empty($subject) ? (string) $subject[0] : '';
            $meta['CreationDate'] = !empty($created) ? (string) $created[0] : '';
            $meta['ModDate'] = !empty($modified) ? (string) $modified[0] : '';
            $meta['Revision'] = !empty($revision) ? (string) $revision[0] : '';
            $meta['LastModifiedBy'] = !empty($lastModifiedBy) ? (string) $lastModifiedBy[0] : '';
            $meta['Category'] = !empty($category) ? (string) $category[0] : '';
        }

        $zip->close();
        return $meta;
    }

    /**
     * Extract extended properties (application name, company, etc.).
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<string, string> Extended property key-value pairs.
     */
    public function getExtendedProperties(string $pptxFile): array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            return [];
        }

        $props = [];
        $appXml = $zip->getFromName('docProps/app.xml');
        $zip->close();

        if ($appXml === false) {
            return [];
        }

        $xml = new SimpleXMLElement($appXml);
        $fields = ['Application', 'Company', 'PresentationFormat', 'Slides', 'Notes', 'HiddenSlides', 'TotalTime'];

        foreach ($fields as $field) {
            if (isset($xml->$field)) {
                $props[$field] = (string) $xml->$field;
            }
        }

        return $props;
    }

    /**
     * Get the document title from metadata.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return string|null Title, or null if not set.
     */
    public function getTitle(string $pptxFile): ?string
    {
        $meta = $this->getMetadata($pptxFile);
        return ($meta['Title'] ?? '') !== '' ? $meta['Title'] : null;
    }

    /**
     * Get the document creator/author from metadata.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return string|null Creator name, or null if not set.
     */
    public function getCreator(string $pptxFile): ?string
    {
        $meta = $this->getMetadata($pptxFile);
        return ($meta['Creator'] ?? '') !== '' ? $meta['Creator'] : null;
    }

    /**
     * Get the file size in bytes.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return int File size in bytes.
     */
    public function getFileSize(string $pptxFile): int
    {
        $this->ensureReadable($pptxFile);
        return filesize($pptxFile);
    }

    /**
     * Get a human-readable file size string.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return string Formatted size (e.g. "4.2 MB").
     */
    public function getFileSizeFormatted(string $pptxFile): string
    {
        return $this->formatBytes($this->getFileSize($pptxFile));
    }

    /**
     * Compute a hash digest for the file.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @param string $algo     Hash algorithm: 'md5' or 'sha1'.
     * @return string Hexadecimal hash.
     */
    public function getFileHash(string $pptxFile, string $algo = 'md5'): string
    {
        $this->ensureReadable($pptxFile);
        return match ($algo) {
            'md5' => md5_file($pptxFile),
            'sha1' => sha1_file($pptxFile),
            default => throw new InvalidArgumentException("Unsupported algorithm: $algo"),
        };
    }

    // ── Images & Media ──────────────────────────────────────────────────

    /**
     * Extract images from the PPTX file into the specified output directory.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @param string $outDir   Destination directory.
     * @return array<int, string> Array of saved image file paths.
     * @throws RuntimeException If the file cannot be opened.
     */
    public function extractImages(string $pptxFile, string $outDir): array
    {
        $this->ensureReadable($pptxFile);
        $this->ensureDirectory($outDir);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            throw new RuntimeException("Failed to open PPTX archive: $pptxFile");
        }

        $saved = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Images are usually stored in ppt/media/
            if (strpos($filename, 'ppt/media/') === 0) {
                $basename = basename($filename);
                $destination = $outDir . '/' . $basename;

                // Extract the specific stream and save it
                $stream = $zip->getFromIndex($i);
                if ($stream !== false && file_put_contents($destination, $stream) !== false) {
                    $saved[] = $destination;
                }
            }
        }

        $zip->close();
        return $saved;
    }

    /**
     * Get the number of media files (images, audio, video) in the presentation.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return int Media file count.
     */
    public function getMediaCount(string $pptxFile): int
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            return 0;
        }

        $count = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name && strpos($name, 'ppt/media/') === 0) {
                $count++;
            }
        }

        $zip->close();
        return $count;
    }

    /**
     * List all media files with their types and sizes.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<int, array{name: string, extension: string, size: int}>
     */
    public function listMediaFiles(string $pptxFile): array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            return [];
        }

        $media = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!$name || strpos($name, 'ppt/media/') !== 0) {
                continue;
            }

            $stat = $zip->statIndex($i);
            $media[] = [
                'name' => basename($name),
                'extension' => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
                'size' => $stat ? $stat['size'] : 0,
            ];
        }

        $zip->close();
        return $media;
    }

    /**
     * Check if the presentation contains embedded audio or video.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return bool True if audio or video files are found.
     */
    public function hasEmbeddedMedia(string $pptxFile): bool
    {
        $mediaFiles = $this->listMediaFiles($pptxFile);
        $avExts = ['mp3', 'wav', 'wma', 'ogg', 'aac', 'mp4', 'avi', 'wmv', 'mov', 'flv', 'webm', 'm4a', 'm4v'];

        foreach ($mediaFiles as $file) {
            if (in_array($file['extension'], $avExts, true)) {
                return true;
            }
        }
        return false;
    }

    // ── Themes & Layouts ────────────────────────────────────────────────

    /**
     * Get the theme name used in the presentation.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return string|null Theme name, or null if not found.
     */
    public function getThemeName(string $pptxFile): ?string
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            return null;
        }

        $themeName = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!$name || !preg_match('#^ppt/theme/theme\d+\.xml$#', $name)) {
                continue;
            }

            $themeXml = $zip->getFromName($name);
            if ($themeXml === false) {
                continue;
            }

            $xml = new SimpleXMLElement($themeXml);
            $xml->registerXPathNamespace('a', self::NS_A);

            // Theme name is in the root element's "name" attribute
            $themeName = (string) ($xml['name'] ?? '');
            if ($themeName !== '') {
                break;
            }
        }

        $zip->close();
        return $themeName !== '' ? $themeName : null;
    }

    /**
     * List all slide layout names used in the presentation.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<int, string> Layout names.
     */
    public function getSlideLayoutNames(string $pptxFile): array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            return [];
        }

        $layouts = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!$name || !preg_match('#^ppt/slideLayouts/slideLayout\d+\.xml$#', $name)) {
                continue;
            }

            $layoutXml = $zip->getFromName($name);
            if ($layoutXml === false) {
                continue;
            }

            $xml = new SimpleXMLElement($layoutXml);
            $xml->registerXPathNamespace('p', self::NS_P);

            // Layout name is typically in the cSld element's name attribute
            $cSld = $xml->xpath('//p:cSld');
            if (!empty($cSld)) {
                $layoutName = (string) ($cSld[0]['name'] ?? '');
                if ($layoutName !== '') {
                    $layouts[] = $layoutName;
                }
            }
        }

        $zip->close();
        return array_values(array_unique($layouts));
    }

    /**
     * Get the number of slide masters in the presentation.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return int Slide master count.
     */
    public function getSlideMasterCount(string $pptxFile): int
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            return 0;
        }

        $count = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name && preg_match('#^ppt/slideMasters/slideMaster\d+\.xml$#', $name)) {
                $count++;
            }
        }

        $zip->close();
        return $count;
    }

    // ── Slide Dimensions ────────────────────────────────────────────────

    /**
     * Get the presentation slide dimensions in EMU (English Metric Units).
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array{cx: int, cy: int}|null Width and height in EMU, or null.
     */
    public function getSlideSizeEmu(string $pptxFile): ?array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
            return null;
        }

        $presXml = $zip->getFromName('ppt/presentation.xml');
        $zip->close();

        if ($presXml === false) {
            return null;
        }

        $xml = new SimpleXMLElement($presXml);
        $xml->registerXPathNamespace('p', self::NS_P);

        $sldSz = $xml->xpath('//p:sldSz');
        if (empty($sldSz)) {
            return null;
        }

        $cx = (int) ($sldSz[0]['cx'] ?? 0);
        $cy = (int) ($sldSz[0]['cy'] ?? 0);

        return ($cx > 0 && $cy > 0) ? ['cx' => $cx, 'cy' => $cy] : null;
    }

    /**
     * Get the presentation slide dimensions in inches.
     *
     * Standard EMU conversion: 1 inch = 914400 EMU.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array{width_in: float, height_in: float}|null
     */
    public function getSlideSizeInches(string $pptxFile): ?array
    {
        $emu = $this->getSlideSizeEmu($pptxFile);
        if ($emu === null) {
            return null;
        }

        return [
            'width_in' => round($emu['cx'] / 914400.0, 2),
            'height_in' => round($emu['cy'] / 914400.0, 2),
        ];
    }

    /**
     * Get the slide aspect ratio as a simplified string (e.g. "16:9", "4:3").
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return string|null Aspect ratio string, or null if unavailable.
     */
    public function getAspectRatio(string $pptxFile): ?string
    {
        $emu = $this->getSlideSizeEmu($pptxFile);
        if ($emu === null) {
            return null;
        }

        $gcd = $this->gcd($emu['cx'], $emu['cy']);
        $w = $emu['cx'] / $gcd;
        $h = $emu['cy'] / $gcd;

        // Round to common ratios
        $ratio = $emu['cx'] / $emu['cy'];
        if (abs($ratio - 16.0 / 9.0) < 0.02) {
            return '16:9';
        }
        if (abs($ratio - 4.0 / 3.0) < 0.02) {
            return '4:3';
        }
        if (abs($ratio - 16.0 / 10.0) < 0.02) {
            return '16:10';
        }

        return "{$w}:{$h}";
    }

    // ── Internal Archive Structure ──────────────────────────────────────

    /**
     * List all internal file entries in the PPTX ZIP archive.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<int, string> Internal file paths.
     */
    public function listInternalFiles(string $pptxFile): array
    {
        $this->ensureReadable($pptxFile);

        $zip = new ZipArchive();
        if ($zip->open($pptxFile) !== true) {
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

    // ── Summary ─────────────────────────────────────────────────────────

    /**
     * Produce a comprehensive summary of the presentation.
     *
     * @param string $pptxFile Path to the PPTX file.
     * @return array<string, mixed> Summary information.
     */
    public function getSummary(string $pptxFile): array
    {
        $meta = $this->getMetadata($pptxFile);
        $extProps = $this->getExtendedProperties($pptxFile);

        return [
            'slideCount' => $this->getSlideCount($pptxFile),
            'wordCount' => $this->getWordCount($pptxFile),
            'characterCount' => $this->getCharacterCount($pptxFile),
            'mediaCount' => $this->getMediaCount($pptxFile),
            'hasNotes' => $this->hasNotes($pptxFile),
            'hasComments' => $this->hasComments($pptxFile),
            'hasEmbeddedMedia' => $this->hasEmbeddedMedia($pptxFile),
            'theme' => $this->getThemeName($pptxFile),
            'aspectRatio' => $this->getAspectRatio($pptxFile),
            'fileSize' => $this->getFileSizeFormatted($pptxFile),
            'fileSizeBytes' => $this->getFileSize($pptxFile),
            'metadata' => $meta,
            'extendedProps' => $extProps,
        ];
    }

    // ── File Operations ─────────────────────────────────────────────────

    /**
     * Save a copy of the PPTX to another path.
     *
     * @param string $pptxFile Source PPTX path.
     * @param string $dest     Destination path.
     * @return bool True on success.
     * @throws RuntimeException If the source is not readable.
     */
    public function saveAs(string $pptxFile, string $dest): bool
    {
        $this->ensureReadable($pptxFile);
        $this->ensureDirectory(dirname($dest));
        return copy($pptxFile, $dest);
    }

    // ── Private Helpers ─────────────────────────────────────────────────

    /**
     * Verify that the file exists and is readable.
     *
     * @param string $file File path.
     * @throws RuntimeException If the file is not readable.
     */
    private function ensureReadable(string $file): void
    {
        if (!is_readable($file)) {
            throw new RuntimeException("PowerPoint file not readable: $file");
        }
    }

    /**
     * Ensure a directory exists, creating it recursively if needed.
     *
     * @param string $dir Directory path.
     * @throws RuntimeException If directory creation fails.
     */
    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: $dir");
        }
    }

    /**
     * Format a byte count into a human-readable string.
     *
     * @param int $bytes Byte count.
     * @return string Formatted string (e.g. "3.14 MB").
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
     * Compute the greatest common divisor of two integers.
     *
     * Used for simplifying aspect ratios.
     *
     * @param int $a First integer.
     * @param int $b Second integer.
     * @return int GCD.
     */
    private function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        while ($b !== 0) {
            $t = $b;
            $b = $a % $b;
            $a = $t;
        }
        return $a;
    }
}
