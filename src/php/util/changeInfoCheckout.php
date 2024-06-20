<?php
require_once __DIR__ . "/../../config.php";

global $logger;
global $errorHandler;
global $sessionHandler;
global $accessControlHandler;

// Check if the user is logged
$accessControlHandler->redirectIfAnonymous();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (checkPostFields(['editInfo'])) {
            // Protect against XSS
            $token = htmlspecialchars($_POST['token'], ENT_QUOTES, 'UTF-8');
            $editInfo = htmlspecialchars($_POST['editInfo'], ENT_QUOTES, 'UTF-8');

            // Protect against XSRF
            if (!$token || $token !== $_SESSION['token']) {
                // return 405 http status code
                $accessControlHandler->redirectIfXSRFAttack();
            } else {
                // redirection to the shippingInfo that needs to be edited
                if ($editInfo === 'shippingInfo') {
                    $sessionHandler->clearCheckoutInfo('shippingInfo');
                }
                // redirection to the paymentInfo that needs to be edited
                elseif ($editInfo === 'paymentInfo'){
                    $sessionHandler->clearCheckoutInfo('paymentInfo');
                }
                else {
                    throw new Exception("Checkout Information Value to edit is wrong");
                }
                $accessControlHandler->routeMultiStepCheckout();
            }
        }
    } catch (Exception $e) {
        $errorHandler->handleException($e);
    }
}