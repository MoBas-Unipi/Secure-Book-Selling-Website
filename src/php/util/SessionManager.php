<?php
global $logger;

/**
 * This class manages the session variable and checks the type of the current user
 */
class SessionManager
{
    private static ?SessionManager $instance = null;
    private $lifetime;
    private $path;
    private $secure;
    private $httponly;
    private $private_key;

    /**
     * Sets session cookie parameters and starts the session.
     * @param int $lifetime The lifetime of the session cookie in seconds.
     * @param string $path The path on the server in which the cookie will be available on.
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection.
     * @param bool $httponly When true, the cookie will be made accessible only through the HTTP protocol.
     */
    private function __construct($lifetime, $path, $secure, $httponly)
    {
        // Set session parameters
        $this->lifetime = $lifetime;
        $this->path = $path;
        $this->secure = $secure;
        $this->httponly = $httponly;
        // Load encryption keys
        $this->loadKeys();
        // Start the session
        $this->startSession();
    }


    /**
     * Loads the private key from the specified file.
     * Keys are used for encryption and decryption operations
     * @return void
     * @throws Exception if the key file cannot be read
    */
    private function loadKeys(): void
    {
        // Read the private key from the specified file
        $this->private_key = $this->readFile(__DIR__ . '/../../keys/private_key.bin');
    }


    /**
     * Reads the contents of a file.
     * Used to read the file containing the private keys.
     * @param string $filePath The path to the file to be read.
     * @return string The contents of the file.
     * @throws Exception if the file cannot be read.
    */
    private function readFile(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            // Handle the file read error
            throw new Exception("Error! Impossible read the file: $filePath");
        }
        return $content;
    }


    /**
     * Returns the singleton instance of SessionManager.
     * If the instance doesn't exist, it creates one; otherwise, it returns the existing instance.
     * @param int $lifetime The lifetime of the session cookie in seconds.
     * @param string $path The path on the server in which the cookie will be available on.
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection.
     * @param bool $httponly When true, the cookie will be made accessible only through the HTTP protocol.
     * @return SessionManager The singleton instance of SessionManager.
     */
    public static function getInstance($lifetime, $path, $secure, $httponly): ?SessionManager
    {
        // Check if the instance is not already created
        if (self::$instance == null) {
            // Create a new instance of SessionManager
            self::$instance = new SessionManager($lifetime, $path, $secure, $httponly);
        }
        // Return the singleton instance
        return self::$instance;
    }

    /**
     * Sets the session cookie parameters and starts the session.
     * It also sets the session token to prevent XSRF attacks on the login form.
     * @return void
     */
    private function startSession(): void
    {
        // Set session cookie parameters
        session_set_cookie_params([
            'path' => $this->path,
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => $this->secure,
            'httponly' => $this->httponly
        ]);
        // Start the session
        session_start();

        // Set the session token to prevent XSRF on the login form if not already set
        if (!isset($_SESSION['token'])) {
            $_SESSION['token'] = md5(uniqid(mt_rand(), true));
        }
    }

    /**
     * Sets the session variables of the user after login.
     * These variables are used to identify the current user.
     * @param int $userId The ID of the current user.
     * @param string $email The email of the current user.
     * @param string $name The name of the current user.
     * @return void
     */
    public function setSession($userId, $email, $name): void
    {
        // Set session variables with user information
        $_SESSION['userId'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['first_name'] = $name;
        // Set a new session token to prevent XSRF
        $_SESSION['token'] = md5(uniqid(mt_rand(), true));
        // Record the time of the last interaction
        $_SESSION['lastInteraction'] = time();
    }


    /**
     * Encrypts and saves the credit card information of the current user as an array
     * inside the paymentInfo session variable.
     * @param string $cardHolderName The name of the credit card holder.
     * @param string $cardNumber The credit card number.
     * @param string $Expire The expiration date of the card.
     * @param string $cvv The secure code of the credit card.
     * @return void
     */
    public function saveCreditCardInfo($cardHolderName, $cardNumber, $Expire, $cvv): void
    {
        // Encrypt the credit card information
        $encryptedCardHolderName = $this->encryptData($cardHolderName);
        $encryptedCardNumber = $this->encryptData($cardNumber);
        $encryptedExpire = $this->encryptData($Expire);
        $encryptedCvv = $this->encryptData($cvv);

        // Store the encrypted credit card information in the paymentInfo array
        $_SESSION['paymentInfo'] = array(
            'cardHolderName' => $encryptedCardHolderName,
            'cardNumber' => $encryptedCardNumber,
            'expire' => $encryptedExpire,
            'cvv' => $encryptedCvv
        );

        // Retrieve the stored credit card information (used to check the correctness)
        //$this->getCreditCardInfo();
    }


    /**
     * Encrypts the given data using AES-128-GCM encryption.
     * Used to encrypt credit card information.
     * @param string $data The data to be encrypted.
     * @return string The encrypted data, encoded in base64.
     */
    function encryptData($data) {
        // Select the cipher method and determine the IV length
        $ciphering = "aes-128-gcm";
        $iv_length = openssl_cipher_iv_length($ciphering);

        // Generate a random IV
        $encryption_iv = random_bytes($iv_length);

        // Encrypt the data using AES-GCM with the server's private key
        $tag = null;
        $encrypted_data = openssl_encrypt($data, $ciphering, $this->private_key, $options=0, $encryption_iv, $tag);

        // Concatenate the IV, tag, and encrypted data, then encode in base64
        return base64_encode($encryption_iv . $tag . $encrypted_data);
    }


    /**
     * Decrypts the given encrypted data using AES-128-GCM encryption.
     * Used to decrypt credit card information.
     * @param string $encryptedDataWithIvAndTag The encrypted data, including the IV and tag, encoded in base64.
     * @return string The decrypted data, or false if decryption fails.
     */
    function decryptData($encryptedDataWithIvAndTag) {
        // Select the cipher method and determine the IV length
        $ciphering = "aes-128-gcm";
        $iv_length = openssl_cipher_iv_length($ciphering);
        $tag_length = 16; // TAG length for AES-GCM authentication

        // Decode the data from base64 format
        $data = base64_decode($encryptedDataWithIvAndTag);

        // Extract the IV from the data
        $iv = substr($data, 0, $iv_length);

        // Extract the TAG
        $tag = substr($data, $iv_length, $tag_length);

        // Extract the encrypted data
        $encrypted_data = substr($data, $iv_length + $tag_length);

        // Decrypt the data using AES-GCM with the server's private key
        return openssl_decrypt($encrypted_data, $ciphering, $this->private_key, $options=0, $iv, $tag);
    }


    /**
     * Retrieves and decrypts the stored credit card information from the session variable.
     * Utility function used to check the correctness.
     * @return string[] An array containing the decrypted credit card information.
     */
    public function getCreditCardInfo(): array
    {
        // Extract the encrypted payment information array from the session
        $encryptedPaymentInfo = $_SESSION['paymentInfo'];

        // Decrypt each credit card information
        $cardHolderName = $this->decryptData($encryptedPaymentInfo['cardHolderName']);
        $cardNumber = $this->decryptData($encryptedPaymentInfo['cardNumber']);
        $expire = $this->decryptData($encryptedPaymentInfo['expire']);
        $cvv = $this->decryptData($encryptedPaymentInfo['cvv']);

        // Return the decrypted payment information as an array
        return array(
            'cardHolderName' => $cardHolderName,
            'cardNumber' => $cardNumber,
            'expire' => $expire,
            'cvv' => $cvv
        );
    }


    /**
     * Clears the specified session variable related to shipping information and credit card information.
     * @param string $checkoutInfo The name of the session variable to clear (either 'shippingInfo' or 'paymentInfo').
     * @return void
     */
    public function clearCheckoutInfo($checkoutInfo): void
    {
        // Check if the specified session variable is set, and if so, unset it
        if (isset($_SESSION[$checkoutInfo])) {
            unset($_SESSION[$checkoutInfo]);
        }
    }


    /**
     * Saves the shipping information of the current user as an array
     * inside the shippingInfo session variable.
     * @param string $fullName The full name at the domicile.
     * @param string $address The address of the current user.
     * @param string $city The city of the user.
     * @param string $province The province of the user.
     * @param string $postal_code The postal code of the user.
     * @param string $country The country of the user.
     * @return void
     */
    public function saveShippingInfo($fullName, $address, $city, $province, $postal_code, $country): void
    {
        $_SESSION['shippingInfo'] = array(
            'fullName' => $fullName,
            'address' => $address,
            'country' => $country,
            'province' => $province,
            'city' => $city,
            'postal_code' => $postal_code
        );
    }


    /**
     * Clears all session data and regenerates the session ID to ensure a safe logout.
     * Called during logout.
     * @return bool True on successful session unset, false otherwise.
     */
    public function unsetSession(): bool
    {
        try {
            // Free all session variables currently registered
            session_unset();
            // Replace the current session ID with a new one to avoid Session Fixation attacks
            session_regenerate_id(true);
            // Destroy all data associated with the current session
            // This does not unset any of the global variables associated with the session or unset the session cookie
            session_destroy();
            // Restart the session
            $this->startSession();
            return true;
        } catch (Exception) {
            return false;
        }
    }


    /**
     * Checks if the user is logged in or anonymous.
     * @return int Returns 1 if the user is logged in (all required session fields are set), otherwise returns 0.
     */
    public function isLogged(): int
    {
        global $logger;
        // Define the required session fields to check for login status
        $loggedFields = ['userId', 'email', 'first_name', 'token'];
        // Iterate through the required fields
        foreach ($loggedFields as $field) {
            // If any required field is missing from the session, return 0 (not logged in)
            if (!isset($_SESSION[$field])) {
                return 0;
            }
        }
        // If all required fields are present in the session, return 1 (logged in)
        return 1;
    }


    /**
     * Manages the session lifetime by comparing the timestamp of the last interaction
     * with the current timestamp. If more time has passed than the session lifetime
     * (sessionLifetime in config.php file), it initiates a logout process.
     * Otherwise, it updates the timestamp of the last interaction.
     * @return void
     */
    public function checkSessionLifetime(): void
    {
        global $logger;
        // Check if the user is logged in
        if ($this->isLogged()) {
            $currentInteraction = time();
            // Compare the time since the last interaction with the session lifetime
            if (($currentInteraction - $_SESSION['lastInteraction']) > $this->lifetime) {
                // Log session expiration and redirect to logout page
                $logger->log('INFO', "the session for the user: " . $_SESSION['email'] . " is expired");
                header('Location: //' . SERVER_ROOT . '/php/logout.php');
                exit;
            } else {
                // Update the timestamp of the last interaction
                $_SESSION['lastInteraction'] = $currentInteraction;
            }
        }
    }

}

