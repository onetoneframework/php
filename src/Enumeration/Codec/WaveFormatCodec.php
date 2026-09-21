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
 * WAVE Form Audio Codec Registration
 *
 * Maps WAVE format names to their official hexadecimal registration numbers
 * as defined in the IANA WAVE codec registry.
 *
 * @see https://www.iana.org/assignments/wave-avi-codec-registry
 */
enum WaveFormatCodec: int
{
    /** Microsoft Unknown Wave Format */
    case WAVE_FORMAT_UNKNOWN = 0x0000;

    /** Microsoft PCM Format */
    case WAVE_FORMAT_PCM = 0x0001;

    /** Microsoft ADPCM Format */
    case WAVE_FORMAT_ADPCM = 0x0002;

    /** IEEE Float */
    case WAVE_FORMAT_IEEE_FLOAT = 0x0003;

    /** Compaq Computer's VSELP — codec for Windows CE 2.0 devices */
    case WAVE_FORMAT_VSELP = 0x0004;

    /** IBM CVSD */
    case WAVE_FORMAT_IBM_CVSD = 0x0005;

    /** Microsoft ALAW */
    case WAVE_FORMAT_ALAW = 0x0006;

    /** Microsoft MULAW */
    case WAVE_FORMAT_MULAW = 0x0007;

    /** OKI ADPCM */
    case WAVE_FORMAT_OKI_ADPCM = 0x0010;

    /** Intel's DVI ADPCM */
    case WAVE_FORMAT_DVI_ADPCM = 0x0011;

    /** Videologic's MediaSpace ADPCM */
    case WAVE_FORMAT_MEDIASPACE_ADPCM = 0x0012;

    /** Sierra ADPCM */
    case WAVE_FORMAT_SIERRA_ADPCM = 0x0013;

    /** G.723 ADPCM (Antex Electronics) */
    case WAVE_FORMAT_G723_ADPCM = 0x0014;

    /** DSP Solution's DIGISTD */
    case WAVE_FORMAT_DIGISTD = 0x0015;

    /** DSP Solution's DIGIFIX */
    case WAVE_FORMAT_DIGIFIX = 0x0016;

    /** Dialogic OKI ADPCM — for OKI ADPCM chips or firmware */
    case WAVE_FORMAT_DIALOGIC_OKI_ADPCM = 0x0017;

    /** MediaVision ADPCM — for Jazz 16 chip set */
    case WAVE_FORMAT_MEDIAVISION_ADPCM = 0x0018;

    /** HP CU Codec */
    case WAVE_FORMAT_CU_CODEC = 0x0019;

    /** Yamaha ADPCM */
    case WAVE_FORMAT_YAMAHA_ADPCM = 0x0020;

    /** Speech Compression's Sonarc */
    case WAVE_FORMAT_SONARC = 0x0021;

    /** DSP Group's True Speech */
    case WAVE_FORMAT_DSPGROUP_TRUESPEECH = 0x0022;

    /** Echo Speech's EchoSC1 */
    case WAVE_FORMAT_ECHOSC1 = 0x0023;

    /** Audiofile AF36 (Virtual Music) */
    case WAVE_FORMAT_AUDIOFILE_AF36 = 0x0024;

    /** APTX (Audio Processing Technology) */
    case WAVE_FORMAT_APTX = 0x0025;

    /** Audiofile AF10 (Virtual Music) */
    case WAVE_FORMAT_AUDIOFILE_AF10 = 0x0026;

    /** Prosody 1612 — Prosody CTI Speech Card (Aculab) */
    case WAVE_FORMAT_PROSODY_1612 = 0x0027;

    /** LRC (Merging Technologies) */
    case WAVE_FORMAT_LRC = 0x0028;

    /** Dolby AC2 */
    case WAVE_FORMAT_DOLBY_AC2 = 0x0030;

    /** GSM 610 (Microsoft) */
    case WAVE_FORMAT_GSM610 = 0x0031;

    /** Microsoft MSN Audio Codec */
    case WAVE_FORMAT_MSNAUDIO = 0x0032;

    /** Antex ADPCME */
    case WAVE_FORMAT_ANTEX_ADPCME = 0x0033;

    /** Control Resources VQLPC */
    case WAVE_FORMAT_CONTROL_RES_VQLPC = 0x0034;

    /** Digireal (DSP Solutions) */
    case WAVE_FORMAT_DIGIREAL = 0x0035;

    /** DigiADPCM (DSP Solutions) */
    case WAVE_FORMAT_DIGIADPCM = 0x0036;

    /** Control Resources CR10 */
    case WAVE_FORMAT_CONTROL_RES_CR10 = 0x0037;

    /** NMS VBXADPCM (Natural MicroSystems) */
    case WAVE_FORMAT_NMS_VBXADPCM = 0x0038;

    /** Roland RDAC Proprietary Format */
    case WAVE_FORMAT_ROLAND_RDAC = 0x0039;

    /** Echo Speech EchoSC3 — proprietary compressed format */
    case WAVE_FORMAT_ECHOSC3 = 0x003A;

    /** Rockwell ADPCM */
    case WAVE_FORMAT_ROCKWELL_ADPCM = 0x003B;

    /** Rockwell DIGITALK */
    case WAVE_FORMAT_ROCKWELL_DIGITALK = 0x003C;

    /** Xebec — proprietary compression */
    case WAVE_FORMAT_XEBEC = 0x003D;

    /** Antex Electronics G.721 ADPCM */
    case WAVE_FORMAT_G721_ADPCM = 0x0040;

    /** G.728 CELP (Antex Electronics) */
    case WAVE_FORMAT_G728_CELP = 0x0041;

    /** MSG723 (Microsoft) */
    case WAVE_FORMAT_MSG723 = 0x0042;

    /** MPEG (Microsoft) */
    case WAVE_FORMAT_MPEG = 0x0050;

    /** RT24 — alternative ID for Voxware MetaVoice (0x0074); prefer 0x0074 */
    case WAVE_FORMAT_RT24 = 0x0052;

    /** PAC (InSoft) */
    case WAVE_FORMAT_PAC = 0x0053;

    /** MPEG Layer 3 — ISO/MPEG Layer3 Format Tag */
    case WAVE_FORMAT_MPEGLAYER3 = 0x0055;

    /** Lucent G.723 */
    case WAVE_FORMAT_LUCENT_G723 = 0x0059;

    /** Cirrus Logic */
    case WAVE_FORMAT_CIRRUS = 0x0060;

    /** ESPCM (ESS Technology) */
    case WAVE_FORMAT_ESPCM = 0x0061;

    /** Voxware — now obsolete */
    case WAVE_FORMAT_VOXWARE = 0x0062;

    /** Canopus ATRAC — ATRACWAVEFORMAT */
    case WAVE_FORMAT_CANOPUS_ATRAC = 0x0063;

    /** G.726 ADPCM (APICOM) */
    case WAVE_FORMAT_G726_ADPCM = 0x0064;

    /** G.722 ADPCM (APICOM) */
    case WAVE_FORMAT_G722_ADPCM = 0x0065;

    /** DSAT (Microsoft) */
    case WAVE_FORMAT_DSAT = 0x0066;

    /** DSAT Display (Microsoft) */
    case WAVE_FORMAT_DSAT_DISPLAY = 0x0067;

    /** Voxware Byte Aligned — now obsolete */
    case WAVE_FORMAT_VOXWARE_BYTE_ALIGNED = 0x0069;

    /** Voxware AC8 — now obsolete */
    case WAVE_FORMAT_VOXWARE_AC8 = 0x0070;

    /** Voxware AC10 — now obsolete */
    case WAVE_FORMAT_VOXWARE_AC10 = 0x0071;

    /** Voxware AC16 — now obsolete */
    case WAVE_FORMAT_VOXWARE_AC16 = 0x0072;

    /** Voxware AC20 — now obsolete */
    case WAVE_FORMAT_VOXWARE_AC20 = 0x0073;

    /** Voxware MetaVoice — file and stream oriented */
    case WAVE_FORMAT_VOXWARE_RT24 = 0x0074;

    /** Voxware MetaSound — file and stream oriented */
    case WAVE_FORMAT_VOXWARE_RT29 = 0x0075;

    /** Voxware RT29HW — now obsolete */
    case WAVE_FORMAT_VOXWARE_RT29HW = 0x0076;

    /** Voxware VR12 — now obsolete */
    case WAVE_FORMAT_VOXWARE_VR12 = 0x0077;

    /** Voxware VR18 — now obsolete */
    case WAVE_FORMAT_VOXWARE_VR18 = 0x0078;

    /** Voxware TQ40 — now obsolete */
    case WAVE_FORMAT_VOXWARE_TQ40 = 0x0079;

    /** Softsound (Softsound Ltd.) */
    case WAVE_FORMAT_SOFTSOUND = 0x0080;

    /** Voxware TQ60 — now obsolete */
    case WAVE_FORMAT_VOXWARE_TQ60 = 0x0081;

    /** MSRT24 — alternative ID for Voxware MetaVoice (0x0074); prefer 0x0074 */
    case WAVE_FORMAT_MSRT24 = 0x0082;

    /** G.729A (AT&T Laboratories) */
    case WAVE_FORMAT_G729A = 0x0083;

    /** MVI MV12 (Motion Pixels) */
    case WAVE_FORMAT_MVI_MV12 = 0x0084;

    /** DF G.726 (DataFusion Systems) */
    case WAVE_FORMAT_DF_G726 = 0x0085;

    /** DF GSM610 (DataFusion Systems) */
    case WAVE_FORMAT_DF_GSM610 = 0x0086;

    /** ISIAudio (Iterated Systems) */
    case WAVE_FORMAT_ISIAUDIO = 0x0088;

    /** OnLive (OnLive! Technologies) */
    case WAVE_FORMAT_ONLIVE = 0x0089;

    /** SBC24 (Siemens Business Communications) */
    case WAVE_FORMAT_SBC24 = 0x0091;

    /** Dolby AC3 SPDIF (Sonic Foundry) */
    case WAVE_FORMAT_DOLBY_AC3_SPDIF = 0x0092;

    /** ZyXEL ADPCM */
    case WAVE_FORMAT_ZYXEL_ADPCM = 0x0097;

    /** Philips LPCBB */
    case WAVE_FORMAT_PHILIPS_LPCBB = 0x0098;

    /** Packed (Studer Professional Audio) */
    case WAVE_FORMAT_PACKED = 0x0099;

    /** Rhetorex ADPCM */
    case WAVE_FORMAT_RHETOREX_ADPCM = 0x0100;

    /** BeCubed Software's IRAT */
    case WAVE_FORMAT_IRAT = 0x0101;

    /** Vivo G.723 */
    case WAVE_FORMAT_VIVO_G723 = 0x0111;

    /** Vivo Siren */
    case WAVE_FORMAT_VIVO_SIREN = 0x0112;

    /** Digital G.723 (Digital Equipment Corporation) */
    case WAVE_FORMAT_DIGITAL_G723 = 0x0123;

    /** Creative ADPCM (Creative Labs) */
    case WAVE_FORMAT_CREATIVE_ADPCM = 0x0200;

    /** Creative FastSpeech8 (Creative Labs) */
    case WAVE_FORMAT_CREATIVE_FASTSPEECH8 = 0x0202;

    /** Creative FastSpeech10 (Creative Labs) */
    case WAVE_FORMAT_CREATIVE_FASTSPEECH10 = 0x0203;

    /** Quarterdeck (Quarterdeck Corporation) */
    case WAVE_FORMAT_QUARTERDECK = 0x0220;

    /** FM Towns Sound (Fujitsu) */
    case WAVE_FORMAT_FM_TOWNS_SND = 0x0300;

    /** BTV Digital — Brooktree digital audio format */
    case WAVE_FORMAT_BTV_DIGITAL = 0x0400;

    /** VME VMPCM (AT&T Labs) */
    case WAVE_FORMAT_VME_VMPCM = 0x0680;

    /** Olivetti GSM */
    case WAVE_FORMAT_OLIGSM = 0x1000;

    /** Olivetti ADPCM */
    case WAVE_FORMAT_OLIADPCM = 0x1001;

    /** Olivetti CELP */
    case WAVE_FORMAT_OLICELP = 0x1002;

    /** Olivetti SBC */
    case WAVE_FORMAT_OLISBC = 0x1003;

    /** Olivetti OPR */
    case WAVE_FORMAT_OLIOPR = 0x1004;

    /** LH Codec (Lernout & Hauspie) */
    case WAVE_FORMAT_LH_CODEC = 0x1100;

    /** Norris Communications */
    case WAVE_FORMAT_NORRIS = 0x1400;

    /** ISIAudio 2 (AT&T Labs) */
    case WAVE_FORMAT_ISIAUDIO_2 = 0x1401;

    /** Soundspace Music Compression (AT&T Labs) */
    case WAVE_FORMAT_SOUNDSPACE_MUSICOMPRESS = 0x1500;

    /** AC3 DVM (FAST Multimedia) */
    case WAVE_FORMAT_DVM = 0x2000;

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Returns the hexadecimal string representation of the registration number.
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
     * @param  string $hex Hexadecimal string with or without "0x" prefix
     * @return self|null   Matching enum case, or null if not found
     */
    public static function fromHex(string $hex): ?self
    {
        $int = (int) hexdec(ltrim($hex, '0x'));
        return self::tryFrom($int);
    }
}
