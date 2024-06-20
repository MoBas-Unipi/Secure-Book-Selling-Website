<?php

class Logger
{
    /**
     * @var Logger|null The singleton instance of the Logger class.
     */
    private static ?Logger $instance = null;

    /**
     * @var string The path to the file where log messages will be stored.
     */
    private string $logFilePath;

    /**
     * @var bool A flag indicating whether debug mode is enabled or not.
     */
    private bool $debugMode;

    /**
     * Logger constructor.
     *
     * @param string $logFilePath The path to the file where log messages will be stored.
     * @param bool $debugMode A flag indicating whether debug mode is enabled or not.
     */
    private function __construct(string $logFilePath, bool $debugMode)
    {
        $this->logFilePath = $logFilePath;
        $this->debugMode = $debugMode;
    }

    /**
     * Returns the singleton instance of the Logger class.
     *
     * @param string $logFilePath The file path where log messages will be stored.
     * @param bool $debugMode A flag indicating whether debug mode is enabled or not.
     * @return Logger|null The singleton instance of the Logger class.
     */
    public static function getInstance(string $logFilePath, bool $debugMode): ?Logger
    {
        if (self::$instance === null) {
            self::$instance = new Logger($logFilePath, $debugMode);
        }
        return self::$instance;
    }

    /**
     * Logs a message with optional context, error code, and additional info.
     *
     * @param string $logLevel The level of the log message (e.g., 'INFO', 'ERROR', etc.).
     * @param string $message The log message to be logged.
     * @param string|null $context Optional context information (filename and its path).
     * @param string|null $error Optional error message.
     * @param string|null $info Optional information related to the message.
     * @return void
     */
    public function log(string $logLevel, string $message,
                        ?string $context = null, ?string $error = null, ?string $info = null): void
    {
        // Format the log message.
        $formattedLog = $this->formatLog($logLevel, $message, $context, $error, $info);

        // Write the formatted log to the log file.
        $this->writeToFile($formattedLog);
    }

    /**
     * Formats the log message with timestamp and optional details.
     *
     * @param string $logLevel The level of the log message (e.g., 'INFO', 'ERROR', etc.).
     * @param string $message The log message to be formatted.
     * @param string|null $context Optional context information (filename and its path).
     * @param string|null $error Optional error message.
     * @param string|null $info Optional information related to the message.
     * @return string The formatted log message.
     */
    private function formatLog(string  $logLevel, string $message,
                               ?string $context = null, ?string $error = null, ?string $info = null): string
    {
        // Get current timestamp.
        $timestamp = date("Y-m-d H:i:s");

        // Start with the log level and timestamp.
        $formattedLog = "[$logLevel] - $timestamp - $message";

        // Include optional details if debug mode is enabled.
        if ($this->debugMode) {
            if ($info !== null) {
                $formattedLog .= " - $info";
            }
            if ($context !== null) {
                $formattedLog .= " - Context: $context";
            }
            if ($error !== null) {
                $formattedLog .= " - Error: $error";
            }
        }

        // Append a new line character at the end of the log message.
        return $formattedLog . PHP_EOL;
    }

    /**
     * Writes the formatted log message to the log file.
     *
     * @param string $formattedLog The formatted log message to be written to the log file.
     * @return void
     */
    private function writeToFile(string $formattedLog): void
    {
        // Write the log message appending it to the end of the file.
        file_put_contents($this->logFilePath, $formattedLog, FILE_APPEND);
    }
}
