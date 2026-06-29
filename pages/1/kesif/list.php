<?php
permcontrol('kesifView');


use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;
use App\Model\KesifModel;

$logger = \getLogger("kesif");
// Kesif listesi açıldığını loglama
$logger->info('Keşif listesi açıldı', [
    'user_id' => $_SESSION['lid'] ?? 0,
    'username' => $_SESSION['username'] ?? 'Guest'
]);

$kesifObj = new KesifModel();
$kesifler = $kesifObj->getAllActive();

// İstatistik bilgileri - Durumara göre sayımlar
$toplam_kesif = count($kesifler);
$bekleyen_kesif = 0;
$iptal_kesif = 0;
$teklif_gonderilen_kesif = 0;
$tamamlanan_kesif = 0;

foreach ($kesifler as $kesif) {
    if ($kesif->durum == 'bekliyor') {
        $bekleyen_kesif++;
    } elseif ($kesif->durum == 'iptal_edildi') {
        $iptal_kesif++;
    } elseif ($kesif->durum == 'teklif_gonderildi' || $kesif->durum == 'teklif_hazirlandi') {
        $teklif_gonderilen_kesif++;
    } elseif ($kesif->durum == 'kesif_tamamlandi') {
        $tamamlanan_kesif++;
    }
}

// Şu anki oturum açmış kullanıcı ID'si
$current_user_id = $_SESSION['user_id'] ?? 0;

$start_time = microtime(true);
?>

<style>
    /* Premium Modal Styles */

    .modal-xl {
        max-width: 1200px;
    }

    #kesifModal .modal-dialog-scrollable .modal-content {
        height: 95vh;
        border: none;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    #kesifModal .modal-dialog-scrollable .modal-body {
        overflow-y: auto !important;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 30px !important;
    }

    #kesifModal .modal-dialog-scrollable .modal-body::-webkit-scrollbar {
        display: none;
    }

    #kesifModal .modal-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 18px 30px;
    }

    #kesifModal .modal-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.2rem;
    }

    .form-section {
        margin-bottom: 24px;
        padding: 20px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .form-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f1f5f9;
    }

    .section-title i {
        color: #3b82f6;
        font-size: 1rem;
    }

    #kesifModal .form-group label {
        font-weight: 600;
        color: #475569;
        font-size: 0.82rem;
        margin-bottom: 6px;
    }

    #kesifModal .form-control {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 9px 14px;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    #kesifModal .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    textarea#kesif_sonu_notu {
        height: calc(100vh - 570px) !important;
    }

    /* Görsel Silme Butonu */
    .btn-delete-gorsel {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #ef4444;
        color: #fff;
        border: 2px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        cursor: pointer;
        z-index: 10;
        transition: all 0.2s;
        padding: 0;
        line-height: 1;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
    }

    .btn-delete-gorsel:hover {
        background: #dc2626;
        transform: scale(1.15);
    }

    .gorsel-item {
        border-radius: 10px;
        overflow: visible;
    }

    /* ==========================================
       DARK MODE ADJUSTMENTS
       ========================================== */
    .dark-mode #kesifModal .modal-content,
    .dark-mode #detaylarModal .modal-content {
        background-color: #2a2a2c;
        border: 1px solid #3e3e42;
    }

    .dark-mode #kesifModal .modal-header,
    .dark-mode #detaylarModal .modal-header {
        background: #202022;
        border-bottom: 1px solid #3e3e42;
    }

    .dark-mode #kesifModal .modal-title,
    .dark-mode #detaylarModal .modal-title {
        color: #f3f4f6;
    }

    .dark-mode #kesifModal .modal-header .close,
    .dark-mode #detaylarModal .modal-header .close {
        color: #ef4444;
        opacity: 0.8;
    }

    .dark-mode #kesifModal .modal-header .close:hover,
    .dark-mode #detaylarModal .modal-header .close:hover {
        opacity: 1;
    }

    .dark-mode .form-section {
        background: #343438;
        border: 1px solid #48484f;
    }

    .dark-mode .section-title {
        color: #e2e8f0;
        border-bottom: 2px solid #48484f;
    }

    .dark-mode .section-title i {
        color: #60a5fa;
    }

    .dark-mode #kesifModal .form-group label {
        color: #cbd5e1;
    }

    .dark-mode #kesifModal .form-control {
        background-color: #202022;
        border-color: #48484f;
        color: #f3f4f6;
    }

    .dark-mode #kesifModal .form-control:focus {
        background-color: #1a1a1c;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
    }

    .dark-mode #kesifModal .modal-footer,
    .dark-mode #detaylarModal .modal-footer {
        background-color: #202022 !important;
        border-top: 1px solid #3e3e42 !important;
    }

    .dark-mode #kesifModal .modal-footer .btn-light,
    .dark-mode #detaylarModal .btn-secondary {
        background-color: #3a3a3c;
        border-color: #48484f;
        color: #cbd5e1;
    }

    .dark-mode #kesifModal .modal-footer .btn-light:hover,
    .dark-mode #detaylarModal .btn-secondary:hover {
        background-color: #48484f;
        color: #f3f4f6;
    }

    .dark-mode #detaylarModal .text-muted {
        color: #9ca3af !important;
    }

    .dark-mode #detaylarModal p {
        color: #f3f4f6;
    }

    .dark-mode #detaylarModal hr {
        border-top: 1px solid #3e3e42;
    }
</style>
<div class="bg-white premium-section-card box-shadow mb-4 animate-fade-in">
    <div class="row">
        <!-- Toplam Keşif Sayısı -->
        <div class="col-xl col-lg-4 col-md-6 col-sm-12 mb-4 mb-xl-0">
            <div class="dashboard-card card-blue">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="d-block text-muted font-14 weight-500 mb-1">Toplam Keşif Sayısı</span>
                        <span class="no text-blue weight-700 font-30">
                            <?php echo $toplam_kesif; ?>
                        </span>
                    </div>
                    <div class="icon bg-blue text-white box-shadow">
                        <i class="fa fa-list"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bekleyen Keşif -->
        <div class="col-xl col-lg-4 col-md-6 col-sm-12 mb-4 mb-xl-0">
            <div class="dashboard-card card-yellow">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="d-block text-muted font-14 weight-500 mb-1">Bekleyen Keşif</span>
                        <span class="no text-warning weight-700 font-30">
                            <?php echo $bekleyen_kesif; ?>
                        </span>
                    </div>
                    <div class="icon bg-warning text-white box-shadow">
                        <i class="fa fa-hourglass-o"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- İptal Edilen Keşif -->
        <div class="col-xl col-lg-4 col-md-6 col-sm-12 mb-4 mb-xl-0">
            <div class="dashboard-card card-red">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="d-block text-muted font-14 weight-500 mb-1">İptal Edilen Keşif</span>
                        <span class="no text-danger weight-700 font-30">
                            <?php echo $iptal_kesif; ?>
                        </span>
                    </div>
                    <div class="icon bg-danger text-white box-shadow">
                        <i class="fa fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teklif İşlemleri -->
        <div class="col-xl col-lg-4 col-md-6 col-sm-12 mb-4 mb-md-0">
            <div class="dashboard-card card-purple">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="d-block text-muted font-14 weight-500 mb-1">Teklif İşlemleri</span>
                        <span class="no text-purple weight-700 font-30">
                            <?php echo $teklif_gonderilen_kesif; ?>
                        </span>
                    </div>
                    <div class="icon bg-purple text-white box-shadow">
                        <i class="fa fa-paper-plane"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tamamlanan Keşif -->
        <div class="col-xl col-lg-4 col-md-6 col-sm-12">
            <div class="dashboard-card card-green">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="d-block text-muted font-14 weight-500 mb-1">Tamamlanan Keşif</span>
                        <span class="no text-success weight-700 font-30">
                            <?php echo $tamamlanan_kesif; ?>
                        </span>
                    </div>
                    <div class="icon bg-success text-white box-shadow">
                        <i class="fa fa-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<div class="bg-white premium-section-card box-shadow mb-30 animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-30" style="flex-wrap: wrap; gap: 15px;">
        <div>
            <h4 class="text-blue weight-600 mb-0">Oluşturulan Tüm Keşifler</h4>
        </div>
        <div>
            <!-- Excele aktarma yetkisi varsa aktar butonu -->
            <?php if (permtrue('kesifExport')) { ?>
                <a href="/pages/1/kesif/export.php" target="_blank" class="btn btn-outline-success mr-2" id="exceleAktar">
                    <i class="fa fa-file-excel-o mr-1"></i> Excel Aktar
                </a>
            <?php } ?>
            <?php if (permtrue('kesifCreate')) { ?>
                <button class="btn btn-primary" data-toggle="modal" data-target="#kesifModal">
                    <i class="fa fa-plus-circle mr-1"></i> Yeni Keşif Ekle
                </button>
            <?php } ?>
        </div>
    </div>
    <div class="search-input-area d-flex"></div>
    <div class="table-responsive">
        <table id="kesifTable" class="data-table table-hover table-bordered" style="width: 100%;">
        <thead>
            <tr>
                <th scope="col">Sıra No</th>
                <th scope="col">Keşif Tarihi</th>
                <th>Firma Adı</th>
                <th>Yapılacak İş</th>
                <th>Keşife Gidecek Kişi</th>
                <th>Form Kimde?</th>
                <th>Konum</th>
                <th>Görseller</th>
                <th>Durum</th>
                <th>Keşif Sonu Notu</th>
                <th>Kayıt Tarihi</th>
                <th>Kayıt Yapan</th>
                <th class="text-nowrap no-export">İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sira = 1;
            if (count($kesifler) > 0) {
                foreach ($kesifler as $kesif) {
                    $enc_id = Security::encrypt($kesif->id);
                    $formatted_kesif_date = Date::dmyHis($kesif->kesif_tarihi);
                    $sort_kesif_date = date('Y-m-d H:i:s', strtotime(str_replace('.', '-', $kesif->kesif_tarihi)));

                    $formatted_gidecek_kisi = htmlspecialchars($kesif->gidecek_kisi ?? '', ENT_QUOTES, 'UTF-8');
                    $kesif_sonu_notu = htmlspecialchars($kesif->kesif_sonu_notu ?? '', ENT_QUOTES, 'UTF-8');
                    $formatted_kayit_date = Date::dmyHis($kesif->kayit_tarihi);
                    $sort_kayit_date = date('Y-m-d H:i:s', strtotime(str_replace('.', '-', $kesif->kayit_tarihi)));
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $sira; ?></td>
                        <td class="text-center" data-sort="<?php echo $sort_kesif_date; ?>">
                            <?php echo $formatted_kesif_date; ?>
                        </td>
                        <td data-tooltip="<?php echo htmlspecialchars($kesif->firma); ?>">
                            <?php echo htmlspecialchars($kesif->firma); ?>
                        </td>
                        <td class="text-center">
                            <?php echo htmlspecialchars(substr($kesif->yapilacak_is, 0, 40)) . (strlen($kesif->yapilacak_is) > 40 ? '...' : ''); ?>
                        </td>
                        <td class="text-center">
                            <?php echo $formatted_gidecek_kisi; ?>
                        </td>
                        <td class="text-center">
                            <span
                                class="badge badge-light"><?php echo htmlspecialchars($kesif->formun_bulundugu_kisi ?? '-'); ?></span>
                        </td>
                        <td class="text-center">
                            <?php echo htmlspecialchars($kesif->konum); ?>
                        </td>
                        <td class="text-center">
                            <?php
                            if (!empty($kesif->gorseller)) {
                                $gorseller = json_decode($kesif->gorseller, true);
                                if (!empty($gorseller)) {
                                    echo '<div class="d-flex justify-content-center">';
                                    foreach (array_slice($gorseller, 0, 3) as $img) {
                                        echo '<a href="' . $img . '" target="_blank" class="mr-1">
                                                <img src="' . $img . '" style="width:30px; height:30px; object-fit:cover; border-radius:4px; border:1px solid #ddd;">
                                              </a>';
                                    }
                                    if (count($gorseller) > 3)
                                        echo '<small>+' . (count($gorseller) - 3) . '</small>';
                                    echo '</div>';
                                } else {
                                    echo '-';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php
                            $durum = $kesif->durum ?? 'bekliyor';
                            if ($durum == 'bekliyor') {
                                echo '<span class="badge badge-warning">Bekliyor</span>';
                            } elseif ($durum == 'iptal_edildi') {
                                echo '<span class="badge badge-danger">İptal Edildi</span>';
                            } elseif ($durum == 'kesif_tamamlandi') {
                                echo '<span class="badge badge-info" style="background-color: #007bff;">Keşif Tamamlandı</span>';
                            } elseif ($durum == 'teklif_hazirlandi') {
                                echo '<span class="badge badge-primary" style="background-color: #6f42c1;">Teklif Hazırlandı</span>';
                            } elseif ($durum == 'teklif_gonderildi') {
                                echo '<span class="badge badge-success">Teklif Gönderildi</span>';
                            } else {
                                echo '<span class="badge badge-secondary">' . htmlspecialchars($durum) . '</span>';
                            }
                            ?>
                        </td>
                        <td class="text-center" data-export="<?php echo $kesif->kesif_sonu_notu; ?>"
                            data-tooltip="<?php echo $kesif_sonu_notu; ?>">
                            <?php echo strlen($kesif_sonu_notu) > 30 ? substr($kesif_sonu_notu, 0, 30) . '...' : $kesif_sonu_notu; ?>
                        </td>
                        <td class="text-center" data-sort="<?php echo $sort_kayit_date; ?>">
                            <?php echo $formatted_kayit_date; ?>
                        </td>
                        <td class="text-center">
                            <?php echo htmlspecialchars($kesif->kullanici_adi); ?>
                        </td>
                        <td class="text-center" style="width:10%; white-space: nowrap;">
                            <?php if (permtrue('kesifEdit')) { ?>
                                <button class="btn btn-sm btn-outline-info edit-btn" data-id="<?php echo $kesif->id; ?>"
                                    data-toggle="modal" data-target="#kesifModal" data-tooltip="Düzenle">
                                    <i class="fa fa-pencil"></i>
                                </button>
                            <?php } ?>

                            <?php if (permtrue('kesifDelete')) { ?>
                                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?php echo $kesif->id; ?>"
                                    data-tooltip="Sil">
                                    <i class="fa fa-trash"></i>
                                </button>
                            <?php } ?>

                            <button class="btn btn-secondary btn-sm" type="button" data-toggle="dropdown">
                                <i class="fa fa-ellipsis-v ml-1 mr-1"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-detail">
                                <a href="#" class="dropdown-item view-btn" data-id="<?php echo $kesif->id; ?>">
                                    <i class="fa fa-eye mr-2"></i>Detayları Görüntüle
                                </a>
                                <a href="/pages/1/kesif/view-pdf.php?id=<?php echo $enc_id; ?>" class="dropdown-item"
                                    target="_blank" rel="noopener" data-tooltip="Keşfi PDF olarak yeni sekmede görüntüle">
                                    <i class="fa fa-file-pdf-o mr-2"></i>PDF Görüntüle
                                </a>

                            </div>
                        </td>
                    </tr>
                    <?php
                    $sira++;
                }
            } ?>
        </tbody>
        <!-- <tfoot>
            <tr>
                <th scope="col">Sıra No</th>
                <th scope="col">Keşif Tarihi</th>
                <th>Firma Adı</th>
                <th>Yapılacak İş</th>
                <th>Konum</th>
                <th>Durum</th>
                <th>Kayıt Tarihi</th>
                <th>Kayıt Yapan</th>
                <th class="text-nowrap">İşlem</th>
            </tr>
        </tfoot> -->
    </table>
    </div>
</div>


<!-- Keşif Modal -->
<div class="modal fade" id="kesifModal" tabindex="-1" role="dialog" aria-labelledby="kesifModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <form id="kesifForm" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="kesifModalLabel">Yeni Keşif Ekle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="kesif_id" name="id" value="">

                    <div class="row">
                        <!-- SOL KOLON: Genel Bilgiler + Görevli & Durum -->
                        <div class="col-lg-6">
                            <!-- Section: Genel Bilgiler -->
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fa fa-info-circle"></i> Genel Bilgiler
                                </div>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="kesif_tarihi">Keşif Tarihi *</label>
                                            <input type="text" id="kesif_tarihi" name="kesif_tarihi" autocomplete="off"
                                                class="form-control datetimepicker" required>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="form-group">
                                            <label for="firma">Firma *</label>
                                            <input type="text" id="firma" name="firma" class="form-control" required
                                                placeholder="Firma adını girin">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="yapilacak_is">Yapılacak İş *</label>
                                    <textarea id="yapilacak_is" name="yapilacak_is" class="form-control" rows="2"
                                        required placeholder="Yapılacak işi açıklayınız"></textarea>
                                </div>
                            </div>

                            <!-- Section: Görevli & Durum -->
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fa fa-users"></i> Görevli & Durum
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="gidecek_kisi">Keşife Gidecek Kişi *</label>
                                            <input type="text" id="gidecek_kisi" name="gidecek_kisi" required
                                                class="form-control" placeholder="Gidecek kişi">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="formun_bulundugu_kisi">Formun Bulunduğu Kişi</label>
                                            <input type="text" id="formun_bulundugu_kisi" name="formun_bulundugu_kisi"
                                                class="form-control" list="kisi_listesi"
                                                placeholder="Seçiniz veya yazınız...">
                                            <datalist id="kisi_listesi">
                                                <option value="Ömer SEÇKİN">
                                                <option value="EREN YUNUSOĞLU">
                                                <option value="Gamze TOKGÖZ KÖKEN">
                                                <option value="BERK CEYLAN">
                                            </datalist>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-md-0">
                                            <label for="konum">Konum *</label>
                                            <input type="text" id="konum" name="konum" class="form-control" required
                                                placeholder="İl, İlçe veya Tam Adres">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="durum">Durum *</label>
                                            <select id="durum" name="durum" class="form-control" required>
                                                <option value="bekliyor">Bekliyor (Sarı)</option>
                                                <option value="iptal_edildi">İptal edildi (Kırmızı)</option>
                                                <option value="kesif_tamamlandi">Keşif Tamamlandı (Mavi)</option>
                                                <option value="teklif_hazirlandi">Teklif Hazırlandı (Mor)</option>
                                                <option value="teklif_gonderildi">Teklif Gönderildi (Yeşil)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SAĞ KOLON: Medya & Notlar -->
                        <div class="col-lg-6">
                            <div class="form-section" style="height: 100%;">
                                <div class="section-title">
                                    <i class="fa fa-camera"></i> Medya & Notlar
                                </div>
                                <div class="form-group">
                                    <label for="kesif_gorseller">Yeni Görseller Ekle</label>
                                    <input type="file" id="kesif_gorseller" name="gorseller[]" class="form-control"
                                        multiple accept="image/*">
                                    <div id="current_gorseller" class="mt-3 d-flex flex-wrap"></div>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="kesif_sonu_notu">Keşif Sonu Notu</label>
                                    <textarea id="kesif_sonu_notu" name="kesif_sonu_notu" class="form-control" rows="15"
                                        style="flex:1;"
                                        placeholder="Keşif sonrası detaylı notlarınızı buraya ekleyin..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light" style="border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Vazgeç</button>
                    <button type="submit" id="btnSaveKesif" class="btn btn-primary px-5"
                        style="border-radius: 10px; font-weight: 600;">
                        <span class="btn-text">Bilgileri Kaydet</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Detaylar Modal -->
<div class="modal fade" id="detaylarModal" tabindex="-1" role="dialog" aria-labelledby="detaylarModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detaylarModalLabel">Keşif Detayları</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted">Keşif Tarihi</h6>
                        <p id="detail_kesif_tarihi" class="h6 mb-0"></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Keşife Gidecek Kişi</h6>
                        <p id="detail_gidecek_kisi" class="h6 mb-0"></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted">Firma</h6>
                        <p id="detail_firma" class="h6 mb-0"></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Konum</h6>
                        <p id="detail_konum" class="h6 mb-0"></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted">Durum</h6>
                        <p id="detail_durum" class="h6 mb-0"></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Formun Bulunduğu Kişi</h6>
                        <p id="detail_form_kimde" class="h6 mb-0"></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <h6 class="text-muted">Görseller</h6>
                        <div id="detail_gorseller" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <h6 class="text-muted">Yapılacak İş</h6>
                        <p id="detail_yapilacak_is" class="h6 mb-0" style="white-space: pre-wrap;"></p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <h6 class="text-muted">Keşif Sonu Notu</h6>
                        <p id="detail_kesif_sonu_notu" class="h6 mb-0" style="white-space: pre-wrap;"></p>
                    </div>
                </div>

                <hr>

                <div class="row mb-0">
                    <div class="col-md-6">
                        <h6 class="text-muted">Kayıt Tarihi</h6>
                        <p id="detail_kayit_tarihi" class="h6 mb-2"></p>
                        <h6 class="text-muted">Kayıt Yapan</h6>
                        <p id="detail_kayit_yapan" class="h6 mb-0"></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Güncelleme Tarihi</h6>
                        <p id="detail_guncelleme_tarihi" class="h6 mb-2"></p>
                        <h6 class="text-muted">Güncelleyen Kullanıcı</h6>
                        <p id="detail_guncelleyen_kullanici" class="h6 mb-0"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>




<script src="include/js/data-table.js"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
<script src="pages/1/kesif/kesif.js"></script>