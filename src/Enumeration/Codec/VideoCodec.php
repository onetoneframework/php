<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Codec;

/**
 * Video Codec Registry (FourCC)
 *
 * Maps codec case names to their FourCC (Four Character Code) identifiers.
 * Codec information provided by Kurohane (http://www.kurohane.net/)
 * for Shinkuu Hadoken, 2002-09-16.
 *
 * Backing value: 4-byte FourCC string (as stored in AVI/RIFF stream headers).
 *
 * Naming rules applied to produce valid PHP identifiers:
 *   - FourCC strings starting with a digit are prefixed with an underscore (_3IV0)
 *   - Trailing spaces are removed from the case name (value retains original)
 *   - Dots are replaced with underscores (Q1.0 → Q1_0)
 *   - All characters are upper-cased
 */
enum VideoCodec: string
{
    // -------------------------------------------------------------------------
    // Uncompressed
    // -------------------------------------------------------------------------

    /** NoCompress (DIB) */
    case DIB = 'DIB ';
    /** NoCompress (RGB) */
    case RGB = 'RGB ';
    /** NoCompress (RGB32) */
    case BGR = 'BGR ';
    /** NoCompress (RAW) */
    case RAW = 'RAW ';

    // -------------------------------------------------------------------------
    // Compressed
    // -------------------------------------------------------------------------

    /** 3ivx Delta 1.0-3.5 */
    case _3IV0 = '3IV0';
    /** 3ivx Delta 1.0-3.5 */
    case _3IV1 = '3IV1';
    /** 3ivx Delta 4.0 */
    case _3IV2 = '3IV2';
    /** 3ivx Delta 1-3 */
    case _3IVX = '3IVX';
    /** DivX3/MS MPEG4-V3 */
    case _3IVD = '3IVD';
    /** Planar RGB Codec */
    case _8BPS = '8BPS';
    /** Autodesk Animator */
    case AAS4 = 'AAS4';
    /** Autodesk Animator */
    case AASC = 'AASC';
    case ABYR = 'ABYR';
    /** Loronix WaveCodec */
    case ADV1 = 'ADV1';
    /** Avid M-JPEG */
    case ADVJ = 'ADVJ';
    /** Intel Indeo 3.2 */
    case AEIK = 'AEIK';
    /** Array VideoONE MPEG1-I Capture */
    case AEMI = 'AEMI';
    /** Autodesk Animator FLC */
    case AFLC = 'AFLC';
    /** Autodesk Animator FLI */
    case AFLI = 'AFLI';
    /** Animated JPEG */
    case AJPG = 'AJPG';
    /** AMV2 MT Codec */
    case AMM2 = 'AMM2';
    /** Array VideoONE MPEG */
    case AMPG = 'AMPG';
    /** AMV3 Codec */
    case AMV3 = 'AMV3';
    /** AMV4 Codec */
    case AMV4 = 'AMV4';
    /** Intel RDX */
    case ANIM = 'ANIM';
    /** AngelPotion MPEG4-V3 Hacked */
    case AP41 = 'AP41';
    /** Alparysoft Lossless Video Codec */
    case ASLC = 'ASLC';
    /** Asus Video */
    case ASV1 = 'ASV1';
    /** Asus Video (2) */
    case ASV2 = 'ASV2';
    /** Asus Video 2.0 */
    case ASVX = 'ASVX';
    /** Aura 2 YUV 422 */
    case AUR2 = 'AUR2';
    /** Aura 1 YUV 411 */
    case AURA = 'AURA';
    /** H.264 */
    case AVC1 = 'AVC1';
    /** Avid M-JPEG */
    case AVRN = 'AVRN';
    /** Raw 8bit RGB Bayer */
    case BA81 = 'BA81';
    /** Bink Video */
    case BINK = 'BINK';
    /** DivX 5.x */
    case BLZ0 = 'BLZ0';
    /** Conexant Prosumer Video */
    case BT20 = 'BT20';
    /** Conexant Composite Video */
    case BTCV = 'BTCV';
    /** Broadway MPEG Capture/Compression */
    case BW10 = 'BW10';
    /** Raw 8bit RGB Bayer */
    case BYR1 = 'BYR1';
    /** Raw 16bit RGB Bayer */
    case BYR2 = 'BYR2';
    /** Intel YUV12 */
    case CC12 = 'CC12';
    /** Canopus DV */
    case CDVC = 'CDVC';
    /** DPS Perception */
    case CFCC = 'CFCC';
    /** Adobe Premiere HDV */
    case CFHD = 'CFHD';
    /** Microsoft Camcorder Video */
    case CGDI = 'CGDI';
    /** Winnov Caviara Champagne */
    case CHAM = 'CHAM';
    /** Creative WebCam JPEG */
    case CJPG = 'CJPG';
    /** Cirrus Logic YUV */
    case CLJR = 'CLJR';
    /** Colorgraph 32Bit CMYK */
    case CMYK = 'CMYK';
    /** DivX3/MS MPEG4-V3 */
    case COL0 = 'COL0';
    /** DivX3/MS MPEG4-V3 */
    case COL1 = 'COL1';
    /** Weitek YUV 4:2:0 */
    case CPLA = 'CPLA';
    /** Microsoft Video 1 */
    case CRAM = 'CRAM';
    /** RenderSoft CamStudio lossless CODEC */
    case CSCD = 'CSCD';
    /** Citrix Scalable Video Codec */
    case CTRX = 'CTRX';
    /** Radius Cinepak */
    case CVID = 'CVID';
    /** Microsoft Color WLT DIB */
    case CWLT = 'CWLT';
    /** Conexant YUV 4:1:1 */
    case CXY1 = 'CXY1';
    /** Conexant YUV 4:2:2 */
    case CXY2 = 'CXY2';
    /** Creative YUV */
    case CYUV = 'CYUV';
    /** ATI YUV */
    case CYUY = 'CYUY';
    /** DEC H.261 */
    case D261 = 'D261';
    /** DEC H.263 */
    case D263 = 'D263';
    /** H.264 (DAVC) */
    case DAVC = 'DAVC';
    /** Data Connection Conferencing Codec */
    case DCL1 = 'DCL1';
    /** Data Connection Multimedia Conferencing Codec */
    case DCL2 = 'DCL2';
    /** Data Connection Enhanced Conferencing Codec */
    case DCL3 = 'DCL3';
    /** Data Connection Extended Conferencing Codec */
    case DCL4 = 'DCL4';
    /** Data Connection Media Conferencing Codec */
    case DCL5 = 'DCL5';
    /** DivA MPEG-4 */
    case DIVA = 'DIVA';
    /** DivX4 */
    case DIVX = 'DIVX';
    /** Microsoft MPEG4-V1 */
    case DIV1 = 'DIV1';
    /** Microsoft MPEG4-V1/V2 */
    case DIV2 = 'DIV2';
    /** DivX ;-) MPEG-4 Video Codec (low motion) */
    case DIV3 = 'DIV3';
    /** DivX ;-) MPEG-4 Video Codec (fast motion) */
    case DIV4 = 'DIV4';
    /** DivX5 */
    case DIV5 = 'DIV5';
    /** DivX */
    case DIV6 = 'DIV6';
    /** DivX */
    case DIVF = 'DIVF';
    /** DV Codec "Iris" */
    case DVIS = 'DVIS';
    /** DV Codec "Iris" Reference AVI */
    case DVRS = 'DVRS';
    /** DV Codec "Iris" [vdsd] */
    case DVSD = 'DVSD';
    /** Dicas MPEGable MPEG-4 */
    case DM4V = 'DM4V';
    /** Matrox Rainbow Runner MJPEG */
    case DMB1 = 'DMB1';
    case DMB2 = 'DMB2';
    /** ViewSonic V36 */
    case DMK2 = 'DMK2';
    /** DummyCodec */
    case DOHC = 'DOHC';
    /** DynaPel MPEG-4 */
    case DP02 = 'DP02';
    /** DPS Reality */
    case DPS0 = 'DPS0';
    /** DPS PAR */
    case DPSC = 'DPSC';
    /** DV Codec */
    case DSVD = 'DSVD';
    /** Duck TrueMotion 1.0 */
    case DUCK = 'DUCK';
    /** Matrox DVCPRO */
    case DV25 = 'DV25';
    /** Matrox DVCPRO50 */
    case DV50 = 'DV50';
    case DVAN = 'DVAN';
    /** DV Codec */
    case DVC = 'DVC ';
    /** DV Codec */
    case DVCP = 'DVCP';
    /** DV Codec */
    case DVCS = 'DVCS';
    /** Insoft DVE-2 Videoconferencing */
    case DVE2 = 'DVE2';
    /** Panasonic SMPTE 370M */
    case DVH1 = 'DVH1';
    /** DV Codec */
    case DVHD = 'DVHD';
    /** Darim Vision DVMPEG */
    case DVMA = 'DVMA';
    /** DV Codec */
    case DVSL = 'DVSL';
    /** Lucent DVX1000SP */
    case DVX1 = 'DVX1';
    /** Lucent DVX2000S */
    case DVX2 = 'DVX2';
    /** Lucent DVX3000S */
    case DVX3 = 'DVX3';
    /** DivX5 */
    case DX50 = 'DX50';
    /** EA/CinemaWare Game Movie */
    case DXGM = 'DXGM';
    /** DirectX Compressed Texture DXT1 */
    case DXT1 = 'DXT1';
    /** DirectX Compressed Texture DXT2 */
    case DXT2 = 'DXT2';
    /** DirectX Compressed Texture DXT3 */
    case DXT3 = 'DXT3';
    /** DirectX Compressed Texture DXT4 */
    case DXT4 = 'DXT4';
    /** DirectX Compressed Texture DXT5 */
    case DXT5 = 'DXT5';
    /** DirectX Texture Compression */
    case DXTC = 'DXTC';
    /** DirectX Compressed Texture */
    case DXTN = 'DXTN';
    /** Elsa YUV */
    case EKQ0 = 'EKQ0';
    /** Elsa YUV */
    case ELK0 = 'ELK0';
    /** Etymonix MPEG-2 I-frame */
    case EM2V = 'EM2V';
    /** Eyestream 7 Codec */
    case ES07 = 'ES07';
    /** Eidos Escape */
    case ESCP = 'ESCP';
    /** eTreppid Video */
    case ETV1 = 'ETV1';
    /** eTreppid Video */
    case ETV2 = 'ETV2';
    /** eTreppid Video */
    case ETVC = 'ETVC';
    /** FFMPEG */
    case FFV1 = 'FFV1';
    /** D-Vision Field Encoded Motion JPEG */
    case FLJP = 'FLJP';
    /** FlashVideo VP6 (ffdshow) */
    case FLV1 = 'FLV1';
    /** FlashVideo VP6 (ffdshow) */
    case FLV4 = 'FLV4';
    /** FFmpeg MPEG4 */
    case FMP4 = 'FMP4';
    /** FM Screen Capture Codec */
    case FMVC = 'FMVC';
    /** Fraps Movie Capture */
    case FPS1 = 'FPS1';
    /** SoftLab-Nsk Forward Motion JPEG with alpha channel */
    case FRWA = 'FRWA';
    /** SoftLab-Nsk Forward Motion JPEG */
    case FRWD = 'FRWD';
    /** SoftLab-Nsk Forward Motion JPEG */
    case FRWT = 'FRWT';
    /** Darim Vision Forward */
    case FRWU = 'FRWU';
    /** Fractal Video Frame */
    case FVF1 = 'FVF1';
    /** FFVFW */
    case FVFW = 'FVFW';
    /** GEOMPEG4 */
    case GEOX = 'GEOX';
    /** GT891x Codec */
    case GJPG = 'GJPG';
    /** GigaLink Video Codec */
    case GLCC = 'GLCC';
    /** Motion LZW */
    case GLZW = 'GLZW';
    /** Motion JPEG */
    case GPEG = 'GPEG';
    /** Microsoft Greyscale WLT DIB */
    case GWLT = 'GWLT';
    /** Intel ITU H.260 */
    case H260 = 'H260';
    /** Intel ITU H.261 */
    case H261 = 'H261';
    /** Intel ITU H.262 */
    case H262 = 'H262';
    /** Intel ITU H.263 */
    case H263 = 'H263';
    /** Intel ITU H.264 */
    case H264 = 'H264';
    /** Intel ITU H.265 */
    case H265 = 'H265';
    /** Intel ITU H.266 */
    case H266 = 'H266';
    /** Intel ITU H.267 */
    case H267 = 'H267';
    /** Intel ITU H.268 */
    case H268 = 'H268';
    /** Intel ITU H.269 */
    case H269 = 'H269';
    /** Raw YUV 4:2:2 */
    case HDYC = 'HDYC';
    /** Ben Rudiak-Gould Huffyuv */
    case HFYU = 'HFYU';
    /** Rendition Motion Compensation Format */
    case HMCR = 'HMCR';
    /** Rendition Motion Compensation Format */
    case HMRR = 'HMRR';
    /** Huffyuv MT */
    case HYMT = 'HYMT';
    /** Intel ITU H.263 */
    case I263 = 'I263';
    /** Intel Indeo 4 */
    case I420 = 'I420';
    /** Intel RDX */
    case IAN = 'IAN ';
    /** InSoft CellB Videoconferencing */
    case ICLB = 'ICLB';
    /** Intel Intermediate YUV9 */
    case IF09 = 'IF09';
    /** Power DVD */
    case IGOR = 'IGOR';
    /** Intergraph JPEG */
    case IJPG = 'IJPG';
    /** Intel Layered Video */
    case ILVC = 'ILVC';
    /** ITU H.263+ */
    case ILVR = 'ILVR';
    /** I/O DATA Giga AVI DV */
    case IPDV = 'IPDV';
    /** Image Power JPEG2000 */
    case IPJ2 = 'IPJ2';
    /** Intel Indeo 2.1 */
    case IR21 = 'IR21';
    case IR45 = 'IR45';
    /** Intel Uncompressed UYUV */
    case IRAW = 'IRAW';
    case ISME = 'ISME';
    /** Intel Indeo 3 */
    case IV30 = 'IV30';
    /** Intel Indeo 3.1 */
    case IV31 = 'IV31';
    /** Intel Indeo 3.2 */
    case IV32 = 'IV32';
    /** Intel Indeo 3.3 */
    case IV33 = 'IV33';
    /** Intel Indeo 3.4 */
    case IV34 = 'IV34';
    /** Intel Indeo 3.5 */
    case IV35 = 'IV35';
    /** Intel Indeo 3.6 */
    case IV36 = 'IV36';
    /** Intel Indeo 3.7 */
    case IV37 = 'IV37';
    /** Intel Indeo 3.8 */
    case IV38 = 'IV38';
    /** Intel Indeo 3.9 */
    case IV39 = 'IV39';
    /** Intel Indeo 4.0 */
    case IV40 = 'IV40';
    /** Intel Indeo 4.1 */
    case IV41 = 'IV41';
    /** Intel Indeo 4.2 */
    case IV42 = 'IV42';
    /** Intel Indeo 4.3 */
    case IV43 = 'IV43';
    /** Intel Indeo 4.4 */
    case IV44 = 'IV44';
    /** Intel Indeo 4.5 */
    case IV45 = 'IV45';
    /** Intel Indeo 4.6 */
    case IV46 = 'IV46';
    /** Intel Indeo 4.7 */
    case IV47 = 'IV47';
    /** Intel Indeo 4.8 */
    case IV48 = 'IV48';
    /** Intel Indeo 4.9 */
    case IV49 = 'IV49';
    /** Intel Indeo 5.0 */
    case IV50 = 'IV50';
    /** Intel Indeo iYUV R2.0 */
    case IYUV = 'IYUV';
    case JBYR = 'JBYR';
    /** Microsoft StillImage JPEG */
    case JPEG = 'JPEG';
    /** DIVIO JPEG Light */
    case JPGL = 'JPGL';
    /** Karl Morton's Video */
    case KMVC = 'KMVC';
    /** Lead H.261 */
    case L261 = 'L261';
    /** Lead H.263 */
    case L263 = 'L263';
    case LBYR = 'LBYR';
    /** Lead Motion CMW */
    case LCMW = 'LCMW';
    /** LEAD MJPEG2000 */
    case LCW2 = 'LCW2';
    /** LEAD Video */
    case LEAD = 'LEAD';
    /** LEAD Grayscale Image */
    case LGRY = 'LGRY';
    /** LEAD JPEG 4:1:1 */
    case LJ11 = 'LJ11';
    /** LEAD JPEG 4:2:2 */
    case LJ22 = 'LJ22';
    /** LEAD JPEG 2000 */
    case LJ2K = 'LJ2K';
    /** LEAD JPEG 4:4:4 */
    case LJ44 = 'LJ44';
    /** LEAD MJPEG */
    case LJPG = 'LJPG';
    /** LEAD MPEG-2 Video Codec */
    case LMP2 = 'LMP2';
    /** LEAD MPEG-4 Video Codec */
    case LMP4 = 'LMP4';
    /** Lightning Strike Video Codec */
    case LSVC = 'LSVC';
    /** Vianet Lighting Strike Vmail */
    case LSVM = 'LSVM';
    /** Lightning Strike Video Codec */
    case LSVX = 'LSVX';
    /** Lempel-Ziv-Oberhumer */
    case LZO1 = 'LZO1';
    /** Microsoft H.261 */
    case M261 = 'M261';
    /** Microsoft H.263 */
    case M263 = 'M263';
    /** Divio MPEG-4 */
    case M4CC = 'M4CC';
    /** MPEG-4 version 2 simple profile */
    case M4S2 = 'M4S2';
    /** ATI Motion Compensation Format */
    case MC12 = 'MC12';
    /** ATI Motion Compensation Format */
    case MCAM = 'MCAM';
    /** Motion JPEG2000 */
    case MJ2C = 'MJ2C';
    /** Pinnacle ReelTime MJPG-A Software */
    case MJPA = 'MJPA';
    /** Motion JPEG */
    case MJPG = 'MJPG';
    /** Matrox MPEG-2 ES */
    case MMES = 'MMES';
    /** Media Excel MPEG-2 Audio */
    case MP2A = 'MP2A';
    /** Media Excel MPEG-2 Transport Stream */
    case MP2T = 'MP2T';
    /** Media Excel MPEG-2 Video */
    case MP2V = 'MP2V';
    /** Microsoft MPEG4-V2 */
    case MP42 = 'MP42';
    /** Microsoft MPEG4-V3 */
    case MP43 = 'MP43';
    /** Media Excel MPEG-4 Audio */
    case MP4A = 'MP4A';
    /** Microsoft MPEG4 */
    case MP4S = 'MP4S';
    /** Media Excel MPEG-4 Transport Stream */
    case MP4T = 'MP4T';
    /** Media Excel MPEG-4 Video */
    case MP4V = 'MP4V';
    /** Chromatic MPEG1 Video I Frame */
    case MPEG = 'MPEG';
    /** DivX3/MS MPEG4-V3 */
    case MPG3 = 'MPG3';
    /** Microsoft MPEG4-V1 */
    case MPG4 = 'MPG4';
    /** Sigma Designs MPEG */
    case MPGI = 'MPGI';
    /** PNG images decoder */
    case MPNG = 'MPNG';
    case MR16 = 'MR16';
    /** FAST Multimedia Mrcodec */
    case MRCA = 'MRCA';
    /** Microsoft RLE */
    case MRLE = 'MRLE';
    /** Microsoft Video1 */
    case MSVC = 'MSVC';
    /** LCL AVImszh */
    case MSZH = 'MSZH';
    /** TGA images decoder */
    case MTGA = 'MTGA';
    /** Matrox */
    case MTX1 = 'MTX1';
    /** Matrox */
    case MTX2 = 'MTX2';
    /** Matrox */
    case MTX3 = 'MTX3';
    /** Matrox */
    case MTX4 = 'MTX4';
    /** Matrox */
    case MTX5 = 'MTX5';
    /** Matrox */
    case MTX6 = 'MTX6';
    /** Matrox */
    case MTX7 = 'MTX7';
    /** Matrox */
    case MTX8 = 'MTX8';
    /** Matrox */
    case MTX9 = 'MTX9';
    case MV12 = 'MV12';
    /** Motion Pixels MVI1 */
    case MVI1 = 'MVI1';
    /** Motion Pixels MVI2 */
    case MVI2 = 'MVI2';
    /** Aware Motion Wavelets */
    case MWV1 = 'MWV1';
    case NAVI = 'NAVI';
    /** Nero Digital Cinema */
    case NDSC = 'NDSC';
    /** Nero MPEG4 */
    case NDSM = 'NDSM';
    /** Nero Digital Portable */
    case NDSP = 'NDSP';
    /** Nero Digital Standard */
    case NDSS = 'NDSS';
    /** Nero Digital AVC Cinema */
    case NDXC = 'NDXC';
    /** Nero Digital AVC HDTV */
    case NDXH = 'NDXH';
    /** Nero Digital AVC Portable */
    case NDXP = 'NDXP';
    /** Nero Digital AVC Standard */
    case NDXS = 'NDXS';
    /** NVidia Texture Format */
    case NHVU = 'NHVU';
    /** NewTek LightWave HDTV */
    case NT00 = 'NT00';
    /** Nogatech Video Compression 1 */
    case NTN1 = 'NTN1';
    /** Nogatech Video Compression 2 */
    case NTN2 = 'NTN2';
    /** netsuzo */
    case NTZ0 = 'NTZ0';
    /** netsuzo */
    case NTZO = 'NTZO';
    /** NuppelVideo */
    case NUV1 = 'NUV1';
    /** nVIDIA GeForce3 Texture */
    case NVDS = 'NVDS';
    /** nVIDIA GeForce3 Texture */
    case NVHS = 'NVHS';
    /** nVIDIA GeForce3 Texture */
    case NVHU = 'NVHU';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVS0 = 'NVS0';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVS1 = 'NVS1';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVS2 = 'NVS2';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVS3 = 'NVS3';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVS4 = 'NVS4';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVS5 = 'NVS5';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVT0 = 'NVT0';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVT1 = 'NVT1';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVT2 = 'NVT2';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVT3 = 'NVT3';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVT4 = 'NVT4';
    /** nVIDIA GeForce2 GTS Pro Texture */
    case NVT5 = 'NVT5';
    /** I/O DATA DVC */
    case PDVC = 'PDVC';
    /** Radius Video Vision */
    case PGVV = 'PGVV';
    /** IBM Photomotion */
    case PHMO = 'PHMO';
    /** Pegasus Imaging Lossless JPEG */
    case PIM1 = 'PIM1';
    /** Pegasus Imaging Lossless JPEG */
    case PIM2 = 'PIM2';
    /** Pegasus Imaging Lossless JPEG */
    case PIMJ = 'PIMJ';
    /** Pinnacle Video XL */
    case PIXL = 'PIXL';
    /** PA MJPEG */
    case PJPG = 'PJPG';
    /** CorePNG v8 */
    case PNG1 = 'PNG1';
    /** Horizons Technology PowerEZ */
    case PVEZ = 'PVEZ';
    /** PacketVideo Corporation MPEG-4 */
    case PVMM = 'PVMM';
    /** Pegasus Wavelet Compression */
    case PVWV = 'PVWV';
    /** Pegasus Wavelet Compression */
    case PVW2 = 'PVW2';
    /** Q-Team QPEG */
    case Q1_0 = 'Q1.0';
    /** Q-Team QPEG */
    case Q1_1 = 'Q1.1';
    /** Q-Team QPEG */
    case QPEG = 'QPEG';
    /** Q-Team QPEG */
    case QPEQ = 'QPEQ';
    /** GTRON ReferenceAVI */
    case RAVI = 'RAVI';
    /** GTRON ReferenceAVI */
    case RAV_ = 'RAV_';
    /** Computer Concepts 32Bits RGB */
    case RGBT = 'RGBT';
    /** Microsoft RLE */
    case RLE = 'RLE ';
    /** Microsoft RLE4 */
    case RLE4 = 'RLE4';
    /** Microsoft RLE8 */
    case RLE8 = 'RLE8';
    /** REALMagic MPEG4 */
    case RMP4 = 'RMP4';
    /** Id RoQ File Video Decoder */
    case ROQV = 'ROQV';
    /** Apple Video */
    case RPZA = 'RPZA';
    /** Intel Real Time Video 2.1 */
    case RT21 = 'RT21';
    /** NewTek VideoToaster */
    case RTV0 = 'RTV0';
    /** nico Rududu */
    case RUD0 = 'RUD0';
    /** RushCodec */
    case RUSH = 'RUSH';
    /** RealVideo 1.0 */
    case RV10 = 'RV10';
    /** Real G2 */
    case RV20 = 'RV20';
    /** Real 8 */
    case RV30 = 'RV30';
    /** Real 9/10 */
    case RV40 = 'RV40';
    /** Intel RDX */
    case RVX = 'RVX ';
    /** Tekram VideoCap C210 YUV */
    case S422 = 'S422';
    /** DivX 3.11a Copy */
    case SAN3 = 'SAN3';
    /** Sun Digital Camera */
    case SDCC = 'SDCC';
    /** Samsung MPEG-4 */
    case SEDG = 'SEDG';
    /** CrystalNet Surface Fitting Method */
    case SFMC = 'SFMC';
    /** Huffyuvs v2.1.1 */
    case SHYU = 'SHYU';
    /** White Pine */
    case SJPG = 'SJPG';
    /** Apple Graphics */
    case SMC = 'SMC ';
    case SMP4 = 'SMP4';
    /** Radius Proprietary */
    case SMSC = 'SMSC';
    /** Radius Proprietary */
    case SMSD = 'SMSD';
    /** WorldConnect Wavelet Video */
    case SMSV = 'SMSV';
    case SNOW = 'SNOW';
    /** Sunplus SP40 */
    case SP40 = 'SP40';
    /** Sunplus SP44 */
    case SP44 = 'SP44';
    /** Aiptek MegaCam */
    case SP53 = 'SP53';
    /** Aiptek MegaCam */
    case SP54 = 'SP54';
    /** Aiptek MegaCam */
    case SP55 = 'SP55';
    /** Aiptek MegaCam */
    case SP56 = 'SP56';
    /** Aiptek MegaCam */
    case SP57 = 'SP57';
    /** Aiptek MegaCam */
    case SP58 = 'SP58';
    /** Radius Spigot */
    case SPIG = 'SPIG';
    /** Splash Studios ACM */
    case SPLC = 'SPLC';
    /** Microsoft VXTreme V2 */
    case SQZ2 = 'SQZ2';
    /** ST CMOS Imager Data (Bayer) */
    case STVA = 'STVA';
    /** ST CMOS Imager Data (Nudged Bayer) */
    case STVB = 'STVB';
    /** ST CMOS Imager Data (Bunched) */
    case STVC = 'STVC';
    /** ST CMOS Imager Data (Extended CODEC Data Format) */
    case STVX = 'STVX';
    /** ST CMOS Imager Data (Extended CODEC Data Format with Correction Data) */
    case STVY = 'STVY';
    /** Sorenson Video R1 */
    case SV10 = 'SV10';
    /** Sorenson Video 1 */
    case SVQ1 = 'SVQ1';
    /** Sorenson Video 3 */
    case SVQ3 = 'SVQ3';
    /** Toshiba YUV 4:2:0 & 4:1:1 */
    case T420 = 'T420';
    /** TeraLogic Motion Intraframe */
    case TLMS = 'TLMS';
    /** TeraLogic Motion Intraframe */
    case TLST = 'TLST';
    /** Duck TrueMotion 2.0 */
    case TM20 = 'TM20';
    /** On2 TrueMotion2X */
    case TM2A = 'TM2A';
    /** On2 TrueMotion2X */
    case TM2X = 'TM2X';
    /** TeraLogic Motion Intraframe */
    case TMIC = 'TMIC';
    /** Horizons Technology TrueMotion S */
    case TMOT = 'TMOT';
    /** Duck TrueMotion RT 2.0 */
    case TR20 = 'TR20';
    /** TechSmith Screen Capture */
    case TSCC = 'TSCC';
    /** Tecomac Low-Bit Rate */
    case TV10 = 'TV10';
    /** Truevision TARGA 2000 */
    case TVJP = 'TVJP';
    /** Truevision TARGA 2000 */
    case TVMJ = 'TVMJ';
    /** Trident Decompression */
    case TY0N = 'TY0N';
    /** Trident Decompression */
    case TY2C = 'TY2C';
    /** Trident Decompression */
    case TY2N = 'TY2N';
    /** UB Video StreamForce */
    case U263 = 'U263';
    /** eMagix ClearVideo */
    case UCOD = 'UCOD';
    /** IBM Ultimotion */
    case ULTI = 'ULTI';
    /** Ut Video Codec RGB */
    case ULRG = 'ULRG';
    /** Ut Video Codec YUV420 */
    case ULY0 = 'ULY0';
    /** Ut Video Codec YUV422 */
    case ULY2 = 'ULY2';
    /** DivX3/MS MPEG4-V1 */
    case UMP4 = 'UMP4';
    /** Microsoft UYVY 4:2:2 */
    case UYVY = 'UYVY';
    /** AJA Video Systems 10-bit 4:2:2 Component YCbCr */
    case V210 = 'V210';
    /** Lucent VX2000S */
    case V261 = 'V261';
    /** Vitec Multimedia 24bit YUV */
    case V422 = 'V422';
    /** Vitec Multimedia 16bit YUV */
    case V655 = 'V655';
    /** ATI Video Codec 1.0 */
    case VCR1 = 'VCR1';
    /** ATI Video Codec 2.0 */
    case VCR2 = 'VCR2';
    /** ATI Video Codec 3.0 */
    case VCR3 = 'VCR3';
    /** ATI Video Codec 4.0 */
    case VCR4 = 'VCR4';
    /** ATI Video Codec 5.0 */
    case VCR5 = 'VCR5';
    /** ATI Video Codec 6.0 */
    case VCR6 = 'VCR6';
    /** ATI Video Codec 7.0 */
    case VCR7 = 'VCR7';
    /** ATI Video Codec 8.0 */
    case VCR8 = 'VCR8';
    /** ATI Video Codec 9.0 */
    case VCR9 = 'VCR9';
    /** Vitec Video Maker Pro DIB */
    case VDCT = 'VDCT';
    case VDEC = 'VDEC';
    /** VDOWave */
    case VDOM = 'VDOM';
    /** VDOLive */
    case VDOW = 'VDOW';
    /** VirtualDub remote frameclient */
    case VDST = 'VDST';
    /** Darim Vision VideoTizer YUV */
    case VDTZ = 'VDTZ';
    /** Alaris Wee Cam */
    case VGPX = 'VGPX';
    /** Vitec YUV 4:2:2 CCIR 601 for V422 */
    case VIDS = 'VIDS';
    /** VFAPI Codec */
    case VIFP = 'VIFP';
    /** Vivo H.263 */
    case VIVO = 'VIVO';
    /** Miro Video XL */
    case VIXL = 'VIXL';
    case VLV1 = 'VLV1';
    /** On2 Open-Source VP3 */
    case VP30 = 'VP30';
    /** On2 Open-Source VP3 */
    case VP31 = 'VP31';
    /** On2 VP4 */
    case VP40 = 'VP40';
    /** On2 VP5 */
    case VP50 = 'VP50';
    /** On2 VP6 Simple */
    case VP60 = 'VP60';
    /** On2 VP6 Advanced */
    case VP61 = 'VP61';
    /** On2 VP6 */
    case VP62 = 'VP62';
    /** On2 VP6 (ffdshow) */
    case VP6F = 'VP6F';
    /** On2 VP7 */
    case VP70 = 'VP70';
    /** H.264 (VSSH) */
    case VSSH = 'VSSH';
    /** ViewQuest VideoQuest Codec 1 */
    case VQC1 = 'VQC1';
    /** ViewQuest VideoQuest Codec 2 */
    case VQC2 = 'VQC2';
    case VQJC = 'VQJC';
    /** Vanguard Software VSS Video */
    case VSSV = 'VSSV';
    /** Alaris VideoGram QuickVideo VGPixel */
    case VTLP = 'VTLP';
    case VUUU = 'VUUU';
    /** Lucent VX1000S */
    case VX1K = 'VX1K';
    /** Lucent VX2000S */
    case VX2K = 'VX2K';
    /** Lucent VX1000SP */
    case VXSP = 'VXSP';
    /** ATI YUV */
    case VYU9 = 'VYU9';
    /** ATI YUV */
    case VYUY = 'VYUY';
    /** Winbond W9960 */
    case WBVC = 'WBVC';
    /** Microsoft Video 1 */
    case WHAM = 'WHAM';
    /** Winnov Software Compression */
    case WINX = 'WINX';
    /** AverMedia USB TV-tuner/capture */
    case WJPG = 'WJPG';
    /** Windows Media Video 7 */
    case WMV1 = 'WMV1';
    /** Windows Media Video 8 */
    case WMV2 = 'WMV2';
    /** Windows Media Video 9 */
    case WMV3 = 'WMV3';
    /** WMV9 Advanced Profile */
    case WMVA = 'WMVA';
    /** WMV9 Advanced Profile */
    case WVC1 = 'WVC1';
    /** WniWni Video3 */
    case WNIX = 'WNIX';
    /** Winnov Hardware Compression */
    case WNV1 = 'WNV1';
    /** VideoTools VideoServer Client Codec */
    case WRPR = 'WRPR';
    /** Xirlink H.263 Video Codec */
    case X263 = 'X263';
    /** H.264 (X264) */
    case X264 = 'X264';
    /** NetXL XL Video Decoder */
    case XLV0 = 'XLV0';
    /** Videologic VLCAP */
    case XLV1 = 'XLV1';
    /** XING IFlameOnly MPEG */
    case XMPG = 'XMPG';
    /** Dxtory */
    case XTOR = 'XTOR';
    /** XviD MPEG4 */
    case XVID = 'XVID';
    /** DivX3/MS MPEG4-V2 */
    case XVIX = 'XVIX';
    /** XiWave Video */
    case XWV0 = 'XWV0';
    /** XiWave Video */
    case XWV1 = 'XWV1';
    /** XiWave Video */
    case XWV2 = 'XWV2';
    /** XiWave Video */
    case XWV3 = 'XWV3';
    /** XiWave Video */
    case XWV4 = 'XWV4';
    /** XiWave Video */
    case XWV5 = 'XWV5';
    /** XiWave Video */
    case XWV6 = 'XWV6';
    /** XiWave Video */
    case XWV7 = 'XWV7';
    /** XiWave Video */
    case XWV8 = 'XWV8';
    /** XiWave Video */
    case XWV9 = 'XWV9';
    case XXAN = 'XXAN';
    /** 16bpp Grayscale Video */
    case Y16 = 'Y16 ';
    /** Microsoft YUV 2:1:1 */
    case Y211 = 'Y211';
    /** Microsoft YUV 4:1:1 Packed */
    case Y411 = 'Y411';
    /** Microsoft YUV 4:1:1 Planar */
    case Y41B = 'Y41B';
    /** Brooktree YUV 4:1:1 */
    case Y41P = 'Y41P';
    /** Brooktree PCI 4:1:1 with transparency */
    case Y41T = 'Y41T';
    /** Weitek YUV 4:2:2 */
    case Y42B = 'Y42B';
    /** Brooktree PCI 4:2:2 with transparency */
    case Y42T = 'Y42T';
    case Y444 = 'Y444';
    /** Grayscale video */
    case Y8 = 'Y8  ';
    /** Intel YUV12 */
    case YC12 = 'YC12';
    /** Intel YUV */
    case YU92 = 'YU92';
    /** Winnov Caviar YUV8 */
    case YUV8 = 'YUV8';
    /** Indeo Video Raw */
    case YUV9 = 'YUV9';
    /** Uncompressed YCrCb 4:2:2 */
    case YUVP = 'YUVP';
    /** Microsoft Raw uncompressed YUV 4:2:2 */
    case YUY2 = 'YUY2';
    /** Canopus YUVY */
    case YUYV = 'YUYV';
    /** Weitek YVU12 */
    case YV12 = 'YV12';
    /** Elecard YUV 4:2:2 Planar */
    case YV16 = 'YV16';
    /** Intel Smart Video Recorder */
    case YV92 = 'YV92';
    /** Indeo Video Raw */
    case YVU9 = 'YVU9';
    /** Microsoft YVYU 4:2:2 */
    case YVYU = 'YVYU';
    /** LCL AVIzlib */
    case ZLIB = 'ZLIB';
    /** DoxBox Capture Codec */
    case ZMBV = 'ZMBV';
    /** Metheus Video Zipper */
    case ZPEG = 'ZPEG';
    /** ZyGo ZyGoVideo */
    case ZYGO = 'ZYGO';
    case ZYYY = 'ZYYY';

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Returns the human-readable codec description.
     */
    public function label(): string
    {
        return match ($this) {
            self::DIB => 'NoCompress (DIB)',
            self::RGB => 'NoCompress (RGB)',
            self::BGR => 'NoCompress (RGB32)',
            self::RAW => 'NoCompress (RAW)',
            self::_3IV0 => '3ivx Delta 1.0-3.5 (3IV0)',
            self::_3IV1 => '3ivx Delta 1.0-3.5 (3IV1)',
            self::_3IV2 => '3ivx Delta 4.0',
            self::_3IVX => '3ivx Delta 1-3',
            self::_3IVD => 'DivX3/MS MPEG4-V3 (3IVD)',
            self::_8BPS => 'Planar RGB Codec (8BPS)',
            self::AAS4 => 'Autodesk Animator (AAS4)',
            self::AASC => 'Autodesk Animator (AASC)',
            self::ABYR => 'Kensington (ABYR)',
            self::ADV1 => 'Loronix WaveCodec',
            self::ADVJ => 'Avid M-JPEG (ADVJ)',
            self::AEIK => 'Intel Indeo 3.2',
            self::AEMI => 'Array VideoONE MPEG1-I Capture',
            self::AFLC => 'Autodesk Animator (FLC)',
            self::AFLI => 'Autodesk Animator (FLI)',
            self::AJPG => 'Animated JPEG',
            self::AMM2 => 'AMV2 MT Codec',
            self::AMPG => 'Array VideoONE MPEG',
            self::AMV3 => 'AMV3 Codec',
            self::AMV4 => 'AMV4 Codec',
            self::ANIM => 'Intel RDX (ANIM)',
            self::AP41 => 'AngelPotion MPEG4-V3 Hacked',
            self::ASLC => 'Alparysoft Lossless Video Codec',
            self::ASV1 => 'Asus Video',
            self::ASV2 => 'Asus Video (2)',
            self::ASVX => 'Asus Video 2.0',
            self::AUR2 => 'Aura 2 YUV 422',
            self::AURA => 'Aura 1 YUV 411',
            self::AVC1 => 'H.264 (AVC1)',
            self::AVRN => 'Avid M-JPEG (AVRN)',
            self::BA81 => 'Raw 8bit RGB Bayer (BA81)',
            self::BINK => 'Bink Video',
            self::BLZ0 => 'DivX 5.x (BLZ0)',
            self::BT20 => 'Conexant Prosumer Video',
            self::BTCV => 'Conexant Composite Video',
            self::BW10 => 'Broadway MPEG Capture/Compression',
            self::BYR1 => 'Raw 8bit RGB Bayer (BYR1)',
            self::BYR2 => 'Raw 16bit RGB Bayer (BYR2)',
            self::CC12 => 'Intel YUV12',
            self::CDVC => 'Canopus DV',
            self::CFCC => 'DPS Perception',
            self::CFHD => 'Adobe Premiere HDV',
            self::CGDI => 'Microsoft Camcorder Video',
            self::CHAM => 'Winnov Caviara Champagne',
            self::CJPG => 'Creative WebCam JPEG',
            self::CLJR => 'Cirrus Logic YUV',
            self::CMYK => 'Colorgraph 32Bit CMYK',
            self::COL0 => 'DivX3/MS MPEG4-V3 (COL0)',
            self::COL1 => 'DivX3/MS MPEG4-V3 (COL1)',
            self::CPLA => 'Weitek YUV 4:2:0',
            self::CRAM => 'Microsoft Video 1 (CRAM)',
            self::CSCD => 'RenderSoft CamStudio Lossless CODEC',
            self::CTRX => 'Citrix Scalable Video Codec (CTRX)',
            self::CVID => 'Radius Cinepak',
            self::CWLT => 'Microsoft Color WLT DIB',
            self::CXY1 => 'Conexant YUV 4:1:1 (CXY1)',
            self::CXY2 => 'Conexant YUV 4:2:2 (CXY2)',
            self::CYUV => 'Creative YUV',
            self::CYUY => 'ATI YUV',
            self::D261 => 'DEC H.261',
            self::D263 => 'DEC H.263',
            self::DAVC => 'H.264 (DAVC)',
            self::DCL1 => 'Data Connection Conferencing Codec',
            self::DCL2 => 'Data Connection Multimedia Conferencing Codec',
            self::DCL3 => 'Data Connection Enhanced Conferencing Codec',
            self::DCL4 => 'Data Connection Extended Conferencing Codec',
            self::DCL5 => 'Data Connection Media Conferencing Codec',
            self::DIVA => 'DivA MPEG-4',
            self::DIVX => 'DivX4',
            self::DIV1 => 'Microsoft MPEG4-V1',
            self::DIV2 => 'Microsoft MPEG4-V1/V2',
            self::DIV3 => 'DivX ;-) MPEG-4 Video Codec (low motion)',
            self::DIV4 => 'DivX ;-) MPEG-4 Video Codec (fast motion)',
            self::DIV5 => 'DivX5 (DIV5)',
            self::DIV6 => 'DivX (DIV6)',
            self::DIVF => 'DivX (DIVF)',
            self::DVIS => 'DV Codec "Iris"',
            self::DVRS => 'DV Codec "Iris" Reference AVI',
            self::DVSD => 'DV Codec "Iris" [vdsd]',
            self::DM4V => 'Dicas MPEGable MPEG-4',
            self::DMB1 => 'Matrox Rainbow Runner MJPEG',
            self::DMB2 => 'Paradigm MJPG',
            self::DMK2 => 'ViewSonic V36',
            self::DOHC => 'DummyCodec',
            self::DP02 => 'DynaPel MPEG-4',
            self::DPS0 => 'DPS Reality',
            self::DPSC => 'DPS PAR',
            self::DSVD => 'DV Codec (DSVD)',
            self::DUCK => 'Duck TrueMotion 1.0',
            self::DV25 => 'Matrox DVCPRO',
            self::DV50 => 'Matrox DVCPRO50',
            self::DVAN => '(DVAN)',
            self::DVC => 'DV Codec (DVC)',
            self::DVCP => 'DV Codec (DVCP)',
            self::DVCS => 'DV Codec (DVCS)',
            self::DVE2 => 'Insoft DVE-2 Videoconferencing',
            self::DVH1 => 'Panasonic SMPTE 370M',
            self::DVHD => 'DV Codec (DVHD)',
            self::DVMA => 'Darim Vision DVMPEG',
            self::DVSL => 'DV Codec (DVSL)',
            self::DVX1 => 'Lucent DVX1000SP',
            self::DVX2 => 'Lucent DVX2000S',
            self::DVX3 => 'Lucent DVX3000S',
            self::DX50 => 'DivX5',
            self::DXGM => 'EA/CinemaWare Game Movie',
            self::DXT1 => 'DirectX Compressed Texture (DXT1)',
            self::DXT2 => 'DirectX Compressed Texture (DXT2)',
            self::DXT3 => 'DirectX Compressed Texture (DXT3)',
            self::DXT4 => 'DirectX Compressed Texture (DXT4)',
            self::DXT5 => 'DirectX Compressed Texture (DXT5)',
            self::DXTC => 'DirectX Texture Compression',
            self::DXTN => 'DirectX Compressed Texture',
            self::EKQ0 => 'Elsa YUV (EKQ0)',
            self::ELK0 => 'Elsa YUV (ELK0)',
            self::EM2V => 'Etymonix MPEG-2 I-frame',
            self::ES07 => 'Eyestream 7 Codec (ES07)',
            self::ESCP => 'Eidos Escape',
            self::ETV1 => 'eTreppid Video (ETV1)',
            self::ETV2 => 'eTreppid Video (ETV2)',
            self::ETVC => 'eTreppid Video (ETVC)',
            self::FFV1 => 'FFMPEG',
            self::FLJP => 'D-Vision Field Encoded Motion JPEG',
            self::FLV1 => 'FlashVideo VP6 (ffdshow)',
            self::FLV4 => 'FlashVideo VP6 (ffdshow)',
            self::FMP4 => 'FFmpeg MPEG4',
            self::FMVC => 'FM Screen Capture Codec (FMVC)',
            self::FPS1 => 'Fraps Movie Capture',
            self::FRWA => 'SoftLab-Nsk Forward Motion JPEG with alpha channel',
            self::FRWD => 'SoftLab-Nsk Forward Motion JPEG (FRWD)',
            self::FRWT => 'SoftLab-Nsk Forward Motion JPEG (FRWT)',
            self::FRWU => 'Darim Vision Forward',
            self::FVF1 => 'Fractal Video Frame',
            self::FVFW => 'FFVFW',
            self::GEOX => 'GEOMPEG4 (GEOX)',
            self::GJPG => 'GT891x Codec (GJPG)',
            self::GLCC => 'GigaLink Video Codec',
            self::GLZW => 'Motion LZW',
            self::GPEG => 'Motion JPEG (GPEG)',
            self::GWLT => 'Microsoft Greyscale WLT DIB',
            self::H260 => 'Intel ITU H.260',
            self::H261 => 'Intel ITU H.261',
            self::H262 => 'Intel ITU H.262',
            self::H263 => 'Intel ITU H.263',
            self::H264 => 'Intel ITU H.264',
            self::H265 => 'Intel ITU H.265',
            self::H266 => 'Intel ITU H.266',
            self::H267 => 'Intel ITU H.267',
            self::H268 => 'Intel ITU H.268',
            self::H269 => 'Intel ITU H.269',
            self::HDYC => 'Raw YUV 4:2:2 (HDYC)',
            self::HFYU => 'Ben Rudiak-Gould Huffyuv',
            self::HMCR => 'Rendition Motion Compensation Format (HMCR)',
            self::HMRR => 'Rendition Motion Compensation Format (HMRR)',
            self::HYMT => 'Huffyuv MT',
            self::I263 => 'Intel ITU H.263',
            self::I420 => 'Intel Indeo 4',
            self::IAN => 'Intel RDX (IAN)',
            self::ICLB => 'InSoft CellB Videoconferencing',
            self::IF09 => 'Intel Intermediate YUV9',
            self::IGOR => 'Power DVD',
            self::IJPG => 'Intergraph JPEG',
            self::ILVC => 'Intel Layered Video',
            self::ILVR => 'ITU H.263+',
            self::IPDV => 'I/O DATA Giga AVI DV',
            self::IPJ2 => 'Image Power JPEG2000',
            self::IR21 => 'Intel Indeo 2.1',
            self::IR45 => '(IR45)',
            self::IRAW => 'Intel Uncompressed UYUV',
            self::ISME => '(ISME)',
            self::IV30 => 'Intel Indeo 3',
            self::IV31 => 'Intel Indeo 3.1',
            self::IV32 => 'Intel Indeo 3.2',
            self::IV33 => 'Intel Indeo 3.3',
            self::IV34 => 'Intel Indeo 3.4',
            self::IV35 => 'Intel Indeo 3.5',
            self::IV36 => 'Intel Indeo 3.6',
            self::IV37 => 'Intel Indeo 3.7',
            self::IV38 => 'Intel Indeo 3.8',
            self::IV39 => 'Intel Indeo 3.9',
            self::IV40 => 'Intel Indeo 4.0',
            self::IV41 => 'Intel Indeo 4.1',
            self::IV42 => 'Intel Indeo 4.2',
            self::IV43 => 'Intel Indeo 4.3',
            self::IV44 => 'Intel Indeo 4.4',
            self::IV45 => 'Intel Indeo 4.5',
            self::IV46 => 'Intel Indeo 4.6',
            self::IV47 => 'Intel Indeo 4.7',
            self::IV48 => 'Intel Indeo 4.8',
            self::IV49 => 'Intel Indeo 4.9',
            self::IV50 => 'Intel Indeo 5.0',
            self::IYUV => 'Intel Indeo iYUV R2.0',
            self::JBYR => 'Kensington (JBYR)',
            self::JPEG => 'Microsoft StillImage JPEG',
            self::JPGL => 'DIVIO JPEG Light',
            self::KMVC => "Karl Morton's Video",
            self::L261 => 'Lead H.261',
            self::L263 => 'Lead H.263',
            self::LBYR => '(LBYR)',
            self::LCMW => 'Lead Motion CMW',
            self::LCW2 => 'LEAD MJPEG2000 (LCW2)',
            self::LEAD => 'LEAD Video',
            self::LGRY => 'LEAD Grayscale Image',
            self::LJ11 => 'LEAD JPEG 4:1:1 (LJ11)',
            self::LJ22 => 'LEAD JPEG 4:2:2 (LJ22)',
            self::LJ2K => 'LEAD JPEG 2000',
            self::LJ44 => 'LEAD JPEG 4:4:4 (LJ44)',
            self::LJPG => 'LEAD MJPEG',
            self::LMP2 => 'LEAD MPEG-2 Video Codec (LMP2)',
            self::LMP4 => 'LEAD MPEG-4 Video Codec (LMP4)',
            self::LSVC => 'Lightning Strike Video Codec (LSVC)',
            self::LSVM => 'Vianet Lighting Strike Vmail',
            self::LSVX => 'Lightning Strike Video Codec (LSVX)',
            self::LZO1 => 'Lempel-Ziv-Oberhumer',
            self::M261 => 'Microsoft H.261',
            self::M263 => 'Microsoft H.263',
            self::M4CC => 'Divio MPEG-4',
            self::M4S2 => 'MPEG-4 version 2 simple profile',
            self::MC12 => 'ATI Motion Compensation Format (MC12)',
            self::MCAM => 'ATI Motion Compensation Format (MCAM)',
            self::MJ2C => 'Motion JPEG2000',
            self::MJPA => 'Pinnacle ReelTime MJPG-A Software',
            self::MJPG => 'Motion JPEG (MJPG)',
            self::MMES => 'Matrox MPEG-2 ES',
            self::MP2A => 'Media Excel MPEG-2 Audio',
            self::MP2T => 'Media Excel MPEG-2 Transport Stream',
            self::MP2V => 'Media Excel MPEG-2 Video',
            self::MP42 => 'Microsoft MPEG4-V2',
            self::MP43 => 'Microsoft MPEG4-V3',
            self::MP4A => 'Media Excel MPEG-4 Audio',
            self::MP4S => 'Microsoft MPEG4 (MP4S)',
            self::MP4T => 'Media Excel MPEG-4 Transport Stream',
            self::MP4V => 'Media Excel MPEG-4 Video',
            self::MPEG => 'Chromatic MPEG1 Video I Frame',
            self::MPG3 => 'DivX3/MS MPEG4-V3 (MPG3)',
            self::MPG4 => 'Microsoft MPEG4-V1',
            self::MPGI => 'Sigma Designs MPEG',
            self::MPNG => 'PNG images decoder',
            self::MR16 => '(MR16)',
            self::MRCA => 'FAST Multimedia Mrcodec',
            self::MRLE => 'Microsoft RLE',
            self::MSVC => 'Microsoft Video1 (MSVC)',
            self::MSZH => 'LCL AVImszh',
            self::MTGA => 'TGA images decoder',
            self::MTX1 => 'Matrox (MTX1)',
            self::MTX2 => 'Matrox (MTX2)',
            self::MTX3 => 'Matrox (MTX3)',
            self::MTX4 => 'Matrox (MTX4)',
            self::MTX5 => 'Matrox (MTX5)',
            self::MTX6 => 'Matrox (MTX6)',
            self::MTX7 => 'Matrox (MTX7)',
            self::MTX8 => 'Matrox (MTX8)',
            self::MTX9 => 'Matrox (MTX9)',
            self::MV12 => '(MV12)',
            self::MVI1 => 'Motion Pixels MVI1',
            self::MVI2 => 'Motion Pixels MVI2',
            self::MWV1 => 'Aware Motion Wavelets',
            self::NAVI => 'NAVI',
            self::NDSC => 'Nero Digital Cinema (NDSC)',
            self::NDSM => 'Nero MPEG4 (NDSM)',
            self::NDSP => 'Nero Digital Portable (NDSP)',
            self::NDSS => 'Nero Digital Standard (NDSS)',
            self::NDXC => 'Nero Digital AVC Cinema (NDXC)',
            self::NDXH => 'Nero Digital AVC HDTV (NDXH)',
            self::NDXP => 'Nero Digital AVC Portable (NDXP)',
            self::NDXS => 'Nero Digital AVC Standard (NDXS)',
            self::NHVU => 'NVidia Texture Format (NHVU)',
            self::NT00 => 'NewTek LightWave HDTV',
            self::NTN1 => 'Nogatech Video Compression 1',
            self::NTN2 => 'Nogatech Video Compression 2',
            self::NTZ0 => 'netsuzo',
            self::NTZO => 'netsuzo',
            self::NUV1 => 'NuppelVideo',
            self::NVDS => 'nVIDIA GeForce3 Texture (NVDS)',
            self::NVHS => 'nVIDIA GeForce3 Texture (NVHS)',
            self::NVHU => 'nVIDIA GeForce3 Texture (NVHU)',
            self::NVS0 => 'nVIDIA GeForce2 GTS Pro Texture (NVS0)',
            self::NVS1 => 'nVIDIA GeForce2 GTS Pro Texture (NVS1)',
            self::NVS2 => 'nVIDIA GeForce2 GTS Pro Texture (NVS2)',
            self::NVS3 => 'nVIDIA GeForce2 GTS Pro Texture (NVS3)',
            self::NVS4 => 'nVIDIA GeForce2 GTS Pro Texture (NVS4)',
            self::NVS5 => 'nVIDIA GeForce2 GTS Pro Texture (NVS5)',
            self::NVT0 => 'nVIDIA GeForce2 GTS Pro Texture (NVT0)',
            self::NVT1 => 'nVIDIA GeForce2 GTS Pro Texture (NVT1)',
            self::NVT2 => 'nVIDIA GeForce2 GTS Pro Texture (NVT2)',
            self::NVT3 => 'nVIDIA GeForce2 GTS Pro Texture (NVT3)',
            self::NVT4 => 'nVIDIA GeForce2 GTS Pro Texture (NVT4)',
            self::NVT5 => 'nVIDIA GeForce2 GTS Pro Texture (NVT5)',
            self::PDVC => 'I/O DATA DVC',
            self::PGVV => 'Radius Video Vision',
            self::PHMO => 'IBM Photomotion',
            self::PIM1 => 'Pegasus Imaging Lossless JPEG (PIM1)',
            self::PIM2 => 'Pegasus Imaging Lossless JPEG (PIM2)',
            self::PIMJ => 'Pegasus Imaging Lossless JPEG (PIMJ)',
            self::PIXL => 'Pinnacle Video XL',
            self::PJPG => 'PA MJPEG (PJPG)',
            self::PNG1 => 'CorePNG v8',
            self::PVEZ => 'Horizons Technology PowerEZ',
            self::PVMM => 'PacketVideo Corporation MPEG-4',
            self::PVWV => 'Pegasus Wavelet Compression (PVWV)',
            self::PVW2 => 'Pegasus Wavelet Compression (PVW2)',
            self::Q1_0 => 'Q-Team QPEG (Q1.0)',
            self::Q1_1 => 'Q-Team QPEG (Q1.1)',
            self::QPEG => 'Q-Team QPEG (QPEG)',
            self::QPEQ => 'Q-Team QPEG (QPEQ)',
            self::RAVI => 'GTRON ReferenceAVI (RAVI)',
            self::RAV_ => 'GTRON ReferenceAVI (RAV_)',
            self::RGBT => 'Computer Concepts 32Bits RGB',
            self::RLE => 'Microsoft RLE',
            self::RLE4 => 'Microsoft RLE4',
            self::RLE8 => 'Microsoft RLE8',
            self::RMP4 => 'REALMagic MPEG4',
            self::ROQV => 'Id RoQ File Video Decoder',
            self::RPZA => 'Apple Video',
            self::RT21 => 'Intel Real Time Video 2.1',
            self::RTV0 => 'NewTek VideoToaster',
            self::RUD0 => 'nico Rududu',
            self::RUSH => 'RushCodec',
            self::RV10 => 'RealVideo 1.0',
            self::RV20 => 'Real G2',
            self::RV30 => 'Real 8',
            self::RV40 => 'Real 9/10',
            self::RVX => 'Intel RDX (RVX)',
            self::S422 => 'Tekram VideoCap C210 YUV',
            self::SAN3 => 'DivX 3.11a Copy',
            self::SDCC => 'Sun Digital Camera',
            self::SEDG => 'Samsung MPEG-4',
            self::SFMC => 'CrystalNet Surface Fitting Method',
            self::SHYU => 'Huffyuvs v2.1.1',
            self::SJPG => 'White Pine',
            self::SMC => 'Apple Graphics (SMC)',
            self::SMP4 => '(SMP4)',
            self::SMSC => 'Radius Proprietary',
            self::SMSD => 'Radius Proprietary',
            self::SMSV => 'WorldConnect Wavelet Video',
            self::SNOW => 'SNOW',
            self::SP40 => 'Sunplus SP40',
            self::SP44 => 'Sunplus SP44',
            self::SP53 => 'Aiptek MegaCam (SP53)',
            self::SP54 => 'Aiptek MegaCam (SP54)',
            self::SP55 => 'Aiptek MegaCam (SP55)',
            self::SP56 => 'Aiptek MegaCam (SP56)',
            self::SP57 => 'Aiptek MegaCam (SP57)',
            self::SP58 => 'Aiptek MegaCam (SP58)',
            self::SPIG => 'Radius Spigot',
            self::SPLC => 'Splash Studios ACM',
            self::SQZ2 => 'Microsoft VXTreme V2',
            self::STVA => 'ST CMOS Imager Data (Bayer)',
            self::STVB => 'ST CMOS Imager Data (Nudged Bayer)',
            self::STVC => 'ST CMOS Imager Data (Bunched)',
            self::STVX => 'ST CMOS Imager Data (Extended CODEC Data Format)',
            self::STVY => 'ST CMOS Imager Data (Extended CODEC Data Format with Correction Data)',
            self::SV10 => 'Sorenson Video R1',
            self::SVQ1 => 'Sorenson Video 1',
            self::SVQ3 => 'Sorenson Video 3',
            self::T420 => 'Toshiba YUV 4:2:0 & 4:1:1',
            self::TLMS => 'TeraLogic Motion Intraframe (TLMS)',
            self::TLST => 'TeraLogic Motion Intraframe (TLST)',
            self::TM20 => 'Duck TrueMotion 2.0',
            self::TM2A => 'On2 TrueMotion2X (TM2A)',
            self::TM2X => 'On2 TrueMotion2X (TM2X)',
            self::TMIC => 'TeraLogic Motion Intraframe (TMIC)',
            self::TMOT => 'Horizons Technology TrueMotion S',
            self::TR20 => 'Duck TrueMotion RT 2.0',
            self::TSCC => 'TechSmith Screen Capture',
            self::TV10 => 'Tecomac Low-Bit Rate',
            self::TVJP => 'Truevision TARGA 2000 (TVJP)',
            self::TVMJ => 'Truevision TARGA 2000 (TVMJ)',
            self::TY0N => 'Trident Decompression (TY0N)',
            self::TY2C => 'Trident Decompression (TY2C)',
            self::TY2N => 'Trident Decompression (TY2N)',
            self::U263 => 'UB Video StreamForce',
            self::UCOD => 'eMagix ClearVideo',
            self::ULTI => 'IBM Ultimotion',
            self::ULRG => 'Ut Video Codec RGB',
            self::ULY0 => 'Ut Video Codec YUV420',
            self::ULY2 => 'Ut Video Codec YUV422',
            self::UMP4 => 'DivX3/MS MPEG4-V1 (UMP4)',
            self::UYVY => 'Microsoft UYVY 4:2:2',
            self::V210 => 'AJA Video Systems 10-bit 4:2:2 Component YCbCr (V210)',
            self::V261 => 'Lucent VX2000S',
            self::V422 => 'Vitec Multimedia 24bit YUV',
            self::V655 => 'Vitec Multimedia 16bit YUV',
            self::VCR1 => 'ATI Video Codec 1.0',
            self::VCR2 => 'ATI Video Codec 2.0',
            self::VCR3 => 'ATI Video Codec 3.0',
            self::VCR4 => 'ATI Video Codec 4.0',
            self::VCR5 => 'ATI Video Codec 5.0',
            self::VCR6 => 'ATI Video Codec 6.0',
            self::VCR7 => 'ATI Video Codec 7.0',
            self::VCR8 => 'ATI Video Codec 8.0',
            self::VCR9 => 'ATI Video Codec 9.0',
            self::VDCT => 'Vitec Video Maker Pro DIB',
            self::VDEC => '(VDEC)',
            self::VDOM => 'VDOWave',
            self::VDOW => 'VDOLive',
            self::VDST => 'VirtualDub remote frameclient',
            self::VDTZ => 'Darim Vision VideoTizer YUV',
            self::VGPX => 'Alaris Wee Cam',
            self::VIDS => 'Vitec YUV 4:2:2 CCIR 601 for V422',
            self::VIFP => 'VFAPI Codec (VIFP)',
            self::VIVO => 'Vivo H.263',
            self::VIXL => 'Miro Video XL',
            self::VLV1 => 'VideoLogic',
            self::VP30 => 'On2 Open-Source VP3 (VP30)',
            self::VP31 => 'On2 Open-Source VP3 (VP31)',
            self::VP40 => 'On2 VP4',
            self::VP50 => 'On2 VP5',
            self::VP60 => 'On2 VP6 (Simple)',
            self::VP61 => 'On2 VP6 (Advanced)',
            self::VP62 => 'On2 VP6 (VP62)',
            self::VP6F => 'On2 VP6 (ffdshow)',
            self::VP70 => 'On2 VP7',
            self::VSSH => 'H.264 (VSSH)',
            self::VQC1 => 'ViewQuest VideoQuest Codec 1',
            self::VQC2 => 'ViewQuest VideoQuest Codec 2',
            self::VQJC => '(VQJC)',
            self::VSSV => 'Vanguard Software VSS Video',
            self::VTLP => 'Alaris VideoGram QuickVideo VGPixel (VTLP)',
            self::VUUU => '(VUUU)',
            self::VX1K => 'Lucent VX1000S',
            self::VX2K => 'Lucent VX2000S',
            self::VXSP => 'Lucent VX1000SP',
            self::VYU9 => 'ATI YUV (VYU9)',
            self::VYUY => 'ATI YUV (VYUY)',
            self::WBVC => 'Winbond W9960',
            self::WHAM => 'Microsoft Video 1 (WHAM)',
            self::WINX => 'Winnov Software Compression',
            self::WJPG => 'AverMedia USB TV-tuner/capture',
            self::WMV1 => 'Windows Media Video 7',
            self::WMV2 => 'Windows Media Video 8',
            self::WMV3 => 'Windows Media Video 9 (WMV3)',
            self::WMVA => 'WMV9 Advanced Profile (WMVA)',
            self::WVC1 => 'WMV9 Advanced Profile (WVC1)',
            self::WNIX => 'WniWni Video3',
            self::WNV1 => 'Winnov Hardware Compression',
            self::WRPR => 'VideoTools VideoServer Client Codec',
            self::X263 => 'Xirlink H.263 Video Codec',
            self::X264 => 'H.264 (X264)',
            self::XLV0 => 'NetXL XL Video Decoder',
            self::XLV1 => 'Videologic VLCAP',
            self::XMPG => 'XING IFlameOnly MPEG',
            self::XTOR => 'Dxtory',
            self::XVID => 'XviD MPEG4',
            self::XVIX => 'DivX3/MS MPEG4-V2 (XVIX)',
            self::XWV0 => 'XiWave Video (XWV0)',
            self::XWV1 => 'XiWave Video (XWV1)',
            self::XWV2 => 'XiWave Video (XWV2)',
            self::XWV3 => 'XiWave Video (XWV3)',
            self::XWV4 => 'XiWave Video (XWV4)',
            self::XWV5 => 'XiWave Video (XWV5)',
            self::XWV6 => 'XiWave Video (XWV6)',
            self::XWV7 => 'XiWave Video (XWV7)',
            self::XWV8 => 'XiWave Video (XWV8)',
            self::XWV9 => 'XiWave Video (XWV9)',
            self::XXAN => '(XXAN)',
            self::Y16 => '16bpp Grayscale Video (Y16)',
            self::Y211 => 'Microsoft YUV 2:1:1',
            self::Y411 => 'Microsoft YUV 4:1:1 Packed',
            self::Y41B => 'Microsoft YUV 4:1:1 Planar',
            self::Y41P => 'Brooktree YUV 4:1:1',
            self::Y41T => 'Brooktree PCI 4:1:1 with transparency',
            self::Y42B => 'Weitek YUV 4:2:2',
            self::Y42T => 'Brooktree PCI 4:2:2 with transparency',
            self::Y444 => '(Y444)',
            self::Y8 => 'Grayscale video',
            self::YC12 => 'Intel YUV12',
            self::YU92 => 'Intel YUV (YU92)',
            self::YUV8 => 'Winnov Caviar YUV8',
            self::YUV9 => 'Indeo Video Raw (YUV9)',
            self::YUVP => 'Uncompressed YCrCb 4:2:2',
            self::YUY2 => 'Microsoft Raw Uncompressed YUV 4:2:2',
            self::YUYV => 'Canopus YUVY',
            self::YV12 => 'Weitek YVU12',
            self::YV16 => 'Elecard YUV 4:2:2 Planar',
            self::YV92 => 'Intel Smart Video Recorder',
            self::YVU9 => 'Indeo Video Raw (YVU9)',
            self::YVYU => 'Microsoft YVYU 4:2:2',
            self::ZLIB => 'LCL AVIzlib',
            self::ZMBV => 'DoxBox Capture Codec (ZMBV)',
            self::ZPEG => 'Metheus Video Zipper',
            self::ZYGO => 'ZyGo ZyGoVideo',
            self::ZYYY => '(ZYYY)',
        };
    }

    /**
     * Attempts to find a case by a FourCC string.
     * Handles both padded (e.g. "DIB ") and unpadded (e.g. "DIB") inputs.
     *
     * @param  string $fourcc Raw FourCC string (1-4 characters)
     * @return self|null       Matching enum case, or null if not found
     */
    public static function fromFourCC(string $fourcc): ?self
    {
        // Pad to exactly 4 characters with spaces
        $padded = str_pad($fourcc, 4, ' ', STR_PAD_RIGHT);
        return self::tryFrom($padded);
    }
}
