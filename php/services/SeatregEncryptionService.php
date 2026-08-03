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

    /**
     * Encrypt a value for storing it in the database.
     *
     * The returned value carries a version prefix so that a stored value can later be recognised
     * as encrypted without having to try to decrypt it first.
     *
     * @param string $plainText The value to encrypt.
     * @return string The prefixed, encrypted value.
     * @throws Exception If the OpenSSL extension is not enabled.
     */
    public static function encryptValue($plainText) {
        return SEATREG_ENCRYPTED_VALUE_PREFIX . self::encrypt($plainText);
    }

    /**
     * Check if a stored value was encrypted by encryptValue().
     *
     * @param string $storedValue The value read from the database.
     * @return bool
     */
    public static function isEncryptedValue($storedValue) {
        if( !is_string($storedValue) ) {
            return false;
        }

        return strpos($storedValue, SEATREG_ENCRYPTED_VALUE_PREFIX) === 0;
    }

    /**
     * Decrypt a value that was stored with encryptValue().
     *
     * Values without the prefix are returned as they are, so that values saved before encryption
     * was added keep working.
     *
     * @param string $storedValue The value read from the database.
     * @return string|null The plain text value, or null when the value is encrypted but can not be
     *                     read. That happens when AUTH_KEY has changed since the value was stored.
     */
    public static function decryptValue($storedValue) {
        if( !self::isEncryptedValue($storedValue) ) {
            return $storedValue;
        }

        if( !self::isOpenSSLEnabled() ) {
            return null;
        }

        $decrypted = self::decrypt( substr($storedValue, strlen(SEATREG_ENCRYPTED_VALUE_PREFIX)) );

        return $decrypted === false ? null : $decrypted;
    }
}