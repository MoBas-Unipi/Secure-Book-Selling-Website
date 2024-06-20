<?php
require_once __DIR__ . "/../../config.php";

global $logger;
global $sessionHandler;
global $shoppingCartHandler;
global $errorHandler;
global $accessControlHandler;

$items = $shoppingCartHandler->getBooks();
$totalPrice = 0;

// Check if a form submission with itemId is detected
if (checkPostFields(['itemId'])) {

    // Sanitize and protect against XSS
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $bookId = filter_input(INPUT_POST, 'itemId', FILTER_SANITIZE_NUMBER_INT);

    // Protect against XSRF attacks
    if (!$token || $token !== $_SESSION['token']) {
        // Redirect with 405 HTTP status code if attack detected
        $accessControlHandler->redirectIfXSRFAttack();
    } else {
        try {
            // Attempt to remove the item from the shopping cart
            if ($shoppingCartHandler->removeItem($bookId)) {
                $logger->log('INFO', "Book with id=" . $bookId . " successfully removed from the shopping cart");
                // Redirect to update the shopping cart display
                header('Location: //' . SERVER_ROOT . '/php/user/shoppingCart.php');
                exit;
            }
        } catch (Exception $e) {
            // Handle any exceptions with the error handler
            $errorHandler->handleException($e);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="../../css/shoppingCart.css">
    <title>Book Selling - Shopping Cart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<?php
include "./../layout/header.php";
?>

<div class="container px-3 my-5 clearfix">
    <div class="card">
        <div class="card-header">
            <h2>Shopping Cart</h2>
        </div>
        <div class="card-body">
            <?php
            if ($items !== null) {
                ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-4">
                        <thead>
                        <tr>
                            <!-- Define column widths -->
                            <th class="text-center py-3 px-4" style="min-width: 400px;">Books & Details</th>
                            <th class="text-center py-3 px-4" style="width: 100px;">Price</th>
                            <th class="text-center py-3 px-4" style="width: 120px;">Quantity</th>
                            <th class="text-center py-3 px-4" style="width: 100px;">Total</th>
                            <th class="text-center align-middle py-3 px-0" style="width: 40px;"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($items as $itemId => $itemDetails) {
                            $totalPrice += $itemDetails['price'] * $itemDetails['quantity'];
                            ?>
                            <tr>
                                <td class="p-2">
                                    <div class="d-flex align-items-center">
                                        <img src="../../img/books/<?php echo htmlspecialchars($itemId); ?>.jpg" class="img-thumbnail me-2" alt="Book Image" style="width: 100px; height: auto;">
                                        <div class="flex-grow-1">
                                            <a href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/bookDetails.php?book_id=' . $itemId); ?>" class="d-block text-dark font-weight-bold"><?= htmlspecialchars($itemDetails['title']) ?></a>
                                            <small class="text-muted">
                                                <span class="d-block">Author: <?= htmlspecialchars($itemDetails['author']) ?></span>
                                                <span class="d-block">Publisher: <?= htmlspecialchars($itemDetails['publisher']) ?></span>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center font-weight-bold align-middle p-2">
                                    <span class="h6">$<?= htmlspecialchars($itemDetails['price']) ?></span>
                                </td>
                                <td class="text-center font-weight-bold align-middle p-2">
                                    <span class="h6"><?= htmlspecialchars($itemDetails['quantity']) ?></span>
                                </td>
                                <td class="text-center font-weight-bold align-middle p-2">
                                    <span class="h6">$<?= htmlspecialchars($itemDetails['price'] * $itemDetails['quantity']) ?></span>
                                </td>
                                <td class="text-center align-middle px-3">
                                    <form action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/user/shoppingCart.php'); ?>" method="POST">
                                        <input type="hidden" name="itemId" value="<?php echo htmlspecialchars($itemId); ?>">
                                        <!-- Hidden token to protect against CSRF -->
                                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm ms-1 px-3 py-2"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-4 mr-3 h5">
                    <label class="text-muted font-weight-normal m-0">Total price</label>
                    <div class="text-large"><strong>$<?= $totalPrice ?></strong></div>
                    <br>
                </div>

                <?php
            } else {
                ?>
                <h4>No items to show in the shopping cart</h4>
                <?php
            }
            ?>

            <div class="d-flex justify-content-end">
                <a href="../../" class="btn btn-lg btn-default md-btn-flat mt-2 mr-3">Back to shopping</a>
                <?php
                // Display checkout button if user is logged in and has items in cart
                if ($items !== null) {
                    if ($sessionHandler->isLogged()) {
                        $pathNextStepToCheckout = $accessControlHandler->getNextStepToCheckout();
                        ?>
                        <a href="//<?php echo htmlspecialchars($pathNextStepToCheckout); ?>" class="btn btn-outline-dark btn-custom btn-block mt-2 mr-3 h-75">
                            <i class="fas fa-credit-card me-1"></i>Checkout
                        </a>
                        <?php
                    } else {
                        ?>
                        <a href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/login.php');?>" class="btn btn-outline-dark mt-2 mr-3">Checkout</a>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
