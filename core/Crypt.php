<?php

namespace MkyCore;

class Crypt
{
    private static string $cipher;
    private static string $key;
    private static string $algo;

    /**
     * Encrypt text
     *
     * @param string $plaintext
     * @param string|null $key
     * @return string
     */
    public static function encrypt(string $plaintext, ?string $key = null): string
    {
        self::set();
        $key ??= self::$key;
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, self::$cipher, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac(self::$algo, $ciphertext_raw, $key, true);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    /**
     * @return void
     */
    private static function set(): void
    {
        self::$cipher = "AES-128-CBC";
        self::$key = env('APP_KEY', 'd56b367ce779578be2833208fc499202');
        self::$algo = 'sha256';
    }

    /**
     * Decrypt encrypted text
     *
     * @param string $ciphertext
     * @param string|null $key
     * @return string
     */
    public static function decrypt(string $ciphertext, ?string $key = null): string
    {
        self::set();
        $key ??= self::$key;
        $c = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, self::$cipher, $key, OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
        // timing attack safe comparison
        return hash_equals($hmac, $calcmac) ? $original_plaintext : '';
    }

    public static function hash(string $value): string
    {
        if(password_get_info($value)['algoName'] === 'unknown'){
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        return $value;
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}