<?php
global $sessionHandler;

/**
 * ShoppingCartHandler class
 *
 * Singleton class to handle shopping cart operations.
 */
class ShoppingCartHandler {
    /**
     * The single instance of the ShoppingCartHandler class
     *
     * @var ShoppingCartHandler|null
     */
    private static ?ShoppingCartHandler $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     *
     * Initializes the shopping cart in the session if it does not already exist.
     */
    private function __construct() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    /**
     * Gets the single instance of the ShoppingCartHandler class
     *
     * @return ShoppingCartHandler|null The single instance of the ShoppingCartHandler class
     */
    public static function getInstance(): ?ShoppingCartHandler {
        if (self::$instance == null) {
            self::$instance = new ShoppingCartHandler();
        }
        return self::$instance;
    }

    /**
     * Adds an item to the shopping cart
     *
     * @param int $itemId The ID of the item to add
     * @param int $quantity The quantity of the item to add
     * @return bool True if the item is added successfully, false otherwise
     * @throws Exception If the book is not found or if there is an error retrieving availability
     */
    public function addItem($itemId, $quantity): bool {
        $quantityToLoad = $quantity;
        if (isset($_SESSION['cart'][$itemId])) {
            $quantityToLoad += $_SESSION['cart'][$itemId]['quantity'];
        }
        $result = checkBookAvailability($itemId, $quantityToLoad);
        if ($result) {
            $bookDetails = $result->fetch_assoc();
            if ($bookDetails !== null && $result->num_rows === 1) {
                $_SESSION['cart'][$itemId] = array(
                    'title' => $bookDetails['title'],
                    'author' => $bookDetails['author'],
                    'publisher' => $bookDetails['publisher'],
                    'quantity' => $quantityToLoad,
                    'price' => $bookDetails['price']
                );
                return true;
            } else {
                throw new Exception("Book not found in the database, impossible to add to the shopping cart.");
            }
        } else {
            throw new Exception("Error retrieving the book availability.");
        }
    }

    /**
     * Removes an item from the shopping cart
     *
     * @param int $itemId The ID of the item to remove
     * @return bool True if the item is removed successfully, false otherwise
     * @throws Exception If the item does not exist in the cart
     */
    public function removeItem($itemId): bool {
        if (isset($_SESSION['cart'][$itemId])) {
            if ($_SESSION['cart'][$itemId]['quantity'] > 1) {
                $_SESSION['cart'][$itemId]['quantity']--;
            } else {
                unset($_SESSION['cart'][$itemId]);
            }
            return true;
        } else {
            throw new Exception("The item does not exist in the cart.");
        }
    }

    /**
     * Gets all the books in the shopping cart
     *
     * @return array|null An array of books in the cart, or null if the cart is empty
     */
    public function getBooks() {
        if (!empty($_SESSION['cart'])) {
            return $_SESSION['cart'];
        } else {
            return null;
        }
    }

    /**
     * Clears the shopping cart
     *
     * @return bool True if the cart is cleared successfully, false otherwise
     * @throws Exception If there is an error during the clearing of the cart
     */
    public function clearShoppingCart(): bool {
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $itemId => $quantity) {
                if (isset($_SESSION['cart'][$itemId])) {
                    unset($_SESSION['cart'][$itemId]);
                }
            }
            return true;
        } else {
            throw new Exception("Error during the clear of the ShoppingCart");
        }
    }
}
