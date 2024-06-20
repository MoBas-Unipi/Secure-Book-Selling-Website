<?php
require_once __DIR__ . "/../config.php";

// Declare global variables used in the login function
global $logger;
global $errorHandler;
global $sessionHandler;
global $accessControlHandler;
global $loginAttempts;
global $timeWindowLogin;

/**
 * Handles user login.
 *
 * @param string $email User's email address.
 * @param string $submittedPassword The password inserted by the user.
 * @param string|null $timestampAccess Timestamp of the last login attempt.
 * @param int $failedAccesses Number of failed login attempts.
 * @param int $blockedTime Duration of the account block time.
 *
 * @return bool Returns true if login is successful, false if unsuccessful.
 */
function login($email, $submittedPassword, $timestampAccess, $failedAccesses, $blockedTime): bool
{
    global $logger;
    global $errorHandler;
    global $sessionHandler;
    global $loginAttempts;
    global $timeWindowLogin;

    try {
        // Check if both email and password are provided
        if ($email != null) {
            // Fetch user data
            $result = authenticate($email);
            if ($result) {
                $dataQuery = $result->fetch_assoc();
                if ($dataQuery !== null && $result->num_rows === 1) {
                    // Verify the password
                    if (password_verify($submittedPassword, $dataQuery['password'])) {
                        // Update login failure information on successful login
                        $information = updateBlockLoginInformation(0, 0, $email);
                        if (updateFailedAccesses($information)) {
                            // Set session variables and regenerate session ID to prevent session fixation attacks
                            $sessionHandler->setSession($dataQuery['id'], $email, $dataQuery['first_name']);
                            session_regenerate_id(true);
                            $logger->log('INFO', "SessionID changed after the login, in order to avoid Session Fixation Attacks");
                            return true;
                        } else {
                            throw new Exception('Something went wrong during the login.');
                        }
                    } else {
                        // Handle login failure and update failure information
                        if ($timestampAccess === null) {
                            $timestampAccess = 0;
                        } else {
                            $timestampAccess = strtotime($timestampAccess);
                        }
                        $logger->log('INFO', "Timestamp: " .$timestampAccess);
                        $logger->log('INFO', "Time: " . time());

                        if (time() - $timestampAccess < $timeWindowLogin || $timestampAccess === 0) {
                            $failedAccesses++;
                            if ($failedAccesses >= $loginAttempts) {
                                // Block user if maximum login attempts are exceeded
                                $information = updateBlockLoginInformation(0, 30, $email);
                            } else {
                                $information = updateBlockLoginInformation($failedAccesses, $blockedTime, $email);
                            }
                        } else {
                            $failedAccesses = 1;
                            $information = updateBlockLoginInformation($failedAccesses, 0, $email);
                        }
                        if (!updateFailedAccesses($information)) {
                            throw new Exception('Something went wrong during the login.');
                        }
                        if ($information[1] === 30) {
                            throw new Exception('Your account is currently blocked');
                        } else {
                            throw new Exception('Email and/or password are not valid.');
                        }
                    }
                }
            } else {
                throw new Exception("Error performing the login.");
            }
        } else {
            throw new Exception('Error retrieving inserted data.');
        }
    } catch (Exception $e) {
        // Handle exceptions and log errors
        $errorHandler->handleException($e);
        $errorCode = $e->getCode();
        if ($errorCode > 0) {
            $logger->log('WARNING',
                "Failed Login for the user: " . $email,
                $_SERVER['SCRIPT_NAME'],
                "Login:",
                $e->getMessage() . " The user failed login " . $failedAccesses . " times");
        } else {
            $logger->log('ERROR',
                "Failed Login for the user: " . $email,
                $_SERVER['SCRIPT_NAME'],
                "Login:",
                $e->getMessage());
        }
        return false;
    }
    return false;
}

// Check if the user is already logged in, if so, redirect to the home page
if ($sessionHandler->isLogged()) {
    $accessControlHandler->redirectToHome();
}

// Check if the required POST fields are present
if (checkPostFields(['email', 'password'])) {

    // Sanitize input fields to prevent XSS
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $submittedPassword = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Verify CSRF token to prevent attacks
    if (!$token || $token !== $_SESSION['token']) {
        $accessControlHandler->redirectIfXSRFAttack();
    } else {
        try {
            // Get the access information for the provided email
            $result = getAccessInformation($email);
            if ($result) {
                $dataQuery = $result->fetch_assoc();
                if ($dataQuery !== null && $result->num_rows === 1) {

                    // Check if the user is blocked
                    if ($dataQuery['blockedTime'] !== 0 && $dataQuery['failedAccesses'] === 0) {
                        $blockedTime = $dataQuery['blockedTime'] + strtotime($dataQuery['timestampAccess']);
                        $currentTime = time();
                        if (($currentTime - $blockedTime) < 0) {
                            throw new Exception('Your account is currently blocked');
                        }
                    }
                    // Attempt to log in the user
                    if (login($email, $submittedPassword, $dataQuery['timestampAccess'], $dataQuery['failedAccesses'], $dataQuery['blockedTime'])) {
                        $logger->log('INFO', "Login of the user: " . $email . ", Succeeded");
                        $accessControlHandler->redirectToHome();
                    } else {
                        if ($dataQuery['blockedTime'] !== 0 && $dataQuery['failedAccesses'] === 0) {
                            $updates = updateBlockLoginInformation(0, $dataQuery['blockedTime'] + 1800, $email);
                            if (updateFailedAccesses($updates)) {
                                throw new Exception('Something went wrong during the login.');
                            }
                            throw new Exception('Your account is currently blocked');
                        }
                    }
                } else {
                    throw new Exception("Email and/or password are not valid.");
                }
            } else {
                throw new Exception('Error retrieving access information');
            }
        } catch (Exception $e) {
            // Handle exceptions and log errors
            $errorHandler->handleException($e);
            $logger->log('ERROR',
                "Failed Login for the user: " . $email,
                $_SERVER['SCRIPT_NAME'],
                $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Book Selling - Login</title>
</head>
<body>
<?php include "./layout/header.php"; ?>

<section class="p-4 p-md-5 m-5">
    <div class="row d-flex justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-4">
            <div class="card rounded-4">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h3>Sign in</h3>
                    </div>
                    <main class="form-login w-100 m-auto">
                        <form name="login" action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/login.php'); ?>" method="POST">
                            <img class="mb-4 mx-auto d-block" src="../img/icon.png" alt="Login Icon" width="160" height="160">
                            <div class="form-outline mb-4">
                                <label class="form-label" for="floatingInput">Email address</label>
                                <input type="email" class="form-control form-control-lg" id="floatingInput" placeholder="Email address" name="email" required>
                            </div>
                            <div class="form-outline mb-4">
                                <label class="form-label" for="floatingPassword">Password</label>
                                <input type="password" class="form-control form-control-lg" id="floatingPassword" placeholder="Password" name="password" required>
                            </div>
                            <!-- Hidden token to protect against XSRF -->
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">
                            <div class="text-center">
                                <button class="btn btn-outline-dark btn-custom btn-block w-50 mx-auto" type="submit">
                                    <i class="fas fa-sign-in-alt me-1"></i>
                                    Sign in
                                </button>
                            </div>
                        </form>
                        <a href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/otpRequest.php'); ?>" class="forgot-pwd d-block text-center mt-3">Forgot Password?</a>
                    </main>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include "./layout/footer.php"; ?>
</body>
</html>
