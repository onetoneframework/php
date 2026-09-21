<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Linux;

/**
 * Enumeration class for Linux packages.
 */
enum LinuxPackage: string
{
    /**
     * Advanced Linux Sound Architecture
     */
    case ALSA = "alsa";
    /**
     * Firmware Data Files for ALSA
     */
    case ALSA_FIREWARE = "alsa-firmware";
    /**
     * LD_PRELOAD-able library that translate
     */
    case ALSA_OSS = "alsa-oss";
    /**
     * Extra Plug-Ins for ALSA Library
     */
    case ALSA_PLUGINS = "alsa-plugins";
    /**
     * Plug-Ins for ALSA Library to Access OSS Devices
     */
    case ALSA_PLUGINS_OSS = "alsa-plugins-oss";
    /**
     * Pulseaudio Plug-In for ALSA Library
     */
    case ALSA_PLUGINS_PULSE = "alsa-plugins-pulse";
    /**
     * Rate Converter Plug-In for ALSA Library
     */
    case ALSA_PLUGINS_SPEEXRATE = "alsa-plugins-speexrate";
    /**
     * PCM Up-mix Plug-In for ALSA Library 
     */
    case ALSA_PLUGINS_UPMIX = "alsa-plugins-upmix";
    /**
     * PCM I/O Plug-In for ALSA Library to access USB USx2y audio
     */
    case ALSA_PLUGINS_USB_STREAM = "alsa-plugins-usb-stream";
    /**
     * ALSA UCM Profiles
     */
    case ALSA_UCM_CONF = "alsa-ucm-conf";
    /**
     * Advanced Linux Sound Architecture Utility
     */
    case ALSA_UTILS = "alsa-utils";
    /**
     * PulseAudio emulation for ALSA
     */
    case APULSE = "apulse";
    /**
     * ALSA Topology Library
     */
    case LIBATOPOLOGY2 = "libatopology2";
    /**
     * "alsamixer" for pulseaudio
     */
    case PAMIX = "pamix";
    /**
     * PipeWire media server ALSA support
     */
    case PIPEWIRE_ALSA = "pipewire-alsa";
    /**
     * Qt bindings for PulseAudio
     */
    case LIBKF5PULSEAUDIOQT2 = "libKF5PulseAudioQt2";
    /**
     * GLIB 2.0 Main Loop wrapper
     */
    case LIBPULSE_MAINLOOP_GLIB0 = "libpulse-mainloop-glib0";
    /**
     * Client interface to PulseAudio
     */
    case LIBPULSE0 = "libpulse0";
    /**
     * Gstreamer Plugin for PipeWire
     */
    case GSTREAMER_PLUGIN_PIPEWIRE = "gstreamer-plugin-pipewire";
    /**
     * https://software.opensuse.org/package/pipewire
     * 
     * A Multimedia Framework designed to be an audio and video server and more
     */
    case PIPEWIRE = "pipewire";
    case LIBAVCODEC_EXTRA = "libavcodec-extra";
    case CMAKE = "cmake";
    case P7ZIP_FULL = "p7zip-full";
    case LIBBOOST_ALL_DEV = "libboost-all-dev";
    case LIBGOOGLE_PERFTOOLS_DEV = "libgoogle-perftools-dev";
    case LIBOMP_DEV = "libomp-dev";
    case LIBOPENBLAS_DEV = "libopenblas-dev";
    case LIBPROTOBUF_DEV = "libprotobuf-dev";
    case PROTOBUF_COMPILER = "protobuf-compiler";
    case LIBZMQ3_DEV = "libzmq3-dev";
    case LIBAIO_DEV = "libaio-dev";
    case LIBCURL4_OPENSSL_DEV = "libcurl4-openssl-dev";
    case LIBFFI_DEV = "libffi-dev";
    case LIBFREETYPE6_DEV = "libfreetype6-dev";
    case LIBGOMP1 = "libgomp1";
    case libicu_dev = "libicu-dev";
    case libjpeg_dev = "libjpeg-dev";
    case LIBMAGICKPLUSPLUS_DEV = "libmagick++-dev";
    case LIBMAGICKWAND_DEV = "libmagickwand-dev";
    case LIBMCRYPT_DEV = "libmcrypt-dev";
    case LIBONIG_DEV = "libonig-dev";
    case LIBEIGEN3_DEV = "libeigen3-dev";
    case LIBOPENBLAS0 = "libopenblas0";
    case LIBPCRE2_DEV = "libpcre2-dev";
    case LIBPNG_DEV = "libpng-dev";
    case LIBPQ_DEV = "libpq-dev";
    case LIBSSL_DEV = "libssl-dev";
    case LIBTOOL = "libtool";
    case LIBWEBP_DEV = "libwebp-dev";
    case LIBXML2_DEV = "libxml2-dev";
    case LIBXPM_DEV = "libxpm-dev";
    case LIBZ_DEV = "libz-dev";
    case LIBZIP_DEV = "libzip-dev";
    case LSB_RELEASE = "lsb-release";
    case NANO = "nano";
    case PYTHON3_LAUNCHPADLIB = "python3-launchpadlib";
    case UNIXODBC_DEV = "unixodbc-dev";
    case ZLIB1G_DEV = "zlib1g-dev";
    case LIBMEMCACHED_TOOLS = "libmemcached-tools";
    case LIBMEMCACHED_DEV = "libmemcached-dev";
    case LIBMEMCACHEDUTIL2 = "libmemcachedutil2";
    case LIBMEMCACHED11 = "libmemcached11";
    case WATCH = "watch";
    case NINJA_BUILD = "ninja-build";
    case GIT = "git";
    case CMAKE_DATA = "cmake-data";
    case PYTHON3 = "python3";
    case GNUPG = "gnupg";
    case CA_CERTIFICATES = "ca-certificates";
    case APT_TRANSPORT_HTTPS = "apt-transport-https";
    case LIBSTDCPLUSPLUS_6 = "libstdc++6";
    case AUTOCONF = "autoconf";
    case DNSUTILS = "dnsutils";
    case UNZIP = "unzip";
    case VIM = "vim";
    case ZLIB1G = "zlib1g";
    case EXIFTOOL = "exiftool";
    case INOTIFY_TOOLS = "inotify-tools";
    case GOSU = "gosu";
    case FLAC = "flac";
    case FFMPEG = "ffmpeg";
    case APACHE2 = "apache2";
    case RE2C = "re2c";
    case LAME = "lame";
    case LIBEV_LIBEVENT_DEV = "libev-libevent-dev";
    case LIBFAAC_DEV = "libfaac-dev";
    case LIBMP3LAME_DEV = "libmp3lame-dev";
    case LIBTHEORA_DEV = "libtheora-dev";
    case LIBVPX_DEV = "libvpx-dev";
    case LOGROTATE = "logrotate";
    case PHP8_2 = "php8.2";
    case PHP8_2_CURL = "php8.2-curl";
    case PHP8_2_DEV = "php8.2-dev";
    case PHP8_2_GD = "php8.2-gd";
    case PHP8_2_INTL = "php8.2-intl";
    case PHP8_2_LDAP = "php8.2-ldap";
    case PHP8_2_MYSQL = "php8.2-mysql";
    case PHP8_2_XML = "php8.2-xml";
    case PHP8_2_ZIP = "php8.2-zip";
    case PHP8_5 = "php8.5";
    case PHP8_5_CURL = "php8.5-curl";
    case PHP8_5_DEV = "php8.5-dev";
    case PHP8_5_GD = "php8.5-gd";
    case PHP8_5_INTL = "php8.5-intl";
    case PHP8_5_LDAP = "php8.5-ldap";
    case PHP8_5_MYSQL = "php8.5-mysql";
    case PHP8_5_XML = "php8.5-xml";
    case PHP8_5_ZIP = "php8.5-zip";
    case PHP_PEAR = "php-pear";
    case PWGEN = "pwgen";
    case SUPERVISOR = "supervisor";
    case VORBIS_TOOLS = "vorbis-tools";
    case LIBDVD_PKG = "libdvd-pkg";
    case EXTREPO = "extrepo";
    case BUSYBOX_STATIC = "busybox-static";
    case BASH = "bash";
    case LIBJPEG62_TURBO_DEV = "libjpeg62-turbo-dev";
    case LIBGMP_DEV = "libgmp-dev";
}