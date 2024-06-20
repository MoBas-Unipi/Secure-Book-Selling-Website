<?php

ErrorHandler::getInstance();

/**
 * ErrorHandler class
 *
 * Singleton class to handle PHP errors and exceptions.
 */
class ErrorHandler
{
    /**
     * The single instance of the ErrorHandler class
     *
     * @var ErrorHandler|null
     */
    private static ?ErrorHandler $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     *
     * Sets the error and exception handlers.
     */
    private function __construct()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Gets the single instance of the ErrorHandler class
     *
     * @return ErrorHandler|null The single instance of the ErrorHandler class
     */
    public static function getInstance(): ?ErrorHandler
    {
        if (self::$instance == null) {
            self::$instance = new ErrorHandler();
        }

        return self::$instance;
    }

    /**
     * Handles PHP errors by converting them to ErrorException
     *
     * @param int $level The error level
     * @param string $message The error message
     * @param string $file The file where the error occurred (optional)
     * @param int $line The line where the error occurred (optional)
     *
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0)
    {
        throw new ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Handles uncaught exceptions
     *
     * Displays the exception message in an alert box
     *
     * @param Throwable $exception The uncaught exception
     */
    public function handleException(Throwable $exception): void
    {
        $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
        echo '<div style="display: none;" id="exception-message">' . $message . '</div>';
        echo <<<HTML
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var message = document.getElementById("exception-message").textContent;
                    if (message) {
                        alert(message);
                    }
                });
            </script>
        HTML;
    }
}
