<?php
namespace System\Security\Encryption;

/**
 * Class providing basic string encryption.
 */
class TEncrypt {
    /**
     * Encrypts given `$inputString` using `$encryptionKey`. The resulting string is automatically
     * base64-encoded unless `$base64encode` is set to `false`.
     */
    public static function encrypt(string $inputString, string $encryptionKey, bool $base64encode = true) : string {
        $cipher     = 'AES-256-CBC';
        $options    = OPENSSL_RAW_DATA;
        $hashAlgo  = 'sha256';
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $cipherTeztRaw = openssl_encrypt($inputString, $cipher, $encryptionKey, $options, $iv);
        $hmac = hash_hmac($hashAlgo, $cipherTeztRaw, $encryptionKey, true);

        $result = gzcompress($iv.$hmac.$cipherTeztRaw, 9);
        return $base64encode ? base64_encode($result) : $result;
    }

    /**
     * Tries to decrypt given `$encryptedString` using `$encryptionKey`. This function assumes that
     * input string is base64-encoded unless `$base64decode` is set to `false`.
     * 
     * Returns decrypted string or null on failure.
     */
    public static function decrypt(string $encryptedString, string $encryptionKey, bool $base64decode = true) : ?string {
        $encryptedString = gzuncompress($base64decode ? base64_decode($encryptedString) : $encryptedString);

        $cipher     = 'AES-256-CBC';
        $options    = OPENSSL_RAW_DATA;
        $hashAlgo  = 'sha256';
        $sha2len    = 32;
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($encryptedString, 0, $ivlen);
        $hmac = substr($encryptedString, $ivlen, $sha2len);
        $cipherTeztRaw = substr($encryptedString, $ivlen+$sha2len);
        $plainText = openssl_decrypt($cipherTeztRaw, $cipher, $encryptionKey, $options, $iv);
        $calcmac = hash_hmac($hashAlgo, $cipherTeztRaw, $encryptionKey, true);
        
        if (hash_equals($hmac, $calcmac)) {
            return $plainText;
        }

        return null;
    }
}