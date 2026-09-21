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
use Error;

class SSHClient extends BaseClass
{
    private $connection;
    private $sftpConnection;
    private string $hostname;
    private int $port = 22;
    private string $username;
    private string $password;

    public function __construct(string $hostname, int $port = 22, ?string $username = null, ?string $password = null)
    {
        if (!extension_loaded('ssh2') | !function_exists("ssh2_connect")) {
            throw new Error("SSH2 extension is not installed.");
        }

        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function connectSftp()
    {
        $this->sftpConnection = ssh2_sftp($this->connection);

        if (!$this->sftpConnection) {
            throw new Error('Failed to create a sftp connection.');
        }
    }

    public function connect()
    {
        $this->connection = ssh2_connect($this->hostname, $this->port);
        if (!$this->connection) {
            throw new Error("Could not connect to SSH server.");
        }

        if (!ssh2_auth_password($this->connection, $this->username, $this->password)) {
            throw new Error("SSH authentication failed.");
        }
    }

    public function execute($command)
    {
        $stream = ssh2_exec($this->connection, $command);
        if (!$stream) {
            throw new Error("Could not execute command: $command");
        }

        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);
        fclose($stream);
        return $output;
    }

    public function close()
    {
        ssh2_exec($this->connection, 'echo "EXITING" && exit;');
    }

    public function disconnect(): bool
    {
        return ssh2_disconnect($this->connection);
    }

    public function uploadFile($localPath, $remotePath, $mode = 0644): bool
    {
        return ssh2_scp_send($this->connection, $localPath, $remotePath, $mode);
    }

    public function downloadFile($remotePath, $localPath): bool
    {
        return ssh2_scp_recv($this->connection, $remotePath, $localPath);
    }

    public function createDirectory($directory): bool
    {
        return ssh2_sftp_mkdir($this->sftpConnection, $directory);
    }

    public function rename($path, $name): bool
    {
        return ssh2_sftp_rename($this->sftpConnection, $path, $name);
    }


}
