<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\OperationSystem\Linux;

use Clover\Classes\OperationSystem\Linux\Base;

/**
 * Class Extension
 *
 * @package Clover\Classes\OperationSystem\Linux
 */
class Extension extends Base
{
    /** 
     * Check if the EGL library is installed
     * 
     * @return bool True if the EGL library is installed, false otherwise
     */
    public static function isEGLInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/lib64/libEGL.so');
    }
    
    /** 
     * Check if the GL dispatch library is installed
     * 
     * @return bool True if the GL dispatch library is installed, false otherwise
     */
    public static function isGLDispatchInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/lib64/libGLdispatch.so');
    }

    /** 
     * Check if the GLESv1_CM library is installed
     * 
     * @return bool True if the GLESv1_CM library is installed, false otherwise
     */
    public static function isGLESV1_CmInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/lib64/libGLESv1_CM.so');
    }

    /** 
     * Check if the GLESv2 library is installed
     * 
     * @return bool True if the GLESv2 library is installed, false otherwise
     */
    public static function isGLESV2Installed(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/lib64/libGLESv2.so');
    }

    /** 
     * Check if the GL library is installed
     * 
     * @return bool True if the GL library is installed, false otherwise
     */
    public static function isGLInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/lib64/libGL.so');
    }

    /** 
     * Check if the GLX library is installed
     * 
     * @return bool True if the GLX library is installed, false otherwise
     */
    public static function isGLXInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/lib64/libGLX.so');
    }

    /** 
     * Check if the OpenGL library is installed
     * 
     * @return bool True if the OpenGL library is installed, false otherwise
     */
    public static function isOpenGLInstalled(): bool
    {
        return self::isLinuxExtensionInstalled('/usr/lib64/libOpenGL.so');
    }

}