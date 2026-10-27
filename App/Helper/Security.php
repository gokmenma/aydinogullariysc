<?php

namespace App\Helper;

class Security
{
    public static function escape($data)
    {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    // CSRF Token
    public static function csrf()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $token = bin2hex(random_bytes(48));
            $_SESSION['csrf_token'] = $token;
        } else {
            $token = $_SESSION['csrf_token'];
        }
        return $token;
    }

    public static function checkCsrfToken($token = null)
    {
        $token = $token ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? ''));
        $expected = (string) ($_SESSION['csrf_token'] ?? '');
        return $expected !== '' && is_string($token) && $token !== '' && hash_equals($expected, $token);
    }

    public static function generatePassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function passwordControl($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public static function encrypt($data)
    {
        if (empty($data)) {
            return '';
        }

        $method = "AES-256-GCM";
        $key = hash('sha256', 'mysecretkey', true);
        $iv = openssl_random_pseudo_bytes(12);
        $tag = null;
        $encrypted_data = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
        $result = base64_encode($iv . $tag . $encrypted_data);
        return rawurlencode($result);
    }

    public static function decrypt($data)
    {
        $method = "AES-256-GCM";
        $key = hash('sha256', 'mysecretkey', true);
        $data = rawurldecode($data);
        $data = base64_decode($data);
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted_data = substr($data, 28);
        return openssl_decrypt($encrypted_data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
    }

    /**
     * ISO 27001 Fiziksel Belge Şifreleme (AES-256-GCM)
     */
    public static function encryptFile(string $data): string
    {
        $keyString = getenv('APP_KEY') ?: (getenv('FILE_ENCRYPTION_KEY') ?: 'aydinogullari_file_secret_key_2026');
        $key = hash('sha256', $keyString, true);
        $method = "AES-256-GCM";
        $iv = openssl_random_pseudo_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
        return "ENCGCM:" . $iv . $tag . $encrypted;
    }

    /**
     * ISO 27001 Fiziksel Belge Deşifreleme (AES-256-GCM)
     * Şifreli değilse (eski sistem verileri) ham içeriği döner.
     */
    public static function decryptFile(string $data): string
    {
        if (str_starts_with($data, "ENCGCM:")) {
            $data = substr($data, 7);
            if (strlen($data) >= 28) {
                $keyString = getenv('APP_KEY') ?: (getenv('FILE_ENCRYPTION_KEY') ?: 'aydinogullari_file_secret_key_2026');
                $key = hash('sha256', $keyString, true);
                $method = "AES-256-GCM";
                $iv = substr($data, 0, 12);
                $tag = substr($data, 12, 16);
                $ciphertext = substr($data, 28);
                $decrypted = openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
                if ($decrypted !== false) {
                    return $decrypted;
                }
            }
        }
        return $data;
    }
}
