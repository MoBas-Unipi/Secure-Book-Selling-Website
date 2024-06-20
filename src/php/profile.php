<?php
require_once __DIR__ . "/../config.php";

global $sessionHandler;
global $accessControlHandler;

// Check path manipulation and broken access control
// Check if the user is logged
$accessControlHandler->redirectIfAnonymous();

// Retrieves user's data from the db
$result = getUserData($_SESSION['email']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Profile</title>
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        .form-group {
            margin-bottom: 15px; /* Add vertical space between form groups */
        }
    </style>
</head>
<body>

<?php include "./layout/header.php"; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h3>Profile</h3>
                    </div>

                    <?php if ($result && $user = $result->fetch_assoc()) { ?>
                        <form>
                            <!-- Display user's information -->
                            <?php foreach ($user as $key => $value) { ?>
                                <div class="form-group row">
                                    <label for="<?php echo $key; ?>" class="col-sm-4 col-form-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" id="<?php echo $key; ?>" readonly="readonly"
                                               value="<?php echo htmlspecialchars($value); ?>">
                                    </div>
                                </div>
                            <?php } ?>
                        </form>
                        <div class="text-center">
                            <a href="passwordChange.php" class="btn btn-outline-dark flex-shrink-0 mx-auto btn-custom btn-block">Change Password</a>
                        </div>
                    <?php } else { ?>
                        <div class='alert alert-danger mt-4'>User not found in the database</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
