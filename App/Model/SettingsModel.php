<?php

namespace App\Model;

use PDO;
use Exception;

class SettingsModel extends BaseModel
{
    protected $table = 'settings';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Tüm ayarları anahtar => değer dizisi olarak getirir
     * 
     * @return array<string, string>
     */
    public function getAllSettings(): array
    {
        $stmt = $this->db->prepare("SELECT var, val FROM {$this->table}");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['var']] = $row['val'];
        }

        return $settings;
    }

    /**
     * Tekil bir ayar değerini getirir
     * 
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $var, $default = '')
    {
        $stmt = $this->db->prepare("SELECT val FROM {$this->table} WHERE var = ? LIMIT 1");
        $stmt->execute([$var]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row['val'] : $default;
    }

    /**
     * Birden fazla ayarı günceller
     * 
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateSettings(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE {$this->table} SET val = ? WHERE var = ?");
            foreach ($data as $var => $val) {
                $stmt->execute([$val, $var]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("SettingsModel::updateSettings error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tekil bir ayarı günceller
     * 
     * @param string $var
     * @param mixed $val
     * @return bool
     */
    public function updateSetting(string $var, $val): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET val = ? WHERE var = ?");
            return $stmt->execute([$val, $var]);
        } catch (Exception $e) {
            error_log("SettingsModel::updateSetting error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Şirket logosunu günceller
     * 
     * @param array $file $_FILES['logos']
     * @return array ['success' => bool, 'message' => string, 'path' => string]
     */
    public function updateLogo(array $file): array
    {
        if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Geçersiz dosya veya dosya yüklenmedi.', 'path' => ''];
        }

        $allowedExtensions = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExtensions)) {
            return [
                'success' => false,
                'message' => 'Geçersiz dosya formatı. Sadece PNG, JPG, JPEG, SVG ve WEBP formatları desteklenir.',
                'path' => ''
            ];
        }

        // 5 MB boyut sınırı
        if ($file['size'] > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'Logo dosyası en fazla 5MB olabilir.',
                'path' => ''
            ];
        }

        $targetDir = 'src/images/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $cleanBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
        $newFileName = 'logo_' . time() . '_' . $cleanBaseName . '.' . $fileExt;
        $targetPath = $targetDir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $oldLogo = $this->getSetting('logo');

            // Eski özel logoyu temizle (varsayılan değilse)
            if (!empty($oldLogo) && file_exists($oldLogo) && $oldLogo !== 'src/images/logo.png' && strpos($oldLogo, 'logo_') !== false) {
                @unlink($oldLogo);
            }

            $this->updateSetting('logo', $targetPath);

            return [
                'success' => true,
                'message' => 'Logo başarıyla güncellendi.',
                'path' => $targetPath
            ];
        }

        return [
            'success' => false,
            'message' => 'Logo sunucuya kaydedilirken bir hata oluştu.',
            'path' => ''
        ];
    }
}
