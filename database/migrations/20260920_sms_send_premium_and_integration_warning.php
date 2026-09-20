<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Model\VersionNoteModel;

echo "SMS Gönderim Sayfası Premium Tema & Entegrasyon Kontrolü migration başlatılıyor...\n";

try {
    $versionModel = new VersionNoteModel();
    $versionModel->addVersionNote([
        'title' => 'SMS Gönderim Sayfası Premium Tema & Entegrasyon Kontrolü',
        'version_tag' => 'v2.4.5',
        'category' => 'improvement',
        'description' => "- SMS Gönderim sayfası (`send-sms.php`) premium-theme standartlarına uygun olarak modernleştirildi.\n- NetGSM SMS entegrasyon ayarları kontrolü eklendi; ayarlar eksik veya pasif olduğunda açıklayıcı uyarı kartı ve Panel Ayarlarına hızlı geçiş butonu sunuldu.\n- Dinamik karakter sayacı ve SMS parça hesaplayıcı eklendi.\n- Müşteri seçimi Select2 ile modernize edildi; hızlı seçim araçları ve arama özellikleri sağlandı.\n- SMS gönderim işlemleri NetGSM servisine bağlandı ve audit_log ile loglama entegre edildi.",
        'author' => 'Antigravity AI',
        'created_at' => '2026-09-20 22:05:00'
    ]);
    
    echo "Migration başarıyla tamamlandı.\n";
} catch (Exception $e) {
    echo "Hata oluştu: " . $e->getMessage() . "\n";
    exit(1);
}
