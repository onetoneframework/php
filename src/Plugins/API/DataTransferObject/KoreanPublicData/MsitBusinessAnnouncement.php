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

class MsitBusinessAnnouncement implements PublicDataInterface
{
    private ?string $subject;
    private ?string $viewUrl;
    private ?string $departmentName;
    private ?string $managerName;
    private ?string $managerTelephone;
    private ?string $pressDate;
    private ?string $fileName;
    private ?string $fileUrl;

    public function __construct($subject, $viewUrl, $departmentName, $managerName, $managerTelephone, $pressDate, $fileName, $fileUrl)
    {
        $this->subject = $subject;
        $this->viewUrl = $viewUrl;
        $this->departmentName = $departmentName;
        $this->managerName = $managerName;
        $this->managerTelephone = $managerTelephone;
        $this->pressDate = $pressDate;
        $this->fileName = $fileName;
        $this->fileUrl = $fileUrl;
    }

    public static function from(mixed $key = null, mixed $data): self 
    {
        return new self($data->subject, $data->viewUrl, $data->deptName, $data->managerName, $data->managerTel, $data->pressDt, $data->fileName, $data->fileUrl);
    }
    
}