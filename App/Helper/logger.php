<?php
use App\Logging\LoggerFactory;
use Monolog\Logger;

/**
 * Global logger getter - basit ve hızlı
 * Kullanım: $logger = \getLogger("kesif");
 *           $logger->info("Mesaj", ['user_id' => $_SESSION['lid']]);
 */
function getLogger($module = 'app'): Logger
{
    global $ac;
    
    try {
        $user_id = $_SESSION['lid'] ?? 0;
        $username = $_SESSION['username'] ?? 'Guest';
        
        // Module'e göre logger seç
        if ($module === 'kesif') {
            return LoggerFactory::kesif($ac, $user_id, $username);
        } elseif ($module === 'security') {
            return LoggerFactory::security($ac, $user_id, $username);
        } elseif ($module === 'database') {
            return LoggerFactory::database($ac, $user_id, $username);
        } else {
            return LoggerFactory::file();
        }
    } catch (\Exception $e) {
        error_log("Logger hatası: " . $e->getMessage());
        return LoggerFactory::file();
    }
}

/**
 * Kısayol fonksiyon - hızlı loglama
 * Kullanım: log_info("Mesaj", "kesif");
 */
function log_info($message, $module = 'app', $context = [])
{
    \getLogger($module)->info($message, $context);
}

function log_error($message, $module = 'app', $context = [])
{
    \getLogger($module)->error($message, $context);
}

function log_warning($message, $module = 'app', $context = [])
{
    \getLogger($module)->warning($message, $context);
}

function log_debug($message, $module = 'app', $context = [])
{
    \getLogger($module)->debug($message, $context);
}

/**
 * Kullanıcı işlemlerini standart ve sorgulanabilir biçimde kaydeder.
 *
 * $context yalnızca işlemi açıklayan güvenli alanları içermelidir; parola,
 * oturum anahtarı ve dosya geçici yolu gibi hassas veriler gönderilmemelidir.
 */
function audit_log(
    string $eventType,
    string $module,
    string $summary,
    ?string $entityType = null,
    int|string|null $entityId = null,
    array $context = [],
    string $level = 'info'
): void {
    $allowedEvents = [
        'view', 'login', 'logout', 'create', 'update', 'delete',
        'export', 'copy', 'status_change', 'upload', 'download', 'error'
    ];
    $eventType = strtolower(trim($eventType));
    if (!in_array($eventType, $allowedEvents, true)) {
        $eventType = 'update';
    }

    $auditContext = [
        '_audit' => [
            'event_type' => $eventType,
            'module' => mb_substr(trim($module), 0, 80),
            'entity_type' => $entityType ? mb_substr(trim($entityType), 0, 80) : null,
            'entity_id' => $entityId !== null ? mb_substr((string) $entityId, 0, 100) : null,
            'summary' => mb_substr(trim($summary), 0, 255),
        ],
        'data' => $context,
    ];

    try {
        $logger = \getLogger('database');
        $level = strtolower($level);
        if (!in_array($level, ['debug', 'info', 'warning', 'error', 'critical'], true)) {
            $level = 'info';
        }
        $logger->{$level}($summary, $auditContext);
    } catch (\Throwable $e) {
        // Loglama problemi ana kullanıcı işlemini durdurmamalıdır.
        error_log("Audit log hatası: " . $e->getMessage());
    }
}

/**
 * İzin verilen alanlarda değişen eski/yeni değerleri döndürür.
 */
function audit_changes(array|object|null $before, array $after, array $allowedFields): array
{
    $before = is_object($before) ? get_object_vars($before) : ($before ?? []);
    $changes = [];
    foreach ($allowedFields as $field) {
        $oldValue = $before[$field] ?? null;
        $newValue = $after[$field] ?? null;
        $isEqual = is_numeric($oldValue) && is_numeric($newValue)
            ? abs((float) $oldValue - (float) $newValue) < 0.000001
            : (string) $oldValue === (string) $newValue;

        if (!$isEqual) {
            $normalize = static function ($value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                return is_string($value) ? mb_substr($value, 0, 500) : $value;
            };
            $changes[$field] = [
                'old' => $normalize($oldValue),
                'new' => $normalize($newValue),
            ];
        }
    }
    return $changes;
}
