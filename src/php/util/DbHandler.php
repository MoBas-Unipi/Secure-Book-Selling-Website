<?php

/**
 * DbHandler class
 *
 * Singleton class to handle database connections and operations using MySQLi.
 */
class DbHandler
{
    /**
     * The single instance of the DbHandler class
     *
     * @var DbHandler|null
     */
    private static ?DbHandler $instance = null;

    /**
     * The MySQLi connection
     *
     * @var mysqli|null
     */
    private $mysqli_connection = null;

    /**
     * Database connection parameters
     *
     * @var string
     */
    private $host;
    private $user;
    private $password;
    private $dbName;
    private $port;

    /**
     * Private constructor to prevent direct instantiation
     *
     * Initializes database connection parameters and opens the connection.
     */
    private function __construct()
    {
        $this->host = "mysql-server";
        $this->user = getenv("MYSQL_USER");
        $this->password = getenv("MYSQL_PASSWORD");
        $this->dbName = getenv("MYSQL_DATABASE");
        $this->port = '3306';
        $this->openConnection();
    }

    /**
     * Gets the single instance of the DbHandler class
     *
     * @return DbHandler|null The single instance of the DbHandler class
     */
    public static function getInstance(): ?DbHandler
    {
        if (self::$instance == null) {
            self::$instance = new DbHandler();
        }
        return self::$instance;
    }

    /**
     * Opens a new database connection if it is not already opened
     *
     * @throws Exception If connection or database selection fails
     */
    function openConnection(): void
    {
        if (!$this->isOpened()) {
            $this->mysqli_connection = new mysqli(
                $this->host,
                $this->user,
                $this->password,
                $this->dbName,
                $this->port
            );
            if ($this->mysqli_connection->connect_error) {
                throw new Exception('Connection Error (' . $this->mysqli_connection->connect_errno . ') ' .
                    $this->mysqli_connection->connect_error);
            }
            $this->mysqli_connection->select_db($this->dbName) or
            throw new Exception('Cannot use the database: ' . $this->mysqli_connection->error);
        }
    }

    /**
     * Checks if the database connection is opened
     *
     * @return bool True if the connection is opened, false otherwise
     */
    function isOpened(): bool
    {
        return ($this->mysqli_connection != null);
    }

    /**
     * Performs a database query
     *
     * @param string $crudOperation The type of CRUD operation ("SELECT", "INSERT", "UPDATE", "DELETE")
     * @param string $querytext The SQL query text
     * @param array $parameters The parameters for the prepared statement (optional)
     * @param string $types The types of the parameters (optional)
     *
     * @return mixed The result of the query execution. For "SELECT" operations, returns a mysqli_result object. For other operations, returns a boolean.
     * @throws Exception If the query preparation, binding, or execution fails
     */
    function performQuery(string $crudOperation, string $querytext, array $parameters = [], string $types = "")
    {
        if (!$this->isOpened()) {
            $this->openConnection();
        }

        $statement = $this->mysqli_connection->prepare($querytext);
        if (!$statement) {
            throw new Exception('Prepare failed (' . $this->mysqli_connection->connect_errno . ') ' .
                $this->mysqli_connection->connect_error);
        }

        if (!empty($parameters) && !$statement->bind_param($types, ...$parameters)) {
            throw new Exception('Bind failed (' . $statement->connect_errno . ') ' .
                $statement->connect_error);
        }

        $executionReturn = $statement->execute();
        if (!$executionReturn) {
            throw new Exception('Execute failed (' . $statement->connect_errno . ') ' .
                $statement->connect_error);
        }

        if ($crudOperation == "SELECT") {
            $result = $statement->get_result();
            if (!$result) {
                throw new Exception('Get Result failed (' . $statement->connect_errno . ') ' .
                    $statement->connect_error);
            }
            return $result;
        } else {
            return $executionReturn;
        }
    }

    /**
     * Closes the database connection
     */
    function closeConnection(): void
    {
        $this->mysqli_connection->close();
        $this->mysqli_connection = null;
    }
}
