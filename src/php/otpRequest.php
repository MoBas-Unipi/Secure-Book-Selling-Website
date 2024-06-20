<?php
require_once __DIR__ . "/../config.php";

// Initialize global objects
global $logger;
global $errorHandler;
global $emailSender;
global $accessControlHandler;
global $otpInterval;

// Handle form submission if POST vars are set
if (checkPostFields(['email'])) {

    // Sanitize input fields to prevent XSS
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Protect against XSRF attacks
    if (!$token || $token !== $_SESSION['token']) {
        $accessControlHandler->redirectIfXSRFAttack();
    } else {
        try {
            // Check when the last OTP has been requested for the email
            $result = getOtpTimeInformation($email);
            if ($result) {
                $dataQuery = $result->fetch_assoc();
                if ($dataQuery !== null && $result->num_rows === 1) {
                    // Retrieve timestamp of the last OTP
                    $lastOtpTime = strtotime($dataQuery['lastOtp']);
                    $currentTime = time();
                    // Check if 120 seconds have elapsed since the last OTP
                    if (($currentTime - $lastOtpTime) > $otpInterval) {
                        // Generate a new OTP
                        $newOTP = generateRandomString(8); // Generates a random string of 8 characters
                        // Hash the OTP before storing it in the database
                        $hashedNewOTP = hash('sha256', $newOTP . $dataQuery['salt']);
                        // Save the hashed OTP in the database
                        if (setOtp($email, $hashedNewOTP)) {
                            // Send the OTP via email to the user
                            if ($emailSender->sendEmail($email,
                                "BookSelling - Your OTP code",
                                "Password Recovery",
                                "This is the OTP requested: $newOTP", "It will be valid for 2 minutes.")) {

                                // Log success and redirect to password recovery page
                                $logger->log('INFO', "OTP successfully created and sent to user: " . $email);
                                header('Location: //' . SERVER_ROOT . '/php/passwordRecovery.php');
                                exit;
                            } else {
                                throw new Exception("Failed to send email to specified address: " . $email);
                            }
                        } else {
                            throw new Exception("Error creating OTP for email: " . $email);
                        }
                    } else {
                        throw new Exception('OTP already sent recently to this email: ' . $email);
                    }
                } else {
                    // Redirect to password recovery page also if no account is found for the email
                    header('Location: //' . SERVER_ROOT . '/php/passwordRecovery.php');
                    $logger->log('ERROR', 'No account found for the given email: ' . $email);
                }
            } else {
                throw new Exception('Error retrieving last OTP generated for email: ' . $email);
            }
        } catch (Exception $e) {
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <title>Book Selling - OTP Request</title>
</head>
<body>
<?php include "./layout/header.php"; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="p-4 border rounded">
                <h2 class="text-center mb-5">Insert your email to receive an OTP</h2>
                <form name="otp_request"
                      action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/otpRequest.php'); ?>" method="POST">
                    <div class="form-group m-auto w-75 ">
                        <label for="email" class="sr-only">Email</label>
                        <input class="form-control mb-4" type="email" placeholder="Email" name="email" required>
                        <!-- Hidden token to protect against CSRF -->
                        <input type="hidden" name="token"
                               value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">

                        <button class="btn btn-outline-dark flex-shrink-0 mx-auto btn-custom btn-block" type="submit">Generate OTP</button>
                    </div>
                </form>
                <div class="text-center">
                    <a href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/passwordRecovery.php'); ?>"
                       class="btn btn-link mt-3">I already have an OTP</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
