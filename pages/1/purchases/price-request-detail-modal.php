<?php
require_once dirname(__DIR__, 3) . "/bootstrap.php";

use App\Helper\Helper;
use App\Model\PurchaseModel;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$Purchase = new PurchaseModel();
$purchase = $Purchase->find($id);

if (!$purchase) {
    echo '<div class="alert alert-warning m-4 d-flex align-items-center"><i class="fa fa-exclamation-triangle mr-2 fa-lg"></i> Fiyat talebi kaydı bulunamadı!</div>';
    exit;
}

$items = $Purchase->getPurchaseItems($id);
$customer_name = getCustomerName($purchase->companyID);
$creator_name = getUserName($purchase->creator);

// Müşteri ek iletişim bilgileri
$cust_extra = null;
if (!empty($purchase->companyID)) {
    try {
        $cust_sql = $ac->prepare("SELECT yetkili, email, gsm, telefon, city, address FROM customers WHERE id = ?");
        $cust_sql->execute([$purchase->companyID]);
        $cust_extra = $cust_sql->fetch(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
}

if (!function_exists('parseCurrencyNumber')) {
    function parseCurrencyNumber($val): float {
        if (is_numeric($val)) return (float)$val;
        if (is_string($val)) {
            $val = trim($val);
            if ($val === '') return 0.0;
            if (strpos($val, '.') !== false && strpos($val, ',') !== false) {
                if (strrpos($val, ',') > strrpos($val, '.')) {
                    $val = str_replace('.', '', $val);
                    $val = str_replace(',', '.', $val);
                } else {
                    $val = str_replace(',', '', $val);
                }
            } elseif (strpos($val, ',') !== false) {
                $val = str_replace(',', '.', $val);
            }
            return is_numeric($val) ? (float)$val : 0.0;
        }
        return 0.0;
    }
}

function getFileIconClass($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) return 'fa-file-image-o text-primary';
    if ($ext === 'pdf') return 'fa-file-pdf-o text-danger';
    if (in_array($ext, ['xls', 'xlsx', 'csv'])) return 'fa-file-excel-o text-success';
    if (in_array($ext, ['doc', 'docx'])) return 'fa-file-word-o text-info';
    if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) return 'fa-file-archive-o text-warning';
    return 'fa-paperclip text-secondary';
}

$totalItemCount = count($items);
$totalQuantity = 0;
foreach ($items as $it) {
    $totalQuantity += parseCurrencyNumber($it->amount ?? 0);
}

$state = (int)($purchase->state ?? 0);
if ($state === 0) {
    $stateBadge = '<span class="detail-status-badge badge-warning"><i class="fa fa-clock-o mr-1"></i> Bekliyor</span>';
} elseif ($state === 1) {
    $stateBadge = '<span class="detail-status-badge badge-info"><i class="fa fa-hourglass-half mr-1"></i> Onaylandı</span>';
} elseif ($state === 2) {
    $stateBadge = '<span class="detail-status-badge badge-success"><i class="fa fa-check-circle mr-1"></i> Tamamlandı</span>';
} elseif ($state === 3) {
    $stateBadge = '<span class="detail-status-badge badge-danger"><i class="fa fa-times-circle mr-1"></i> Reddedildi</span>';
} else {
    $stateBadge = Helper::getStateBadge($state);
}

$createDateFormatted = !empty($purchase->create_time) ? date('d.m.Y H:i', strtotime($purchase->create_time)) : '-';
$deadlineFormatted = !empty($purchase->deadline) ? date('d.m.Y', strtotime($purchase->deadline)) : '-';

$altToplam = parseCurrencyNumber($purchase->altToplam ?? 0);
$tlTotal = parseCurrencyNumber($purchase->TLTotal ?? 0);
if ($tlTotal <= 0 && $altToplam > 0) {
    $tlTotal = $altToplam;
}
?>

<style>
    .ft-detail-container {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #1e293b;
        background: #f8fafc;
        padding: 16px 20px;
    }

    /* Hero / Header Card */
    .ft-hero-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }
    .ft-hero-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #7c3aed 0%, #3b82f6 50%, #10b981 100%);
    }

    .ft-id-badge {
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -0.5px;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
        line-height: 1.1;
    }
    .ft-type-chip {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: #f3e8ff;
        color: #7c3aed;
        padding: 3px 9px;
        border-radius: 6px;
        display: inline-block;
    }

    .ft-meta-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 14px;
    }
    .ft-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12.5px;
        font-weight: 500;
        padding: 5px 12px;
        border-radius: 8px;
    }
    .ft-meta-pill strong {
        color: #0f172a;
        font-weight: 600;
    }

    .ft-company-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: right;
    }
    .ft-company-title {
        font-size: 17px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
        margin-bottom: 4px;
        word-break: break-word;
    }
    .ft-company-sub {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 8px;
    }

    /* Status Badges */
    .detail-status-badge {
        display: inline-flex;
        align-items: center;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        line-height: 1.4;
    }
    .detail-status-badge.badge-warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .detail-status-badge.badge-info {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .detail-status-badge.badge-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    .detail-status-badge.badge-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* Stat Cards */
    .ft-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 20px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .ft-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }
    .ft-stat-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
    }
    .ft-stat-subtotal::before { background: #3b82f6; }
    .ft-stat-grandtotal::before { background: #10b981; }
    .ft-stat-items::before { background: #7c3aed; }

    .ft-stat-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
        display: block;
        margin-bottom: 4px;
    }
    .ft-stat-val {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        margin: 0;
    }
    .ft-stat-val.text-emerald {
        color: #059669;
    }
    .ft-stat-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .ft-stat-icon-blue { background: #eff6ff; color: #2563eb; }
    .ft-stat-icon-green { background: #ecfdf5; color: #059669; }
    .ft-stat-icon-purple { background: #f3e8ff; color: #7c3aed; }

    /* Note & Description Box */
    .ft-note-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #3b82f6;
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
        margin-bottom: 20px;
    }
    .ft-note-header {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #2563eb;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
    }
    .ft-note-text {
        font-size: 13.5px;
        color: #334155;
        line-height: 1.5;
        margin: 0;
        white-space: pre-line;
    }

    /* Items Section */
    .ft-items-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }
    .ft-items-header {
        padding: 16px 22px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .ft-items-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .ft-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
    }
    .ft-table thead th {
        background: #f8fafc;
        padding: 12px 16px;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
        border-top: none;
        white-space: nowrap;
    }
    .ft-table tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        border-top: 1px solid #f1f5f9;
        font-size: 13px;
        color: #334155;
    }
    .ft-table tbody tr:hover {
        background: #f8fafc;
    }

    .ft-row-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: #334155;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .ft-stock-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        background: #f1f5f9;
        color: #4338ca;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        display: inline-block;
    }

    .ft-product-name {
        font-weight: 700;
        color: #0f172a;
        font-size: 13.5px;
        display: block;
        line-height: 1.3;
    }
    .ft-product-desc {
        font-size: 12px;
        color: #64748b;
        margin-top: 3px;
        display: block;
        line-height: 1.3;
    }

    .ft-qty-badge {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        display: inline-block;
        white-space: nowrap;
    }

    .ft-price-val {
        font-weight: 600;
        color: #475569;
        white-space: nowrap;
    }
    .ft-total-val {
        font-weight: 700;
        color: #0f172a;
        font-size: 13.5px;
        white-space: nowrap;
    }

    .ft-attachment-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-size: 11.5px;
        font-weight: 600;
        text-decoration: none !important;
        transition: all 0.2s ease;
        max-width: 160px;
    }
    .ft-attachment-chip:hover {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(37, 99, 235, 0.12);
    }
    .ft-attachment-chip span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .ft-company-box {
            text-align: left;
            margin-top: 14px;
        }
        .ft-detail-container {
            padding: 14px;
        }
    }
</style>

<div class="ft-detail-container" data-detail-id="<?php echo (int)$purchase->id; ?>">
    <!-- Hero / Header Card -->
    <div class="ft-hero-card">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="ft-id-badge">
                    <span><?php echo htmlspecialchars($purchase->siparisNo ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="ft-type-chip"><i class="fa fa-tag mr-1"></i> Fiyat Talebi</span>
                </div>

                <div class="ft-meta-pills">
                    <div class="ft-meta-pill" title="Kayıt Tarihi">
                        <i class="fa fa-calendar-plus-o text-primary"></i>
                        <span>Kayıt: <strong><?php echo htmlspecialchars($createDateFormatted, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                    </div>
                    <div class="ft-meta-pill" title="Termin Tarihi">
                        <i class="fa fa-calendar-check-o text-warning"></i>
                        <span>Termin: <strong><?php echo htmlspecialchars($deadlineFormatted, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                    </div>
                    <div class="ft-meta-pill" title="Talebi Oluşturan">
                        <i class="fa fa-user-circle-o text-purple" style="color: #7c3aed;"></i>
                        <span>Oluşturan: <strong><?php echo htmlspecialchars($creator_name ?: 'Sistem', ENT_QUOTES, 'UTF-8'); ?></strong></span>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="ft-company-box">
                    <div class="ft-company-title">
                        <i class="fa fa-building-o mr-1 text-muted"></i>
                        <?php echo htmlspecialchars($customer_name ?: 'Belirtilmedi', ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php if (!empty($cust_extra['yetkili']) || !empty($cust_extra['email']) || !empty($cust_extra['telefon']) || !empty($cust_extra['gsm'])): ?>
                        <div class="ft-company-sub">
                            <?php if (!empty($cust_extra['yetkili'])): ?>
                                <span><i class="fa fa-user-o mr-1"></i><?php echo htmlspecialchars($cust_extra['yetkili'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($cust_extra['email'])): ?>
                                <span class="ml-2"><i class="fa fa-envelope-o mr-1"></i><?php echo htmlspecialchars($cust_extra['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($cust_extra['gsm']) || !empty($cust_extra['telefon'])): ?>
                                <span class="ml-2"><i class="fa fa-phone mr-1"></i><?php echo htmlspecialchars($cust_extra['gsm'] ?: $cust_extra['telefon'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-md-end align-items-center mt-1">
                        <?php echo $stateBadge; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards Row -->
    <div class="row g-3 mb-3">
        <!-- 1. Ara Toplam -->
        <div class="col-12 col-sm-4">
            <div class="ft-stat-card ft-stat-subtotal">
                <div>
                    <span class="ft-stat-label">Ara Toplam (TL)</span>
                    <h4 class="ft-stat-val"><?php echo number_format($tlTotal, 2, ',', '.') . ' ₺'; ?></h4>
                </div>
                <div class="ft-stat-icon-box ft-stat-icon-blue">
                    <i class="fa fa-calculator"></i>
                </div>
            </div>
        </div>

        <!-- 2. Genel Toplam -->
        <div class="col-12 col-sm-4">
            <div class="ft-stat-card ft-stat-grandtotal">
                <div>
                    <span class="ft-stat-label">Genel Toplam (TL)</span>
                    <h4 class="ft-stat-val text-emerald"><?php echo number_format($altToplam > 0 ? $altToplam : $tlTotal, 2, ',', '.') . ' ₺'; ?></h4>
                </div>
                <div class="ft-stat-icon-box ft-stat-icon-green">
                    <i class="fa fa-money"></i>
                </div>
            </div>
        </div>

        <!-- 3. Kalem ve Miktar Özeti -->
        <div class="col-12 col-sm-4">
            <div class="ft-stat-card ft-stat-items">
                <div>
                    <span class="ft-stat-label">Kalem & Miktar</span>
                    <h4 class="ft-stat-val" style="color: #7c3aed;"><?php echo $totalItemCount; ?> <small style="font-size: 13px; font-weight: 600; color: #64748b;">Kalem</small></h4>
                </div>
                <div class="ft-stat-icon-box ft-stat-icon-purple">
                    <i class="fa fa-cubes"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Note / Description Box (If Exists) -->
    <?php if (!empty($purchase->description1)): ?>
    <div class="ft-note-card">
        <div class="ft-note-header">
            <i class="fa fa-info-circle"></i> Genel Açıklama / Notlar
        </div>
        <p class="ft-note-text"><?php echo nl2br(htmlspecialchars($purchase->description1, ENT_QUOTES, 'UTF-8')); ?></p>
    </div>
    <?php endif; ?>

    <!-- Items Table Card -->
    <div class="ft-items-card">
        <div class="ft-items-header">
            <h6 class="ft-items-title">
                <i class="fa fa-shopping-cart text-primary"></i> Talep Edilen Kalemler
            </h6>
            <span class="badge badge-secondary" style="font-size: 12px; padding: 4px 10px; border-radius: 20px;">
                <?php echo $totalItemCount; ?> Ürün / Hizmet
            </span>
        </div>

        <div class="table-responsive">
            <table class="ft-table table">
                <thead>
                    <tr>
                        <th style="width: 5%;" class="text-center">#</th>
                        <th style="width: 14%;">Stok Kodu</th>
                        <th style="width: 33%;">Ürün / Hizmet Açıklaması</th>
                        <th style="width: 12%;" class="text-center">Miktar</th>
                        <th style="width: 13%;" class="text-right">Birim Fiyat</th>
                        <th style="width: 13%;" class="text-right">Toplam</th>
                        <th style="width: 10%;" class="text-center">Ekler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fa fa-inbox fa-2x mb-2 d-block text-muted"></i>
                                Bu fiyat talebine ait ürün veya hizmet kalemi bulunmamaktadır.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $i = 0;
                        foreach ($items as $item): 
                            $i++;
                            $amount = parseCurrencyNumber($item->amount ?? 0);
                            $price = parseCurrencyNumber($item->price ?? 0);
                            $rowTotal = $amount * $price;
                            $curr = !empty($item->currency) ? htmlspecialchars($item->currency, ENT_QUOTES, 'UTF-8') : 'TRY';
                            $unit = !empty($item->unit) ? htmlspecialchars($item->unit, ENT_QUOTES, 'UTF-8') : 'Adet';
                        ?>
                        <tr>
                            <td class="text-center">
                                <span class="ft-row-num"><?php echo $i; ?></span>
                            </td>

                            <td>
                                <?php if (!empty($item->stokKodu)): ?>
                                    <span class="ft-stock-code"><?php echo htmlspecialchars($item->stokKodu, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="ft-product-name"><?php echo htmlspecialchars($item->product ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (!empty($item->description)): ?>
                                    <span class="ft-product-desc"><?php echo htmlspecialchars($item->description, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <span class="ft-qty-badge">
                                    <?php echo rtrim(rtrim(number_format($amount, 2, ',', '.'), '0'), ',') . ' ' . $unit; ?>
                                </span>
                            </td>

                            <td class="text-right">
                                <span class="ft-price-val"><?php echo number_format($price, 2, ',', '.') . ' ' . $curr; ?></span>
                            </td>

                            <td class="text-right">
                                <span class="ft-total-val"><?php echo number_format($rowTotal, 2, ',', '.') . ' ' . $curr; ?></span>
                            </td>

                            <td class="text-center">
                                <div class="d-flex justify-content-center flex-column align-items-center gap-1">
                                    <?php if (!empty($item->image)): ?>
                                        <a href="<?php echo htmlspecialchars($item->image, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="ft-attachment-chip" title="Görseli Görüntüle">
                                            <i class="fa <?php echo getFileIconClass($item->image); ?>"></i>
                                            <span><?php echo htmlspecialchars(basename($item->image), ENT_QUOTES, 'UTF-8'); ?></span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($item->excel_file)): ?>
                                        <a href="<?php echo htmlspecialchars($item->excel_file, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="ft-attachment-chip" title="Dosyayı İndir">
                                            <i class="fa <?php echo getFileIconClass($item->excel_file); ?>"></i>
                                            <span><?php echo htmlspecialchars(basename($item->excel_file), ENT_QUOTES, 'UTF-8'); ?></span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (empty($item->image) && empty($item->excel_file)): ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

