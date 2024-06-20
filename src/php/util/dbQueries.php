<?php
require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/DbHandler.php";

$SecureBookSellingDB = DbHandler::getInstance();

/**
 * Retrieves user data based on email.
 *
 * @param string $email The email of the user whose data is to be retrieved.
 * @return array|false Returns an associative array of user data or false on failure.
 */
function getUserData($email)
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "SELECT email, first_name, last_name, address, city, province, postal_code, country  
                          FROM user 
                          WHERE email = ?;";

        $result = $SecureBookSellingDB->performQuery("SELECT", $query, [$email], "s");
        $SecureBookSellingDB->closeConnection();
        return $result;

    } catch (Exception $e) {
        $logger->log("ERROR",
            "Error performing the query to retrieve all the personal data of a user",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Inserts a new user into the database.
 *
 * @param array $userInformation An array containing user information to be inserted.
 * @return bool Returns true on success or false on failure.
 */
function insertUser($userInformation): bool
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "INSERT INTO user (password, salt, email, first_name, last_name, address, city, province, postal_code, country) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        $logger->log("INFO",
            "Trying to performing the query to insert the user");
        $SecureBookSellingDB->performQuery("INSERT", $query, $userInformation, "ssssssssis");
        $SecureBookSellingDB->closeConnection();
        return true;

    } catch (Exception $e) {
        $logger->log('ERROR',
            "Error performing the query to insert new user",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Authenticates a user based on email.
 *
 * @param string $email The email of the user to be authenticated.
 * @return array|false Returns an associative array of authentication data or false on failure.
 */
function authenticate($email)
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "SELECT id, `first_name`, `password`
                  FROM user
                  WHERE email = ?;";

        $result = $SecureBookSellingDB->performQuery("SELECT", $query, [$email], "s");
        $SecureBookSellingDB->closeConnection();
        return $result;
    } catch (Exception $e) {
        $logger->log('ERROR',
            "Error during the authentication of the user: " . $email,
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Retrieves access information for a user based on email.
 *
 * @param string $email The email of the user whose access information is to be retrieved.
 * @return array|false Returns an associative array of access information or false on failure.
 */
function getAccessInformation(string $email)
{
    global $SecureBookSellingDB;
    global $logger;

    try {

        $query = "SELECT salt, timestampAccess, failedAccesses, blockedTime, password
                        FROM user
                        WHERE email = ?;";

        $result = $SecureBookSellingDB->performQuery("SELECT", $query, [$email], "s");
        $SecureBookSellingDB->closeConnection();
        return $result;

    } catch (Exception $e) {
        $logger->log('ERROR',
            "Error getting access information for the user",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Updates the failed access attempts of a user.
 *
 * @param array $information An array containing the failed accesses, blocked time, and email of the user.
 * @return bool Returns true on success or false on failure.
 */
function updateFailedAccesses($information): bool
{
    global $SecureBookSellingDB;
    global $logger;
    global $loginAttempts;

    try {
        if ($information[0] <= 1 or $information[0] >= $loginAttempts) {
            $query = "UPDATE user
                        SET timestampAccess = NOW(), failedAccesses = ?, blockedTime = ?
                        WHERE email = ?;";
        } else {
            $query = "UPDATE user
                        SET failedAccesses = ?, blockedTime = ?
                        WHERE email = ?;";
        }

        $SecureBookSellingDB->performQuery("UPDATE", $query, $information, "iis");
        $SecureBookSellingDB->closeConnection();
        return true;
    } catch (Exception $e) {
        $logger->log('ERROR',
            "Error incrementing the failed access of the user",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Retrieves OTP time information for a user based on email.
 *
 * @param string $email The email of the user whose OTP time information is to be retrieved.
 * @return array|false Returns an associative array of OTP time information or false on failure.
 */
function getOtpTimeInformation($email)
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "SELECT lastOtp, salt
                        FROM user
                        WHERE email = ?;";

        $result = $SecureBookSellingDB->performQuery("SELECT", $query, [$email], "s");
        $SecureBookSellingDB->closeConnection();
        return $result;

    } catch (Exception $e) {
        $logger->log('ERROR',
            "Error getting the lastOtp for the user",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Sets a new OTP for a user.
 *
 * @param string $email The email of the user.
 * @param string $newOtp The new OTP to be set.
 * @return bool Returns true on success or false on failure.
 */
function setOtp($email, $newOtp): bool
{
    global $SecureBookSellingDB;
    global $logger;

    try {

        $query = "UPDATE user
                        SET otp = ? , lastOtp = NOW()
                        WHERE email = ?;";

        $SecureBookSellingDB->performQuery("UPDATE", $query, [$newOtp, $email], "ss");
        $SecureBookSellingDB->closeConnection();
        return true;

    } catch (Exception $e) {
        $logger->log('ERROR',
            "Error updating OTP for the user",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Retrieves security information for a user based on email.
 *
 * @param string $email The email of the user whose security information is to be retrieved.
 * @return array|false Returns an associative array of security information or false on failure.
 */
function getSecurityInfo($email)
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "SELECT otp, lastOtp, first_name, last_name, salt, password
                        FROM user
                        WHERE email = ?;";

        $result = $SecureBookSellingDB->performQuery("SELECT", $query, [$email], "s");
        $SecureBookSellingDB->closeConnection();
        return $result;

    } catch (Exception $e) {
        $logger->log("ERROR",
            "Error performing the query to retrieve security information of a user",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Updates the password of a user.
 *
 * @param array $userInformation An array containing the new password and the email of the user.
 * @return bool Returns true on success or false on failure.
 */
function updateUserPassword($userInformation): bool
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "UPDATE user
                        SET password = ?, otp = NULL
                        WHERE email = ?;";

        $SecureBookSellingDB->performQuery("INSERT", $query, $userInformation, "ss");
        $SecureBookSellingDB->closeConnection();
        return true;

    } catch (Exception $e) {
        $logger->log('ERROR',
            "Error performing the query to insert new user",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Retrieves all books from the database.
 *
 * @return array|false Returns an associative array of books or false on failure.
 */
function getBooks()
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "SELECT id, title, author, price FROM book;";

        $result = $SecureBookSellingDB->performQuery("SELECT", $query);
        $SecureBookSellingDB->closeConnection();
        return $result;

    } catch (Exception $e) {
        $logger->log("ERROR",
            "Error performing the query to retrieve all the books",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Searches for books based on the title.
 *
 * @param string $title The title or part of the title of the book to be searched.
 * @return array|false Returns an associative array of books matching the search criteria or false on failure.
 */
function searchBooks($title)
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "SELECT id, title, author, price FROM book WHERE title LIKE ?;";

        $titleParam = "%$title%";

        $result = $SecureBookSellingDB->performQuery("SELECT", $query, [$titleParam], "s");
        $SecureBookSellingDB->closeConnection();
        return $result;

    } catch (Exception $e) {
        $logger->log("ERROR",
            "Error performing the query to retrieve a searched book",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Retrieves the details of a specific book based on its ID.
 *
 * @param string $bookId The ID of the book whose details are to be retrieved.
 * @return array|false Returns an associative array of book details or false on failure.
 */
function getBookDetails($bookId)
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "SELECT id, title, author, publisher, price, category, stocks_number FROM book WHERE id = ?;";

        $result = $SecureBookSellingDB->performQuery("SELECT", $query, [$bookId], "s");
        $SecureBookSellingDB->closeConnection();
        return $result;

    } catch (Exception $e) {
        $logger->log("ERROR",
            "Error performing the query to retrieve all the details of the book",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Checks the availability of a specific book in the database.
 *
 * @param string $bookId The ID of the book to check availability for.
 * @param int $quantity The quantity of the book needed.
 * @return array|false Returns an associative array containing book details if available,
 *                     or false if the book or required quantity is not available.
 */
function checkBookAvailability($bookId, $quantity)
{
    global $SecureBookSellingDB;
    global $logger;

    try {

        $query = "SELECT *
                        FROM book
                        WHERE   id = ?
                                AND
                                ? <= book.stocks_number;";

        $result = $SecureBookSellingDB->performQuery("SELECT", $query, [$bookId, $quantity], "ii");
        $SecureBookSellingDB->closeConnection();
        return $result;

    } catch (Exception $e) {
        $logger->log('ERROR',
            "Error getting the price of the book",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Retrieves all purchases made by a specific user from the database.
 *
 * @param int $userId The ID of the user whose purchases are to be retrieved.
 * @return array|false Returns an associative array containing details of user's purchases,
 *                     or false if no purchases found or query fails.
 */
function getUserPurchases($userId)
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "SELECT b.title, o.time, o.amount, o.quantity, o.payment_method, b.id AS id_book
                        FROM purchase o INNER JOIN book b ON o.id_book = b.id
                        WHERE id_user = ?
                        ORDER BY o.time DESC";

        $result = $SecureBookSellingDB->performQuery("SELECT", $query, [$userId], "s");
        $SecureBookSellingDB->closeConnection();
        return $result;

    } catch (Exception $e) {
        $logger->log("ERROR",
            "Error performing the query to retrieve all the purchases of a user",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Adds items from the shopping cart to the purchases table and updates book stock.
 *
 * @param int $userId The ID of the user making the purchase.
 * @param string $currentTime The current timestamp of the purchase.
 * @param array $cartItems An associative array containing items in the shopping cart.
 * @param float $totalPrice The total price of all items in the shopping cart.
 * @return bool Returns true if the items were successfully added to purchases and stock updated,
 *              false otherwise.
 */
    function addItemToPurchases($userId, $currentTime, $cartItems, $totalPrice): bool
{
    global $SecureBookSellingDB;
    global $logger;

    try {

        $query = "INSERT INTO purchase (id_user, id_book, time, amount, quantity, payment_method) 
                    VALUES (?, ?, ?, ?, ?, ?)";

        foreach ($cartItems as $itemId => $itemDetails) {
            $parameters = array(
                $userId,
                $itemId,
                $currentTime,
                $totalPrice,
                $itemDetails['quantity'],
                "Card",
            );
            $SecureBookSellingDB->performQuery("INSERT", $query, $parameters, "iisdis");
        }

        $query = "UPDATE book 
                      SET stocks_number = stocks_number - ? 
                      WHERE id = ?";

        foreach ($cartItems as $itemId => $itemDetails) {
            $parameters = array(
                $itemDetails['quantity'],
                $itemId,
            );
            $SecureBookSellingDB->performQuery("UPDATE", $query, $parameters, "ii");
        }

        $SecureBookSellingDB->closeConnection();
        return true;

    } catch (Exception $e) {
        $logger->log('ERROR',
            "Error performing the query to insert into the purchases",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}

/**
 * Checks if a user has purchased a specific book.
 *
 * @param int $userId The ID of the user to check for the purchase.
 * @param int $bookId The ID of the book to check for purchase.
 * @return array|false Returns an associative array containing book details if the user has purchased,
 *                     or false if the user has not purchased the book or query fails.
 */
function checkBookPurchaseByBook($userId, $bookId)
{
    global $SecureBookSellingDB;
    global $logger;

    try {
        $query = "SELECT b.ebook_name
                        FROM purchase o INNER JOIN book b ON o.id_book = b.id
                        WHERE o.id_user = ? AND o.id_book = ?
                        LIMIT ?";

        $limit = 1;
        $result = $SecureBookSellingDB->performQuery("SELECT", $query, [$userId, $bookId, $limit], "iii");
        $SecureBookSellingDB->closeConnection();
        return $result;

    } catch (Exception $e) {
        $logger->log("ERROR",
            "Error performing the query to check the book purchase",
            $_SERVER['SCRIPT_NAME'],
            "MySQL - Code: " . $e->getCode(),
            $e->getMessage());
        $SecureBookSellingDB->closeConnection();
        return false;
    }
}