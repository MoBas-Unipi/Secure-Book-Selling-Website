<?php
require_once __DIR__ . "/../config.php";

global $logger;
global $errorHandler;
global $sessionHandler;
global $accessControlHandler;

// Check path manipulation and broken access control
// Check if the user is logged
$accessControlHandler->redirectIfAnonymous();

try{
    // Call of the method that clears all session data, regenerates the session id, and destroy the session
    // in order to provide a safe logout.
    if($sessionHandler->unsetSession()){
        $logger->log('INFO', "SessionID changed after the logout, in order to avoid SESSION FIXATION attacks ");
        $logger->log('INFO', "Logout of the user succeeded");
        $accessControlHandler->redirectToHome();
    }
    else{
        throw new Exception('Error during the logout');
    }
}
catch (Exception $e) {
    $logger->log('ERROR', "Logout of the user failed");
    $errorHandler->handleException($e);
}

