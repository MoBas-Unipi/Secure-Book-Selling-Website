<?php
require_once __DIR__ . "/../../config.php";

global $logger;
global $errorHandler;
global $sessionHandler;
global $shoppingCartHandler;
global $accessControlHandler;
global $inputValidator;

// Check path manipulation and broken access control
// Check if the user is logged
$accessControlHandler->redirectIfAnonymous();

try {
    // If POST vars are set it means that a POST form has been submitted 
    if (checkPostFields(['CardHolderName', 'CardNumber', 'Expire', 'CVV'])) {

        // Protect against XSS
        $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $cardHolderName = filter_input(INPUT_POST, 'CardHolderName', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $cardNumber = filter_input(INPUT_POST, 'CardNumber', FILTER_SANITIZE_NUMBER_INT);
        $expire = filter_input(INPUT_POST, 'Expire', FILTER_SANITIZE_NUMBER_INT);
        $CVV = filter_input(INPUT_POST, 'CVV', FILTER_SANITIZE_NUMBER_INT);

        // Protect against XSRF
        if (!$token || $token !== $_SESSION['token']) {
            // return 405 http status code
            $accessControlHandler->redirectIfXSRFAttack();
        } else {
            // check validation of credit card
            if ($inputValidator->validateCreditCardInfo($cardHolderName, $cardNumber, $expire, $CVV)) {
                // Save card information in $_SESSION and redirect depending on $_SESSION vars set
                $sessionHandler->saveCreditCardInfo($cardHolderName, $cardNumber, $expire, $CVV);
                $logger->log('INFO', "User: " . $_SESSION['email'] . " successfully set his payment info");
                $accessControlHandler->routeMultiStepCheckout();
            }
        }
    }
} catch (Exception $e) {
    $logger->log('ERROR', $e->getMessage());
    $errorHandler->handleException($e);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Method</title>
    <script type="text/javascript" src="../../js/payment.js"></script>
    <link rel="stylesheet" href="../../css/styles.css">

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Shop Item - Start Bootstrap Template</title>
    <!-- Bootstrap icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="../../css/styles.css" rel="stylesheet" />

    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>


<body>
<!-- Header-->
<?php
include "../layout/header.php";
?>


<!-- Payment section-->
<section class="py-5">
    <div class="container px-4 px-lg-5 my-5">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8 col-xl-5 w-75">
                <div class="card rounded-4">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h3>1. Payment</h3>
                        </div>
                        <form name="paymentInfoForm"
                              action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/user/creditCardInfo.php'); ?>"
                              method="POST">
                            <div id="cardOptions">
                                <div class="accordion" id="accordionPayment">
                                    <!-- Credit card -->
                                    <div class="accordion-item mb-3">
                                        <h2 class="h5 px-4 py-3 accordion-header d-flex justify-content-between align-items-center">
                                            <div class="w-100">
                                                <label class="form-check-label pt-1" for="payment1">
                                                    Credit Card
                                                </label>
                                            </div>
                                        </h2>
                                        <div id="collapseCC" class="accordion-collapse collapse show" data-bs-parent="#accordionPayment" style="">
                                            <div class="accordion-body">
                                                <div class="form-outline mb-4">
                                                    <label class="form-label" for="formControlLgXsd">Cardholder's Name</label>
                                                    <input type="text" id="formControlLgXsd"
                                                           name="CardHolderName"
                                                           class="form-control form-control-lg"
                                                           placeholder="Name Surname"
                                                           title="Please Insert Name and Surname"
                                                           pattern="[A-Za-z ]+" required>
                                                </div>
                                                <div class="row mb-4">
                                                    <div class="col-7">
                                                        <div class="form-outline">
                                                            <label class="form-label" for="formControlLgXM">Card Number</label>
                                                            <input type="text" id="formControlLgXM"
                                                                   name="CardNumber"
                                                                   class="form-control form-control-lg"
                                                                   placeholder="1234 5678 1234 5678"
                                                                   maxlength="19"
                                                                   pattern="[0-9]{4} [0-9]{4} [0-9]{4} [0-9]{4}"
                                                                   title="Please enter a valid 16-digit card number divided by a space"
                                                                   oninput="formatCardNumber(event)" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="form-outline">
                                                            <label class="form-label" for="formControlLgExpk">Expire</label>
                                                            <input type="text" id="formControlLgExpk"
                                                                   name="Expire"
                                                                   class="form-control form-control-lg"
                                                                   placeholder="MM/YY"
                                                                   pattern="\d{2}/\d{2}"
                                                                   title="Please Insert this format MM/YY"
                                                                   maxlength="5"
                                                                   oninput="formatExpirationDate(event)"
                                                                   onblur="checkExpirationDate(event.target.value)" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-2">
                                                        <div class="form-outline">
                                                            <label class="form-label" for="formControlLgcvv">CVV</label>
                                                            <input type="password" id="formControlLgcvv"
                                                                   name="CVV"
                                                                   class="form-control form-control-lg"
                                                                   placeholder="CVV"
                                                                   pattern="[0-9]{3,4}"
                                                                   title="Please enter a valid 3 or 4-digit CVV" required
                                                                   maxlength="4">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Hidden token to protect against XSRF -->
                                <input type="hidden" name="token"
                                       value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-outline-dark btn-custom btn-block w-50 mx-auto">
                                        <i class="fas fa-truck me-1"></i>
                                        Continue to Shipping Info
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer-->
<?php
include "../layout/footer.php";
?>


<!-- Bootstrap core JS-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Core theme JS-->
<script src="js/scripts.js"></script>
</body>
</html>