<?php
use App\Helper\customer;

// il-bolge.json dosyasından 81 ili çek
$provinces = [];
$candidatePaths = [
    dirname(__DIR__, 3) . '/src/scripts/il-bolge.json',
    dirname(__DIR__, 2) . '/src/scripts/il-bolge.json',
    __DIR__ . '/../../../src/scripts/il-bolge.json',
    ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/src/scripts/il-bolge.json'
];

foreach ($candidatePaths as $path) {
    if (!empty($path) && file_exists($path)) {
        $jsonContent = @file_get_contents($path);
        if ($jsonContent) {
            $decoded = json_decode($jsonContent, true);
            if (!empty($decoded) && is_array($decoded)) {
                $provinces = $decoded;
                break;
            }
        }
    }
}
?>
<!-- Modern, Temaya Duyarlı & Kullanıcı Dostu Hızlı Firma Ekleme Modalı -->
<div class="modal fade" id="quickAddCustomerModal" tabindex="-1" role="dialog" aria-labelledby="quickAddCustomerModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content quick-modal-content">
            
            <!-- Modal Başlık (Header - Aktif Tema Rengi) -->
            <div class="modal-header quick-modal-header">
                <div class="d-flex align-items-center" style="gap: 14px;">
                    <div class="quick-modal-icon-badge">
                        <i class="fa fa-building-o"></i>
                    </div>
                    <div>
                        <h5 class="modal-title quick-modal-title" id="quickAddCustomerModalTitle">Hızlı Firma Ekle</h5>
                        <p class="quick-modal-subtitle">Teklif için yeni firma kaydını bu pencereden anında oluşturabilirsiniz.</p>
                    </div>
                </div>
                <button type="button" class="quick-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <!-- Modal Gövdesi (Body) -->
            <div class="modal-body quick-modal-body">
                <form id="quickCustomerForm" autocomplete="off">
                    <input type="hidden" name="action" value="create">

                    <!-- 1. BÖLÜM: TEMEL FİRMA & YETKİLİ BİLGİLERİ -->
                    <div class="quick-section-card mb-3">
                        <div class="quick-section-title">
                            <i class="fa fa-id-card-o"></i> <span>Temel Firma & Yetkili Bilgileri</span>
                        </div>
                        <div class="row">
                            <!-- Firma Adı (Tam Genişlik) -->
                            <div class="col-12 mb-3">
                                <label for="quick_company" class="quick-form-label">
                                    <span class="quick-required-star">*</span> Firma Adı / Tam Ticari Ünvan
                                </label>
                                <div class="quick-input-wrap">
                                    <i class="fa fa-building quick-input-icon"></i>
                                    <input type="text" required name="company" id="quick_company" class="form-control quick-input" placeholder="Örn: ABC Yangın ve Güvenlik Sistemleri San. Tic. Ltd. Şti.">
                                </div>
                            </div>

                            <!-- Yetkili Ad-Soyad -->
                            <div class="col-md-6 mb-2">
                                <label for="quick_yetkili" class="quick-form-label">
                                    Firma Yetkilisi
                                </label>
                                <div class="quick-input-wrap">
                                    <i class="fa fa-user quick-input-icon"></i>
                                    <input type="text" name="yetkili" id="quick_yetkili" class="form-control quick-input" placeholder="Örn: Ahmet Yılmaz">
                                </div>
                            </div>

                            <!-- Telefon (GSM) -->
                            <div class="col-md-6 mb-2">
                                <label for="quick_cgsm" class="quick-form-label">
                                    <span class="quick-required-star">*</span> Telefon (GSM / Sabit)
                                </label>
                                <div class="quick-input-wrap">
                                    <i class="fa fa-phone quick-input-icon"></i>
                                    <input type="tel" required name="cgsm" id="quick_cgsm" class="form-control quick-input" placeholder="05XXXXXXXXX" maxlength="11" minlength="10">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. BÖLÜM: İLETİŞİM & TİCARİ DETAYLAR -->
                    <div class="quick-section-card mb-3">
                        <div class="quick-section-title">
                            <i class="fa fa-sliders"></i> <span>İletişim & Ticari Detaylar</span>
                        </div>
                        <div class="row">
                            <!-- E-Posta Adresi -->
                            <div class="col-md-6 mb-3">
                                <label for="quick_cemail" class="quick-form-label">
                                    E-Posta Adresi
                                </label>
                                <div class="quick-input-wrap">
                                    <i class="fa fa-envelope quick-input-icon"></i>
                                    <input type="email" name="cemail" id="quick_cemail" class="form-control quick-input" placeholder="ornek@firma.com">
                                </div>
                            </div>

                            <!-- Ödeme Vadesi -->
                            <div class="col-md-6 mb-3">
                                <label for="quick_vade" class="quick-form-label">
                                    Ödeme Vadesi
                                </label>
                                <div class="quick-select-wrap">
                                    <select name="vade" id="quick_vade" class="form-control modal-select2-tags" data-placeholder="Ödeme Vadesi Seçiniz veya Yazınız" style="width: 100%;">
                                        <option value="">Ödeme Vadesi Seçiniz veya Yazınız</option>
                                        <option value="Peşin">Peşin</option>
                                        <option value="15 Gün">15 Gün</option>
                                        <option value="30 Gün">30 Gün</option>
                                        <option value="45 Gün">45 Gün</option>
                                        <option value="60 Gün">60 Gün</option>
                                        <option value="90 Gün">90 Gün</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Firma Grubu -->
                            <div class="col-md-6 mb-2">
                                <label for="categoryName" class="quick-form-label">
                                    Firma Grubu
                                </label>
                                <div class="quick-select-wrap">
                                    <?php echo customer::getCustomerGroups("categoryName", '', 'form-control modal-select2'); ?>
                                </div>
                            </div>

                            <!-- Satış Temsilcisi -->
                            <div class="col-md-6 mb-2">
                                <label for="quick_represant" class="quick-form-label">
                                    Satış Temsilcisi
                                </label>
                                <div class="quick-input-wrap">
                                    <i class="fa fa-user-circle quick-input-icon"></i>
                                    <input type="text" name="represant" id="quick_represant" class="form-control quick-input" value="<?php echo htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Temsilci Adı">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. BÖLÜM: LOKASYON & AÇIK ADRES -->
                    <div class="quick-section-card">
                        <div class="quick-section-title">
                            <i class="fa fa-map-marker"></i> <span>Lokasyon & Adres Bilgileri</span>
                        </div>
                        <div class="row">
                            <!-- İl -->
                            <div class="col-md-6 mb-3">
                                <label for="quick_il" class="quick-form-label">
                                    <span class="quick-required-star">*</span> İl
                                </label>
                                <div class="quick-select-wrap">
                                    <select required name="il" id="quick_il" class="form-control modal-select2" data-placeholder="İl Seçiniz" style="width: 100%;">
                                        <option value="">İl Seçiniz</option>
                                        <?php if (!empty($provinces)): ?>
                                            <?php foreach ($provinces as $p): ?>
                                                <option value="<?php echo htmlspecialchars($p['il'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($p['il'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- İlçe -->
                            <div class="col-md-6 mb-3">
                                <label for="quick_ilce" class="quick-form-label">
                                    İlçe
                                </label>
                                <div class="quick-select-wrap">
                                    <select name="ilce" id="quick_ilce" class="form-control modal-select2" data-placeholder="İlçe Seçiniz" style="width: 100%;">
                                        <option value="">İlçe Seçiniz</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Açık Adres -->
                            <div class="col-12">
                                <label for="quick_customer_address" class="quick-form-label">
                                    <span class="quick-required-star">*</span> Açık Adres
                                </label>
                                <div class="quick-input-wrap">
                                    <textarea required name="customer_address" id="quick_customer_address" class="form-control quick-textarea" rows="2" placeholder="Mahalle, cadde, sokak, kapı no ve diğer adres detayları..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            <!-- Modal Alt Bilgi & Butonlar (Footer) -->
            <div class="modal-footer quick-modal-footer">
                <button type="button" class="btn btn-quick-cancel" data-dismiss="modal" data-bs-dismiss="modal">
                    <i class="fa fa-times"></i> Vazgeç
                </button>
                <button type="button" id="btnSaveQuickCustomer" class="btn btn-quick-submit">
                    <i class="fa fa-check-circle"></i> <span>Firmayı Kaydet & Teklife Seç</span>
                </button>
            </div>

        </div>
    </div>
</div>

<style>
/* ==========================================================================
   HIZLI FİRMA EKLEME MODALI - DİNAMİK TEMA VE DARK MODE UYUMLU UI/UX
   ========================================================================== */

.quick-modal-content {
    border-radius: 18px !important;
    border: none !important;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35) !important;
    overflow: hidden !important;
    background: #ffffff !important;
}

/* Header - Aktif Tema Rengi ile Dinamik */
.quick-modal-header {
    background: linear-gradient(135deg, #1e293b 0%, var(--theme-primary, #2563eb) 100%) !important;
    padding: 20px 24px !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
}

.quick-modal-icon-badge {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.quick-modal-title {
    color: #ffffff !important;
    font-size: 16.5px !important;
    font-weight: 700 !important;
    margin: 0 !important;
    letter-spacing: -0.01em;
    line-height: 1.2;
}

.quick-modal-subtitle {
    color: rgba(255, 255, 255, 0.88) !important;
    font-size: 12.5px !important;
    margin: 3px 0 0 0 !important;
    font-weight: 400;
}

.quick-modal-close {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #ffffff !important;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
    outline: none !important;
    padding: 0;
}

.quick-modal-close:hover {
    background: rgba(239, 68, 68, 0.9);
    border-color: rgba(239, 68, 68, 1);
    color: #ffffff !important;
    transform: scale(1.05);
}

/* Modal Body */
.quick-modal-body {
    padding: 20px 24px !important;
    background: #f8fafc !important;
    max-height: calc(85vh - 140px);
    overflow-y: auto;
}

/* Section Card */
.quick-section-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 16px 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.quick-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 14px;
    padding-bottom: 8px;
    border-bottom: 1px dashed #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: 0.01em;
}

.quick-section-title i {
    font-size: 15px;
    color: var(--theme-primary, #2563eb);
}

/* Labels */
.quick-form-label {
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 5px;
    display: block;
}

.quick-required-star {
    color: #ef4444;
    font-weight: 700;
    margin-right: 2px;
}

/* Input Wrappers & Fields */
.quick-input-wrap {
    position: relative;
    width: 100%;
}

.quick-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
    pointer-events: none;
    transition: color 0.2s ease;
    z-index: 2;
}

.quick-input {
    height: 42px !important;
    border-radius: 10px !important;
    border: 1px solid #cbd5e1 !important;
    padding-left: 38px !important;
    padding-right: 14px !important;
    font-size: 13.5px !important;
    color: #0f172a !important;
    background: #ffffff !important;
    transition: all 0.2s ease !important;
    box-shadow: none !important;
}

.quick-input:focus {
    border-color: var(--theme-primary, #2563eb) !important;
    box-shadow: 0 0 0 3px var(--theme-primary-shadow, rgba(37, 99, 235, 0.18)) !important;
    background: #ffffff !important;
}

.quick-input:focus + .quick-input-icon,
.quick-input-wrap:focus-within .quick-input-icon {
    color: var(--theme-primary, #2563eb) !important;
}

.quick-textarea {
    border-radius: 10px !important;
    border: 1px solid #cbd5e1 !important;
    padding: 10px 14px !important;
    font-size: 13px !important;
    color: #0f172a !important;
    background: #ffffff !important;
    transition: all 0.2s ease !important;
    box-shadow: none !important;
    resize: vertical;
    min-height: 58px;
}

.quick-textarea:focus {
    border-color: var(--theme-primary, #2563eb) !important;
    box-shadow: 0 0 0 3px var(--theme-primary-shadow, rgba(37, 99, 235, 0.18)) !important;
}

/* Select2 Container Overrides inside Modal */
.quick-select-wrap .select2-container--default .select2-selection--single {
    height: 42px !important;
    border-radius: 10px !important;
    border: 1px solid #cbd5e1 !important;
    padding: 6px 12px !important;
    background: #ffffff !important;
    transition: all 0.2s ease !important;
    display: flex !important;
    align-items: center !important;
}

.quick-select-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px !important;
    font-size: 13px !important;
    color: #0f172a !important;
    padding-left: 0 !important;
}

.quick-select-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    right: 10px !important;
}

.quick-select-wrap .select2-container--default.select2-container--focus .select2-selection--single,
.quick-select-wrap .select2-container--default.select2-container--open .select2-selection--single {
    border-color: var(--theme-primary, #2563eb) !important;
    box-shadow: 0 0 0 3px var(--theme-primary-shadow, rgba(37, 99, 235, 0.18)) !important;
}

/* Modal Footer */
.quick-modal-footer {
    background: #ffffff !important;
    padding: 16px 24px !important;
    border-top: 1px solid #e2e8f0 !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
}

.btn-quick-cancel {
    border-radius: 10px !important;
    height: 42px !important;
    padding: 0 20px !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    color: #64748b !important;
    background: #f1f5f9 !important;
    border: 1px solid #cbd5e1 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 7px !important;
    transition: all 0.2s ease !important;
}

.btn-quick-cancel:hover {
    background: #e2e8f0 !important;
    color: #334155 !important;
}

.btn-quick-submit {
    border-radius: 10px !important;
    height: 42px !important;
    padding: 0 24px !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    color: #ffffff !important;
    background: var(--theme-primary, #2563eb) !important;
    border: 1px solid var(--theme-primary, #2563eb) !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    box-shadow: 0 4px 14px var(--theme-primary-shadow, rgba(37, 99, 235, 0.35)) !important;
    transition: all 0.2s ease !important;
}

.btn-quick-submit:hover {
    background: var(--theme-primary-hover, #1d4ed8) !important;
    border-color: var(--theme-primary-hover, #1d4ed8) !important;
    box-shadow: 0 6px 18px var(--theme-primary-shadow, rgba(37, 99, 235, 0.45)) !important;
    transform: translateY(-1px);
    color: #ffffff !important;
}

.btn-quick-submit:active {
    transform: translateY(0);
}

.btn-quick-submit:disabled {
    opacity: 0.7 !important;
    cursor: not-allowed !important;
    transform: none !important;
}

/* ==============================================================
   DARK MODE UYUMLULUĞU
   ============================================================== */
.dark-mode .quick-modal-content {
    background: #1e293b !important;
    color: #f1f5f9 !important;
    border: 1px solid #334155 !important;
}

.dark-mode .quick-modal-body {
    background: #0f172a !important;
}

.dark-mode .quick-section-card {
    background: #1e293b !important;
    border-color: #334155 !important;
    box-shadow: none !important;
}

.dark-mode .quick-section-title {
    color: #f8fafc !important;
    border-bottom-color: #334155 !important;
}

.dark-mode .quick-form-label {
    color: #cbd5e1 !important;
}

.dark-mode .quick-input,
.dark-mode .quick-textarea {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}

.dark-mode .quick-input:focus,
.dark-mode .quick-textarea:focus {
    background: #0f172a !important;
    border-color: var(--theme-primary, #3b82f6) !important;
}

.dark-mode .quick-input::placeholder,
.dark-mode .quick-textarea::placeholder {
    color: #64748b !important;
}

.dark-mode .quick-input-icon {
    color: #64748b !important;
}

.dark-mode .quick-select-wrap .select2-container--default .select2-selection--single {
    background: #0f172a !important;
    border-color: #334155 !important;
}

.dark-mode .quick-select-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #f8fafc !important;
}

.dark-mode .quick-modal-footer {
    background: #1e293b !important;
    border-top-color: #334155 !important;
}

.dark-mode .btn-quick-cancel {
    background: #334155 !important;
    border-color: #475569 !important;
    color: #cbd5e1 !important;
}

.dark-mode .btn-quick-cancel:hover {
    background: #475569 !important;
    color: #ffffff !important;
}
</style>
