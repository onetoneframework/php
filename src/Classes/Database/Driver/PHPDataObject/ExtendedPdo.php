<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Database\Driver;

use Override;
use PDO;

/**
 * ExtendedPdo class extends the built-in PDO class to provide additional functionality for database connections. It allows for immediate connection establishment and retains connection arguments for later use.
 */
class ExtendedPdo extends PDO
{
    // Constant to indicate that the connection should be established immediately.
    public const CONNECT_IMMEDIATELY = 'immediate';

    // Array to retain the arguments used for the PDO connection.
    protected array $args = [];

    // Flag to indicate whether driver-specific connection methods should be used.
    protected bool $driverSpecific = false;

    // Property to hold the PDO connection instance.
    private $pdo;

    /**
     * Constructor for the ExtendedPdo class.
     *
     * This constructor initializes the PDO connection with the provided DSN, username, password, and options. If the CONNECT_IMMEDIATELY option is set in the options array, it establishes the connection immediately using the establishConnection method. It also ensures that if no error mode is specified in the options, it defaults to using exceptions for error handling.
     *
     * @param string $dsn The Data Source Name, which contains the information required to connect to the database.
     * @param string|null $username The username for the database connection (optional).
     * @param string|null $password The password for the database connection (optional).
     * @param array|null $options An array of options for the PDO connection (optional).
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        // if no error mode is specified, use exceptions
        if (!isset($options[PDO::ATTR_ERRMODE])) {
            $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        }

        // retain the arguments for later
        $this->args = [$dsn, $username, $password, $options,];

        if (isset($options[self::CONNECT_IMMEDIATELY])) {
            $this->establishConnection($dsn, $username, $password, $options);
        }

        parent::__construct($dsn, $username, $password, $options);
    }

    /*#[Override]
    public static function connect(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        $pdo = new self($dsn, $username, $password, $options);
        $pdo->driverSpecific = true;
        return $pdo;
    }*/

    /**
     * Establish a connection to the database using the provided DSN, username, password, and options.
     * This method checks if a connection has already been established. If not, it attempts to create a new PDO connection using either a driver-specific method (if available) or the standard PDO constructor.
     * @param string $dsn The Data Source Name, which contains the information required to connect to the database.
     * @param string|null $username The username for the database connection (optional).
     * @param string|null $password The password for the database connection (optional).
     * @param array|null $options An array of options for the PDO connection (optional).
     * @return void
     */
    function establishConnection($dsn, $username, $password, $options): void
    {
        if ($this->pdo) {
            return;
        }

        if ($this->driverSpecific && method_exists('PDO', 'connect')) {
            $this->pdo = PDO::connect($dsn, $username, $password, $options);
        } else {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        }
    }

}
