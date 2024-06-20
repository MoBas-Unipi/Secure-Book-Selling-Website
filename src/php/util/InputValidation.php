<?php

use ZxcvbnPhp\Zxcvbn;

global $logger;

class InputValidation
{
    private static ?InputValidation $instance = null;

    private $zxcvbn;

    /**
     * Private constructor to ensure a single instance of the InputValidation class.
     * Requires the autoload file and initializes the zxcvbn password strength estimator.
     */
    private function __construct()
    {
        require '/home/bookselling/composer/vendor/autoload.php';

        $this->zxcvbn = new Zxcvbn();
    }

    /**
     * Retrieves the singleton instance of the InputValidation class.
     * If the instance does not exist, creates a new one.
     * @return InputValidation
     */
    public static function getInstance(): ?InputValidation
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /***************************************** password validation ************************************************/

    /**
     * Validates the strength of a password based on specified criteria.
     *
     * @param $password , The password to be validated.
     * @param $email , The email associated with the user.
     *
     * @return bool Returns true if the password meets the strength criteria.
     * @throws Exception
     */
    private function regexControl($password, $email): bool
    {
        // Password length and characteristics check
        if (!(strlen($password) >= 9
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password))) {

            throw new Exception("User: " . $email . " The password is too weak. Please choose a stronger password.");
        }
        return true;
    }

    /**
     * Validates the strength of a password using the zxcvbn library
     * to assess the password strength and provides a score.
     * @throws Exception
     */
    private function zxcvbnControl($password, $email): bool
    {

        $strengthResult = $this->zxcvbn->passwordStrength($password);

        if ($strengthResult['score'] < 3) {

            throw new Exception('User: ' . $email . ' Password Score: ' . $strengthResult['score'] .
                ' Feedback: ' . implode(', ', $strengthResult['feedback']['suggestions']));
        }

        return true;
    }

    /**
     * Performs a regular expression check on the password length and characteristics,
     * ensuring it meets specific criteria such as minimum length, uppercase, lowercase,
     * numeric, and special character requirements.
     *
     * @param $password , The password to be validated.
     * @param $email , The email associated with the user.
     *
     * @return bool Returns true if the password passes both the regular expression and zxcvbn checks,
     *              indicating it is strong and not easily guessable. Otherwise, throws an Exception
     *              with a descriptive message explaining the weakness and prompts the user to choose
     *              a stronger password.
     *
     * @throws Exception Throws an exception if the password fails the strength checks, providing
     *                   information on why it is considered weak.
     */
    public function checkPasswordStrength($password, $email): bool
    {

        $isRegexValid = $this->regexControl($password, $email);

        $isZxcvbnValid = $this->zxcvbnControl($password, $email);

        return $isRegexValid && $isZxcvbnValid;

    }


    /*************************************** payment method validation ************************************************************/

    /**
     * Validates a credit card number depending on its length.
     *
     * @param $creditCardNumber, The credit card number to be validated.
     *
     * @return bool Returns true if the credit card number is valid, considering its length.
     */
    private function validateCreditCard($creditCardNumber): bool
    {
        // Remove any spaces, dashes, or other separators from the card number
        $creditCardNumber = preg_replace('/\D/', '', $creditCardNumber);

        // Check if the card number has a length between 13 and 19 digits
        return preg_match('/^\d{13,19}$/', $creditCardNumber);
    }

    /**
     * Validates a CVV (Card Verification Value) to ensure it is a 3 or 4-digit numeric value.
     *
     * @param $cvv , The CVV to be validated.
     * @return bool Returns true if the CVV is valid; otherwise, returns false.
     */
    private function validateCVV($cvv): bool
    {
        // Check if CVV is a 3 or 4-digit numeric value
        return preg_match('/^\d{3,4}$/', $cvv);
    }

    /**
     * Validates the format of a cardholder's name, allowing only letters and spaces.
     *
     * @param $cardholderName , The cardholder's name to be validated.
     * @return bool Returns true if the cardholder's name has a correct format; otherwise, returns false.
     */
    private function validateCardholderName($cardholderName): bool
    {
        // Check if the cardholder's name has a correct format (only letters and spaces)
        return preg_match('/^[A-Za-z\s]+$/', $cardholderName);
    }

    /**
     * Validates the format and expiration date of a credit card.
     *
     * @param $expirationDate , The expiration date of the credit card in MM/YY format.
     * @return bool Returns true if the expiration date is valid and in the future; otherwise, returns false.
     */
    private function validateExpirationDate($expirationDate): bool
    {
        // Check if the expiration date is in the MMYY format
        if (!preg_match('/^\d{4}$/', $expirationDate)) {
            return false;
        }

        // Extract month and year from the expiration date
        $expiryMonth = substr($expirationDate, 0, 2);
        $expiryYear = substr($expirationDate, 2, 2);

        if ($expiryMonth > 12 || $expiryMonth < 1) {
            return false;
        }

        // Convert to integers for comparison
        $currentMonth = (int)date('m');
        $currentYear = (int)date('y');

        // Check if the expiration date is in the future
        return ($currentYear < $expiryYear) || ($currentYear == $expiryYear && $currentMonth <= $expiryMonth);
    }

    /**
     * Validates a set of payment method details, including cardholder name, credit card number,
     * expiration date, and CVV.
     *
     * @param $cardholder , The cardholder's name.
     * @param $card , The credit card number.
     * @param $expire , The expiration date in MM/YY format.
     * @param $cvv , The CVV (Card Verification Value).
     *
     * @return bool Returns true if all payment method details are valid; otherwise, throws an exception.
     * @throws Exception Throws an exception with an error message for any validation failure.
     */
    public function validateCreditCardInfo($cardholder, $card, $expire, $cvv): bool
    {
        if (!$this->validateCardholderName($cardholder)) {
            throw new Exception('User: ' . $_SESSION['email'] . ' Invalid cardholder name.');
        }

        if (!$this->validateCreditCard($card)) {
            throw new Exception('User: ' . $_SESSION['email'] . ' Invalid credit card number.');
        }

        if (!$this->validateExpirationDate($expire)) {
            throw new Exception('User: ' . $_SESSION['email'] . ' Invalid expiration date.');
        }

        if (!$this->validateCVV($cvv)) {
            throw new Exception('User: ' . $_SESSION['email'] . ' Invalid CVV.');
        }

        return true;
    }

    /********************************************** shipping info validation *********************************************************************/

    /**
     * Validates the format of a name for shipping, allowing only letters and spaces.
     *
     * @param $name , The name to be validated for shipping.
     * @return void
     * @throws Exception Throws an exception with an error message for an invalid name.
     */
    private function validateName($name): void
    {
        // Check if the name has a correct format (only letters and spaces)
        if (!preg_match('/^[A-Za-z\s]+$/', $name)) {
            throw new Exception('User: ' . $_SESSION['email'] . ' Invalid name for shipping.');
        }
    }

    /**
     * Validates a common format for location parameters in shipping information, checking for valid characters and length.
     *
     * @param $value , The value to be validated.
     * @param $errorMessage , The error message to be thrown for invalid values.
     * @return void
     * @throws Exception Throws an exception with the specified error message for an invalid value.
     */
    private function validateCommonFormat($value, $errorMessage): void
    {
        // Validate the common format for location parameters in shipping info
        // Check for valid characters, length, etc.
        if (!preg_match('/^[A-Za-z0-9\s.,-]+$/i', $value)) {
            throw new Exception($errorMessage);
        }
    }

    /**
     * Validates the format of a postal code for shipping, ensuring it is exactly 5 digits.
     *
     * @param $postal_code , The postal code to be validated for shipping.
     * @return void
     * @throws Exception Throws an exception with an error message for an invalid postal code.
     */
    private function validatePostalCode($postal_code): void
    {
        // Check if postal code is exactly 5 digits
        if (!preg_match('/^\d{5}$/', $postal_code)) {
            throw new Exception('User: ' . $_SESSION['email'] . ' Invalid postal code for shipping.');
        }
    }

    /**
     * Validates the format of a postal code for shipping, ensuring it is exactly 5 digits.
     *
     * @param $province , The Province  to be validated for shipping.
     * @return void
     * @throws Exception Throws an exception with an error message for an invalid Province.
     */
    private function validateProvince($province): void
    {
        // Check if Province is exactly 2 capital letters
        if (!preg_match('/^[A-Z]{2}$/', $province)) {
            throw new Exception('User: ' . $_SESSION['email'] . ' Invalid Province for shipping.');
        }
    }

    /**
     * Validates shipping information, including name, address, city, province, postal code, and country.
     *
     * @param $name , The name for shipping.
     * @param $address , The address for shipping.
     * @param $city , The city for shipping.
     * @param $province , The province for shipping.
     * @param $postal_code , The postal code for shipping.
     * @param $country , The country for shipping.
     * @return bool Returns true if all validations pass; otherwise, throws an exception.
     * @throws Exception Throws an exception with an error message for any validation failure.
     */
    public function validateShippingInformation($name, $address, $city, $province, $postal_code, $country): bool
    {
        $this->validateName($name);
        $this->validateCommonFormat($address, 'User: ' . $_SESSION['email'] . ' Invalid address for shipping.');
        $this->validateCommonFormat($city, 'User: ' . $_SESSION['email'] . ' Invalid city for shipping.');
        $this->validateCommonFormat($country, 'User: ' . $_SESSION['email'] . ' Invalid country for shipping.');
        $this->validatePostalCode($postal_code);
        $this->validateProvince($province);

        return true; // All validations passed
    }

}