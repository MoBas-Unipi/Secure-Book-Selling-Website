<?php
require_once __DIR__ . "/../../config.php";

global $sessionHandler;
global $shoppingCartHandler;
global $errorHandler;
global $accessControlHandler;
global $logger;

// Check path manipulation and broken access control by verifying the user authentication status
$accessControlHandler->redirectIfAnonymous();

// Define the path to the e-books directory, located outside the web root
// to ensure it is inaccessible to users directly
$eBookPath = '/home/bookselling/e-books/';

try {
    // Check if required POST fields are present
    if (checkPostFields(['id_book'])) {

        // Sanitize and validate against XSS
        $token = htmlspecialchars($_POST['token'], ENT_QUOTES, 'UTF-8');
        $idBook = htmlspecialchars($_POST['id_book'], ENT_QUOTES, 'UTF-8');

        // Protect against CSRF attacks
        if (!$token || $token !== $_SESSION['token']) {
            // Redirect with a 405 HTTP status code in case of attack
            $accessControlHandler->redirectIfXSRFAttack();
        } else {
            // Check if the user has purchased the selected book
            $result = checkBookPurchaseByBook($_SESSION['userId'], $idBook);
            if ($result) {
                // Ensure the query returned exactly one valid result
                $dataQuery = $result->fetch_assoc();
                if ($dataQuery !== null && $result->num_rows === 1) {
                    $ebookName = $dataQuery['ebook_name'];
                    // Construct the file path for download
                    $filePath = $eBookPath . $ebookName;
                    if (file_exists($filePath)) {
                        // Stream the file as a PDF attachment
                        header('Content-Type: application/pdf');
                        header('Content-Disposition: attachment; filename="' . $ebookName . '"');
                        readfile($filePath);
                        $logger->log('INFO', "E-Book: " . $ebookName . " downloaded by user: " . $_SESSION['email']);
                        exit;
                    } else {
                        throw new Exception("File not found");
                    }
                } else {
                    throw new Exception("Book is not available for download");
                }
            } else {
                throw new Exception("Error retrieving book information");
            }
        }
    } else {
        throw new Exception("Invalid data provided");
    }
} catch (Exception $e) {
    // Log and redirect user to home page on error, displaying an appropriate message
    $logger->log('ERROR', "E-Book download failed for user: " . $_SESSION['email'] .
        ". Reason: " . $e->getMessage());
    $accessControlHandler->redirectToHome("downloadBookError", $e->getMessage());
}
?>
