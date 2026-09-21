<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Client;

use Clover\Classes\BaseClass;
use FTP\Connection;
use function in_array;

/**
 * Class FTP
 *
 * @package Clover\Classes\Client
 */
class FTP extends BaseClass
{
    /**
     * FTP connection context
     * @var Connection $context
     */
    private Connection $context;

    /**
     * FTP constructor.
     * 
     * @param string $hostname
     * @param int $port
     * @param int $timeout
     */
    public function __construct(string $hostname, int $port = 21, int $timeout = 90)
    {
        $connection = ftp_connect($hostname, $port, $timeout);

        if (!$connection) {
            return;
        }

        $this->context = $connection;
    }

    /**
     * Log in to the FTP server
     * 
     * @param string $username
     * @param string $password
     * 
     * @return bool
     */
    public function login(string $username, string $password): bool
    {
        return ftp_login($this->context, $username, $password);
    }

    /**
     * Get a file from the FTP server
     * 
     * @param string $local_filename
     * @param string $remote_filename
     * @param int    $mode
     * @param int    $offset
     * 
     * @return bool
     */
    public function get(string $local_filename, string $remote_filename, int $mode = FTP_BINARY, int $offset = 0): bool
    {
        return ftp_get($this->context, $local_filename, $remote_filename, $mode, $offset);
    }

    /**
     * Put a file to the FTP server
     * 
     * @param string     $remote_filename
     * @param string     $local_filename
     * @param int        $mode
     * @param int        $offset
     * 
     * @return bool
     */
    public function put(string $remote_filename, string $local_filename, int $mode = FTP_BINARY, int $offset = 0): bool
    {
        return ftp_put($this->context, $remote_filename, $local_filename, $mode, $offset);
    }

    /**
     * Close the FTP connection
     * 
     * @return bool
     */
    public function close(): bool
    {
        return ftp_close($this->context);
    }

    /**
     * Delete a file on the FTP server
     * 
     * @param string $filename
     * 
     * @return bool
     */
    public function delete(string $filename): bool
    {
        return ftp_delete($this->context, $filename);
    }

    /**
     * Download a file from the FTP server to a stream
     * 
     * @param resource $stream
     * @param string   $remote_filename
     * @param int      $mode
     * @param int      $offset
     * 
     * @return bool: bool
     */
    public function downloadFile(mixed $stream, string $remote_filename, int $mode = FTP_BINARY, int $offset = 0): bool
    {
        return ftp_fget($this->context, $stream, $remote_filename, $mode, $offset);
    }

    /**
     * Upload a file to the FTP server from a stream
     * 
     * @param string   $remote_filename
     * @param resource $stream
     * @param int      $mode
     * @param int      $offset
     * 
     * @return bool
     */
    public function uploadFile(string $remote_filename, mixed $stream, int $mode = FTP_BINARY, int $offset = 0): bool
    {
        return ftp_fput($this->context, $remote_filename, $stream, $mode, $offset);
    }

    /**
     * Create a directory on the FTP server
     * 
     * @param string $directory
     * 
     * @return bool|string
     */
    public function createDirectory(string $directory): bool|string
    {
        return ftp_mkdir($this->context, $directory);
    }

    /**
     * Get a list of files in a directory on the FTP server
     * @param string $directory
     * @return array|bool
     */
    public function getListOfFiles(string $directory): array|bool
    {
        return ftp_mlsd($this->context, $directory);
    }

    /**
     * Get the current directory on the FTP server
     * 
     * @return bool|string
     */
    public function getCurrentDirectory(): bool|string
    {
        return ftp_pwd($this->context);
    }

    /**
     * Remove a directory on the FTP server
     * 
     * @param string $directory
     * 
     * @return bool
     */
    public function removeDirectory(string $directory): bool
    {
        return ftp_rmdir($this->context, $directory);
    }

    /**
     * Get the size of a file on the FTP server
     * 
     * @param string $filename
     * 
     * @return int: int
     */
    public function getFileSize(string $filename): int
    {
        return ftp_size($this->context, $filename);
    }

    /**
     * Get the last modified time of a file on the FTP server
     * 
     * @param string $filename
     * 
     * @return int: int
     */
    public function getFileModifiedTime(string $filename): int
    {
        return ftp_mdtm($this->context, $filename);
    }

    /**
     * Change the current directory on the FTP server
     * 
     * @param string $directory
     * 
     * @return bool
     */
    public function changeDirectory(string $directory): bool
    {
        return ftp_chdir($this->context, $directory);
    }

    /**
     * Rename a file on the FTP server
     * 
     * @param string $oldname
     * @param string $newname
     * 
     * @return bool
     */
    public function rename(string $oldname, string $newname): bool
    {
        return ftp_rename($this->context, $oldname, $newname);
    }

    /**
     * Check if a file exists on the FTP server
     * 
     * @param string $filename
     * 
     * @return bool
     */
    public function fileExists(string $filename): bool
    {
        $files = ftp_nlist($this->context, '.');
        return in_array($filename, $files);
    }

    /**
     * Check if a directory exists on the FTP server
     * 
     * @param string $directory
     * 
     * @return bool
     */
    public function directoryExists(string $directory): bool
    {
        $currentDir = $this->getCurrentDirectory();
        if ($this->changeDirectory($directory)) {
            $this->changeDirectory($currentDir);
            return true;
        }

        return false;
    }

    /**
     * Get the FTP connection context
     * 
     * @return Connection
     */
    public function getContext(): Connection
    {
        return $this->context;
    }

    /**
     * Destructor to ensure the FTP connection is closed
     */
    public function __destruct()
    {
        $this->close();
    }
}
