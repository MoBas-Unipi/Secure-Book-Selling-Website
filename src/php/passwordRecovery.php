<?php
require_once __DIR__ . "/../config.php";

// Initialize global objects
global $logger;
global $errorHandler;
global $sessionHandler;
global $accessControlHandler;
global $inputValidator;
global $otpInterval;

// Handle form submission if POST vars are set
if (checkPostFields(['email', 'otp', 'password', 'repeat_password'])) {

    // Sanitize input fields to prevent XSS
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $otp = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $passwordSubmitted = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $repeatPassword = filter_input(INPUT_POST, 'repeat_password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Protect against XSRF attacks
    if (!$token || $token !== $_SESSION['token']) {
        $accessControlHandler->redirectIfXSRFAttack();
    } else {
        try {
            // Validate if passwords match
            if ($passwordSubmitted !== $repeatPassword) {
                throw new Exception('The two passwords do not match');
            } else {
                // Get security info from the database to verify user
                $result = getSecurityInfo($email);
                if ($result) {
                    $userSecurityInfo = $result->fetch_assoc();
                    if ($userSecurityInfo !== null && $result->num_rows === 1) {
                        // Check if OTP was generated and validate its expiration
                        if ($userSecurityInfo['otp'] !== null && $userSecurityInfo['lastOtp'] !== null) {
                            // Convert time for comparison
                            $lastOtpTime = strtotime($userSecurityInfo['lastOtp']);
                            $currentTime = time();

                            // Hash the provided OTP for comparison
                            $otpHashed = hash('sha256', $otp . $userSecurityInfo['salt']);

                            // Check if OTP is correct and not expired
                            if (($currentTime - $lastOtpTime) > $otpInterval || $userSecurityInfo['otp'] !== $otpHashed) {
                                throw new Exception('The OTP is incorrect and/or expired for the user: ' . $email);
                            }

                            // Validate new password strength
                            if ($inputValidator->checkPasswordStrength($passwordSubmitted, $email)) {
                                // Generate bcrypt hash for the new password
                                $hashedPassword = password_hash($passwordSubmitted, PASSWORD_DEFAULT);

                                // Prepare data for updating user's password
                                $userData = array(
                                    $hashedPassword,
                                    $email,
                                );

                                // Update user's password
                                if (updateUserPassword($userData)) {
                                    // Log password update success
                                    $logger->log('INFO', "Password updated successfully for user: " . $email);
                                    header('Location: //' . SERVER_ROOT . '/php/login.php');
                                    exit;
                                } else {
                                    throw new Exception('Could not update the password for user: ' . $email);
                                }
                            }
                        } else {
                            throw new Exception('No OTP was generated for this user: ' . $email);
                        }
                    } else {
                        throw new Exception('No account found for the given email: ' . $email);
                    }
                } else {
                    throw new Exception('Error retrieving user security information for email: ' . $email);
                }
            }
        } catch (Exception $e) {
            // Log and handle exceptions
            $logger->log('ERROR', $e->getMessage());
            $errorHandler->handleException($e);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
    <script src="../js/utilityFunction.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <title>Book Selling - Password Recovery</title>
</head>
<body>
<?php include "./layout/header.php"; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mb-4">Password Recovery</h2>
            <form class="pwd-recovery-form"
                  action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/passwordRecovery.php') ?>" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label"><b>Email</b></label>
                    <input class="form-control" type="email" placeholder="Email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="otp" class="form-label"><b>OTP</b></label>
                    <input class="form-control" type="text" placeholder="OTP code" name="otp" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label"><b>Password</b></label>
                    <input class="form-control" type="password" placeholder="Password" name="password" id="password"
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{9,}"
                           title="Must contain at least one number, one uppercase letter, one lowercase letter, and at least 9 or more characters"
                           required oninput="checkPasswordStrength()">
                    <meter max="4" id="password-strength-meter"></meter>
                    <p id="password-strength-text"></p>
                    <p id="suggestions"></p>
                </div>

                <div class="mb-3">
                    <label for="repeat_password" class="form-label"><b>Repeat Password</b></label>
                    <input class="form-control" type="password" placeholder="Repeat Password" name="repeat_password"
                           required>
                </div>

                <!-- Hidden token to protect against CSRF -->
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">

                <button class="btn btn-outline-dark flex-shrink-0 mx-auto btn-custom btn-block" id="change_psw_button" type="submit">Change Password</button>
            </form>
            <p class="mt-3 text-center"><a href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/otpRequest.php') ?>"
                                           class="no-otp">I don't have an OTP</a></p>
        </div>
    </div>
</div>

</body>
</html>
