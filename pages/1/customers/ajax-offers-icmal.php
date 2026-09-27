<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';

use App\Model\CustomerModel;
use App\Model\OfferModel;

if (!isset($_SESSION['login']) || (!permtrue('customeredit') && !permtrue('offerview'))) {
    http_response_code(403);
    exit('<div class="alert alert-danger font-13 m-3"><i class="fa fa-lock mr-2"></i>Bu içeriği görüntüleme yetkiniz bulunmamaktadır.</div>');
}

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    exit('<div class="alert alert-warning font-13 m-3">Geçersiz müşteri kimliği.</div>');
}

$Customer = new CustomerModel();
$customer = $Customer->find($id);
if (!$customer) {
    exit('<div class="alert alert-warning font-13 m-3">Müşteri kaydı bulunamadı.</div>');
}

$offerModel = new OfferModel();
$offerSummaryData = $offerModel->getCustomerOfferSummary($id);
$customerOffers = $offerSummaryData['offers'] ?? [];
$customerOffersSummary = $offerSummaryData['summary'] ?? [];
?>
<style>
    .icmal-kpi-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 15px !important;
        width: 100% !important;
        margin-bottom: 20px !important;
    }
    @media (max-width: 992px) {
        .icmal-kpi-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
    @media (max-width: 576px) {
        .icmal-kpi-grid {
            grid-template-columns: 1fr !important;
        }
    }
    .icmal-kpi-card {
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 16px !important;
        display: flex !important;
        align-items: center !important;
        gap: 14px !important;
        transition: all 0.2s ease !important;
    }
    .icmal-kpi-card:hover {
        border-color: #cbd5e1 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        transform: translateY(-2px) !important;
    }
    .icmal-kpi-icon {
        width: 48px !important;
        height: 48px !important;
        border-radius: 10px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 20px !important;
        flex-shrink: 0 !important;
    }
    .icmal-kpi-info {
        flex-grow: 1 !important;
        min-width: 0 !important;
    }
    .icmal-kpi-title {
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        margin-bottom: 4px !important;
    }
    .icmal-kpi-value {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    .icmal-kpi-sub {
        font-size: 11px !important;
        color: #94a3b8 !important;
        margin-top: 3px !important;
    }
</style>

<div class="form-card animate-fade-in" id="customerOffersIcmalCard">
    <!-- Header / Action Toolbar -->
    <div class="form-card-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 12px; padding: 16px 20px;">
        <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
            <div class="card-icon" style="background: rgba(59, 130, 246, 0.12); color: #2563eb; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                <i class="fa fa-calculator"></i>
            </div>
            <div>
                <h5 class="mb-0 font-16 weight-600">Firma Teklif İcmali & Kalem Dökümü</h5>
                <p class="mb-0 text-muted font-12">İcmale dahil etmek istediğiniz teklifleri seçip resmi Fiyat Teklif Formu çıktısı alabilirsiniz.</p>
            </div>
            <div class="d-flex align-items-center" style="gap: 6px;">
                <span class="badge badge-primary font-12 py-1 px-2 border-radius-5" id="icmalOfferCountBadge"><?= count($customerOffers) ?> Teklif</span>
                <span class="badge badge-success font-12 py-1 px-2 border-radius-5" id="icmalSelectedCountBadge"><?= count($customerOffers) ?> Seçili</span>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnReloadOffersIcmal" title="Teklif İcmalini Yeniden Yükle" style="border-radius: 8px; height: 35px; font-weight: 500;">
                <i class="fa fa-refresh mr-1"></i> Yenile
            </button>

            <button type="button" class="btn btn-sm btn-outline-info" id="btnToggleAllRows" title="Tüm Kalemleri Genişlet / Daralt" style="border-radius: 8px; height: 35px; font-weight: 500;">
                <i class="fa fa-expand mr-1"></i> Tümünü Aç
            </button>

            <!-- Resmi İcmal Yazdır Butonu -->
            <button type="button" class="btn btn-sm btn-primary" id="btnOpenOfficialIcmal" title="Seçilen Teklifleri Resmi İcmal Formatında Yazdır" style="border-radius: 8px; height: 35px; font-weight: 600; box-shadow: 0 2px 6px rgba(59,130,246,0.3);">
                <i class="fa fa-print mr-1"></i> İcmal Yazdır
            </button>

            <?php if (permtrue('data_export_offers')): ?>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportOfficialIcmalExcel()" title="Seçilen Teklifleri Kurumsal İcmal Formatında Excel'e Aktar" style="border-radius: 8px; height: 35px; font-weight: 600;">
                <i class="fa fa-file-excel-o mr-1"></i> Excel İndir
            </button>
            <?php endif; ?>

            <?php if (permtrue("offernew")): ?>
            <a href="index.php?p=offer-new&cid=<?= $id ?>" target="_blank" class="btn btn-sm btn-success" style="border-radius: 8px; height: 35px; display: inline-flex; align-items: center; font-weight: 500;">
                <i class="fa fa-plus mr-1"></i> Yeni Teklif
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="p-3">
        <!-- KPI Kartları Grid -->
        <div id="offerIcmalKpiContainer" class="mb-20">
            <div class="icmal-kpi-grid">
                <!-- 1. Toplam Teklif & Kalem -->
                <div class="icmal-kpi-card">
                    <div class="icmal-kpi-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <div class="icmal-kpi-info">
                        <div class="icmal-kpi-title">Toplam Teklif</div>
                        <div class="icmal-kpi-value" id="kpiTotalOffers"><?= (int)($customerOffersSummary['total_count'] ?? 0) ?> Adet</div>
                        <div class="icmal-kpi-sub"><?= (int)($customerOffersSummary['total_items'] ?? 0) ?> Kalem / Hizmet</div>
                    </div>
                </div>

                <!-- 2. Teklif Başarı Oranı -->
                <div class="icmal-kpi-card">
                    <div class="icmal-kpi-icon" style="background: #dcfce7; color: #16a34a;">
                        <i class="fa fa-check-circle-o"></i>
                    </div>
                    <div class="icmal-kpi-info">
                        <div class="icmal-kpi-title">Kabul Edilen</div>
                        <div class="icmal-kpi-value"><?= (int)($customerOffersSummary['accepted_count'] ?? 0) ?> Adet</div>
                        <div class="icmal-kpi-sub">
                            <?php 
                            $totalOffersCount = (int)($customerOffersSummary['total_count'] ?? 0);
                            $acceptedOffersCount = (int)($customerOffersSummary['accepted_count'] ?? 0);
                            $successRate = $totalOffersCount > 0 ? round(($acceptedOffersCount / $totalOffersCount) * 100, 1) : 0;
                            echo "%{$successRate} Başarı Oranı";
                            ?>
                        </div>
                    </div>
                </div>

                <!-- 3. Bekleyen Teklifler -->
                <div class="icmal-kpi-card">
                    <div class="icmal-kpi-icon" style="background: #fef3c7; color: #d97706;">
                        <i class="fa fa-clock-o"></i>
                    </div>
                    <div class="icmal-kpi-info">
                        <div class="icmal-kpi-title">Bekleyen / Açık</div>
                        <div class="icmal-kpi-value"><?= (int)($customerOffersSummary['pending_count'] ?? 0) ?> Adet</div>
                        <div class="icmal-kpi-sub"><?= (int)($customerOffersSummary['rejected_count'] ?? 0) ?> Reddedilen</div>
                    </div>
                </div>

                <!-- 4. Konsolide Toplam Tutar (TL) -->
                <div class="icmal-kpi-card">
                    <div class="icmal-kpi-icon" style="background: #ede9fe; color: #7c3aed;">
                        <i class="fa fa-try"></i>
                    </div>
                    <div class="icmal-kpi-info">
                        <div class="icmal-kpi-title">Konsolide Tutar (TL)</div>
                        <div class="icmal-kpi-value text-primary" style="font-size: 16px;">
                            <?= number_format((float)($customerOffersSummary['total_amount_tl'] ?? 0), 2, ',', '.') ?> ₺
                        </div>
                        <div class="icmal-kpi-sub">KDV Dahil Toplam Karşılık</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtreleme & Arama Araç Çubuğu -->
        <div class="icmal-filter-toolbar mb-3">
            <div class="row align-items-center g-2">
                <div class="col-lg-4 col-md-5 mb-2 mb-md-0">
                    <div class="d-flex align-items-center" style="gap: 8px;">
                        <label class="mb-0 font-12 weight-600 text-secondary text-nowrap" for="icmalStatusFilter">
                            <i class="fa fa-filter mr-1"></i> Teklif Durumu:
                        </label>
                        <select id="icmalStatusFilter" class="form-control select2" style="width: 100%;">
                            <option value="all">Tüm Durumlar</option>
                            <option value="1">Bekliyor</option>
                            <option value="2">Kabul Edildi / Tamamlandı</option>
                            <option value="3">Reddedildi / İptal</option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-8 col-md-7">
                    <div class="d-flex align-items-center justify-content-md-end" style="gap: 8px;">
                        <div class="input-group" style="max-width: 380px;">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px; border-color: #cbd5e1;">
                                    <i class="fa fa-search text-muted"></i>
                                </span>
                            </div>
                            <input type="text" id="icmalSearchInput" class="form-control border-left-0" placeholder="Teklif no, konu veya ürün ara..." style="border-radius: 0 8px 8px 0; border-color: #cbd5e1; font-size: 13px;">
                        </div>

                        <!-- Standart Kural: Arama Kutusunun Sağında KPI Daraltma Butonu -->
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-kpi-toggle" id="toggleOfferIcmalKpi" title="Özet Kartlarını Gizle / Göster" style="border-radius: 8px; height: 36px; width: 36px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa fa-chevron-up font-12"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($customerOffers)): ?>
            <div class="alert alert-light text-center border p-4" style="border-radius: 12px; background: #f8fafc;">
                <div style="font-size: 36px; color: #94a3b8;" class="mb-2"><i class="fa fa-folder-open-o"></i></div>
                <h6 class="weight-600 text-secondary mb-1">Kayıtlı Teklif Bulunamadı</h6>
                <p class="text-muted font-13 mb-3">Bu firmaya ait henüz kaydedilmiş herhangi bir fiyat teklifi bulunmamaktadır.</p>
                <?php if (permtrue("offernew")): ?>
                <a href="index.php?p=offer-new&cid=<?= $id ?>" target="_blank" class="btn btn-sm btn-success font-13" style="border-radius: 8px;">
                    <i class="fa fa-plus mr-1"></i> İlk Teklifi Oluştur
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Master Detail Tablosu -->
            <div class="table-responsive">
                <table class="table table-icmal mb-0">
                    <thead>
                        <tr>
                            <th style="width: 38px;" class="text-center">
                                <input type="checkbox" id="selectAllOffers" checked title="Tümünü Seç / Kaldır">
                            </th>
                            <th style="width: 38px;" class="text-center"></th>
                            <th style="width: 130px;">Teklif No</th>
                            <th>Teklif Konusu</th>
                            <th style="width: 120px;">Tarih</th>
                            <th style="width: 90px;" class="text-center">Kalem</th>
                            <th style="width: 120px;" class="text-center">Durum</th>
                            <th style="width: 110px;" class="text-right">İsk / KDV</th>
                            <th style="width: 130px;" class="text-right">Tutar (Orijinal)</th>
                            <th style="width: 140px;" class="text-right">Tutar (TL)</th>
                            <th style="width: 120px;" class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $runningConsolidatedTotal = 0;
                        $runningItemsTotal = 0;

                        foreach ($customerOffers as $of): 
                            $oid = (int)$of['id'];
                            $offerNoClean = $of['offerNumber'] ?? ('TK' . $oid);
                            $subjectText = !empty($of['offer_subject']) ? $of['offer_subject'] : 'Fiyat Teklifi';
                            $dateRaw = $of['offer_date'] ?? ($of['created_at'] ?? '');
                            $dateFormatted = !empty($dateRaw) ? date('d.m.Y', strtotime($dateRaw)) : '-';
                            
                            $currencyText = $of['currency'] ?? 'TRY';
                            $originalPrice = (float)($of['total_price'] ?? 0);
                            $rawTl = (float)($of['tl_toplam_karsilik'] ?? $originalPrice);
                            $runningConsolidatedTotal += $rawTl;

                            $itemCount = count($of['items'] ?? []);
                            $runningItemsTotal += $itemCount;

                            $iskontoVal = (float)($of['iskonto'] ?? 0);
                            $kdvRate = (float)($of['kdv'] ?? 20);

                            // Durum Rozetleri
                            $statu = (int)($of['statu'] ?? 1);
                            $statuBadge = '<span class="badge badge-warning font-11 py-1 px-2 border-radius-5"><i class="fa fa-clock-o mr-1"></i>Bekliyor</span>';
                            if ($statu === 2) {
                                $statuBadge = '<span class="badge badge-success font-11 py-1 px-2 border-radius-5"><i class="fa fa-check mr-1"></i>Tamamlandı</span>';
                            } elseif ($statu === 3) {
                                $statuBadge = '<span class="badge badge-danger font-11 py-1 px-2 border-radius-5"><i class="fa fa-times mr-1"></i>Reddedildi</span>';
                            }
                        ?>
                            <!-- Ana Teklif Satırı (Master) -->
                            <tr class="offer-row" 
                                data-offer-id="<?= $oid ?>" 
                                data-statu="<?= $statu ?>"
                                data-amount="<?= $rawTl ?>"
                                data-items="<?= $itemCount ?>">
                                
                                <td class="text-center" onclick="event.stopPropagation();">
                                    <input type="checkbox" class="offer-select-cb" checked 
                                           data-offer-id="<?= $oid ?>"
                                           data-offer-number="<?= htmlspecialchars($offerNoClean, ENT_QUOTES, 'UTF-8') ?>"
                                           data-subject="<?= htmlspecialchars($subjectText, ENT_QUOTES, 'UTF-8') ?>"
                                           data-amount="<?= $rawTl ?>"
                                           data-kdv-rate="<?= $kdvRate ?>"
                                           data-items="<?= $itemCount ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn-expand-row" title="Kalemleri Göster/Gizle">
                                        <i class="fa fa-chevron-right font-10"></i>
                                    </button>
                                </td>
                                <td>
                                    <a href="index.php?p=offer-view&id=<?= $oid ?>" target="_blank" class="font-weight-bold text-primary" style="text-decoration: none;">
                                        <?= htmlspecialchars($offerNoClean, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="font-weight-600 text-dark"><?= htmlspecialchars($subjectText, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($of['creator_name'])): ?>
                                        <small class="text-muted"><i class="fa fa-user mr-1"></i><?= htmlspecialchars($of['creator_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted font-12"><i class="fa fa-calendar-o mr-1"></i><?= $dateFormatted ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light border font-11"><?= $itemCount ?> Kalem</span>
                                </td>
                                <td class="text-center">
                                    <?= $statuBadge ?>
                                </td>
                                <td class="text-right font-12 text-muted">
                                    <?php if ($iskontoVal > 0): ?>
                                        <div class="text-danger" title="İskonto">-%<?= number_format($iskontoVal, 0) ?></div>
                                    <?php endif; ?>
                                    <div>KDV: %<?= number_format($kdvRate, 0) ?></div>
                                </td>
                                <td class="text-right">
                                    <span class="font-weight-600 text-dark">
                                        <?= number_format($originalPrice, 2, ',', '.') ?>
                                    </span>
                                    <small class="text-muted d-block font-11"><?= htmlspecialchars($currencyText, ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td class="text-right">
                                    <span class="font-weight-bold text-primary font-14">
                                        <?= number_format($rawTl, 2, ',', '.') ?> ₺
                                    </span>
                                </td>
                                <td class="text-center action-cell">
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?p=offer-view&id=<?= $oid ?>" target="_blank" class="btn btn-outline-primary btn-sm" title="Teklifi Görüntüle">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="index.php?p=offers/offer-manage&id=<?= $oid ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Düzenle / Yönet">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                        <a href="index.php?p=generate_pdf&id=<?= $oid ?>" target="_blank" class="btn btn-outline-danger btn-sm" title="PDF İndir">
                                            <i class="fa fa-file-pdf-o"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Detay Kalem Satırı (Accordion Child) -->
                            <tr class="offer-details-row" id="offer-details-<?= $oid ?>" style="display: none;">
                                <td colspan="11" class="p-0 border-0">
                                    <div class="details-subtable-wrapper">
                                        <div class="d-flex align-items-center justify-content-between mb-10">
                                            <strong class="font-12 text-secondary">
                                                <i class="fa fa-list-ul mr-1 text-primary"></i> <?= htmlspecialchars($offerNoClean, ENT_QUOTES, 'UTF-8') ?> Kalem Dökümü (<?= $itemCount ?> Adet)
                                            </strong>
                                            <span class="font-12 text-muted">
                                                Teklif Toplamı: <strong class="text-dark"><?= number_format($rawTl, 2, ',', '.') ?> ₺</strong>
                                            </span>
                                        </div>

                                        <?php if (empty($of['items'])): ?>
                                            <div class="alert alert-light border font-12 py-2 mb-0">Bu teklife ait alt ürün/hizmet kalemi bulunmamaktadır.</div>
                                        <?php else: ?>
                                            <table class="subtable-items">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 35px;" class="text-center">#</th>
                                                        <th style="width: 120px;">Stok Kodu</th>
                                                        <th>Ürün / Hizmet Açıklaması</th>
                                                        <th style="width: 90px;" class="text-right">Miktar</th>
                                                        <th style="width: 70px;">Birim</th>
                                                        <th style="width: 120px;" class="text-right">Birim Fiyat</th>
                                                        <th style="width: 130px;" class="text-right">Satır Toplamı</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    foreach ($of['items'] as $itemIndex => $mat): 
                                                        $itemAmount = (float)($mat['amount'] ?? 1);
                                                        $itemSalePrice = (float)($mat['saleprice'] ?? 0);
                                                        $itemSaleCur = $mat['salecur'] ?? 'TRY';
                                                        $itemTotal = (float)($mat['total_price'] ?? ($itemAmount * $itemSalePrice));
                                                    ?>
                                                        <tr>
                                                            <td class="text-center text-muted"><?= $itemIndex + 1 ?></td>
                                                            <td>
                                                                <span class="badge badge-light border font-11">
                                                                    <?= !empty($mat['stokKodu']) ? htmlspecialchars($mat['stokKodu'], ENT_QUOTES, 'UTF-8') : '-' ?>
                                                                </span>
                                                            </td>
                                                            <td class="font-weight-500 text-dark">
                                                                <?= htmlspecialchars($mat['title'] ?? 'Ürün / Hizmet', ENT_QUOTES, 'UTF-8') ?>
                                                            </td>
                                                            <td class="text-right font-weight-bold text-dark"><?= number_format($itemAmount, 2, ',', '.') ?></td>
                                                            <td class="text-muted"><?= htmlspecialchars($mat['unit'] ?? 'Adet', ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td class="text-right"><?= number_format($itemSalePrice, 2, ',', '.') ?> <?= htmlspecialchars($itemSaleCur, ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td class="text-right font-weight-bold text-dark"><?= number_format($itemTotal, 2, ',', '.') ?> <?= htmlspecialchars($itemSaleCur, ENT_QUOTES, 'UTF-8') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot style="background: #f8fafc; border-top: 2px solid #cbd5e1;">
                        <tr class="font-weight-bold">
                            <td colspan="5" class="text-right font-13 text-uppercase text-secondary py-12">
                                SEÇİLEN VE FİLTRELENEN İCMAL TOPLAMI:
                            </td>
                            <td class="text-center font-13" id="icmalFooterItems">
                                <?= $runningItemsTotal ?> Kalem
                            </td>
                            <td colspan="3" class="text-right font-12 text-muted">
                                <span id="icmalFooterOffersCount"><?= count($customerOffers) ?></span> Teklif Seçili
                            </td>
                            <td class="text-right font-16 text-primary" id="icmalFooterTotal">
                                <?= number_format($runningConsolidatedTotal, 2, ',', '.') ?> ₺
                            </td>
                            <td class="action-cell"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Alt Bilgi Akordiyon Kartı -->
            <div class="card mt-3 mb-2 border-radius-10 border icmal-accordion-card" style="border-color: #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                <div class="card-header d-flex align-items-center justify-content-between p-3" 
                     style="background: #ffffff; cursor: pointer; border-bottom: 1px solid #e2e8f0;" 
                     data-toggle="collapse" data-target="#collapseIcmalFooter" aria-expanded="false" aria-controls="collapseIcmalFooter">
                    <div class="d-flex align-items-center" style="gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                            <i class="fa fa-list-ol"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 font-14 weight-600 text-dark">Teklif İcmali Alt Bilgi / Şartlar ve Notlar</h6>
                            <small class="text-muted font-11">İcmal belgesi altında yer alacak şartlar, ödeme ve dipnotları şablondan seçebilir veya düzenleyebilirsiniz.</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center" style="gap: 10px;">
                        <span class="badge badge-light border font-11 text-muted">Şablon & Düzenleyici</span>
                        <i class="fa fa-chevron-down font-12 text-muted accordion-chevron"></i>
                    </div>
                </div>
                <div id="collapseIcmalFooter" class="collapse">
                    <div class="card-body p-3" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
                        <div class="form-group mb-3">
                            <label class="weight-600 font-12 mb-1 text-secondary" for="icmalFooterTemplate">Alt Bilgi Şablonu Seç</label>
                            <div class="input-group">
                                <?php offerTemplate('icmalFooterTemplate', '', 'Footer', 'form-control select2'); ?>
                                <a href="index.php?p=offer-templates&type=Footer" target="_blank" class="btn btn-add-template" data-tooltip="Yeni Şablon Eklemek için tıklayınız!" data-tooltip-location="left">
                                    <i class="fa fa-plus"></i>
                                </a>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="weight-600 font-12 mb-1 text-secondary" for="icmalFooterContent">Alt Bilgi Açıklama Metni</label>
                            <div id="icmalFooterContentWrapper" class="offerFooterContent html-editor">
                                <textarea id="icmalFooterContent" name="icmalFooterContent" class="textarea_editor form-control" style="display: none !important;" placeholder="Alt bilgi açıklaması..."><p><strong>1.</strong> Fiyatlarımıza KDV dahildir/dahil edilmiştir.</p><p><strong>2.</strong> Ödeme Vadesi: Sipariş onayı ile birlikte belirlenen ödeme planına göredir.</p><p><strong>3.</strong> Teklif Geçerlilik Süresi: Teklif tarihinden itibaren 15 gündür.</p></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
