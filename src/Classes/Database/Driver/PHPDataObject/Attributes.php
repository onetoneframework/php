<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Database\Driver;

use PDO;

/**
 * Class PHPDataObjectAttributes
 *
 * This class provides methods to set various attributes for a PDO connection. It allows for configuring column name case, error reporting mode, timeout, statement class, and other PDO attributes related to MySQL connections.
 */
class PHPDataObjectAttributes
{
    // Static property to hold the PDO connection instance.
    private static $connection;

    // Constructor to initialize the PDO connection instance.
    public function __construct($connection)
    {
        self::$connection = $connection;
    }

    /**
     * Set a specific attribute for the PDO connection.
     *
     * @param mixed $key The attribute key to set.
     * @param mixed $value The value to set for the specified attribute.
     * @return void
     */
    public function setAttribute($key, $value)
    {
        self::$connection->setAttribute($key, $value);
    }

    /**
     * Set column name case for the PDO connection.
     * @param mixed $value
     * @return void
     */
    public function setColumNameCase($value)
    {
        $this->setAttribute(PDO::ATTR_CASE, $value);
    }

    /**
     * Set column name case to natural case.
     * @return void
     */
    public function setColumNameNaturalCase()
    {
        $this->setColumNameCase(PDO::CASE_NATURAL);
    }

    /**
     * Set column name case to lower case.
     * @return void
     */
    public function setColumNameLowerCase()
    {
        $this->setColumNameCase(PDO::CASE_LOWER);
    }

    /**
     * Set column name case to upper case.
     * @return void
     */
    public function setColumNameUpperCase()
    {
        $this->setColumNameCase(PDO::CASE_UPPER);
    }

    /**
     * Set the error reporting mode for the PDO connection.
     *
     * @param mixed $value The error reporting mode to set (e.g., PDO::ERRMODE_SILENT, PDO::ERRMODE_WARNING, PDO::ERRMODE_EXCEPTION).
     * @return void
     */
    public function setErrorReportingMode($value)
    {
        $this->setAttribute(PDO::ATTR_ERRMODE, $value);
    }

    /**
     * Convenience methods for setting specific error reporting modes.
     * These methods set the error reporting mode to silent, warning, or exception, respectively.
     */
    public function setSilentErrorReportingMode()
    {
        $this->setColumNameCase(PDO::ERRMODE_SILENT);
    }

    /**
     * Set the error reporting mode to warning.
     * @return void
     */
    public function setWarningErrorReportingMode()
    {
        $this->setColumNameCase(PDO::ERRMODE_WARNING);
    }

    /**
     * Set the error reporting mode to exception.
     * @return void
     */
    public function setExceptionErrorReportingMode()
    {
        $this->setColumNameCase(PDO::ERRMODE_EXCEPTION);
    }

    /** 
     * Set the timeout duration for the PDO connection.
     *
     * @param int $value The timeout duration in seconds.
     * @return void
     */
    public function setTimeout(int $value)
    {
        $this->setAttribute(PDO::ATTR_TIMEOUT, $value);
    }

    /** 
     * Set the class to be used for PDO statements.
     *
     * @param array $class An array containing the class name and any constructor arguments for the statement class.
     * @return void
     */
    public function setStatementClass($class)
    {
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, $class);
    }

    /**
     * Enable or disable emulation of prepared statements for the PDO connection.
     *
     * @param bool $bool A boolean value indicating whether to enable (true) or disable (false) emulation of prepared statements.
     * @return void
     */
    public function useEmulatePreparedStatement(bool $bool)
    {
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, $bool);
    }

    /** 
     * Enable or disable the use of buffered queries for the PDO connection.
     * If this attribute is set to true on a PDOStatement, the MySQL driver will use the buffered versions of the MySQL API.
     *
     * @param bool $bool A boolean value indicating whether to enable (true) or disable (false) the use of buffered queries.
     * @return void
     */
    public function useBufferedQueries(bool $bool): void
    {
        if (PHP_VERSION_ID >= 80400 && class_exists('PDO\MySQL')) {
            // @phpstan-ignore-next-line
            $this->setAttribute(PDO\MySQL::ATTR_USE_BUFFERED_QUERY, $bool);
        } else {
            $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $bool);
        }
    }

    /** 
     * Set the default fetch mode for the PDO connection.
     *
     * @param mixed $string The fetch mode to set (e.g., PDO::FETCH_ASSOC, PDO::FETCH_NUM, PDO::FETCH_OBJ).
     * @return void
     */
    public function setDefaultFetchMode($string)
    {
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $string);
    }

    /**
     * Set the default fetch mode to associative array.
     * @return void
     */
    public function setAssociativeArrayFetch()
    {
        $this->setDefaultFetchMode(PDO::FETCH_ASSOC);
    }

    /** 
     * Set the default fetch mode to named array, which allows for duplicate column names to be fetched without overwriting each other.
     * @return void
     */
    public function setAssociativeArraySameColumnNameFetch()
    {
        $this->setDefaultFetchMode(PDO::FETCH_NAMED);
    }

    /**
     * Set the default fetch mode to numeric array.
     * @return void
     */
    public function setColunNumberFetch()
    {
        $this->setDefaultFetchMode(PDO::FETCH_NUM);
    }

    /** 
     * Set the default fetch mode to object.
     * @return void
     */
    public function setPredefinedClassFetch()
    {
        $this->setDefaultFetchMode(PDO::FETCH_OBJ);
    }

    /** 
     * Set a command to be executed when connecting to the MySQL server. This command will automatically be re-executed when reconnecting.
     * Command to execute when connecting to the MySQL server. Will automatically be re-executed when reconnecting.
     *
     * @param string $string The command to execute upon connecting to the MySQL server.
     * @return void
     */
    public function setConnectionCommand($string)
    {
        $this->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, $string);
    }

    /** 
     * Set the file path to the SSL certificate for the PDO connection.
     *
     * @param string $filepath The file path to the SSL certificate.
     * @return void
     */
    public function setSSLCertificateFilePath($filepath)
    {
        $this->setAttribute(PDO::MYSQL_ATTR_SSL_CERT, $filepath);
    }

    /** 
     * Set the file path to the directory that contains the trusted SSL CA certificates for the PDO connection. The certificates should be stored in PEM format.
     *
     * @param string $filepath The file path to the directory containing the trusted SSL CA certificates.
     * @return void
     */
    public function setTrustedSSLCACertificateFilePath($filepath)
    {
        $this->setAttribute(PDO::MYSQL_ATTR_SSL_CAPATH, $filepath);
    }

    /** 
     * Set the file path to the SSL key for the PDO connection.
     *
     * @param string $filepath The file path to the SSL key.
     * @return void
     */
    public function setSSLCertificateKeyFilePath($filepath)
    {
        $this->setAttribute(PDO::MYSQL_ATTR_SSL_KEY, $filepath);
    }

    /** 
     * Enable or disable network communication compression for the PDO connection. This can help reduce the amount of data transmitted between the client and the MySQL server, potentially improving performance for large queries or result sets.
     *
     * @param bool $enable A boolean value indicating whether to enable (true) or disable (false) network communication compression.
     * @return void
     */
    public function setNetworkCommunicationCompression($enable)
    {
        $this->setAttribute(PDO::MYSQL_ATTR_COMPRESS, $enable);
    }

    /** 
     * Set the SSL cipher to use for the PDO connection. This allows you to specify the encryption cipher to be used for SSL connections to the MySQL server.
     *
     * @param string $cipher The name of the SSL cipher to use for the connection.
     * @return void
     */
    public function setSSLEncryptionCipher($cipher)
    {
        $this->setAttribute(PDO::MYSQL_ATTR_SSL_CIPHER, $cipher);
    }

    /** 
     * Enable or disable multi query execution for the PDO connection. This allows for multiple SQL statements to be executed in a single query string, which can be useful for certain operations but may also pose security risks if not used carefully.
     *
     * @param int $size The number of statements to allow in a single query string (default is 1). Setting this to a value greater than 1 enables multi query execution.
     * @return void
     */
    public function useMultiQueryExecution($size)
    {
        $this->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, $size);
    }

}
