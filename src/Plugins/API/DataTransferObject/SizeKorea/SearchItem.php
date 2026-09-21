<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\SizeKorea;

use Clover\Plugin\API\PublicDataInterface;

class SearchItem implements PublicDataInterface
{
    private int $measItemSeq;
    private ?string $measItemTypeCcd;
    private ?string $measItemKorNm;
    private ?string $measItemEngNm;
    private ?string $measItemOldKorNm;
    private ?string $measItemUnitCcd;
    private ?string $measItemCd;
    private ?string $actionTypeCcd;
    private ?string $poseTypeCcd;
    private ?string $sizeTypeCcd;
    private ?string $bodyPartUppCcd;
    private ?string $bodyPartCcd;
    private ?string $measDefCts;
    private ?string $landmarkDesc;
    private ?string $measMethod;
    private ?string $measPose;
    private ?string $measTool;
    private ?string $movUseYn;
    private ?string $movPath;
    private ?string $movDesc;
    private ?string $imgUseYn;
    private ?string $imgPath;
    private ?string $imgDesc;
    private ?string $picUseYn;
    private ?string $picPath;
    private ?string $picDesc;

    public function __construct(int $measItemSeq, ?string $measItemTypeCcd, ?string $measItemKorNm, ?string $measItemEngNm, ?string $measItemOldKorNm, ?string $measItemUnitCcd, ?string $measItemCd, ?string $actionTypeCcd, ?string $poseTypeCcd, ?string $sizeTypeCcd, ?string $bodyPartUppCcd, ?string $bodyPartCcd, ?string $measDefCts, ?string $landmarkDesc, ?string $measMethod, ?string $measPose, ?string $measTool, ?string $movUseYn, ?string $movPath, ?string $movDesc, ?string $imgUseYn, ?string $imgPath, ?string $imgDesc, ?string $picUseYn, ?string $picPath, ?string $picDesc, )
    {
        $this->measItemSeq = $measItemSeq;
        $this->measItemTypeCcd = $measItemTypeCcd;
        $this->measItemKorNm = $measItemKorNm;
        $this->measItemEngNm = $measItemEngNm;
        $this->measItemOldKorNm = $measItemOldKorNm;
        $this->measItemUnitCcd = $measItemUnitCcd;
        $this->measItemCd = $measItemCd;
        $this->actionTypeCcd = $actionTypeCcd;
        $this->poseTypeCcd = $poseTypeCcd;
        $this->sizeTypeCcd = $sizeTypeCcd;
        $this->bodyPartUppCcd = $bodyPartUppCcd;
        $this->bodyPartCcd = $bodyPartCcd;
        $this->measDefCts = $measDefCts;
        $this->landmarkDesc = $landmarkDesc;
        $this->measMethod = $measMethod;
        $this->measPose = $measPose;
        $this->measTool = $measTool;
        $this->movUseYn = $movUseYn;
        $this->movPath = $movPath;
        $this->movDesc = $movDesc;
        $this->imgUseYn = $imgUseYn;
        $this->imgPath = $imgPath;
        $this->imgDesc = $imgDesc;
        $this->picUseYn = $picUseYn;
        $this->picPath = $picPath;
        $this->picDesc = $picDesc;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self(
            measItemSeq: (int) $data->measItemSeq,
            measItemTypeCcd: $data->measItemTypeCcd,
            measItemKorNm: $data->measItemKorNm,
            measItemEngNm: $data->measItemEngNm,
            measItemOldKorNm: $data->measItemOldKorNm ?? null,
            measItemUnitCcd: $data->measItemUnitCcd,
            measItemCd: $data->measItemCd,
            actionTypeCcd: $data->actionTypeCcd ?? null,
            poseTypeCcd: $data->poseTypeCcd ?? null,
            sizeTypeCcd: $data->sizeTypeCcd ?? null,
            bodyPartUppCcd: $data->bodyPartUppCcd ?? null,
            bodyPartCcd: $data->bodyPartCcd ?? null,
            measDefCts: $data->measDefCts,
            landmarkDesc: $data->landmarkDesc,
            measMethod: $data->measMethod,
            measPose: $data->measPose,
            measTool: $data->measTool,
            movUseYn: $data->movUseYn,
            movPath: $data->movPath ?? null,
            movDesc: $data->movDesc ?? null,
            imgUseYn: $data->imgUseYn,
            imgPath: $data->imgPath ?? null,
            imgDesc: $data->imgDesc ?? null,
            picUseYn: $data->picUseYn,
            picPath: $data->picPath ?? null,
            picDesc: $data->picDesc ?? null,
        );
    }

    public function getMeasItemSeq(): int
    {
        return $this->measItemSeq;
    }
    public function getMeasItemTypeCcd(): string
    {
        return $this->measItemTypeCcd;
    }
    public function getMeasItemKorNm(): string
    {
        return $this->measItemKorNm;
    }
    public function getMeasItemEngNm(): string
    {
        return $this->measItemEngNm;
    }
    public function getMeasItemOldKorNm(): ?string
    {
        return $this->measItemOldKorNm;
    }
    public function getMeasItemUnitCcd(): string
    {
        return $this->measItemUnitCcd;
    }
    public function getMeasItemCd(): string
    {
        return $this->measItemCd;
    }
    public function getActionTypeCcd(): ?string
    {
        return $this->actionTypeCcd;
    }
    public function getPoseTypeCcd(): ?string
    {
        return $this->poseTypeCcd;
    }
    public function getSizeTypeCcd(): ?string
    {
        return $this->sizeTypeCcd;
    }
    public function getBodyPartUppCcd(): ?string
    {
        return $this->bodyPartUppCcd;
    }
    public function getBodyPartCcd(): ?string
    {
        return $this->bodyPartCcd;
    }
    public function getMeasDefCts(): string
    {
        return $this->measDefCts;
    }
    public function getLandmarkDesc(): string
    {
        return $this->landmarkDesc;
    }
    public function getMeasMethod(): string
    {
        return $this->measMethod;
    }
    public function getMeasPose(): string
    {
        return $this->measPose;
    }
    public function getMeasTool(): string
    {
        return $this->measTool;
    }
    public function getMovUseYn(): string
    {
        return $this->movUseYn;
    }
    public function getMovPath(): ?string
    {
        return $this->movPath;
    }
    public function getMovDesc(): ?string
    {
        return $this->movDesc;
    }
    public function getImgUseYn(): string
    {
        return $this->imgUseYn;
    }
    public function getImgPath(): ?string
    {
        return $this->imgPath;
    }
    public function getImgDesc(): ?string
    {
        return $this->imgDesc;
    }
    public function getPicUseYn(): string
    {
        return $this->picUseYn;
    }
    public function getPicPath(): ?string
    {
        return $this->picPath;
    }
    public function getPicDesc(): ?string
    {
        return $this->picDesc;
    }
}
