<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit(); 
}

class SeatregEncryptionService {
    private static $encryptionMethod = 'aes-256-cbc';

    /**
     * Check if the OpenSSL extension is enabled.
     *
     * This method checks if the OpenSSL PHP extension is loaded and available for use.
     *
     * @return bool Returns true if the OpenSSL extension is loaded, false otherwise.
     */
    public static function isOpenSSLEnabled() {
        return extension_loaded('openssl');
    }

    /**
     * Retrieves the encryption key used for authentication.
     *
     * @return string The encryption key defined by the constant AUTH_KEY.
     */
    public static function getEncryptionKey() {
        return AUTH_KEY;
    }

    
    /**
     * Encrypts the given data using OpenSSL.
     *
     * This method encrypts the provided data using the OpenSSL extension. It first checks if the OpenSSL extension is enabled.
     * If not, it throws an exception. It then retrieves the encryption key and method, generates an initialization vector (IV),
     * and encrypts the data. The IV and the encrypted data are concatenated and encoded in base64 format before being returned.
     *
     * @param string $data The data to be encrypted.
     * @return string The base64 encoded string containing the IV and the encrypted data.
     * @throws Exception If the OpenSSL extension is not enabled.
     */
    public static function encrypt($data) {
        if ( !self::isOpenSSLEnabled() ) {
            throw new Exception('OpenSSL extension is not enabled.');
        }

        $key = self::getEncryptionKey();
        $method = self::$encryptionMethod;
        $ivlen  = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);

        return base64_encode( $iv . $encrypted );
    }

    /**
     * Decrypts the given data using OpenSSL.
     *
     * This method first checks if the OpenSSL extension is enabled. If not, it throws an exception.
     * It then retrieves the encryption key and method, decodes the base64-encoded data, extracts the
     * initialization vector (IV) and the encrypted data, and finally decrypts the data using the
     * specified encryption method and key.
     *
     * @param string $data The base64-encoded data to decrypt.
     * @return string|false The decrypted data on success, or false on failure.
     * @throws Exception If the OpenSSL extension is not enabled.
     */
    public static function decrypt($data) {
        if ( !self::isOpenSSLEnabled() ) {
            throw new Exception('OpenSSL extension is not enabled.');
        }

        $key = self::getEncryptionKey();
        $method = self::$encryptionMethod;
        $rawValue = base64_decode($data, true);
        $ivLen = openssl_cipher_iv_length($method);
        $iv = substr($rawValue, 0, $ivLen);
        $encrypted = substr($rawValue, $ivLen);

        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }
}