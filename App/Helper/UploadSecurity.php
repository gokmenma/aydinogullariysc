<?php

namespace App\Helper;

use RuntimeException;

final class UploadSecurity
{
    private const MAX_BYTES = 15728640;
    private const ALLOWED_MIMES = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'text/plain' => ['txt'],
        'text/csv' => ['csv'],
        'application/zip' => ['zip'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
    ];

    public static function validateAll(array $files): void
    {
        foreach ($files as $file) {
            self::walk($file);
        }
    }

    public static function validate(array $file, ?int $maxBytes = null, ?array $allowedMimes = null): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return $file;
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            throw new RuntimeException('Dosya yükleme doğrulaması başarısız oldu.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size < 1 || $size > ($maxBytes ?? self::MAX_BYTES)) {
            throw new RuntimeException('Dosya boyutu izin verilen sınırı aşıyor.');
        }
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, ['php', 'phtml', 'phar', 'cgi', 'pl', 'sh', 'htaccess', 'svg', 'html', 'js'], true)) {
            throw new RuntimeException('Bu dosya türüne izin verilmiyor.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        $rules = $allowedMimes ?? self::ALLOWED_MIMES;
        if (!isset($rules[$mime]) || !in_array($extension, $rules[$mime], true)) {
            throw new RuntimeException('Dosya içeriği ile uzantısı eşleşmiyor veya tür desteklenmiyor.');
        }
        $file['detected_mime'] = $mime;
        $file['safe_extension'] = $extension;
        return $file;
    }

    public static function randomName(array $validatedFile): string
    {
        return bin2hex(random_bytes(18)) . '.' . $validatedFile['safe_extension'];
    }

    private static function walk(array $file): void
    {
        if (isset($file['name']) && is_array($file['name'])) {
            foreach (array_keys($file['name']) as $key) {
                self::walk([
                    'name' => $file['name'][$key] ?? '', 'type' => $file['type'][$key] ?? '',
                    'tmp_name' => $file['tmp_name'][$key] ?? '', 'error' => $file['error'][$key] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $file['size'][$key] ?? 0,
                ]);
            }
            return;
        }
        self::validate($file);
    }
}
