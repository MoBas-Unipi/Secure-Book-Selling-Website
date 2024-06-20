<?php
global $sessionHandler;

/**
 * Generates a random string of the specified length for OTP
 * @param $length , the desired length of the generated string
 * @return string
 * @throws \Random\RandomException
 */
function generateRandomString($length): string
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Displays an alert with the provided message
 * @param $textMessage , the message to be displayed in the alert
 * @return void
 */
function showInfoMessage($textMessage): void
{
    echo '<script>alert("' . htmlspecialchars($textMessage) . '");</script>';
}

/**
 * Validates that the specified POST fields are set and not empty
 * @param $requiredFields , array of required POST fields
 * @return bool
 */
function checkPostFields($requiredFields): bool
{
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            return false;
        }
    }
    return true;
}

/**
 * Updates login block information with the provided details
 * @param $failedAccessesCounter , number of failed login attempts
 * @param $blockedTime , time duration for which login is blocked
 * @param $email , user's email
 * @return array
 */
function updateBlockLoginInformation($failedAccessesCounter, $blockedTime, $email): array
{
    return array(
        $failedAccessesCounter,
        $blockedTime,
        $email,
    );
}
