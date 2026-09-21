<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Codec;

use function sprintf;

/**
 * WAVE Audio Codec Registry
 *
 * Combines the IANA WAVE codec registry and the extended codec database
 * originally compiled by Kurohane (http://www.kurohane.net/) for Shinkuu Hadoken (2002-09-16).
 *
 * Backing value: wFormatTag ID (integer / hex)
 */
enum AudioCodec: int
{
    case UNKNOWN = 0x0000;
    case MICROSOFT_PCM = 0x0001;
    case MICROSOFT_ADPCM = 0x0002;
    case IEEE_FLOAT = 0x0003;
    /** Compaq Computer's VSELP — codec for Windows CE 2.0 devices */
    case COMPAQ_VSELP = 0x0004;
    case IBM_CVSD = 0x0005;
    case CCITT_ALAW = 0x0006;
    case CCITT_ULAW = 0x0007;
    case MICROSOFT_DTS = 0x0008;
    case MICROSOFT_DRM = 0x0009;
    case WMA9_VOICE = 0x000A;
    case OKI_ADPCM = 0x0010;
    /** IMA ADPCM (Intel's DVI ADPCM) */
    case IMA_ADPCM = 0x0011;
    case MEDIASPACE_ADPCM = 0x0012;
    case SIERRA_ADPCM = 0x0013;
    case CCITT_G723_ADPCM = 0x0014;
    case DSP_GROUP_DIGISTD = 0x0015;
    case DSP_GROUP_DIGIFIX = 0x0016;
    /** Dialogic OKI ADPCM — for OKI ADPCM chips or firmware */
    case DIALOGIC_OKI_ADPCM = 0x0017;
    /** MediaVision ADPCM — for Jazz 16 chip set */
    case MEDIAVISION_ADPCM = 0x0018;
    case HP_CU = 0x0019;
    case YAMAHA_ADPCM = 0x0020;
    case SONARC = 0x0021;
    case DSP_GROUP_TRUESPEECH = 0x0022;
    case ECHO_SPEECH_SC1 = 0x0023;
    case AUDIOFILE_AF36 = 0x0024;
    /** APTX (Audio Processing Technology) */
    case APTX = 0x0025;
    case AUDIOFILE_AF10 = 0x0026;
    /** Prosody CTI Speech Card (Aculab) */
    case PROSODY_1612 = 0x0027;
    case LRC = 0x0028;
    case DOLBY_AC2 = 0x0030;
    case GSM_610 = 0x0031;
    case MSNAUDIO = 0x0032;
    case ADPCME = 0x0033;
    case CONTROL_RES_VQLPC = 0x0034;
    case DSP_GROUP_REAL = 0x0035;
    case DSP_GROUP_ADPCM = 0x0036;
    case CONTROL_RES_CR10 = 0x0037;
    case NMS_VBXADPCM = 0x0038;
    /** Roland RDAC Proprietary Format */
    case ROLAND_RDAC = 0x0039;
    case ECHOSC3 = 0x003A;
    case ROCKWELL_ADPCM = 0x003B;
    case ROCKWELL_DIGITALK = 0x003C;
    case XEBEC_MULTIMEDIA = 0x003D;
    case G721_ADPCM = 0x0040;
    case G728_CELP = 0x0041;
    case MS_G723 = 0x0042;
    case IBM_AVC_ADPCM = 0x0043;
    case SHARP_G726 = 0x0045;
    case OGG_VORBIS_MODE1 = 0x004F;
    /** MPEG-1 layer 1 and 2 */
    case MPEG1_LAYER_1_2 = 0x0050;
    case OGG_VORBIS_MODE3 = 0x0051;
    /** RT24 — alternative ID for Voxware MetaVoice (0x0074); prefer 0x0074 */
    case RT24 = 0x0052;
    case PAC = 0x0053;
    /** ISO/MPEG Layer3 Format Tag */
    case MPEG1_LAYER3 = 0x0055;
    case LUCENT_G723 = 0x0059;
    case CIRRUS = 0x0060;
    case ESPCM = 0x0061;
    /** Voxware — now obsolete */
    case VOXWARE = 0x0062;
    case CANOPUS_ATRAC = 0x0063;
    case G726_ADPCM = 0x0064;
    case G722_ADPCM = 0x0065;
    case DSAT = 0x0066;
    case DSAT_DISPLAY = 0x0067;
    /** Voxware Byte Aligned — now obsolete */
    case VOXWARE_BYTE_ALIGNED = 0x0069;
    case OGG_VORBIS_MODE1_PLUS = 0x006F;
    /** Ogg Vorbis mode 2+ (0x0070 also referenced as Voxware AC8, now obsolete) */
    case OGG_VORBIS_MODE2_PLUS = 0x0070;
    /** Ogg Vorbis mode 3+ (0x0071 also referenced as Voxware AC10, now obsolete) */
    case OGG_VORBIS_MODE3_PLUS = 0x0071;
    /** Voxware AC16 — now obsolete */
    case VOXWARE_AC16 = 0x0072;
    /** Voxware AC20 — now obsolete */
    case VOXWARE_AC20 = 0x0073;
    /** Voxware MetaVoice — file and stream oriented */
    case VOXWARE_METAVOICE = 0x0074;
    /** Voxware MetaSound — file and stream oriented */
    case VOXWARE_METASOUND = 0x0075;
    /** Voxware RT29HW — now obsolete */
    case VOXWARE_RT29HW = 0x0076;
    /** Voxware VR12 — now obsolete */
    case VOXWARE_VR12 = 0x0077;
    /** Voxware VR18 — now obsolete */
    case VOXWARE_VR18 = 0x0078;
    /** Voxware TQ40 — now obsolete */
    case VOXWARE_TQ40 = 0x0079;
    case SOFTSOUND = 0x0080;
    /** Voxware TQ60 — now obsolete */
    case VOXWARE_TQ60 = 0x0081;
    /** MSRT24 — alternative ID for Voxware MetaVoice (0x0074); prefer 0x0074 */
    case MSRT24 = 0x0082;
    case G729A = 0x0083;
    case MVI_MV12 = 0x0084;
    case DF_G726 = 0x0085;
    case DF_GSM610 = 0x0086;
    case ISIAUDIO = 0x0088;
    case ONLIVE = 0x0089;
    case SIEMENS_SBC24 = 0x0091;
    case DOLBY_AC3_SPDIF = 0x0092;
    case MEDIASONIC_G723 = 0x0093;
    case ACULAB_8KBPS = 0x0094;
    case ZYXEL_ADPCM = 0x0097;
    case PHILIPS_LPCBB = 0x0098;
    case PACKED = 0x0099;
    case MALDEN_PHONYTALK = 0x00A0;
    /** Alternative Microsoft ADPCM registration (non-standard) */
    case MICROSOFT_ADPCM_E1 = 0x00E1;
    case AAC = 0x00FF;
    case RHETOREX_ADPCM = 0x0100;
    case IRAT = 0x0101;
    case VIVO_G723 = 0x0111;
    case VIVO_SIREN = 0x0112;
    case DIGITAL_G723 = 0x0123;
    case SANYO_ADPCM = 0x0125;
    case ACELP_NET = 0x0130;
    case SIPRO_ACELP_4800 = 0x0131;
    case SIPRO_ACELP_8V3 = 0x0132;
    case SIPRO_ACELP_G729 = 0x0133;
    case SIPRO_ACELP_G729A = 0x0134;
    case SIPRO_ACELP_KELVIN = 0x0135;
    case DICTAPHONE_G726_ADPCM = 0x0140;
    case QUALCOMM_PUREVOICE = 0x0150;
    case QUALCOMM_HALFRATE = 0x0151;
    case RING_ZERO_TUBGSM = 0x0155;
    case WMA1 = 0x0160;
    case DIVX_AUDIO_WMA = 0x0161;
    case WMA9_PROFESSIONAL = 0x0162;
    case WMA9_LOSSLESS = 0x0163;
    case UNISYS_NAP_ADPCM = 0x0170;
    case UNISYS_NAP_ULAW = 0x0171;
    case UNISYS_NAP_ALAW = 0x0172;
    case UNISYS_NAP_16K = 0x0173;
    case CREATIVE_ADPCM = 0x0200;
    case CREATIVE_FASTSPEECH8 = 0x0202;
    case CREATIVE_FASTSPEECH10 = 0x0203;
    case UHER_ADPCM = 0x0210;
    /** Ulead DV ACM (variant 1) */
    case ULEAD_DV_ACM_1 = 0x0215;
    /** Ulead DV ACM (variant 2) */
    case ULEAD_DV_ACM_2 = 0x0216;
    case QUARTERDECK = 0x0220;
    case ILINK_VC = 0x0230;
    case AUREAL_RAW_SPORT = 0x0240;
    case ESST_AC3 = 0x0241;
    case INTERACTIVE_HSX = 0x0250;
    case INTERACTIVE_RPELP = 0x0251;
    case CONSISTENT_CS2 = 0x0260;
    /** Sony ATRAC3 — same as MiniDisc LP2 (SCX) */
    case SONY_ATRAC3 = 0x0270;
    case FUJITSU_FM_TOWNS_SND = 0x0300;
    /** Brooktree digital audio format */
    case BTV_DIGITAL = 0x0400;
    case INTEL_MUSIC_CODER = 0x0401;
    case LIGOS_INDEO_AUDIO = 0x0402;
    case QDESIGN_MUSIC = 0x0450;
    case ON2_AVC_AUDIO = 0x0500;
    case VME_VMPCM = 0x0680;
    case ATT_TPC = 0x0681;
    case OLIVETTI_GSM = 0x1000;
    case OLIVETTI_ADPCM = 0x1001;
    case OLIVETTI_CELP = 0x1002;
    case OLIVETTI_SBC = 0x1003;
    case OLIVETTI_OPR = 0x1004;
    case LH_CODEC = 0x1100;
    case LH_CELP = 0x1101;
    /** Lernout & Hauspie SBC codec (variant 1) */
    case LH_SBC_1 = 0x1102;
    /** Lernout & Hauspie SBC codec (variant 2) */
    case LH_SBC_2 = 0x1103;
    /** Lernout & Hauspie SBC codec (variant 3) */
    case LH_SBC_3 = 0x1104;
    case NORRIS = 0x1400;
    case ISIAUDIO_2 = 0x1401;
    case SOUNDSPACE_MUSICOMPRESS = 0x1500;
    case VOXWARE_RT24_SPEECH = 0x181C;
    case LUCENT_ELEMEDIA_AX24000P = 0x181E;
    case LUCENT_SX8300P = 0x1C07;
    case LUCENT_SX5363S_G723 = 0x1C0C;
    /** CUseeMe DigiTalk (ex-Rockwell) */
    case CUSEEEME_DIGITALK = 0x1F03;
    case NTC_ALF2CD = 0x1FC4;
    /** FAST Multimedia AG DVM — Dolby AC3 */
    case DVM_DOLBY_AC3 = 0x2000;
    case DTS = 0x2001;
    case REALAUDIO_14_4 = 0x2002;
    case REALAUDIO_28_8 = 0x2003;
    case REALAUDIO_COOK = 0x2004;
    case REALAUDIO_DNET = 0x2005;
    /** Ogg Vorbis mode 1 (alternative registration) */
    case OGG_VORBIS_1 = 0x674F;
    /** Ogg Vorbis mode 2 (alternative registration) */
    case OGG_VORBIS_2 = 0x6750;
    /** Ogg Vorbis mode 3 (alternative registration) */
    case OGG_VORBIS_3 = 0x6751;
    /** Ogg Vorbis mode 1+ (alternative registration) */
    case OGG_VORBIS_1_PLUS = 0x676F;
    /** Ogg Vorbis mode 2+ (alternative registration) */
    case OGG_VORBIS_2_PLUS = 0x6770;
    /** Ogg Vorbis mode 3+ (alternative registration) */
    case OGG_VORBIS_3_PLUS = 0x6771;
    /** GSM-AMR CBR no SID */
    case GSM_AMR_CBR = 0x7A21;
    /** GSM-AMR VBR including SID */
    case GSM_AMR_VBR = 0x7A22;
    case TRUE_AUDIO = 0x77A1;
    case GIGALINK_AUDIO = 0xC0CC;
    case DEBUGMODE_VEGAS_ACM = 0xDFAC;
    case COREFLAC = 0xF1AC;
    case EXTENSIBLE = 0xFFFE;
    case UNREGISTERED = 0xFFFF;

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Returns the human-readable codec name.
     */
    public function label(): string
    {
        return match ($this) {
            self::UNKNOWN => 'Unknown',
            self::MICROSOFT_PCM => 'Microsoft PCM',
            self::MICROSOFT_ADPCM => 'Microsoft ADPCM',
            self::IEEE_FLOAT => 'IEEE Float',
            self::COMPAQ_VSELP => "Compaq Computer's VSELP",
            self::IBM_CVSD => 'IBM CVSD',
            self::CCITT_ALAW => 'CCITT A-Law',
            self::CCITT_ULAW => 'CCITT u-Law',
            self::MICROSOFT_DTS => 'Microsoft DTS',
            self::MICROSOFT_DRM => 'Microsoft DRM',
            self::WMA9_VOICE => 'Windows Media Audio 9 Voice',
            self::OKI_ADPCM => 'OKI ADPCM',
            self::IMA_ADPCM => 'IMA ADPCM',
            self::MEDIASPACE_ADPCM => 'MediaSpace ADPCM',
            self::SIERRA_ADPCM => 'Sierra ADPCM',
            self::CCITT_G723_ADPCM => 'CCITT G.723 ADPCM',
            self::DSP_GROUP_DIGISTD => 'DSP Group DigiSTD',
            self::DSP_GROUP_DIGIFIX => 'DSP Group DigiFIX',
            self::DIALOGIC_OKI_ADPCM => 'Dialogic OKI ADPCM',
            self::MEDIAVISION_ADPCM => 'MediaVision ADPCM',
            self::HP_CU => 'HP CU',
            self::YAMAHA_ADPCM => 'YAMAHA ADPCM',
            self::SONARC => 'Sonarc(TM) Compression',
            self::DSP_GROUP_TRUESPEECH => 'DSP Group TrueSpeech(TM)',
            self::ECHO_SPEECH_SC1 => 'Echo Speech',
            self::AUDIOFILE_AF36 => 'AUDIOFILE AF36',
            self::APTX => 'Audio Processing Technology',
            self::AUDIOFILE_AF10 => 'AUDIOFILE AF10',
            self::PROSODY_1612 => 'Prosody 1612',
            self::LRC => 'LRC',
            self::DOLBY_AC2 => 'Dolby AC-2',
            self::GSM_610 => 'GSM 6.10',
            self::MSNAUDIO => 'MSNAudio',
            self::ADPCME => 'ADPCME',
            self::CONTROL_RES_VQLPC => 'Control Resources Limited VQLPC',
            self::DSP_GROUP_REAL => 'DSP Group REAL',
            self::DSP_GROUP_ADPCM => 'DSP Group ADPCM',
            self::CONTROL_RES_CR10 => 'Control Resources Limited CR10',
            self::NMS_VBXADPCM => 'NMS VBXADPCM',
            self::ROLAND_RDAC => 'Roland RDAC',
            self::ECHOSC3 => 'EchoSC3',
            self::ROCKWELL_ADPCM => 'Rockwell ADPCM',
            self::ROCKWELL_DIGITALK => 'Rockwell Digit LK',
            self::XEBEC_MULTIMEDIA => 'Xebec Multimedia Solutions',
            self::G721_ADPCM => 'G.721 ADPCM',
            self::G728_CELP => 'G.728 CELP',
            self::MS_G723 => 'MS G.723',
            self::IBM_AVC_ADPCM => 'IBM AVC ADPCM',
            self::SHARP_G726 => 'SHARP G.726',
            self::OGG_VORBIS_MODE1 => 'Ogg Vorbis (mode 1)',
            self::MPEG1_LAYER_1_2 => 'MPEG-1 layer 1, 2',
            self::OGG_VORBIS_MODE3 => 'Ogg Vorbis (mode 3)',
            self::RT24 => 'RT24',
            self::PAC => 'PAC',
            self::MPEG1_LAYER3 => 'MPEG1-Layer3',
            self::LUCENT_G723 => 'Lucent G.723',
            self::CIRRUS => 'Cirrus',
            self::ESPCM => 'ESPCM',
            self::VOXWARE => 'Voxware',
            self::CANOPUS_ATRAC => 'Canopus Atrac',
            self::G726_ADPCM => 'G.726 ADPCM',
            self::G722_ADPCM => 'G.722 ADPCM',
            self::DSAT => 'DSAT',
            self::DSAT_DISPLAY => 'DSAT Display',
            self::VOXWARE_BYTE_ALIGNED => 'Voxware Byte Aligned',
            self::OGG_VORBIS_MODE1_PLUS => 'Ogg Vorbis (mode 1+)',
            self::OGG_VORBIS_MODE2_PLUS => 'Ogg Vorbis (mode 2+)',
            self::OGG_VORBIS_MODE3_PLUS => 'Ogg Vorbis (mode 3+)',
            self::VOXWARE_AC16 => 'Voxware AC16',
            self::VOXWARE_AC20 => 'Voxware AC20',
            self::VOXWARE_METAVOICE => 'Voxware MetaVoice',
            self::VOXWARE_METASOUND => 'Voxware MetaSound',
            self::VOXWARE_RT29HW => 'Voxware RT29HW',
            self::VOXWARE_VR12 => 'Voxware VR12',
            self::VOXWARE_VR18 => 'Voxware VR18',
            self::VOXWARE_TQ40 => 'Voxware TQ40',
            self::SOFTSOUND => 'Softsound',
            self::VOXWARE_TQ60 => 'Voxware TQ60',
            self::MSRT24 => 'MSRT24',
            self::G729A => 'G.729A',
            self::MVI_MV12 => 'MVI MV12',
            self::DF_G726 => 'DF G.726',
            self::DF_GSM610 => 'DF GSM610',
            self::ISIAUDIO => 'ISIAudio',
            self::ONLIVE => 'Onlive',
            self::SIEMENS_SBC24 => 'Siemens SBC24',
            self::DOLBY_AC3_SPDIF => 'Dolby AC3 SPDIF',
            self::MEDIASONIC_G723 => 'MediaSonic G.723',
            self::ACULAB_8KBPS => 'Aculab 8Kbps',
            self::ZYXEL_ADPCM => 'ZyXEL ADPCM',
            self::PHILIPS_LPCBB => 'Philips LPCBB',
            self::PACKED => 'Packed',
            self::MALDEN_PHONYTALK => 'Malden Electronics PHONYTALK',
            self::MICROSOFT_ADPCM_E1 => 'Microsoft ADPCM (0x00E1)',
            self::AAC => 'Advanced Audio Coding',
            self::RHETOREX_ADPCM => 'Rhetorex ADPCM',
            self::IRAT => "BeCubed Software's IRAT",
            self::VIVO_G723 => 'Vivo G.723',
            self::VIVO_SIREN => 'Vivo Siren',
            self::DIGITAL_G723 => 'Digital G.723',
            self::SANYO_ADPCM => 'Sanyo ADPCM',
            self::ACELP_NET => 'ACELP.net Sipro Lab Audio',
            self::SIPRO_ACELP_4800 => 'Sipro Lab Telecom ACELP.4800',
            self::SIPRO_ACELP_8V3 => 'Sipro Lab Telecom ACELP.8V3',
            self::SIPRO_ACELP_G729 => 'Sipro Lab Telecom ACELP.G.729',
            self::SIPRO_ACELP_G729A => 'Sipro Lab Telecom ACELP.G.729A',
            self::SIPRO_ACELP_KELVIN => 'Sipro Lab Telecom ACELP.KELVIN',
            self::DICTAPHONE_G726_ADPCM => 'Dictaphone G.726 ADPCM',
            self::QUALCOMM_PUREVOICE => 'Qualcomm PUREVOICE',
            self::QUALCOMM_HALFRATE => 'Qualcomm HALFRATE',
            self::RING_ZERO_TUBGSM => 'Ring Zero Systems TUBGSM',
            self::WMA1 => 'Windows Media Audio 1',
            self::DIVX_AUDIO_WMA => 'DivX Audio (WMA)',
            self::WMA9_PROFESSIONAL => 'Windows Media Audio 9 Professional',
            self::WMA9_LOSSLESS => 'Windows Media Audio 9 Lossless',
            self::UNISYS_NAP_ADPCM => 'UNISYS NAP ADPCM',
            self::UNISYS_NAP_ULAW => 'UNISYS NAP ULAW',
            self::UNISYS_NAP_ALAW => 'UNISYS NAP ALAW',
            self::UNISYS_NAP_16K => 'UNISYS NAP 16K',
            self::CREATIVE_ADPCM => 'Creative ADPCM',
            self::CREATIVE_FASTSPEECH8 => 'Creative FastSpeech8',
            self::CREATIVE_FASTSPEECH10 => 'Creative FastSpeech10',
            self::UHER_ADPCM => 'UHER informatic GmbH ADPCM',
            self::ULEAD_DV_ACM_1 => 'Ulead DV ACM',
            self::ULEAD_DV_ACM_2 => 'Ulead DV ACM (variant 2)',
            self::QUARTERDECK => 'Quarterdeck',
            self::ILINK_VC => 'I-link Worldwide ILINK VC',
            self::AUREAL_RAW_SPORT => 'Aureal Semiconductor RAW SPORT',
            self::ESST_AC3 => 'ESST AC3',
            self::INTERACTIVE_HSX => 'Interactive Products HSX',
            self::INTERACTIVE_RPELP => 'Interactive Products RPELP',
            self::CONSISTENT_CS2 => 'Consistent Software CS2',
            self::SONY_ATRAC3 => 'Sony ATRAC3 (SCX, same as MiniDisk LP2)',
            self::FUJITSU_FM_TOWNS_SND => 'Fujitsu FM-TOWNS SND',
            self::BTV_DIGITAL => 'BTV Digital',
            self::INTEL_MUSIC_CODER => 'Intel Music Coder',
            self::LIGOS_INDEO_AUDIO => 'Ligos Indeo Audio',
            self::QDESIGN_MUSIC => 'QDesign Music',
            self::ON2_AVC_AUDIO => 'On2 AVC Audio',
            self::VME_VMPCM => 'AT&T Labs VME VMPCM',
            self::ATT_TPC => 'AT&T Labs TPC',
            self::OLIVETTI_GSM => 'Olivetti GSM',
            self::OLIVETTI_ADPCM => 'Olivetti ADPCM',
            self::OLIVETTI_CELP => 'Olivetti CELP',
            self::OLIVETTI_SBC => 'Olivetti SBC',
            self::OLIVETTI_OPR => 'Olivetti OPR',
            self::LH_CODEC => 'LH Codec',
            self::LH_CELP => 'Lernout & Hauspie CELP codec',
            self::LH_SBC_1 => 'Lernout & Hauspie SBC codec (1)',
            self::LH_SBC_2 => 'Lernout & Hauspie SBC codec (2)',
            self::LH_SBC_3 => 'Lernout & Hauspie SBC codec (3)',
            self::NORRIS => 'Norris',
            self::ISIAUDIO_2 => 'ISIAudio 2',
            self::SOUNDSPACE_MUSICOMPRESS => 'Soundspace Music Compression',
            self::VOXWARE_RT24_SPEECH => 'VoxWare RT24 speech codec',
            self::LUCENT_ELEMEDIA_AX24000P => 'Lucent elemedia AX24000P Music codec',
            self::LUCENT_SX8300P => 'Lucent SX8300P speech codec',
            self::LUCENT_SX5363S_G723 => 'Lucent SX5363S G.723 compliant codec',
            self::CUSEEEME_DIGITALK => 'CUseeMe DigiTalk (ex-Rockwell)',
            self::NTC_ALF2CD => 'NTC ALF2CD ACM',
            self::DVM_DOLBY_AC3 => 'FAST Multimedia AG DVM (Dolby AC3)',
            self::DTS => 'DTS',
            self::REALAUDIO_14_4 => 'RealAudio (14_4)',
            self::REALAUDIO_28_8 => 'RealAudio (28_8)',
            self::REALAUDIO_COOK => 'RealAudio (COOK)',
            self::REALAUDIO_DNET => 'RealAudio (DNET)',
            self::OGG_VORBIS_1 => 'Ogg Vorbis (mode 1)',
            self::OGG_VORBIS_2 => 'Ogg Vorbis (mode 2)',
            self::OGG_VORBIS_3 => 'Ogg Vorbis (mode 3)',
            self::OGG_VORBIS_1_PLUS => 'Ogg Vorbis (mode 1+)',
            self::OGG_VORBIS_2_PLUS => 'Ogg Vorbis (mode 2+)',
            self::OGG_VORBIS_3_PLUS => 'Ogg Vorbis (mode 3+)',
            self::GSM_AMR_CBR => 'GSM-AMR (CBR no SID)',
            self::GSM_AMR_VBR => 'GSM-AMR (VBR including SID)',
            self::TRUE_AUDIO => 'The True Audio',
            self::GIGALINK_AUDIO => 'GigaLink Audio Codec',
            self::DEBUGMODE_VEGAS_ACM => 'DebugMode SonicFoundry Vegas FrameServer ACM Codec',
            self::COREFLAC => 'CoreFLAC ACM',
            self::EXTENSIBLE => 'Extensible wave format',
            self::UNREGISTERED => 'In Development / Unregistered',
        };
    }

    /**
     * Returns the hexadecimal string representation of the wFormatTag ID.
     *
     * @return string e.g. "0x0001"
     */
    public function hex(): string
    {
        return sprintf('0x%04X', $this->value);
    }

    /**
     * Returns the IANA MIME type codec identifier string.
     *
     * @return string e.g. "audio/vnd.wave;codec=1"
     */
    public function ianaCodecId(): string
    {
        return sprintf('audio/vnd.wave;codec=%X', $this->value);
    }

    /**
     * Attempts to find a case from a hexadecimal string (e.g. "0x0001" or "0001").
     *
     * @param  string $hex Hexadecimal string with or without "0x" / "0X" prefix
     * @return self|null   Matching enum case, or null if not found
     */
    public static function fromHex(string $hex): ?self
    {
        return self::tryFrom((int) hexdec(ltrim($hex, '0xX')));
    }
}
