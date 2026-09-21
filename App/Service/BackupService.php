<?php
namespace App\Service;

use App\Model\BackupModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
use PDO;
use ZipArchive;
use Exception;

class BackupService
{
    protected PDO $db;
    protected BackupModel $backupModel;
    protected string $backupDir;
    protected string $rootDir;

    public function __construct(?PDO $db = null)
    {
        global $ac;
        $this->db = $db ?? $ac;
        $this->backupModel = new BackupModel();
        $this->rootDir = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
        $this->backupDir = $this->rootDir . '/backups';

        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0777, true);
        }
        @chmod($this->backupDir, 0777);
    }

    /**
     * Yedekleme işlemini arka planda (asenkron) başlatır
     *
     * @param string $backupType 'full' | 'db' | 'files'
     * @param int|null $userId
     * @return array
     */
    public function runBackupAsync(string $backupType = 'full', ?int $userId = null): array
    {
        $executed = false;
        $disabledFunctions = array_map('trim', explode(',', (string)ini_get('disable_functions')));

        $canExec = function_exists('exec') && !in_array('exec', $disabledFunctions, true);
        $canEscape = function_exists('escapeshellarg') && !in_array('escapeshellarg', $disabledFunctions, true);

        if ($canExec && $canEscape) {
            $phpBin = $this->detectPhpBinary();
            $script = \escapeshellarg($this->rootDir . '/cron_backup.php');
            $typeArg = \escapeshellarg($backupType);

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                if (function_exists('popen') && function_exists('pclose') && !in_array('popen', $disabledFunctions, true)) {
                    @pclose(@popen("start /B {$phpBin} {$script} {$typeArg}", "r"));
                    $executed = true;
                }
            } else {
                @exec("{$phpBin} {$script} {$typeArg} > /dev/null 2>&1 &", $out, $returnCode);
                if (isset($returnCode) && $returnCode === 0) {
                    $executed = true;
                }
            }
        }

        // CLI exec çalışmadıysa veya paylaşımlı hostingde kısıtlıysa, arka plan HTTP Webhook ile tetikle
        if (!$executed) {
            $this->triggerBackupViaHttpAsync($backupType);
        }

        return [
            'success' => true,
            'async' => true,
            'message' => 'Yedekleme işlemi arka planda başlatıldı.'
        ];
    }

    /**
     * Arka planda çalışan PHP CLI yolunu otomatik tespit eder
     */
    protected function detectPhpBinary(): string
    {
        if (file_exists('/opt/lampp/bin/php')) {
            return '/opt/lampp/bin/php';
        }

        if (defined('PHP_BINARY') && !empty(PHP_BINARY) && is_executable(PHP_BINARY) && !str_contains(PHP_BINARY, 'php-fpm') && !str_contains(PHP_BINARY, 'httpd') && !str_contains(PHP_BINARY, 'apache')) {
            return PHP_BINARY;
        }

        $commonPaths = [
            '/usr/local/bin/php',
            '/usr/bin/php',
            '/opt/cpanel/ea-php83/root/usr/bin/php',
            '/opt/cpanel/ea-php82/root/usr/bin/php',
            '/opt/cpanel/ea-php81/root/usr/bin/php',
            '/opt/cpanel/ea-php80/root/usr/bin/php'
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return 'php';
    }

    /**
     * CLI erişimi olmayan sunucularda cron_backup.php'yi arka planda (non-blocking) tetikler
     */
    protected function triggerBackupViaHttpAsync(string $backupType): void
    {
        $settings = $this->backupModel->getBackupSettings();
        $token = $settings['backup_cron_token'] ?? '';
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $baseUri = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $baseUri = ($baseUri === '/' || $baseUri === '\\') ? '' : rtrim($baseUri, '/\\');
        
        $url = $protocol . $domain . $baseUri . '/cron_backup.php?token=' . urlencode($token) . '&type=' . urlencode($backupType);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1200);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            @curl_exec($ch);
            @curl_close($ch);
        }
    }

    /**
     * Ana Yedekleme Sürecini Çalıştırır (DB + Dosyalar + Harici Depolama + Mail + Temizlik)
     *
     * @param string $backupType 'full' | 'db' | 'files'
     * @param int|null $userId
     * @return array Sonuç ve istatistik dizisi
     */
    public function runBackup(string $backupType = 'full', ?int $userId = null): array
    {
        @ignore_user_abort(true);
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $startTime = microtime(true);
        $timestamp = date('Y-m-d_H-i-s');
        $settings = $this->backupModel->getBackupSettings();

        $mainZipName = "backup_{$backupType}_{$timestamp}.zip";
        $mainZipPath = $this->backupDir . '/' . $mainZipName;

        $logId = $this->backupModel->createLog([
            'backup_type' => $backupType,
            'file_name' => $mainZipName,
            'file_path' => 'backups/' . $mainZipName,
            'status' => 'in_progress',
            'created_by' => $userId
        ]);

        // Olası ölümcül PHP / Sunucu kesintilerini yakalamak için kapatma kancası
        register_shutdown_function(function() use (&$logId, &$startTime, &$tempFiles) {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                if ($logId) {
                    $this->backupModel->updateLog($logId, [
                        'status' => 'failed',
                        'duration_sec' => round(microtime(true) - $startTime, 2),
                        'message' => 'SUNUCU KESİNTİSİ / FATAL ERROR: ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line']
                    ]);
                }
            }
        });

        $dbDumpFile = null;
        $filesZipFile = null;
        $tempFiles = [];
        $messages = [];
        $status = 'success';
        $remoteStatus = 'none';
        $mailStatus = 'none';

        try {
            $zip = new ZipArchive();
            if ($zip->open($mainZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Yedek zip arşivi oluşturulamadı: $mainZipPath");
            }

            // 1. Veritabanı Yedeği Al
            if (in_array($backupType, ['full', 'db'], true)) {
                $dbDumpFile = $this->backupDir . "/db_dump_{$timestamp}.sql";
                $tempFiles[] = $dbDumpFile;
                
                $dbResult = $this->exportDatabase($dbDumpFile);
                if ($dbResult['success']) {
                    $zip->addFile($dbDumpFile, "database_{$timestamp}.sql");
                    $messages[] = "Veritabanı yedeği alındı (" . $this->formatBytes(filesize($dbDumpFile)) . ").";
                } else {
                    throw new Exception("Veritabanı yedeği alınamadı: " . $dbResult['error']);
                }
            }

            // 2. Fiziksel Dosyaları (uploads/ ve files/) Arşivle
            if (in_array($backupType, ['full', 'files'], true)) {
                $foldersToBackup = [
                    'uploads' => $this->rootDir . '/uploads',
                    'files' => $this->rootDir . '/files'
                ];

                $fileCount = 0;
                foreach ($foldersToBackup as $prefix => $dirPath) {
                    if (is_dir($dirPath)) {
                        $fileCount += $this->addFolderToZip($zip, $dirPath, "storage/{$prefix}");
                    }
                }
                $messages[] = "Fiziksel evrak ve dökümanlar arşivlendi ($fileCount adet dosya).";
            }

            // Meta bilgisi ekle
            $metaInfo = [
                'created_at' => date('Y-m-d H:i:s'),
                'backup_type' => $backupType,
                'php_version' => PHP_VERSION,
                'db_name' => defined('HOSTDATABASE') ? HOSTDATABASE : 'aydinogu_test',
                'system' => 'Aydınoğulları YSC Yedekleme Modülü'
            ];
            $zip->addFromString('backup_manifest.json', json_encode($metaInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $zip->close();

            $finalSize = file_exists($mainZipPath) ? filesize($mainZipPath) : 0;
            $sha256 = file_exists($mainZipPath) ? hash_file('sha256', $mainZipPath) : null;
            $duration = round(microtime(true) - $startTime, 2);

            // 3. Harici Depolamaya Aktar (FTP veya Google Drive)
            $remoteStatus = 'none';
            $remoteFileId = null;
            if (!empty($settings['backup_remote_enabled']) && $settings['backup_remote_enabled'] === '1') {
                $remoteUpload = $this->uploadToRemote($mainZipPath, $mainZipName, $settings);
                if ($remoteUpload['success']) {
                    $remoteStatus = 'uploaded';
                    $remoteFileId = $remoteUpload['file_id'] ?? null;
                    $targetName = (($settings['backup_remote_type'] ?? 'ftp') === 'gdrive') ? 'Google Drive' : ($settings['backup_remote_host'] ?? 'FTP');
                    $messages[] = "Harici depolamaya aktarıldı ({$targetName}).";
                } else {
                    $remoteStatus = 'failed';
                    $messages[] = "Harici aktarım hatası: " . $remoteUpload['error'];
                }
            } else {
                $remoteStatus = 'skipped';
            }

            // 4. E-Posta Bildirimi & DB Yedeği Eki
            if (!empty($settings['backup_send_mail']) && $settings['backup_send_mail'] === '1') {
                // E-posta eki olarak sadece DB dump veya küçük arşiv gönder
                $mailAttachment = null;
                if ($dbDumpFile && file_exists($dbDumpFile) && filesize($dbDumpFile) <= 20 * 1024 * 1024) {
                    // Sıkıştırılmış DB eki oluştur
                    $zippedDb = $this->backupDir . "/db_backup_{$timestamp}.zip";
                    $dbZip = new ZipArchive();
                    if ($dbZip->open($zippedDb, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                        $dbZip->addFile($dbDumpFile, "database_{$timestamp}.sql");
                        $dbZip->close();
                        $mailAttachment = $zippedDb;
                        $tempFiles[] = $zippedDb;
                    }
                } elseif ($finalSize <= 20 * 1024 * 1024) {
                    $mailAttachment = $mainZipPath;
                }

                $emailResult = $this->sendEmailReport([
                    'backup_type' => $backupType,
                    'file_name' => $mainZipName,
                    'file_size_formatted' => $this->formatBytes($finalSize),
                    'duration' => $duration,
                    'sha256' => $sha256,
                    'remote_status' => $remoteStatus,
                    'messages' => $messages
                ], $mailAttachment, $settings);

                if ($emailResult['success']) {
                    $mailStatus = 'sent';
                    $messages[] = "Yedek bildirim e-postası başarıyla gönderildi.";
                } else {
                    $mailStatus = 'failed';
                    $messages[] = "E-posta gönderim hatası: " . $emailResult['error'];
                }
            } else {
                $mailStatus = 'skipped';
            }

            // 5. Eski Yedeklerin Otomatik Temizliği (Retention Policy)
            $retentionDays = (int)($settings['backup_retention_days'] ?? 30);
            $deletedFilesCount = $this->cleanOldBackups($retentionDays);
            if ($deletedFilesCount > 0) {
                $messages[] = "$deletedFilesCount adet $retentionDays günden eski yedek temizlendi.";
            }

            // Log Güncelle
            $this->backupModel->updateLog($logId, [
                'file_size' => $finalSize,
                'remote_file_id' => $remoteFileId,
                'status' => 'success',
                'remote_status' => $remoteStatus,
                'mail_status' => $mailStatus,
                'duration_sec' => $duration,
                'sha256_hash' => $sha256,
                'message' => implode(' | ', $messages)
            ]);

            return [
                'success' => true,
                'log_id' => $logId,
                'file_name' => $mainZipName,
                'file_size' => $finalSize,
                'file_size_formatted' => $this->formatBytes($finalSize),
                'duration_sec' => $duration,
                'sha256' => $sha256,
                'remote_status' => $remoteStatus,
                'mail_status' => $mailStatus,
                'messages' => $messages
            ];

        } catch (\Throwable $e) {
            $duration = round(microtime(true) - $startTime, 2);
            $this->backupModel->updateLog($logId, [
                'status' => 'failed',
                'remote_status' => $remoteStatus,
                'mail_status' => $mailStatus,
                'duration_sec' => $duration,
                'message' => 'HATA: ' . $e->getMessage()
            ]);

            // Hata durumunda da e-posta uyarısı gönder
            if (!empty($settings['backup_send_mail']) && $settings['backup_send_mail'] === '1') {
                $this->sendEmailReport([
                    'backup_type' => $backupType,
                    'file_name' => $mainZipName,
                    'file_size_formatted' => '0 B',
                    'duration' => $duration,
                    'sha256' => '-',
                    'remote_status' => 'failed',
                    'messages' => ['Yedekleme sırasında KRİTİK HATA oluştu: ' . $e->getMessage()]
                ], null, $settings, true);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration_sec' => $duration
            ];
        } finally {
            // Geçici tekil sql dosyalarını temizle (ana zip içinde saklanıyor)
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }
    }

    /**
     * Veritabanını SQL formatında dışa aktarır (Önce mysqldump dener, başarısızsa PDO Fallback kullanır)
     */
    public function exportDatabase(string $outputSqlFile): array
    {
        $dbName = defined('HOSTDATABASE') ? HOSTDATABASE : 'aydinogu_test';
        $dbUser = defined('HOSTUSERNAME') ? HOSTUSERNAME : 'root';
        $dbPass = defined('HOSTPASSWORD') ? HOSTPASSWORD : '';
        $dbHost = defined('HOSTNAME') ? HOSTNAME : 'localhost';

        $disabledFunctions = array_map('trim', explode(',', (string)ini_get('disable_functions')));

        // 1. mysqldump dene (sadece exec ve escapeshell fonksiyonları tamamen etkinse)
        $canExec = function_exists('exec') && !in_array('exec', $disabledFunctions, true);
        $canEscape = function_exists('escapeshellcmd') && function_exists('escapeshellarg') && 
                     !in_array('escapeshellcmd', $disabledFunctions, true) && 
                     !in_array('escapeshellarg', $disabledFunctions, true);

        if ($canExec && $canEscape) {
            $mysqldumpPath = '/opt/lampp/bin/mysqldump';
            if (!file_exists($mysqldumpPath)) {
                $mysqldumpPath = 'mysqldump';
            }

            $passParam = !empty($dbPass) ? "--password=" . \escapeshellarg($dbPass) : "";
            $cmd = sprintf(
                "%s --host=%s --user=%s %s --routines --triggers --single-transaction --quick %s > %s 2>&1",
                \escapeshellcmd($mysqldumpPath),
                \escapeshellarg($dbHost),
                \escapeshellarg($dbUser),
                $passParam,
                \escapeshellarg($dbName),
                \escapeshellarg($outputSqlFile)
            );

            @exec($cmd, $output, $returnCode);

            if (isset($returnCode) && $returnCode === 0 && file_exists($outputSqlFile) && filesize($outputSqlFile) > 100) {
                return ['success' => true, 'method' => 'mysqldump'];
            }
        }

        // 2. Fallback: Saf PHP PDO ile DDL ve DML export (Tüm hosting ortamlarında %100 sorunsuz çalışır)
        return $this->exportDatabasePhp($outputSqlFile);
    }

    /**
     * Saf PHP ve PDO ile tüm tabloları SQL olarak dışa aktarır
     */
    protected function exportDatabasePhp(string $outputSqlFile): array
    {
        try {
            if (!is_dir(dirname($outputSqlFile))) {
                @mkdir(dirname($outputSqlFile), 0777, true);
            }
            @touch($outputSqlFile);
            @chmod($outputSqlFile, 0666);

            $handle = @fopen($outputSqlFile, 'w');
            if (!$handle) {
                return ['success' => false, 'error' => "SQL dosyası açılamadı: $outputSqlFile (Yazma iznini kontrol edin)"];
            }

            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- Aydınoğulları YSC Veritabanı Yedeği (PHP PDO Engine)\n");
            fwrite($handle, "-- Tarih: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- --------------------------------------------------------\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET NAMES utf8mb4;\n\n");

            // Tabloları getir
            $tablesStmt = $this->db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = $tablesStmt->fetchAll(PDO::FETCH_NUM);

            foreach ($tables as $tableRow) {
                $tableName = $tableRow[0];

                // CREATE TABLE DDL
                fwrite($handle, "--\n-- Tablo yapısı: `$tableName`\n--\n");
                fwrite($handle, "DROP TABLE IF EXISTS `$tableName`;\n");

                $createStmt = $this->db->query("SHOW CREATE TABLE `$tableName`");
                $createRow = $createStmt->fetch(PDO::FETCH_NUM);
                fwrite($handle, $createRow[1] . ";\n\n");

                // DATA DUMP (DML)
                fwrite($handle, "--\n-- Tablo verileri: `$tableName`\n--\n");

                $countStmt = $this->db->query("SELECT COUNT(*) FROM `$tableName`");
                $totalRows = (int)$countStmt->fetchColumn();

                if ($totalRows > 0) {
                    $chunkSize = 500;
                    $offset = 0;

                    while ($offset < $totalRows) {
                        $dataStmt = $this->db->query("SELECT * FROM `$tableName` LIMIT $offset, $chunkSize");
                        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($rows)) {
                            $columnNames = array_keys($rows[0]);
                            $escapedColumns = array_map(fn($col) => "`$col`", $columnNames);

                            fwrite($handle, "INSERT INTO `$tableName` (" . implode(', ', $escapedColumns) . ") VALUES \n");

                            $insertRows = [];
                            foreach ($rows as $row) {
                                $escapedValues = [];
                                foreach ($row as $val) {
                                    if ($val === null) {
                                        $escapedValues[] = "NULL";
                                    } elseif (is_numeric($val) && !is_string($val)) {
                                        $escapedValues[] = $val;
                                    } else {
                                        $escapedValues[] = $this->db->quote($val);
                                    }
                                }
                                $insertRows[] = "(" . implode(', ', $escapedValues) . ")";
                            }

                            fwrite($handle, implode(",\n", $insertRows) . ";\n\n");
                        }

                        $offset += $chunkSize;
                    }
                }
            }

            // Görünümleri (VIEW) getir ve dışa aktar
            $viewsStmt = $this->db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
            $views = $viewsStmt ? $viewsStmt->fetchAll(PDO::FETCH_NUM) : [];

            if (!empty($views)) {
                fwrite($handle, "--\n-- Görünümler (VIEW Yapıları)\n--\n\n");
                foreach ($views as $viewRow) {
                    $viewName = $viewRow[0];

                    fwrite($handle, "--\n-- Görünüm yapısı: `$viewName`\n--\n");
                    fwrite($handle, "DROP VIEW IF EXISTS `$viewName`;\n");

                    $createStmt = $this->db->query("SHOW CREATE VIEW `$viewName`");
                    $createRow = $createStmt ? $createStmt->fetch(PDO::FETCH_NUM) : null;
                    if (!empty($createRow[1])) {
                        // Farklı sunucu/kullanıcılara taşınabilir olması için DEFINER temizlenir
                        $cleanViewSql = preg_replace('/DEFINER=\S+/', '', $createRow[1]);
                        $cleanViewSql = preg_replace('/CREATE ALGORITHM=\S+/', 'CREATE OR REPLACE', $cleanViewSql);
                        fwrite($handle, $cleanViewSql . ";\n\n");
                    }
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            return ['success' => true, 'method' => 'pdo_fallback'];
        } catch (Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Belirtilen klasörü yinelemeli olarak zip arşivine ekler
     */
    protected function addFolderToZip(ZipArchive $zip, string $folderPath, string $zipPath): int
    {
        $fileCount = 0;
        $folderPath = rtrim($folderPath, '/\\');
        
        if (!is_dir($folderPath)) {
            return 0;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($folderPath) + 1);
            $targetZipPath = $zipPath . '/' . str_replace('\\', '/', $relativePath);

            if ($file->isDir()) {
                $zip->addEmptyDir($targetZipPath);
            } elseif ($file->isFile()) {
                // Geçici/kilit dosyalarını atla
                if ($file->getExtension() === 'tmp' || str_starts_with($file->getFilename(), '.DS_Store')) {
                    continue;
                }
                $zip->addFile($filePath, $targetZipPath);
                $fileCount++;
            }
        }

        return $fileCount;
    }

    /**
     * Harici Depolama Sunucusuna Yükler (Google Drive veya FTP/SFTP)
     */
    public function uploadToRemote(string $localFilePath, string $remoteFileName, array $settings): array
    {
        $remoteType = $settings['backup_remote_type'] ?? 'ftp';

        if ($remoteType === 'gdrive') {
            return $this->uploadToGoogleDrive($localFilePath, $remoteFileName, $settings);
        }

        return $this->uploadToFtp($localFilePath, $remoteFileName, $settings);
    }

    /**
     * Google Drive API erişim belirtecini (Access Token) çözer ve döndürür
     */
    public function resolveGoogleAccessToken(array $settings): array
    {
        $refreshToken = trim($settings['backup_gdrive_refresh_token'] ?? '');
        $jsonKeyContent = trim($settings['backup_gdrive_service_account_json'] ?? '');

        if (!empty($refreshToken)) {
            return $this->getGoogleDriveOAuthTokenFromRefresh($settings);
        }

        if (!empty($settings['backup_gdrive_client_id'])) {
            return [
                'success' => false,
                'error' => 'Google Client ID kaydedilmiş ancak henüz "Google Drive ile Bağlan & Yetkilendir" butonuna tıklanarak izin verilmemiş. Lütfen Ayarlar sekmesinden Google hesabınızı bağlayınız.'
            ];
        }

        if (!empty($jsonKeyContent)) {
            $serviceAccount = json_decode($jsonKeyContent, true);
            if (!is_array($serviceAccount) || empty($serviceAccount['client_email']) || empty($serviceAccount['private_key'])) {
                return ['success' => false, 'error' => 'Geçersiz Google Service Account JSON içeriği.'];
            }
            return $this->getGoogleDriveAccessToken($serviceAccount);
        }

        return ['success' => false, 'error' => 'Google Drive bağlantısı kurulmamış. Lütfen Ayarlar sekmesinden Google Drive ile bağlanınız.'];
    }

    /**
     * Google Drive API (v3) ile OAuth 2.0 veya Hizmet Hesabı üzerinden dosya yükler
     */
    public function uploadToGoogleDrive(string $localFilePath, string $remoteFileName, array $settings): array
    {
        $folderId = trim($settings['backup_gdrive_folder_id'] ?? '');

        // 1. Google OAuth2 Access Token Al
        $tokenRes = $this->resolveGoogleAccessToken($settings);
        if (!$tokenRes['success']) {
            return $tokenRes;
        }
        $accessToken = $tokenRes['access_token'];

        $fileSize = filesize($localFilePath);

        // 2. Resumable Upload Oturumu Başlat
        $metadata = [
            'name' => $remoteFileName
        ];
        if (!empty($folderId)) {
            $metadata['parents'] = [$folderId];
        }

        $initUrl = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true';
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; charset=UTF-8',
            'X-Upload-Content-Type: application/zip',
            'X-Upload-Content-Length: ' . $fileSize
        ];

        $ch = curl_init($initUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($metadata));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "Google Drive yükleme oturumu başlatılamadı (HTTP $httpCode): " . $responseBody];
        }

        // Location header'ı al
        $uploadUrl = null;
        if (preg_match('/^location:\s*(.+)$/im', $responseHeaders, $matches)) {
            $uploadUrl = trim($matches[1]);
        }

        if (empty($uploadUrl)) {
            return ['success' => false, 'error' => 'Google Drive Resumable Upload URL alınamadı.'];
        }

        // 3. Dosyayı Resumable URL'ye Akıt
        $fileHandle = fopen($localFilePath, 'rb');
        if (!$fileHandle) {
            return ['success' => false, 'error' => 'Yedek dosyası okunamadı: ' . $localFilePath];
        }

        $putHeaders = [
            'Content-Length: ' . $fileSize,
            'Content-Type: application/zip'
        ];

        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_INFILE, $fileHandle);
        curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);
        curl_setopt($ch, CURLOPT_UPLOAD, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $putHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 dakika
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $uploadResponse = curl_exec($ch);
        $uploadHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fileHandle);

        if ($uploadHttpCode === 200 || $uploadHttpCode === 201) {
            $resultData = json_decode($uploadResponse, true);
            return [
                'success' => true,
                'file_id' => $resultData['id'] ?? '',
                'service' => 'Google Drive'
            ];
        }

        return [
            'success' => false,
            'error' => "Google Drive dosyayı yazarken hata verdi (HTTP $uploadHttpCode): " . $uploadResponse
        ];
    }

    /**
     * Google OAuth 2.0 Refresh Token ile güncel Access Token alır
     */
    public function getGoogleDriveOAuthTokenFromRefresh(array $settings): array
    {
        $clientId = trim($settings['backup_gdrive_client_id'] ?? '');
        $clientSecret = trim($settings['backup_gdrive_client_secret'] ?? '');
        $refreshToken = trim($settings['backup_gdrive_refresh_token'] ?? '');

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            return ['success' => false, 'error' => 'Google OAuth2 bilgileri veya Refresh Token eksik. Lütfen Google ile Yetkilendirme yapınız.'];
        }

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "Google OAuth Token yenilenemedi (HTTP $httpCode): " . $response];
        }

        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            return ['success' => false, 'error' => 'Google Token cevabında access_token bulunamadı.'];
        }

        return ['success' => true, 'access_token' => $data['access_token']];
    }

    /**
     * Google Service Account JSON'ından JWT imzası ile Access Token üretir
     */
    protected function getGoogleDriveAccessToken(array $serviceAccount): array
    {
        $now = time();
        $jwtHeader = ['alg' => 'RS256', 'typ' => 'JWT'];
        $jwtClaim = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($jwtHeader));
        $encodedClaim = $this->base64UrlEncode(json_encode($jwtClaim));
        $toSign = $encodedHeader . '.' . $encodedClaim;

        $privateKey = $serviceAccount['private_key'];
        $signature = '';
        $success = openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$success) {
            return ['success' => false, 'error' => 'Google JWT imzası oluşturulamadı. Özel anahtar (private_key) biçimini kontrol edin.'];
        }

        $jwt = $toSign . '.' . $this->base64UrlEncode($signature);

        // OAuth token isteği
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "Google Token alınamadı (HTTP $httpCode): " . $response];
        }

        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            return ['success' => false, 'error' => 'Google Token cevabında access_token bulunamadı.'];
        }

        return ['success' => true, 'access_token' => $data['access_token']];
    }

    /**
     * URL-Safe Base64 Encode
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Harici FTP Sunucusuna Yükler
     */
    public function uploadToFtp(string $localFilePath, string $remoteFileName, array $settings): array
    {
        $host = $settings['backup_remote_host'] ?? '';
        $port = (int)($settings['backup_remote_port'] ?? 21);
        $user = $settings['backup_remote_user'] ?? '';
        $pass = $settings['backup_remote_pass'] ?? '';
        $path = rtrim($settings['backup_remote_path'] ?? '/backups', '/');

        if (empty($host) || empty($user)) {
            return ['success' => false, 'error' => 'Harici FTP sunucu bilgileri eksik.'];
        }

        if (!function_exists('ftp_connect')) {
            return ['success' => false, 'error' => 'PHP FTP eklentisi yüklü değil.'];
        }

        $conn = @ftp_connect($host, $port, 30);
        if (!$conn) {
            return ['success' => false, 'error' => "FTP sunucusuna bağlanılamadı: $host:$port"];
        }

        try {
            if (!@ftp_login($conn, $user, $pass)) {
                return ['success' => false, 'error' => "FTP giriş başarısız (Kullanıcı: $user)."];
            }

            @ftp_pasv($conn, true);

            // Uzak dizin yoksa oluşturmayı dene
            if (!empty($path)) {
                @ftp_mkdir($conn, $path);
                @ftp_chdir($conn, $path);
            }

            $remoteTarget = !empty($path) ? $path . '/' . $remoteFileName : $remoteFileName;
            
            if (!@ftp_put($conn, $remoteFileName, $localFilePath, FTP_BINARY)) {
                return ['success' => false, 'error' => "Dosya FTP sunucusuna yazılamadı ($remoteTarget)."];
            }

            return ['success' => true];
        } finally {
            @ftp_close($conn);
        }
    }

    /**
     * Harici Depolamadaki (Google Drive veya FTP) yedek dosyasını siler
     */
    public function deleteFromRemote(array $log, array $settings): array
    {
        $remoteType = $settings['backup_remote_type'] ?? 'ftp';
        if ($remoteType === 'gdrive') {
            return $this->deleteFromGoogleDrive($log['remote_file_id'] ?? '', $log['file_name'] ?? '', $settings);
        }
        return $this->deleteFromFtp($log['file_name'] ?? '', $settings);
    }

    /**
     * Google Drive API ile belirtilen dosyayı siler
     */
    public function deleteFromGoogleDrive(string $fileId, string $fileName, array $settings): array
    {
        $accessToken = $this->resolveGoogleAccessToken($settings);
        if (!$accessToken['success']) {
            return $accessToken;
        }
        $token = $accessToken['access_token'];

        // Eğer fileId yoksa, dosya adından klasör içinde ara
        if (empty($fileId) && !empty($fileName)) {
            $folderId = trim($settings['backup_gdrive_folder_id'] ?? '');
            $q = "name = '" . addslashes($fileName) . "' and trashed = false";
            if (!empty($folderId)) {
                $q .= " and '" . addslashes($folderId) . "' in parents";
            }
            $searchUrl = 'https://www.googleapis.com/drive/v3/files?q=' . urlencode($q) . '&fields=files(id,name)';
            $ch = curl_init($searchUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $resp = curl_exec($ch);
            curl_close($ch);
            $resData = json_decode($resp, true);
            if (!empty($resData['files'][0]['id'])) {
                $fileId = $resData['files'][0]['id'];
            }
        }

        if (empty($fileId)) {
            return ['success' => true, 'message' => 'Dosya zaten Google Drive üzerinde bulunamadı.'];
        }

        // 1. Google Drive Çöp Kutusuna Taşı (trashed: true) - 30 gün kurtarma güvencesi
        $trashUrl = 'https://www.googleapis.com/drive/v3/files/' . urlencode($fileId);
        $ch = curl_init($trashUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['trashed' => true]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=UTF-8'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $trashResp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 204 || $httpCode === 404) {
            return ['success' => true, 'message' => 'Google Drive üzerindeki yedek dosyası çöp kutusuna taşındı (30 gün boyunca kurtarılabilir).'];
        }

        // 2. Fallback: Kalıcı DELETE isteği
        $ch = curl_init($trashUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $delResp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 204 || $httpCode === 200 || $httpCode === 404) {
            return ['success' => true, 'message' => 'Google Drive üzerindeki yedek dosyası silindi.'];
        }

        return ['success' => false, 'error' => "Google Drive silme hatası (HTTP $httpCode): " . $delResp];
    }

    /**
     * Harici FTP sunucusundaki dosyayı siler
     */
    public function deleteFromFtp(string $fileName, array $settings): array
    {
        $host = $settings['backup_remote_host'] ?? '';
        $port = (int)($settings['backup_remote_port'] ?? 21);
        $user = $settings['backup_remote_user'] ?? '';
        $pass = $settings['backup_remote_pass'] ?? '';
        $path = rtrim($settings['backup_remote_path'] ?? '/backups', '/');

        if (empty($host) || empty($user) || !function_exists('ftp_connect')) {
            return ['success' => false, 'error' => 'FTP sunucu bilgisi eksik.'];
        }

        $conn = @ftp_connect($host, $port, 15);
        if (!$conn || !@ftp_login($conn, $user, $pass)) {
            if ($conn) @ftp_close($conn);
            return ['success' => false, 'error' => 'FTP bağlantısı kurulamadı.'];
        }

        @ftp_pasv($conn, true);
        $remoteFile = !empty($path) ? $path . '/' . $fileName : $fileName;
        $res = @ftp_delete($conn, $remoteFile);
        @ftp_close($conn);

        return ['success' => $res, 'message' => $res ? 'FTP dosyası silindi.' : 'FTP dosyası silinemedi veya bulunamadı.'];
    }

    /**
     * Google Drive'daki hedef klasör ile sistem kayıtlarını çift yönlü senkronize eder
     */
    public function syncGoogleDriveBackups(array $settings): array
    {
        $accessToken = $this->resolveGoogleAccessToken($settings);
        if (!$accessToken['success']) {
            return $accessToken;
        }
        $token = $accessToken['access_token'];
        $folderId = trim($settings['backup_gdrive_folder_id'] ?? '');

        // 1. Google Drive klasöründeki mevcut (çöp kutusunda olmayan) tüm dosyaları getir
        $q = "trashed = false";
        if (!empty($folderId)) {
            $q .= " and '" . addslashes($folderId) . "' in parents";
        }
        $listUrl = 'https://www.googleapis.com/drive/v3/files?q=' . urlencode($q) . '&pageSize=100&fields=files(id,name,size,createdTime,trashed)';
        
        $ch = curl_init($listUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "Google Drive dosyaları listelenemedi (HTTP $httpCode): " . $resp];
        }

        $data = json_decode($resp, true);
        $driveFiles = $data['files'] ?? [];
        
        $driveFilesByName = [];
        $driveFilesById = [];
        foreach ($driveFiles as $df) {
            $driveFilesByName[$df['name']] = $df;
            $driveFilesById[$df['id']] = $df;
        }

        // 2. Sistemde remote_status = 'uploaded' olan tüm kayıtları kontrol et
        $uploadedLogs = $this->backupModel->getUploadedLogs();
        $updatedMissing = 0;
        $activeSynced = 0;
        $details = [];

        foreach ($uploadedLogs as $log) {
            $existsInDrive = false;
            $matchedDriveId = null;

            // Önce remote_file_id ile kontrol et
            if (!empty($log['remote_file_id']) && isset($driveFilesById[$log['remote_file_id']])) {
                $existsInDrive = true;
                $matchedDriveId = $log['remote_file_id'];
            } elseif (isset($driveFilesByName[$log['file_name']])) {
                // Dosya adıyla eşleşti
                $existsInDrive = true;
                $matchedDriveId = $driveFilesByName[$log['file_name']]['id'];
                // ID eksikse veritabanını güncelle
                if (empty($log['remote_file_id'])) {
                    $this->backupModel->updateLog((int)$log['id'], ['remote_file_id' => $matchedDriveId]);
                }
            }

            if (!$existsInDrive) {
                // Google Drive'dan silinmiş -> Yerel diskten ve sistemden tamamen kaldır
                if (!empty($log['file_path'])) {
                    $baseDir = dirname(__DIR__, 2);
                    $localPath = realpath($baseDir . '/' . ltrim($log['file_path'], '/'));
                    $allowedDir = realpath($baseDir . '/backups');
                    if ($localPath && $allowedDir && str_starts_with($localPath, $allowedDir) && file_exists($localPath)) {
                        @unlink($localPath);
                    }
                }

                $this->backupModel->deleteLog((int)$log['id']);

                if (function_exists('audit_log')) {
                    audit_log("delete", "backup_sync", "Google Drive senkronizasyonu: Drive'dan silindiği için sistemden kaldırıldı: " . $log['file_name'], "backup_logs", (string)$log['id']);
                }

                $updatedMissing++;
                $details[] = "<strong>{$log['file_name']}</strong> Google Drive'da bulunamadığı için sistemden ve diskten tamamen silindi.";
            } else {
                $activeSynced++;
            }
        }

        return [
            'success' => true,
            'total_drive_files' => count($driveFiles),
            'synced_count' => $activeSynced,
            'missing_count' => $updatedMissing,
            'details' => $details,
            'message' => "Google Drive senkronizasyonu tamamlandı. {$activeSynced} yedek doğrulandı, {$updatedMissing} silinmiş dosya sistemden temizlendi."
        ];
    }

    /**
     * PHPMailer ile HTML Durum Raporu ve Yedek Eki Gönderir
     */
    public function sendEmailReport(array $data, ?string $attachmentPath, array $settings, bool $isError = false): array
    {
        $toEmail = $settings['backup_notification_email'] ?? '';
        if (empty($toEmail)) {
            $toEmail = $settings['mail_username'] ?? '';
        }

        if (empty($toEmail)) {
            return ['success' => false, 'error' => 'Alıcı e-posta adresi tanımlı değil.'];
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->SMTPDebug = 0;
            $mail->SMTPAuth = true;
            $mail->Timeout = 8;
            $mail->Host = $settings['mail_host'] ?? '';
            $mail->Port = (int)($settings['mail_port'] ?? 587);
            $mail->Username = $settings['mail_username'] ?? '';
            $mail->Password = $settings['mail_password'] ?? '';
            
            if ($mail->Port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($mail->Port === 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->CharSet = 'UTF-8';
            $mail->setLanguage('tr');
            $mail->isHTML(true);

            $fromEmail = !empty($settings['mail_from']) ? $settings['mail_from'] : $settings['mail_username'];
            $fromName = !empty($settings['mail_name']) ? $settings['mail_name'] : 'Aydınoğulları YSC Sistem';
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail);

            $statusBadge = $isError 
                ? '<span style="color:#d9534f;font-weight:bold;">🚨 BAŞARISIZ / HATA</span>' 
                : '<span style="color:#5cb85c;font-weight:bold;">✅ BAŞARILI</span>';

            $subject = ($isError ? '[KRİTİK HATA] ' : '[BAŞARILI] ') . "Sistem Yedeği Bildirimi - " . date('d.m.Y H:i');
            $mail->Subject = $subject;

            $messageListHtml = '';
            foreach ($data['messages'] ?? [] as $msg) {
                $messageListHtml .= "<li style='margin-bottom:4px;'>" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "</li>";
            }

            $body = "
            <div style='font-family: Arial, sans-serif; background:#f4f6f9; padding:20px; color:#333;'>
                <div style='max-width:650px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e1e8ed;'>
                    <div style='background:#1b3b6f; color:#ffffff; padding:18px 24px;'>
                        <h2 style='margin:0; font-size:20px;'>Aydınoğulları YSC - Otomatik Yedekleme Raporu</h2>
                        <p style='margin:5px 0 0 0; opacity:0.85; font-size:13px;'>Tarih: " . date('d.m.Y H:i:s') . "</p>
                    </div>
                    <div style='padding:24px;'>
                        <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:15px; margin-bottom:20px;'>
                            <table style='width:100%; border-collapse:collapse; font-size:14px;'>
                                <tr>
                                    <td style='padding:6px 0; color:#64748b;'>İşlem Durumu:</td>
                                    <td style='padding:6px 0; text-align:right;'>{$statusBadge}</td>
                                </tr>
                                <tr>
                                    <td style='padding:6px 0; color:#64748b;'>Yedek Dosyası:</td>
                                    <td style='padding:6px 0; text-align:right; font-weight:bold; font-family:monospace;'>" . htmlspecialchars($data['file_name'] ?? '-', ENT_QUOTES, 'UTF-8') . "</td>
                                </tr>
                                <tr>
                                    <td style='padding:6px 0; color:#64748b;'>Dosya Boyutu:</td>
                                    <td style='padding:6px 0; text-align:right; font-weight:bold;'>" . htmlspecialchars($data['file_size_formatted'] ?? '-', ENT_QUOTES, 'UTF-8') . "</td>
                                </tr>
                                <tr>
                                    <td style='padding:6px 0; color:#64748b;'>İşlem Süresi:</td>
                                    <td style='padding:6px 0; text-align:right;'>" . htmlspecialchars($data['duration'] ?? '-', ENT_QUOTES, 'UTF-8') . " sn</td>
                                </tr>
                                <tr>
                                    <td style='padding:6px 0; color:#64748b;'>Harici Depolama:</td>
                                    <td style='padding:6px 0; text-align:right;'>" . htmlspecialchars($data['remote_status'] ?? '-', ENT_QUOTES, 'UTF-8') . "</td>
                                </tr>
                                <tr>
                                    <td style='padding:6px 0; color:#64748b;'>SHA-256 Doğrulama:</td>
                                    <td style='padding:6px 0; text-align:right; font-size:11px; font-family:monospace; word-break:break-all;'>" . htmlspecialchars(substr($data['sha256'] ?? '-', 0, 32) . '...', ENT_QUOTES, 'UTF-8') . "</td>
                                </tr>
                            </table>
                        </div>

                        <h4 style='color:#1e293b; margin:16px 0 8px 0;'>İşlem Detayları</h4>
                        <ul style='background:#f1f5f9; border-radius:6px; padding:12px 12px 12px 28px; margin:0; font-size:13px; color:#334155;'>
                            {$messageListHtml}
                        </ul>
                    </div>
                    <div style='background:#f8fafc; border-top:1px solid #e2e8f0; padding:12px 24px; text-align:center; font-size:12px; color:#94a3b8;'>
                        Bu e-posta sistem tarafından otomatik olarak oluşturulmuştur.
                    </div>
                </div>
            </div>";

            $mail->Body = $body;

            // Ek ekle
            if ($attachmentPath && file_exists($attachmentPath)) {
                $mail->addAttachment($attachmentPath, basename($attachmentPath));
            }

            $mail->send();
            return ['success' => true];
        } catch (MailException $e) {
            return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Eski yedekleri yerel diskten temizler
     */
    public function cleanOldBackups(int $retentionDays): int
    {
        if ($retentionDays <= 0) {
            return 0;
        }

        $deletedCount = 0;
        $expiryTime = time() - ($retentionDays * 86400);

        $files = glob($this->backupDir . '/backup_*.zip');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $expiryTime) {
                    if (@unlink($file)) {
                        $deletedCount++;
                    }
                }
            }
        }

        // DB loglarını da temizle
        $this->backupModel->cleanOldLogs($retentionDays);

        return $deletedCount;
    }

    /**
     * Bayt değerini okunabilir birime çevirir
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
