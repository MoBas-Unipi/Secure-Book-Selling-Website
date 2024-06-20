<?php
require_once __DIR__ . "/../config.php";

// Initialize global objects
global $logger;
global $errorHandler;
global $sessionHandler;
global $inputValidator;
global $accessControlHandler;
global $emailSender;

// Handle form submission if POST vars are set
if (checkPostFields(['current_password', 'new_password', 'repeat_password'])) {

    // Sanitize input fields to prevent XSS
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $currentPassword = filter_input(INPUT_POST, 'current_password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $newPassword = filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $repeatPassword = filter_input(INPUT_POST, 'repeat_password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Protect against XSRF attacks
    if (!$token || $token !== $_SESSION['token']) {
        $accessControlHandler->redirectIfXSRFAttack();
    } else {
        try {
            // Validate if new passwords match
            if ($newPassword !== $repeatPassword) {
                throw new Exception('The two passwords do not match');
            } else {
                // Get user's email from session
                $email = $_SESSION['email'];

                // Fetch current security info from the database
                $result = getSecurityInfo($email);

                if ($result) {
                    $userSecurityInfo = $result->fetch_assoc();
                    if ($userSecurityInfo !== null && $result->num_rows === 1) {
                        // Verify current password
                        if (password_verify($currentPassword, $userSecurityInfo['password'])) {
                            // Validate new password strength
                            if ($inputValidator->checkPasswordStrength($newPassword, $email)) {
                                // Check if new password is same as current password
                                if (password_verify($newPassword, $userSecurityInfo['password'])) {
                                    throw new Exception('The new password cannot be the same as the current password.');
                                }

                                // Hash the new password using bcrypt
                                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                                // Prepare data for updating user's password
                                $userData = array(
                                    $hashedNewPassword,
                                    $email,
                                );

                                // Update user's password
                                if (updateUserPassword($userData)) {
                                    // Send confirmation email
                                    if (!$emailSender->sendEmail($email,
                                        "BookSelling",
                                        "Password Change Request",
                                        "Your password has been changed successfully")) {
                                        $logger->log('ERROR', "Could not send confirmation email.");
                                    }

                                    // Log password change success
                                    $logger->log('INFO', "Password successfully updated for user: " . $email);
                                    echo '<script>alert("Password successfully updated!");';
                                    echo 'window.location.replace("//' . SERVER_ROOT . '/index.php");</script>';
                                    exit;
                                } else {
                                    throw new Exception('Could not update the password.');
                                }
                            }
                        } else {
                            throw new Exception('The current password is incorrect.');
                        }
                    } else {
                        throw new Exception('No account found for the current user.');
                    }
                } else {
                    throw new Exception('Error retrieving user security information.');
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
    <title>Book Selling - Change Password</title>
</head>
<body>
<?php include "./layout/header.php"; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mb-4">Change Password</h2>
            <form class="pwd-change-form"
                  action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/passwordChange.php') ?>" method="POST">
                <div class="mb-3">
                    <label for="current_password" class="form-label"><b>Current Password</b></label>
                    <input class="form-control" type="password" placeholder="Current Password" name="current_password"
                           required>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label"><b>New Password</b></label>
                    <input class="form-control" type="password" placeholder="New Password" name="new_password"
                           id="password"
                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{9,}"
                           title="Must contain at least one number, one uppercase letter, one lowercase letter, and at least 9 or more characters"
                           required oninput="checkPasswordStrength()">
                    <meter max="4" id="password-strength-meter"></meter>
                    <p id="password-strength-text"></p>
                    <p id="suggestions"></p>
                </div>

                <div class="mb-3">
                    <label for="repeat_password" class="form-label"><b>Repeat New Password</b></label>
                    <input class="form-control" type="password" placeholder="Repeat New Password" name="repeat_password"
                           required>
                </div>

                <!-- Hidden token to protect against CSRF -->
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">

                <button class="btn btn-outline-dark flex-shrink-0 mx-auto btn-custom btn-block" id="change_psw_button" type="submit">Change Password</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
