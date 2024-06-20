## AES 128-bit PRIVATE KEY

### USAGE
This key is a 16-byte key used to encrypt/decrypt data on server side with aes-128-gcm cipher.

This private key is loaded in the Session Manager class and used to encrypt/decrypt the customer credit card information saved in the session variables (paymentInfo array) (Number, Expiration Date and CVV).

Is possible to see an example in the shippingInfo.php page (encryption) and purchaseSummary.php page (decryption)

Can also be used to encrypt other data (future works)

----------------------------------------------------

### KEY GENERATION
To ensure more security you can regenerate the key periodically by typing the following command:

openssl rand -base64 16 > private_key.bin
