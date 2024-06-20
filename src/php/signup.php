<?php
// Include the configuration file which contains database connection settings and other configurations
require_once __DIR__ . "/../config.php";

// Declare global variables for logger, error handler, access control handler, email sender, session handler, and inputValidator
global $logger;
global $errorHandler;
global $accessControlHandler;
global $emailSender;
global $sessionHandler;
global $inputValidator;

// Check if the user is already logged in, if so, redirect to the home page
if ($sessionHandler->isLogged()) {
    $accessControlHandler->redirectToHome();
}

// Check if the required POST fields are set in the request
if (checkPostFields(['first_name', 'last_name', 'address', 'city', 'province', 'postal_code', 'country', 'email', 'password', 'repeat_password'])) {
    $logger->log('INFO', "Trying to perform the signup operation");

    // Sanitize and validate input data from the POST request
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $province = filter_input(INPUT_POST, 'province', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $postal_code = filter_input(INPUT_POST, 'postal_code', FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/^\d{5}$/")));
    $country = filter_input(INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $repeatPassword = filter_input(INPUT_POST, 'repeat_password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Check for CSRF token validity
    if (!$token || $token !== $_SESSION['token']) {
        $accessControlHandler->redirectIfXSRFAttack();
    } else {
        try {
            // Check if the passwords match
            if ($password !== $repeatPassword) {
                throw new Exception('The inserted passwords do not match');
            } else {
                // Validate the password strength
                if ($inputValidator->checkPasswordStrength($password, $email)) {
                    // Generate a salt (It could be used later for the OTP)
                    $salt = bin2hex(random_bytes(32));

                    // Hash the password using bcrypt
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // Prepare user data for insertion
                    $userData = array(
                        $hashedPassword,
                        $salt,
                        $email,
                        $first_name,
                        $last_name,
                        $address,
                        $city,
                        $province,
                        $postal_code,
                        $country,
                    );

                    // Insert the user data into the database
                    if (insertUser($userData)) {
                        $logger->log('INFO', "Signup of the user: " . $email . ", Succeeded");

                        // Send a welcome email to the user
                        if ($emailSender->sendEmail($email, "BookSelling - Welcome", "Signup is successfully completed", "Welcome in the bookselling community.", "Thank you for your support!") === false) {
                            $logger->log('ERROR', "Error during the send of the Signup Email");
                        }

                        // Redirect to the login page after successful signup
                        header('Location: //' . SERVER_ROOT . '/php/login.php');
                        exit;
                    } else {
                        throw new Exception('Could not register the user');
                    }
                }
            }
        } catch (Exception $e) {
            // Log and handle any exceptions that occur
            $logger->log('ERROR', $e->getMessage());
            $errorHandler->handleException($e);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Include external CSS stylesheets -->
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="../css/userOperationsStyle.css">
    <!-- Include external JavaScript files -->
    <script src="../js/utilityFunction.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <title>Book Selling - Sign Up</title>
</head>
<body>
<?php include "./layout/header.php"; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="signup-container">
                <h1 class="h3 mb-3 fw-normal text-center">Sign up</h1>
                <!-- Signup form -->
                <form name="sign_up" action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/signup.php'); ?>" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-outline mb-3">
                                <label for="first_name">First name</label>
                                <input type="text" class="form-control" placeholder="First name" name="first_name" pattern="^(?!\s*$)[A-Za-z\s]+$" title="Only letters and spaces allowed, must contain at least one letter" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline mb-3">
                                <label for="last_name">Last name</label>
                                <input type="text" class="form-control" placeholder="Last name" name="last_name" pattern="^(?!\s*$)[A-Za-z\s]+$" title="Only letters and spaces allowed, must contain at least one letter" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-outline mb-3">
                                <label for="address">Street Address</label>
                                <input type="text" class="form-control" placeholder="Street Address" name="address" pattern="^(?=.*[A-Za-z])[A-Za-z\d\s]+$" title="Only letters, digits and spaces allowed, must contain at least one letter" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-outline mb-3">
                                <label for="city">City</label>
                                <input type="text" class="form-control" placeholder="City" name="city" pattern="^(?!\s*$)[A-Za-z\s]+" title="Only letters and spaces allowed, must contain at least one letter" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-outline mb-3">
                                <label for="province">Province</label>
                                <input type="text" class="form-control" placeholder="Province" name="province" pattern="[A-Za-z]{2}" title="Exactly two letters without spaces or numbers allowed" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-outline mb-3">
                                <label for="postal_code">Postal Code</label>
                                <input type="text" class="form-control" placeholder="Postal Code" name="postal_code" pattern="[0-9]{5}" title="Only 5-digit numbers allowed" required maxlength="5">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline mb-3">
                                <label for="country">Country</label>
                                <input type="text" class="form-control" placeholder="Country" name="country" pattern="^(?!\s*$)[A-Za-z\s]+" title="Only letters and spaces allowed, must contain at least one letter" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-outline mb-3">
                        <label for="email">Email</label>
                        <input class="form-control" type="email" placeholder="Email" name="email" required>
                    </div>
                    <div class="form-outline mb-3">
                        <label for="password">Password</label>
                        <input class="form-control" type="password" placeholder="Password" name="password" id="password"
                               title="Must contain at least one number, one uppercase letter, one lowercase letter, and at least 9 or more characters"
                               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{9,}"
                               required oninput="checkPasswordStrength()">
                        <meter max="4" id="password-strength-meter"></meter>
                        <p id="password-strength-text"></p>
                    </div>
                    <div class="form-outline mb-3">
                        <label for="repeat_password">Repeat password</label>
                        <input class="form-control" type="password" placeholder="Repeat Password" name="repeat_password" required>
                    </div>
                    <!-- Include a hidden CSRF token field for preventing CSRF -->
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">
                    <div class="text-center">
                        <button class="btn btn-outline-dark btn-custom btn-block w-50 mx-auto" type="submit">
                            <i class="fas fa-user-plus me-1"></i>
                            Sign up
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "./layout/footer.php"; ?>
</body>
</html>
