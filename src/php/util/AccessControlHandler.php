<?php
global $shoppingCartHandler;
global $logger;

/**
 * AccessControlHandler class
 *
 * Singleton class to handle access control and redirection logic for a multi-step checkout process.
 */
class AccessControlHandler
{
    /**
     * The single instance of the AccessControlHandler class
     *
     * @var AccessControlHandler|null
     */
    private static ?AccessControlHandler $instance = null;

    /**
     * Paths for various steps in the checkout process
     *
     * @var string
     */
    private string $purchaseSummaryPath;
    private string $shippingInfoPath;
    private string $creditCardInfoPath;
    private string $loginPath;
    private string $homePath;
    private string $shoppingCart;

    /**
     * Private constructor to prevent direct instantiation
     *
     * Initializes paths for various checkout steps.
     */
    private function __construct()
    {
        $this->purchaseSummaryPath = '//' . SERVER_ROOT . '/php/user/purchaseSummary.php';
        $this->shippingInfoPath = '//' . SERVER_ROOT . '/php/user/shippingInfo.php';
        $this->creditCardInfoPath = '//' . SERVER_ROOT . '/php/user/creditCardInfo.php';
        $this->loginPath = '//' . SERVER_ROOT . '/php/login.php';
        $this->homePath = '//' . SERVER_ROOT . '/';
        $this->shoppingCart = '//' . SERVER_ROOT . '/php/user/shoppingCart.php';
    }

    /**
     * Gets the single instance of the AccessControlHandler class
     *
     * @return AccessControlHandler|null The single instance of the AccessControlHandler class
     */
    public static function getInstance(): ?AccessControlHandler
    {
        if (self::$instance == null) {
            self::$instance = new AccessControlHandler();
        }
        return self::$instance;
    }

    /**
     * Redirects to the home page, optionally with a GET parameter
     *
     * @param string|null $getParameterName The name of the GET parameter (optional)
     * @param string|null $getParameterValue The value of the GET parameter (optional)
     */
    function redirectToHome($getParameterName = null, $getParameterValue = null): void
    {
        if (isset($getParameterName) && isset($getParameterValue)) {
            header('Location: ' . $this->homePath . '?' . $getParameterName . '=' . $getParameterValue);
        } else {
            header('Location: ' . $this->homePath);
        }
        exit;
    }

    /**
     * Redirects to the login page if the user is not logged in
     */
    function redirectIfAnonymous(): void
    {
        global $sessionHandler;
        global $logger;

        if (!$sessionHandler->isLogged()) {
            $logger->log('WARNING', "Unauthorized Access to the protected area");
            header('Location: ' . $this->loginPath);
            exit;
        }
    }

    /**
     * Routes to the appropriate step in the multi-step checkout process
     */
    function routeMultiStepCheckout(): void
    {
        if (isset($_SESSION['paymentInfo']) && isset($_SESSION['shippingInfo'])) {
            header('Location: ' . $this->purchaseSummaryPath);
            exit;
        } else {
            if (isset($_SESSION['shippingInfo'])) {
                header('Location: ' . $this->creditCardInfoPath);
            } else {
                header('Location: ' . $this->shippingInfoPath);
            }
            exit;
        }
    }

    /**
     * Checks if the final step in the checkout process can be accessed
     *
     * Redirects to the shopping cart if required session variables are not set.
     */
    function checkFinalStepCheckout(): void
    {
        global $logger;
        if (!(isset($_SESSION['paymentInfo']) && isset($_SESSION['shippingInfo']))) {
            $logger->log('WARNING', "Unauthorized Access to the Final Checkout Page Detected");
            header('Location: ' . $this->shoppingCart);
            exit;
        }
    }

    /**
     * Checks if an intermediate step in the checkout process can be accessed
     *
     * Redirects to the shopping cart if the payment information session variable is not set.
     */
    function checkIntermediateStepCheckout(): void
    {
        global $logger;
        if (!isset($_SESSION['paymentInfo'])) {
            $logger->log('WARNING', "Unauthorized Access Detected");
            header('Location: ' . $this->shoppingCart);
            exit;
        }
    }

    /**
     * Gets the next step URL in the checkout process
     *
     * @return string The URL of the next step in the checkout process
     */
    function getNextStepToCheckout(): string
    {
        global $logger;
        $logger->log('INFO', "AccessControlHandler: getNextStepToCheckout");
        if (isset($_SESSION['paymentInfo']) && isset($_SESSION['shippingInfo'])) {
            return $this->purchaseSummaryPath;
        } else {
            if (isset($_SESSION['paymentInfo'])) {
                return $this->shippingInfoPath;
            } else {
                return $this->creditCardInfoPath;
            }
        }
    }

    /**
     * Redirects if an XSRF attack is detected
     */
    function redirectIfXSRFAttack(): void
    {
        global $logger;
        $logger->log('WARNING', "XSRF Attack Detected");
        header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed');
        exit;
    }
}
