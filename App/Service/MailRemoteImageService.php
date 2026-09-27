<?php

namespace App\Service;

use RuntimeException;

final class MailRemoteImageService
{
    private const MAX_BYTES = 5242880;
    private const ALLOWED_MIME = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    public static function proxyUrl(string $url): string
    {
        $encoded = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encoded, self::key());
        return 'api/mailbox_remote_image.php?u=' . rawurlencode($encoded) . '&s=' . $signature;
    }

    public static function fetchUrl(string $url): array
    {
        $encoded = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
        return self::fetch($encoded, hash_hmac('sha256', $encoded, self::key()));
    }

    public static function fetch(string $encoded, string $signature): array
    {
        if (!hash_equals(hash_hmac('sha256', $encoded, self::key()), $signature)) {
            throw new RuntimeException('Geçersiz görsel imzası.');
        }
        $url = base64_decode(strtr($encoded, '-_', '+/'), true);
        if (!is_string($url) || $url === '') throw new RuntimeException('Geçersiz görsel adresi.');
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('Görsel adresine izin verilmiyor.');
        }
        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
        if (!in_array($port, [80, 443], true)) throw new RuntimeException('Görsel portuna izin verilmiyor.');

        $records = dns_get_record($host, DNS_A);
        $ips = array_values(array_unique(array_filter(array_column($records ?: [], 'ip'))));
        if (!$ips) throw new RuntimeException('Görsel sunucusu çözümlenemedi.');
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('Özel veya ayrılmış ağ adreslerine erişim engellendi.');
            }
        }

        $body = '';
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'Aydinogullari-Mail-Image-Proxy/1.0',
            CURLOPT_HTTPHEADER => ['Accept: image/png,image/jpeg,image/gif,image/webp', 'Cookie:', 'Referer:'],
            CURLOPT_RESOLVE => [$host . ':' . $port . ':' . $ips[0]],
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > self::MAX_BYTES) return 0;
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($ok === false || $status < 200 || $status >= 300 || $body === '') {
            throw new RuntimeException($error !== '' ? $error : 'Görsel indirilemedi.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($body) ?: '';
        if (!in_array($mime, self::ALLOWED_MIME, true) || @getimagesizefromstring($body) === false) {
            throw new RuntimeException('İndirilen içerik güvenli bir görsel değil.');
        }
        return ['content' => $body, 'mime_type' => $mime];
    }

    private static function key(): string
    {
        $key = (string) (getenv('MAIL_IMAGE_PROXY_KEY') ?: (defined('KEY') ? KEY : 'mail-image-proxy'));
        return hash('sha256', $key, true);
    }
}
