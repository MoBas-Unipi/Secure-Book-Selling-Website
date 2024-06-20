<?php
global $sessionHandler;
global $shoppingCartHandler;

$currentFile = htmlspecialchars(SERVER_ROOT . $_SERVER['SCRIPT_NAME']);
$currentPage = htmlspecialchars(basename($_SERVER['REQUEST_URI'], ".php"));

$books = $shoppingCartHandler->getBooks();
if($books !== null)
    $booksCount = count($books);
else
    $booksCount = '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Method</title>
    <script type="text/javascript" src="../../js/payment.js"></script>

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

<!-- Navigation-->
<nav class="navbar navbar-expand-lg navbar-light bg-black bg-opacity-10">
    <div class="container px-4 px-lg-5">
        <a class="navbar-brand" href="//<?php echo htmlspecialchars(SERVER_ROOT . '/') ?>">
        <img src="./../../img/iconHeader.png" alt="logo" class="img-fluid" style="width: 100px; height: auto;">
        </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-brand navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == '') ? 'active' : ''; ?>" aria-current="page" href="//<?php echo htmlspecialchars(SERVER_ROOT . '/') ?>">Home</a>
                    </li>

                    <?php if ($sessionHandler->isLogged()) { ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'profile') ? 'active' : ''; ?>" href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/profile.php') ?>">Profile</a>
                        </li>
                    <?php } ?>

                    <?php if ($sessionHandler->isLogged()) { ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage == 'purchases') ? 'active' : ''; ?>" href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/user/purchases.php') ?>">Purchases</a>
                        </li>
                    <?php } ?>
                </ul>
                <form class="d-flex">
                    <button class="btn btn-outline-dark mr-3" type="button">
                        <a class="nav-link" href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/user/shoppingCart.php') ?>">
                            <i class="fas fa-shopping-cart me-1"></i>Cart
                            <div class="badge bg-dark text-white ms-1 rounded-pill"><?php echo htmlspecialchars($booksCount); ?></div>
                        </a>
                    </button>

                    <?php if (!$sessionHandler->isLogged()) {
                        if (strcmp($currentFile, htmlspecialchars(SERVER_ROOT . '/php/login.php')) != 0) { ?>
                            <button class="btn btn-outline-dark ms-2" type="button">
                                <a class="nav-link" href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/login.php') ?>">Sign In</a>
                            </button>
                        <?php } else { ?>
                            <button class="btn btn-outline-dark ms-2" type="button">
                                <a class="nav-link" href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/signup.php') ?>">Sign Up</a>
                            </button>
                        <?php }
                    } else { ?>
                        <button class="btn btn-outline-dark ms-2" type="button">
                            <a class="nav-link" href="//<?php echo htmlspecialchars(SERVER_ROOT . '/php/logout.php') ?>">Log Out</a>
                        </button>
                    <?php } ?>
                </form>
            </div>
    </div>
    <?php if ($sessionHandler->isLogged()) { ?>
        <a class="navbar-brand navbar-nav nav-item">Welcome <?php echo htmlspecialchars($_SESSION['first_name']) ?></a>
    <?php } ?>
</nav>

</html>


