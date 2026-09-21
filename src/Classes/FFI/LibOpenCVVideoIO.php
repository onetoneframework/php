<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\FFI;

use FFI;
use FFI\CData;
use RuntimeException;
use Clover\Classes\File\Handler as FileHandler;

class LibOpenCVVideoIO
{
    private FFI $ffi;
    private ?CData $cvCamera = null;
    private ?int $desiredWidth = null;
    private ?int $desiredHeight = null;

    /**
     * @throws \FFI\Exception If OpenCV is not available
     */
    public function __construct()
    {
        $libOpenCVVideoIOHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/libopencv_videoio.h"));
        if (!$libOpenCVVideoIOHeader) {
            throw new RuntimeException('Lib opencv videoio header file is not exists');
        }
        $this->ffi = FFI::cdef($libOpenCVVideoIOHeader, "libopencv_videoio.so");
    }

    public function setDesiredSize(int $desiredWidth, int $desiredHeight): void
    {
        $this->desiredWidth = $desiredWidth;
        $this->desiredHeight = $desiredHeight;

        if ($this->cvCamera !== null) {
            $this->ffi->cvSetCaptureProperty($this->cvCamera, $this->ffi->CV_CAP_PROP_FRAME_WIDTH, $this->desiredWidth);
            $this->ffi->cvSetCaptureProperty($this->cvCamera, $this->ffi->CV_CAP_PROP_FRAME_HEIGHT, $this->desiredHeight);
        }
    }

    public function open(): bool
    {
        if ($this->cvCamera !== null) {
            return false;
        }

        $this->cvCamera = $this->ffi->cvCreateCameraCapture($this->ffi->CV_CAP_ANY);

        if ($this->cvCamera === null) {
            return false;
        }

        if ($this->desiredWidth !== null && $this->desiredHeight !== null) {
            $this->ffi->cvSetCaptureProperty($this->cvCamera, $this->ffi->CV_CAP_PROP_FRAME_WIDTH, $this->desiredWidth);
            $this->ffi->cvSetCaptureProperty($this->cvCamera, $this->ffi->CV_CAP_PROP_FRAME_HEIGHT, $this->desiredHeight);
        }

        return true;
    }

    public function close(): void
    {
        if ($this->cvCamera === null) {
            return;
        }

        $this->ffi->cvReleaseCapture(FFI::addr($this->cvCamera));
        $this->cvCamera = null;
    }

    public function saveFrame(string $filename, bool $mirror = false): bool
    {
        if ($this->cvCamera === null) {
            return false;
        }

        $image = $this->ffi->cvQueryFrame($this->cvCamera);

        if ($image === null) {
            return false;
        }

        if ($mirror) {
            $this->ffi->cvFlip($image, null, 1);
        }

        return (bool) $this->ffi->cvSaveImage($filename, $image);
    }

    public function __destruct()
    {
        $this->close();
    }

}
