<?php

namespace MkyCore;

class Crypt
{
    private static string $cipher;
    private static string $key;
    private static string $algo;

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

    private static function set()
    {
        self::$cipher = "AES-128-CBC";
        self::$key = env('APP_KEY', 'd56b367ce779578be2833208fc499202');
        self::$algo = 'sha256';
    }

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
        if (hash_equals($hmac, $calcmac))// timing attack safe comparison
        {
            return $original_plaintext;
        }
    }
}