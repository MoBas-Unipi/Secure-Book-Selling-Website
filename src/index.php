<?php
require_once __DIR__ . "/config.php";

// Initialize global objects
global $errorHandler;
global $accessControlHandler;
global $sessionHandler;
global $logger;

try {
    // Sanitize GET parameters for displaying payment response after purchase
    $paymentResponse = isset($_GET['paymentResponse']) ? htmlspecialchars($_GET['paymentResponse'], ENT_QUOTES, 'UTF-8') : null;
    if ($paymentResponse !== null) {
        if ($paymentResponse === "OK") {
            showInfoMessage("Payment Successful! Thank you for your purchase.");
        } else {
            throw new Exception($paymentResponse);
        }
    }

    // Sanitize GET parameters for displaying download errors
    $downloadBookError = isset($_GET['downloadBookError']) ? htmlspecialchars($_GET['downloadBookError'], ENT_QUOTES, 'UTF-8') : null;
    if ($downloadBookError !== null) {
        throw new Exception($downloadBookError);
    }

} catch (Exception $e) {
    $errorHandler->handleException($e);
}

// Process search form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && checkPostFields(['search_query'])) {

    // Sanitize input fields to prevent XSS
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $searchQuery = filter_input(INPUT_POST, "search_query", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Protect against XSRF attacks
    if (!$token || $token !== $_SESSION['token']) {
        // Redirect in case of XSRF attack (405 status code)
        $accessControlHandler->redirectIfXSRFAttack();
    } else {
        // Perform book search based on user input
        $result = searchBooks($searchQuery);
    }
} else {
    // Fetch all books if no search query is submitted
    $result = getBooks();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Include core theme CSS, including Bootstrap -->
    <link href="css/styles.css" rel="stylesheet" />
    <title>Book Selling - Home</title>
</head>
<body>

<?php
include "./php/layout/header.php";
?>

<div class="container mt-5">
    <!-- Search form for books -->
    <form class="d-flex" name="search" action="//<?php echo htmlspecialchars(SERVER_ROOT . '/'); ?>" method="POST">
        <input class="form-control me-2" type="text" name="search_query" placeholder="Search for books" required>
        <!-- Hidden token to protect against CSRF -->
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">
        <button class="btn btn-outline-dark flex-shrink-0 mx-auto btn-custom btn-block" type="submit">
            <i class="fas fa-search"></i> Search
        </button>
    </form>
</div>

<section class="py-5">
    <div class="container px-4 px-lg-5 mt-5">
        <div class="row gx-4 gx-lg-5 row-cols-2 row-cols-md-3 row-cols-xl-4 justify-content-center">
            <?php
            // Display books fetched from the database
            if ($result) {
                if ($result->num_rows >= 1) {
                    while ($book = $result->fetch_assoc()) {
                        ?>
                        <div class="col mb-5">
                            <div class="card h-100">
                                <!-- Product image -->
                                <a href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/bookDetails.php?book_id=' . $book['id']); ?>">
                                    <img class="card-img-top" src="/img/books/<?php echo ($book['id'] < 17) ? htmlspecialchars($book['id']) : 16; ?>.jpg" alt="Book Image" />
                                </a>
                                <!-- Product details -->
                                <div class="card-body p-4">
                                    <div class="text-center">
                                        <!-- Product name -->
                                        <h5 class="fw-bolder">
                                            <a href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/bookDetails.php?book_id=' . $book['id']); ?>">
                                                <?php
                                                $title = $book['title'];
                                                if (strlen($title) > 30) {
                                                    $title = substr($title, 0, 30) . '...';
                                                }
                                                echo htmlspecialchars($title);
                                                ?>
                                            </a>
                                        </h5>
                                        <!-- Product author -->
                                        <p><?php echo htmlspecialchars($book['author']) ?></p>
                                        <!-- Product price -->
                                        <p>$<?php echo htmlspecialchars($book['price']) ?></p>
                                    </div>
                                </div>
                                <!-- Product actions -->
                                <div class="card-footer p-4 pt-0 border-top-0 bg-transparent">
                                    <div class="text-center">
                                        <form action="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/bookDetails.php?book_id=' . $book['id']); ?>" method="POST" class="d-inline">
                                            <input type="hidden" name="bookId" value="<?php echo htmlspecialchars($book['id']); ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <!-- Hidden token to protect against CSRF -->
                                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-outline-dark mt-auto">
                                                <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    // Display message if no books are found
                    ?>
                    <div class='alert alert-danger mt-4'>Book not found in the database</div>
                    <?php
                }
            } else {
                // Display error message if there is an issue retrieving book details
                ?>
                <div class='alert alert-danger mt-4'>Error retrieving book details</div>
                <?php
            }
            ?>
        </div>
    </div>
</section>

<?php
include "php/layout/footer.php";
?>

</body>
</html>