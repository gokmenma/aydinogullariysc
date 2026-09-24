<?php

namespace App\Helper;

use PDO;
use Throwable;

final class MaintenanceMode
{
    public static function isEnabled(PDO $db): bool
    {
        $status = self::getStatus($db);
        return $status['active'];
    }

    /**
     * Bakımın manuel ve planlı durumunu tek noktadan üretir.
     * Tarihler veritabanında uygulamanın Europe/Istanbul saat diliminde tutulur.
     */
    public static function getStatus(PDO $db): array
    {
        $defaults = [
            'active' => false,
            'manual' => false,
            'scheduled' => false,
            'announcement' => false,
            'message' => '',
            'starts_at' => null,
            'ends_at' => null,
            'server_time' => date('Y-m-d H:i:s'),
        ];

        try {
            $keys = [
                'maintenance_mode',
                'maintenance_announcement',
                'maintenance_start_at',
                'maintenance_end_at',
            ];
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $stmt = $db->prepare("SELECT var, val FROM settings WHERE var IN ($placeholders)");
            $stmt->execute($keys);
            $settings = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $settings[$row['var']] = (string) $row['val'];
            }

            $manual = ($settings['maintenance_mode'] ?? '0') === '1';
            $startValue = trim($settings['maintenance_start_at'] ?? '');
            $endValue = trim($settings['maintenance_end_at'] ?? '');
            $start = $startValue !== '' ? strtotime($startValue) : false;
            $end = $endValue !== '' ? strtotime($endValue) : false;
            $now = time();
            $hasValidSchedule = $start !== false && $end !== false && $end > $start;
            $scheduled = $hasValidSchedule && $now >= $start && $now < $end;
            $announcement = $hasValidSchedule && $now < $start;

            return [
                'active' => $manual || $scheduled,
                'manual' => $manual,
                'scheduled' => $scheduled,
                'announcement' => $announcement,
                'message' => trim($settings['maintenance_announcement'] ?? ''),
                'starts_at' => $hasValidSchedule ? date('Y-m-d H:i:s', $start) : null,
                'ends_at' => $hasValidSchedule ? date('Y-m-d H:i:s', $end) : null,
                'server_time' => date('Y-m-d H:i:s', $now),
            ];
        } catch (Throwable $e) {
            error_log('MaintenanceMode::getStatus error: ' . $e->getMessage());
            return $defaults;
        }
    }

    public static function hasAccessPermission(PDO $db): bool
    {
        $userId = isset($_SESSION['lid']) ? (int) $_SESSION['lid'] : 0;
        if (empty($_SESSION['login']) || $userId <= 0) {
            return false;
        }

        try {
            $stmt = $db->prepare(
                "SELECT 1
                 FROM users u
                 INNER JOIN userauths ua ON ua.roleID = u.permission
                 INNER JOIN authority a ON a.id = ua.authID
                 WHERE u.id = ?
                   AND u.statu = 1
                   AND a.authName = ?
                   AND a.isActive = 1
                 LIMIT 1"
            );
            $stmt->execute([$userId, 'maintenance_access']);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('MaintenanceMode::hasAccessPermission error: ' . $e->getMessage());
            return false;
        }
    }

    public static function enforce(PDO $db, string $rootPath): void
    {
        if (PHP_SAPI === 'cli' || !self::isEnabled($db)) {
            return;
        }

        $scriptName = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $isLoggedIn = !empty($_SESSION['login']) && !empty($_SESSION['lid']);

        // Bakım kontrolü oturum açıldıktan sonra yapılır; giriş ekranı her zaman erişilebilir kalır.
        if ($scriptName === 'login.php') {
            return;
        }

        if (!$isLoggedIn) {
            if (self::expectsJson()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'status' => 'unauthorized',
                    'message' => 'Devam etmek için giriş yapmanız gerekiyor.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $returnUrl = urlencode((string) ($_SERVER['REQUEST_URI'] ?? 'index.php?p=home'));
            header('Location: ' . self::projectUrl($rootPath, 'login.php') . '?returnUrl=' . $returnUrl);
            exit;
        }

        if (self::hasAccessPermission($db)) {
            return;
        }

        if ($scriptName === 'maintenance.php' || $scriptName === 'logout.php') {
            return;
        }

        if (self::expectsJson()) {
            http_response_code(503);
            header('Content-Type: application/json; charset=UTF-8');
            header('Retry-After: 300');
            echo json_encode([
                'status' => 'maintenance',
                'message' => 'Sistem bakım çalışması nedeniyle geçici olarak kullanıma kapalıdır.'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        http_response_code(302);
        header('Location: ' . self::projectUrl($rootPath, 'maintenance.php'));
        exit;
    }

    private static function expectsJson(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        return strpos($accept, 'application/json') !== false
            || $requestedWith === 'xmlhttprequest'
            || strpos($scriptPath, '/api/') !== false;
    }

    private static function projectUrl(string $rootPath, string $fileName): string
    {
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        $projectRoot = realpath($rootPath);

        if ($documentRoot && $projectRoot && strpos($projectRoot, $documentRoot) === 0) {
            $relativeRoot = str_replace('\\', '/', substr($projectRoot, strlen($documentRoot)));
            return rtrim($relativeRoot, '/') . '/' . ltrim($fileName, '/');
        }

        return '/' . ltrim($fileName, '/');
    }
}
