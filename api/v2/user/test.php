<?php

// Your password
$password = '12345678';

// Your AES-128 key (base64 decoded)
$key = base64_decode('feUGSmdz4ih/vxOxOZg506eOnfOgSUP1AHmrCqT8ayg=');

// Generate a 16-byte IV
$iv = random_bytes(16);

// Encrypt the password using AES-128-CBC
$cipher = 'aes-128-cbc';
$encryptedPassword = openssl_encrypt($password, $cipher, $key, OPENSSL_RAW_DATA, $iv);

// Encode the IV and encrypted password to base64 for safe transmission/storage
$ivBase64 = base64_encode($iv);
$encryptedPasswordBase64 = base64_encode($encryptedPassword);

// Print or return the IV and encrypted password (base64 encoded)
echo "IV (Base64): " . $ivBase64 . "\n";
echo "Encrypted Password (Base64): " . $encryptedPasswordBase64 . "\n";

?>