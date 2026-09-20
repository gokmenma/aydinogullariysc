<?php
use App\Model\VersionNoteModel;

$versionModel = new VersionNoteModel();
$notes = $versionModel->getNotes();
$stats = $versionModel->getStats();
$isAdmin = (sesset("perm") == 1);

// Kategori yardımcı fonksiyonları
function getCategoryInfo($cat, $title = '', $desc = '') {
    $cat = strtolower(trim((string)$cat));
    
    // Eski kayıtlarda category alanı boş veya default ise başlıktan tahmin et
    if (empty($cat) || $cat === 'feature') {
        $checkText = mb_strtolower($title . ' ' . $desc, 'UTF-8');
        if (strpos($checkText, 'hata') !== false || strpos($checkText, 'düzeltme') !== false || strpos($checkText, 'fix') !== false) {
            $cat = 'bugfix';
        } elseif (strpos($checkText, 'iyileştir') !== false || strpos($checkText, 'düzenleme') !== false || strpos($checkText, 'güncelle') !== false) {
            $cat = 'improvement';
        } elseif (strpos($checkText, 'güvenlik') !== false || strpos($checkText, 'yetki') !== false || strpos($checkText, 'şifre') !== false) {
            $cat = 'security';
        } else {
            $cat = 'feature';
        }
    }

    switch ($cat) {
        case 'bugfix':
            return [
                'name' => 'Hata Düzeltme',
                'class' => 'badge-vn-bugfix',
                'icon' => 'fa-wrench',
                'color' => '#f59e0b',
                'bg' => 'rgba(245, 158, 11, 0.12)',
                'border' => 'rgba(245, 158, 11, 0.3)'
            ];
        case 'improvement':
            return [
                'name' => 'İyileştirme',
                'class' => 'badge-vn-improvement',
                'icon' => 'fa-magic',
                'color' => '#3b82f6',
                'bg' => 'rgba(59, 130, 246, 0.12)',
                'border' => 'rgba(59, 130, 246, 0.3)'
            ];
        case 'security':
            return [
                'name' => 'Güvenlik',
                'class' => 'badge-vn-security',
                'icon' => 'fa-shield',
                'color' => '#ef4444',
                'bg' => 'rgba(239, 68, 68, 0.12)',
                'border' => 'rgba(239, 68, 68, 0.3)'
            ];
        case 'other':
            return [
                'name' => 'Genel',
                'class' => 'badge-vn-other',
                'icon' => 'fa-info-circle',
                'color' => '#64748b',
                'bg' => 'rgba(100, 116, 139, 0.12)',
                'border' => 'rgba(100, 116, 139, 0.3)'
            ];
        case 'feature':
        default:
            return [
                'name' => 'Yeni Özellik',
                'class' => 'badge-vn-feature',
                'icon' => 'fa-rocket',
                'color' => '#10b981',
                'bg' => 'rgba(16, 185, 129, 0.12)',
                'border' => 'rgba(16, 185, 129, 0.3)'
            ];
    }
}

function formatTurkishDate($dateStr) {
    if (empty($dateStr)) return '';
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8');
    
    $months = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    ];
    
    $day = date('j', $timestamp);
    $monthNum = (int)date('n', $timestamp);
    $year = date('Y', $timestamp);
    $time = date('H:i', $timestamp);
    
    $monthName = $months[$monthNum] ?? date('M', $timestamp);
    
    if (strpos($dateStr, ':') !== false) {
        return "{$day} {$monthName} {$year}, {$time}";
    }
    return "{$day} {$monthName} {$year}";
}

function renderDescription($text) {
    if (empty($text)) return '';
    
    // Normalization
    $text = str_replace(['\r\n', '\r'], "\n", $text);
    $lines = preg_split('/<br\s*\/?>|\n/i', $text);
    
    $output = [];
    $inList = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) continue;
        
        // Madde işareti kontrolü (-, *, •, veya 1.)
        if (preg_match('/^[-*•]\s+(.*)$/u', $trimmed, $matches)) {
            if (!$inList) {
                $output[] = '<ul class="vn-desc-list">';
                $inList = true;
            }
            $output[] = '<li><i class="fa fa-angle-right vn-bullet-icon"></i> ' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</li>';
        } else {
            if ($inList) {
                $output[] = '</ul>';
                $inList = false;
            }
            $output[] = '<p class="vn-desc-paragraph">' . htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }
    
    if ($inList) {
        $output[] = '</ul>';
    }
    
    return implode("\n", $output);
}
?>

<!-- Sürüm Notları Sayfası Özel Stilleri -->
<style>
/* ==================== Sürüm Notları Modern Tasarımı ==================== */
.vn-container {
    padding-bottom: 40px;
    font-family: inherit;
}

.vn-hero-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #ffffff;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.1);
    position: relative;
    overflow: hidden;
    margin-bottom: 25px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.vn-hero-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, rgba(0, 0, 0, 0) 70%);
    pointer-events: none;
    border-radius: 50%;
}

.vn-hero-title {
    font-size: 1.75rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin-bottom: 6px;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 12px;
}

.vn-hero-subtitle {
    color: #94a3b8;
    font-size: 0.95rem;
    margin-bottom: 20px;
    max-width: 650px;
    line-height: 1.5;
}

.vn-stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
}

.vn-stat-pill {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    padding: 8px 16px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #e2e8f0;
    font-size: 0.88rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.vn-stat-pill:hover {
    background: rgba(255, 255, 255, 0.14);
    transform: translateY(-2px);
}

.vn-stat-pill .vn-stat-count {
    background: #3b82f6;
    color: #fff;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 2px 8px;
    border-radius: 20px;
}

.vn-stat-pill.stat-feature .vn-stat-count { background: #10b981; }
.vn-stat-pill.stat-improvement .vn-stat-count { background: #3b82f6; }
.vn-stat-pill.stat-bugfix .vn-stat-count { background: #f59e0b; }
.vn-stat-pill.stat-security .vn-stat-count { background: #ef4444; }

/* Filtreleme ve Arama Çubuğu */
.vn-controls-bar {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    margin-bottom: 25px;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.vn-search-wrapper {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 400px;
}

.vn-search-input {
    width: 100%;
    padding: 9px 14px 9px 40px;
    font-size: 0.9rem;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background-color: #f8fafc;
    color: #1e293b;
    transition: all 0.2s ease;
}

.vn-search-input:focus {
    outline: none;
    border-color: #3b82f6;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.vn-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}

.vn-filter-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.vn-filter-btn {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    padding: 6px 14px;
    font-size: 0.85rem;
    font-weight: 500;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.vn-filter-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.vn-filter-btn.active {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
}

/* Timeline Yapısı */
.vn-timeline {
    position: relative;
    padding-left: 32px;
    margin-top: 10px;
}

.vn-timeline::before {
    content: '';
    position: absolute;
    top: 15px;
    bottom: 15px;
    left: 11px;
    width: 2px;
    background: linear-gradient(180deg, #3b82f6 0%, #cbd5e1 50%, #e2e8f0 100%);
    border-radius: 2px;
}

.vn-item {
    position: relative;
    margin-bottom: 28px;
    transition: transform 0.2s ease;
}

.vn-node {
    position: absolute;
    left: -32px;
    top: 22px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #ffffff;
    border: 3px solid #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    transition: all 0.2s ease;
}

.vn-item:hover .vn-node {
    transform: scale(1.15);
}

.vn-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    padding: 24px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}

.vn-card:hover {
    box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.07), 0 4px 8px -4px rgba(0, 0, 0, 0.04);
    border-color: #cbd5e1;
    transform: translateY(-2px);
}

.vn-card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}

.vn-meta-left {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.vn-version-pill {
    font-size: 0.82rem;
    font-weight: 700;
    background: #0f172a;
    color: #ffffff;
    padding: 4px 10px;
    border-radius: 8px;
    letter-spacing: 0.02em;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.vn-category-badge {
    font-size: 0.78rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: 1px solid transparent;
}

.badge-vn-feature {
    background-color: rgba(16, 185, 129, 0.12);
    color: #059669;
    border-color: rgba(16, 185, 129, 0.25);
}

.badge-vn-improvement {
    background-color: rgba(59, 130, 246, 0.12);
    color: #2563eb;
    border-color: rgba(59, 130, 246, 0.25);
}

.badge-vn-bugfix {
    background-color: rgba(245, 158, 11, 0.12);
    color: #d97706;
    border-color: rgba(245, 158, 11, 0.25);
}

.badge-vn-security {
    background-color: rgba(239, 68, 68, 0.12);
    color: #dc2626;
    border-color: rgba(239, 68, 68, 0.25);
}

.badge-vn-other {
    background-color: rgba(100, 116, 139, 0.12);
    color: #475569;
    border-color: rgba(100, 116, 139, 0.25);
}

.vn-date-text {
    font-size: 0.85rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

.vn-title {
    font-size: 1.18rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 12px;
    line-height: 1.4;
    letter-spacing: -0.01em;
}

.vn-body {
    color: #334155;
    font-size: 0.93rem;
    line-height: 1.65;
}

.vn-desc-paragraph {
    margin-bottom: 8px;
}

.vn-desc-paragraph:last-child {
    margin-bottom: 0;
}

.vn-desc-list {
    list-style: none;
    padding: 0;
    margin: 8px 0;
}

.vn-desc-list li {
    position: relative;
    padding-left: 20px;
    margin-bottom: 6px;
    color: #334155;
}

.vn-bullet-icon {
    position: absolute;
    left: 4px;
    top: 4px;
    color: #3b82f6;
    font-size: 0.85rem;
}

.vn-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px dashed #e2e8f0;
    font-size: 0.82rem;
    color: #64748b;
}

.vn-author-tag {
    display: flex;
    align-items: center;
    gap: 6px;
}

.vn-actions {
    display: flex;
    gap: 8px;
}

.vn-action-btn {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.78rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
}

.vn-action-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.vn-action-btn.btn-delete:hover {
    background: #fef2f2;
    color: #ef4444;
    border-color: #fecaca;
}

.vn-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #ffffff;
    border-radius: 16px;
    border: 1px dashed #cbd5e1;
}

.vn-empty-icon {
    font-size: 3rem;
    color: #94a3b8;
    margin-bottom: 16px;
}

/* ==================== Dark Mode Uyumlamaları ==================== */
body.dark-mode .vn-controls-bar {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

body.dark-mode .vn-search-input {
    background-color: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}

body.dark-mode .vn-search-input:focus {
    border-color: #3b82f6;
    background-color: #0f172a;
}

body.dark-mode .vn-filter-btn {
    background: #0f172a;
    border-color: #334155;
    color: #94a3b8;
}

body.dark-mode .vn-filter-btn:hover {
    background: #334155;
    color: #f8fafc;
}

body.dark-mode .vn-filter-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #ffffff;
}

body.dark-mode .vn-timeline::before {
    background: linear-gradient(180deg, #3b82f6 0%, #334155 50%, #1e293b 100%);
}

body.dark-mode .vn-node {
    background: #1e293b;
    border-color: #3b82f6;
}

body.dark-mode .vn-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

body.dark-mode .vn-card:hover {
    border-color: #475569;
}

body.dark-mode .vn-card-header {
    border-bottom-color: #334155;
}

body.dark-mode .vn-version-pill {
    background: #334155;
    color: #f8fafc;
}

body.dark-mode .vn-title {
    color: #f8fafc;
}

body.dark-mode .vn-body,
body.dark-mode .vn-desc-list li {
    color: #cbd5e1;
}

body.dark-mode .vn-date-text,
body.dark-mode .vn-card-footer {
    color: #94a3b8;
    border-top-color: #334155;
}

body.dark-mode .vn-action-btn {
    background: #0f172a;
    border-color: #334155;
    color: #94a3b8;
}

body.dark-mode .vn-action-btn:hover {
    background: #334155;
    color: #f8fafc;
}

body.dark-mode .vn-empty-state {
    background: #1e293b;
    border-color: #334155;
}
</style>

<div class="vn-container">
    <!-- Hero Banner & Stats -->
    <div class="vn-hero-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
                <h1 class="vn-hero-title">
                    <i class="fa fa-code-fork text-primary"></i> Sürüm Notları
                </h1>
                <p class="vn-hero-subtitle">
                    Aydınoğulları YSC sistemindeki en son güncellemeler, yeni özellikler, güvenlik geliştirmeleri ve performans iyileştirmeleri.
                </p>
            </div>
            <?php if ($isAdmin): ?>
            <div>
                <button type="button" class="btn btn-primary btn-sm px-3 py-2 font-weight-bold" style="border-radius: 10px;" onclick="openVersionModal();">
                    <i class="fa fa-plus-circle mr-1"></i> Yeni Sürüm Notu Ekle
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- İstatistik Hapları -->
        <div class="vn-stats-row">
            <div class="vn-stat-pill">
                <i class="fa fa-layer-group"></i> Toplam Güncelleme: <span class="vn-stat-count"><?php echo count($notes); ?></span>
            </div>
            <div class="vn-stat-pill stat-feature">
                <i class="fa fa-rocket text-success"></i> Yeni Özellikler: <span class="vn-stat-count"><?php echo $stats['feature']; ?></span>
            </div>
            <div class="vn-stat-pill stat-improvement">
                <i class="fa fa-magic text-info"></i> İyileştirmeler: <span class="vn-stat-count"><?php echo $stats['improvement']; ?></span>
            </div>
            <div class="vn-stat-pill stat-bugfix">
                <i class="fa fa-wrench text-warning"></i> Düzeltmeler: <span class="vn-stat-count"><?php echo $stats['bugfix']; ?></span>
            </div>
            <?php if ($stats['last_date']): ?>
            <div class="vn-stat-pill">
                <i class="fa fa-clock-o text-muted"></i> Son Güncelleme: <span class="text-white font-weight-bold ml-1"><?php echo formatTurkishDate($stats['last_date']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtreleme & Arama Çubuğu -->
    <div class="vn-controls-bar">
        <div class="vn-search-wrapper">
            <i class="fa fa-search vn-search-icon"></i>
            <input type="text" id="vnSearchInput" class="vn-search-input" placeholder="Sürüm notlarında ara (başlık, versiyon, metin)...">
        </div>

        <div class="vn-filter-pills" id="vnFilterGroup">
            <button type="button" class="vn-filter-btn active" data-category="all">
                <i class="fa fa-th-large"></i> Tümü
            </button>
            <button type="button" class="vn-filter-btn" data-category="feature">
                <i class="fa fa-rocket text-success"></i> Yeni Özellik
            </button>
            <button type="button" class="vn-filter-btn" data-category="improvement">
                <i class="fa fa-magic text-primary"></i> İyileştirme
            </button>
            <button type="button" class="vn-filter-btn" data-category="bugfix">
                <i class="fa fa-wrench text-warning"></i> Hata Düzeltme
            </button>
            <button type="button" class="vn-filter-btn" data-category="security">
                <i class="fa fa-shield text-danger"></i> Güvenlik
            </button>
        </div>
    </div>

    <!-- Timeline Listesi -->
    <div class="vn-timeline" id="vnTimelineList">
        <?php if (empty($notes)): ?>
            <div class="vn-empty-state">
                <div class="vn-empty-icon"><i class="fa fa-history"></i></div>
                <h5 class="text-muted">Henüz yayınlanmış bir sürüm notu bulunmuyor.</h5>
            </div>
        <?php else: ?>
            <?php foreach ($notes as $note): 
                $catInfo = getCategoryInfo($note->category ?? '', $note->title, $note->description);
                $versionTag = !empty($note->version_tag) ? $note->version_tag : null;
                $author = !empty($note->author) ? $note->author : 'Sistem Admin';
            ?>
                <div class="vn-item" data-category="<?php echo htmlspecialchars($note->category ?? 'feature', ENT_QUOTES, 'UTF-8'); ?>" data-id="<?php echo (int)$note->id; ?>">
                    <div class="vn-node" style="border-color: <?php echo $catInfo['color']; ?>;">
                        <i class="fa <?php echo $catInfo['icon']; ?>" style="color: <?php echo $catInfo['color']; ?>; font-size: 10px;"></i>
                    </div>

                    <div class="vn-card">
                        <div class="vn-card-header">
                            <div class="vn-meta-left">
                                <?php if ($versionTag): ?>
                                    <span class="vn-version-pill">
                                        <i class="fa fa-tag"></i> <?php echo htmlspecialchars($versionTag, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="vn-category-badge <?php echo $catInfo['class']; ?>">
                                    <i class="fa <?php echo $catInfo['icon']; ?>"></i> <?php echo htmlspecialchars($catInfo['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                            <div class="vn-date-text">
                                <i class="fa fa-calendar-o"></i> <?php echo formatTurkishDate($note->created_at); ?>
                            </div>
                        </div>

                        <h3 class="vn-title"><?php echo htmlspecialchars($note->title, ENT_QUOTES, 'UTF-8'); ?></h3>

                        <div class="vn-body">
                            <?php echo renderDescription($note->description); ?>
                        </div>

                        <div class="vn-card-footer">
                            <div class="vn-author-tag">
                                <i class="fa fa-user-circle-o text-muted"></i> 
                                <span><?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>

                            <?php if ($isAdmin): ?>
                            <div class="vn-actions">
                                <button type="button" class="vn-action-btn" title="Düzenle" onclick="editVersionNote(<?php echo (int)$note->id; ?>)">
                                    <i class="fa fa-pencil"></i> Düzenle
                                </button>
                                <button type="button" class="vn-action-btn btn-delete" title="Sil" onclick="deleteVersionNote(<?php echo (int)$note->id; ?>)">
                                    <i class="fa fa-trash-o"></i> Sil
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Arama Bulunamadı Durumu -->
    <div class="vn-empty-state d-none" id="vnSearchEmptyState">
        <div class="vn-empty-icon"><i class="fa fa-search"></i></div>
        <h5 class="font-weight-bold text-dark mb-1">Arama sonucu bulunamadı</h5>
        <p class="text-muted mb-0">Filtre kriterlerinize veya arama teriminize uygun sürüm notu bulunamadı.</p>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Sürüm Notu Ekleme / Düzenleme Modal -->
<div class="modal fade" id="versionNoteModal" tabindex="-1" role="dialog" aria-labelledby="versionNoteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <div class="modal-header bg-dark text-white" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <h5 class="modal-title text-white font-weight-bold" id="versionNoteModalTitle">
                    <i class="fa fa-code-fork mr-2 text-primary"></i> Sürüm Notu Ekle / Düzenle
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="versionNoteForm" onsubmit="saveVersionNote(event);">
                <input type="hidden" name="id" id="vn_id" value="">
                <input type="hidden" name="action" value="save">
                
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Başlık <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="vn_title" class="form-control" required placeholder="Örn: Servis Formları İyileştirmeleri">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Sürüm Etiketi</label>
                                <input type="text" name="version_tag" id="vn_version_tag" class="form-control" placeholder="Örn: v2.4.0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Kategori <span class="text-danger">*</span></label>
                                <select name="category" id="vn_category" class="form-control" required>
                                    <option value="feature">🚀 Yeni Özellik (Feature)</option>
                                    <option value="improvement">⚡ İyileştirme (Improvement)</option>
                                    <option value="bugfix">🛠️ Hata Düzeltme (Bug Fix)</option>
                                    <option value="security">🔒 Güvenlik (Security)</option>
                                    <option value="other">📋 Genel Güncelleme</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">Tarih</label>
                                <input type="text" name="created_at" id="vn_created_at" class="form-control" value="<?php echo date('Y-m-d H:i:s'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Açıklama / Değişiklik Maddeleri <span class="text-danger">*</span></label>
                        <textarea name="description" id="vn_description" class="form-control" rows="6" required placeholder="- Yapılan değişiklik 1&#10;- Yapılan değişiklik 2&#10;- İyileştirilen ekran bilgisi"></textarea>
                        <small class="form-text text-muted">Maddeli liste oluşturmak için satır başlarına tire (-) veya yıldız (*) koyabilirsiniz.</small>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-dismiss="modal">İptal</button>
                    <button type="submit" id="btnSaveVn" class="btn btn-primary px-4 font-weight-bold">
                        <i class="fa fa-check mr-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function() {
    // Flatpickr tarih seçici
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#vn_created_at", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            time_24hr: true,
            locale: "tr"
        });
    }

    // Arama ve Filtreleme
    var currentCategory = 'all';
    var currentSearch = '';

    function filterNotes() {
        var visibleCount = 0;
        $('.vn-item').each(function() {
            var item = $(this);
            var itemCat = (item.data('category') || '').toLowerCase();
            var itemText = item.text().toLowerCase();

            var matchesCat = (currentCategory === 'all' || itemCat === currentCategory);
            var matchesSearch = (currentSearch === '' || itemText.indexOf(currentSearch) !== -1);

            if (matchesCat && matchesSearch) {
                item.stop().fadeIn(150);
                visibleCount++;
            } else {
                item.stop().hide();
            }
        });

        if (visibleCount === 0) {
            $('#vnSearchEmptyState').removeClass('d-none').fadeIn(150);
        } else {
            $('#vnSearchEmptyState').addClass('d-none');
        }
    }

    $('#vnSearchInput').on('keyup input', function() {
        currentSearch = $(this).val().trim().toLowerCase();
        filterNotes();
    });

    $('.vn-filter-btn').on('click', function() {
        $('.vn-filter-btn').removeClass('active');
        $(this).addClass('active');
        currentCategory = $(this).data('category').toLowerCase();
        filterNotes();
    });
});

<?php if ($isAdmin): ?>
function openVersionModal() {
    $('#versionNoteForm')[0].reset();
    $('#vn_id').val('');
    $('#vn_created_at').val('<?php echo date('Y-m-d H:i:s'); ?>');
    $('#versionNoteModalTitle').html('<i class="fa fa-plus-circle mr-2 text-primary"></i> Yeni Sürüm Notu Ekle');
    $('#versionNoteModal').modal('show');
}

function editVersionNote(id) {
    $.ajax({
        url: 'App/api/version-notes.php',
        type: 'GET',
        data: { action: 'get', id: id },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success' && res.data) {
                var d = res.data;
                $('#vn_id').val(d.id);
                $('#vn_title').val(d.title);
                $('#vn_version_tag').val(d.version_tag || '');
                $('#vn_category').val(d.category || 'feature');
                $('#vn_created_at').val(d.created_at || '');
                $('#vn_description').val(d.description || '');
                
                $('#versionNoteModalTitle').html('<i class="fa fa-pencil mr-2 text-primary"></i> Sürüm Notunu Düzenle');
                $('#versionNoteModal').modal('show');
            } else {
                toastr.error(res.message || 'Kayıt verisi alınamadı.');
            }
        },
        error: function() {
            toastr.error('Sunucu ile iletişim kurulurken bir hata oluştu.');
        }
    });
}

function saveVersionNote(e) {
    e.preventDefault();
    var form = $('#versionNoteForm');
    var btn = $('#btnSaveVn');
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Kaydediliyor...');

    $.ajax({
        url: 'App/api/version-notes.php',
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Kaydet');
            if (res.status === 'success') {
                toastr.success(res.message);
                $('#versionNoteModal').modal('hide');
                setTimeout(function() {
                    window.location.reload();
                }, 800);
            } else {
                toastr.error(res.message || 'Kayıt sırasında bir hata oluştu.');
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i class="fa fa-check mr-1"></i> Kaydet');
            var errMsg = 'Kayıt sırasında bir hata oluştu.';
            try {
                var json = JSON.parse(xhr.responseText);
                if (json.message) errMsg = json.message;
            } catch(e) {}
            toastr.error(errMsg);
        }
    });
}

function deleteVersionNote(id) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu sürüm notunu silmek istediğinizden emin misiniz?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'App/api/version-notes.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        toastr.success(res.message);
                        $('.vn-item[data-id="' + id + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        toastr.error(res.message || 'Silme işlemi başarısız oldu.');
                    }
                },
                error: function() {
                    toastr.error('Sunucu hatası oluştu.');
                }
            });
        }
    });
}
<?php endif; ?>
</script>