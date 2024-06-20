<?php
require_once __DIR__ . "/../../config.php";

global $logger;
global $sessionHandler;
global $accessControlHandler;

// Checks if the user is logged
$accessControlHandler->redirectIfAnonymous();

$result = getUserPurchases($_SESSION['userId']);
$logger->log('INFO', "User " . $_SESSION['email'] . " requested his purchases");


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" type="text/css" href="../../css/userOperationsStyle.css">
    <title>Book Selling - Purchases</title>
</head>
<body>

<?php
include "./../layout/header.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8"> <!-- Increase the column width -->
            <div class="signup-container">
                <h1 class="h3 mb-3 fw-normal text-center">Your Purchases</h1>

                <?php
                if ($result) {
                    ?>
                    <table class="table table-light table-striped mt-4">
                        <thead>
                        <tr>
                            <th class="text-center align-middle px-0">Time</th>
                            <th class="text-center align-middle px-0">Amount</th>
                            <th class="text-center align-middle px-0">Payment Method</th>
                            <th class="text-center align-middle px-0">Book</th>
                            <th class="text-center align-middle px-0">Quantity</th>
                            <th class="text-center align-middle px-0">E-book</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $previousTime = null;
                        // check if the query returned a result and more than 1 row
                        if ($result->num_rows >= 1) {
                            while ($order = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <?php
                                    // Display orders by time, amount and payment method
                                    if ($order['time'] !== $previousTime) {
                                        ?>
                                        <td class="text-center align-middle px-0"><?php echo htmlspecialchars($order['time']); ?></td>
                                        <td class="text-center align-middle px-0"><?php echo htmlspecialchars($order['amount']); ?></td>
                                        <td class="text-center align-middle px-0"><?php echo htmlspecialchars($order['payment_method']); ?></td>

                                        <?php
                                        $previousTime = $order['time'];
                                    } else {
                                        ?>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <?php
                                    }
                                    ?>
                                    <td class="align-middle px-0"><?php echo htmlspecialchars($order['title']); ?></td>
                                    <td class="text-center align-middle px-0"><?php echo htmlspecialchars($order['quantity']); ?></td>
                                    <td class="text-center align-middle px-0">
                                        <form action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/util/downloadEbook.php'); ?>"
                                              method="POST">
                                            <input type="hidden" name="id_book"
                                                   value="<?php echo htmlspecialchars($order['id_book']); ?>">
                                            <!-- Hidden token to protect against XSRF -->
                                            <input type="hidden" name="token"
                                                   value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">
                                            <button class="btn btn-outline-dark btn-sm" type="submit">
                                                <i class="fas fa-download me-1"></i> Download
                                            </button>

                                        </form>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <div class='alert alert-danger mt-4'>No purchases found.</div>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                    <?php
                } else {
                    ?>
                    <div class='alert alert-danger mt-4'>Error retrieving purchases details</div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>



