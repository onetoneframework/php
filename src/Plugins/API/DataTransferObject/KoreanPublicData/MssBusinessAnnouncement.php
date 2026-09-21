<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\KoreaPublicData;

use Clover\Plugin\API\PublicDataInterface;

class MssBusinessAnnouncement implements PublicDataInterface
{
    private ?string $title;
    private ?string $dataContents;
    private ?string $applicationStartDate;
    private ?string $applicationEndDate;
    private ?string $writerName;
    private ?string $writerPosition;
    private ?string $writerPhone;
    private ?string $writerEmail;
    private ?string $viewUrl;
    private ?array $fileName;
    private ?array $fileUrl;

    public function __construct($title, $dataContents, $applicationStartDate, $applicationEndDate, $writerName, $writerPosition, $writerPhone, $writerEmail, $viewUrl, $fileName, $fileUrl)
    {
        $this->title = $title;
        $this->dataContents = $dataContents;
        $this->applicationStartDate = $applicationStartDate;
        $this->applicationEndDate = $applicationEndDate;
        $this->writerName = $writerName;
        $this->writerPosition = $writerPosition;
        $this->writerPhone = $writerPhone;
        $this->writerEmail = $writerEmail;
        $this->viewUrl = $viewUrl;
        if (is_array($fileName)) {
            $this->fileName = $fileName;
        }
        if (is_array($fileUrl)) {
            $this->fileUrl = $fileUrl;
        }
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($data->title, $data->dataContents, $data->applicationStartDate, $data->applicationEndDate, $data->writerName, $data->writerPosition, $data->writerPhone, $data->writerEmail, $data->viewUrl, $data->fileName, $data->fileUrl);
    }

}