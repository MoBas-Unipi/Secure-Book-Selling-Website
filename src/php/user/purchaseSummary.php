<?php
require_once __DIR__ . "/../../config.php";

global $sessionHandler;
global $shoppingCartHandler;
global $errorHandler;
global $emailSender;
global $logger;
global $accessControlHandler;

// Verify path manipulation and enforce access control
// Ensure the user is logged in
$accessControlHandler->redirectIfAnonymous();

// Check if the user has entered credit card and shipping information, otherwise is redirected to the shopping cart
$accessControlHandler->checkFinalStepCheckout();
$books = $shoppingCartHandler->getBooks();

try {
    // If any POST variables are set, a form has been submitted
    if (checkPostFields(['totalPrice'])) {

        // Prevent XSS
        $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $totalPricePurchase = filter_input(INPUT_POST, 'totalPrice', FILTER_SANITIZE_NUMBER_FLOAT);
        if (strlen($totalPricePurchase) === 2) {
            $totalPricePurchase = number_format($totalPricePurchase / 10, 2);
        } else {
            $totalPricePurchase = number_format($totalPricePurchase / 100, 2);
        }

        $logger->log('INFO', "total price: " .$totalPricePurchase);
        // Prevent XSRF
        if (!$token || $token !== $_SESSION['token']) {
            // return 405 http status code
            $accessControlHandler->redirectIfXSRFAttack();
        } else {
            // Set the timezone to Europe/Berlin
            date_default_timezone_set('Europe/Berlin');
            // Set the timestamp and userId for the purchase records
            $currentTime = date('Y-m-d H:i:s');
            $userId = $_SESSION['userId'];
            // Execute query to add item to the database
            if (addItemToPurchases($userId, $currentTime, $books, $totalPricePurchase)) {
                // Clear the shopping cart after a successful purchase
                $shoppingCartHandler->clearShoppingCart();
                if ($emailSender->sendEmail($_SESSION['email'],
                        "BookSelling - Successful Purchase",
                        "New Books Purchase",
                        "Your Purchase was successfully completed.",
                        "You will be able to download your e-books through the download buttons in the purchase section, 
                                                    accessible after the login.",
                        "Thank you again for your purchase.") !== false) {

                    $logger->log('INFO', "Purchase made by the user: " . $_SESSION['email'] . ", was Successful");
                    // Redirect to the home page
                    $accessControlHandler->redirectToHome("paymentResponse", "OK");
                } else {
                    // Error occurred while sending the email
                    throw new Exception("Failed attempt to send payment confirmation email for user: " . $_SESSION['email']);
                }
            } else {
                // Error occurred during payment
                throw new Exception("Payment failed for user: " . $_SESSION['email']);
            }
        }
    }
} catch (Exception $e) {
    $logger->log('ERROR', $e->getMessage());
    $accessControlHandler->redirectToHome("paymentResponse", $e->getMessage());
}

$totalPrice = 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Summary Page</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
</head>
<body>
<?php
include "../layout/header.php";
?>
<section class="container mt-5 p-5 rounded ">

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2>Checkout</h2>
                </div>
                <div class="card-body">
                    <?php
                    if ($books !== null) {
                        ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-4">

                                <thead>
                                <tr>
                                    <!-- Set columns width -->
                                    <th class="text-center py-3 px-4" style="min-width: 400px;">Books & Details</th>
                                    <th class="text-center py-3 px-4" style="width: 100px;">Total Price</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($books as $bookId => $bookDetails) {
                                    $totalPrice += $bookDetails['price'] * $bookDetails['quantity'];
                                    ?>
                                    <tr>
                                        <td class="p-3">
                                            <div class="d-flex align-items-center">
                                                <img src="../../img/books/<?php echo htmlspecialchars($bookId); ?>.jpg"
                                                     class="img-thumbnail mr-3"
                                                     style="width: 100px; height: auto;" alt="Book Image">
                                                <div class="flex-grow-1">
                                                    <a href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/bookDetails.php?book_id=' . $bookId); ?>"
                                                       class="d-block text-dark font-weight-bold"><?= $bookDetails['title'] ?></a>
                                                    <small class="text-muted">
                                                        <span class="d-block">Author: <?= $bookDetails['author'] ?></span>
                                                        <span class="d-block">Publisher: <?= $bookDetails['publisher'] ?></span>
                                                        <span class="d-block">Quantity: <?= $bookDetails['quantity'] ?></span>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center font-weight-bold align-middle p-4">
                                            <span class="h5">$<?= $bookDetails['price'] * $bookDetails['quantity'] ?></span>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    } else {
                        ?>
                        <h4>No books to show in the shopping cart</h4>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">3. Purchase Summary</h5>
                    <hr>

                    <!-- Total Price -->
                    <div class="mb-3">
                        <h5 class="mb-1">Total Amount</h5>
                        <p class="mb-0"><?php echo htmlspecialchars('$' . $totalPrice); ?></p>
                    </div>

                    <hr>
                    <!-- Address Information -->
                    <div class="mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5>Shipping Info</h5>
                            </div>
                            <div class="col-md-4 d-flex justify-content-end">
                                <form action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/util/changeInfoCheckout.php'); ?>"
                                      method="POST">
                                    <input type="hidden" name="editInfo" value="shippingInfo">
                                    <!-- Hidden token to protect against XSRF -->
                                    <input type="hidden" name="token"
                                           value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm"><i
                                                class="fas fa-edit"></i></button>
                                </form>
                            </div>
                        </div>

                        <small>
                            <?php
                            // Displays the shipping info
                            $shippingInfo = $_SESSION['shippingInfo'];

                            foreach ($_SESSION['shippingInfo'] as $key => $value) {
                                ?>
                                <span><?php echo htmlspecialchars($_SESSION['shippingInfo'][$key]); ?></span><br>
                                <?php
                            }
                            ?>
                        </small>
                    </div>

                    <hr>
                    <!-- Credit Card Information -->
                    <div class="mb-3">
                        <div class="row align-items-center mb-3">
                            <div class="col-md-8">
                                <h5>Payment Method</h5>
                            </div>
                            <div class="col-md-4 d-flex justify-content-end">
                                <form action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/util/changeInfoCheckout.php'); ?>"
                                      method="POST">
                                    <input type="hidden" name="editInfo" value="paymentInfo">
                                    <!-- Hidden token to protect against CSRF -->
                                    <input type="hidden" name="token"
                                           value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm"><i
                                                class="fas fa-edit"></i></button>
                                </form>

                            </div>
                        </div>
                        <p class="mb-0">Credit Card
                            Number: <?php echo htmlspecialchars('****' . substr($sessionHandler->decryptData($_SESSION['paymentInfo']['cardNumber']), -4)); ?></p>
                    </div>

                    <!-- Checkout Button -->
                    <form action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/user/purchaseSummary.php'); ?>"
                          method="POST">
                        <?php
                        if (!empty($books)) {
                            ?>
                            <input type="hidden" name="totalPrice" value="<?php echo htmlspecialchars($totalPrice); ?>">
                            <!-- Hidden token to protect against CSRF -->
                            <input type="hidden" name="token"
                                   value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">
                            <div class="text-center">
                                <button type="submit" class="btn btn-outline-dark btn-custom btn-block mx-auto">
                                    <i class="fas fa-shopping-bag me-1"></i>
                                    Finalize Purchase
                                </button>
                            </div>
                            <?php
                        } else {
                            ?>
                            <p>No books in the shopping cart.</p>
                            <?php
                        }
                        ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Footer-->
<?php
include "../layout/footer.php";
?>
</body>
</html>