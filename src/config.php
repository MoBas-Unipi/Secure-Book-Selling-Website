<?php
require_once __DIR__ . "/php/util/SessionManager.php";
require_once __DIR__ . "/php/util/Logger.php";
require_once __DIR__ . "/php/util/ErrorHandler.php";
require_once __DIR__ . "/php/util/EmailHandler.php";
require_once __DIR__ . "/php/util/SessionManager.php";
require_once __DIR__ . "/php/util/ShoppingCartHandler.php";
require_once __DIR__ . "/php/util/utilities.php";
require_once __DIR__ . "/php/util/dbQueries.php";
require_once __DIR__ . "/php/util/AccessControlHandler.php";
require_once __DIR__ . "/php/util/InputValidation.php";

define("PROJECT_ROOT", $_SERVER["DOCUMENT_ROOT"]);
define("SERVER_ROOT", $_SERVER["SERVER_NAME"]);
define("CURRENT_SCRIPT", basename($_SERVER['PHP_SELF']));

// Content Security Policy (CSP)
header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' https://cdnjs.cloudflare.com/ https://www.bookselling.snh/ 'unsafe-inline' https://code.jquery.com/ https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/ https://stackpath.bootstrapcdn.com/; " .
    "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com/ https://stackpath.bootstrapcdn.com/; " .
    "font-src 'self' https://cdnjs.cloudflare.com/ https://stackpath.bootstrapcdn.com/; " .
    "img-src 'self' data:; " .
    "base-uri 'self'; " .
    "form-action 'self'; " .
    "frame-ancestors 'self';"
);

// Setting debug mode to true will allow the application to display more comprehensive error messages, helpful for debugging purposes
$debug = true;
// The session will last for 7200 seconds (2 hours)
$sessionLifetime = 7200;
// Number of allowed login attempts before blocking
$loginAttempts = 3;
// Time window for login attempts to prevent brute force attacks, set to 30 seconds
$timeWindowLogin = 30;
// Minimum interval between OTP requests set to 2 minutes
$otpInterval = 120;

// --- Session Cookie Parameters ---
// The domain path where the session cookie is valid. A single slash ('/') makes it valid for all paths.
$path = '/';
// Ensures the session cookie is sent over secure connections only.
$secure = true;
// When set to true, the session cookie is only accessible through the HTTP protocol, not via JavaScript, which helps prevent XSS attacks.
$httponly = true;

$logger = Logger::getInstance(__DIR__ . '/logs/web_server_logs.txt', $debug);
$errorHandler = ErrorHandler::getInstance();
$emailSender = EmailHandler::getInstance();
$sessionHandler = SessionManager::getInstance($sessionLifetime, $path, $secure, $httponly);
$shoppingCartHandler = ShoppingCartHandler::getInstance();
$accessControlHandler = AccessControlHandler::getInstance();
$inputValidator = InputValidation::getInstance();

// Check session expiry and logout if expired, except on the logout page
if (CURRENT_SCRIPT !== 'logout.php') {
    $sessionHandler->checkSessionLifetime();
}
?>
