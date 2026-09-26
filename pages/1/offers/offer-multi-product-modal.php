<!-- Geniş & Kullanıcı Dostu Toplu Ürün Ekleme Modalı -->
<div class="modal fade" id="multiProductPickerModal" tabindex="-1" role="dialog" aria-labelledby="multiProductPickerModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered multi-product-dialog" role="document">
        <div class="modal-content quick-modal-content">
            
            <!-- Modal Başlık (Header - Aktif Tema Rengi) -->
            <div class="modal-header quick-modal-header">
                <div class="d-flex align-items-center" style="gap: 14px;">
                    <div class="quick-modal-icon-badge">
                        <i class="fa fa-th-list"></i>
                    </div>
                    <div>
                        <h5 class="modal-title quick-modal-title" id="multiProductPickerModalTitle">Toplu Ürün Ekle</h5>
                        <p class="quick-modal-subtitle">Katalogdan birden fazla ürün ve miktar seçip tek seferde teklif tablosuna aktarabilirsiniz.</p>
                    </div>
                </div>
                <button type="button" class="quick-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <!-- Modal Gövdesi (Body) -->
            <div class="modal-body quick-modal-body p-4">
                
                <!-- Arama ve Hızlı Seçim Araç Çubuğu -->
                <div class="d-flex align-items-center justify-content-between mb-3 gap-3 flex-wrap">
                    <div class="quick-input-wrap" style="flex: 1; min-width: 320px;">
                        <i class="fa fa-search quick-input-icon"></i>
                        <input type="text" id="multiProductSearchInput" class="form-control quick-input" placeholder="Ürün adı, stok kodu veya barkod ile canlı arayın..." autocomplete="off">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="btnMultiSelectAll" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px; height: 42px; font-weight: 600; padding: 0 16px;">
                            <i class="fa fa-check-square-o mr-1"></i> Tümünü Seç
                        </button>
                        <button type="button" id="btnMultiClearAll" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px; height: 42px; font-weight: 600; padding: 0 16px;">
                            <i class="fa fa-square-o mr-1"></i> Temizle
                        </button>
                        <div class="badge px-3 py-2 text-white" id="multiSelectedCounter" style="font-size: 13.5px; font-weight: 700; border-radius: 8px; height: 42px; display: flex; align-items: center; background: var(--theme-primary, #2563eb); box-shadow: 0 2px 8px var(--theme-primary-shadow, rgba(37, 99, 235, 0.3));">
                            0 Kalem Seçildi
                        </div>
                    </div>
                </div>

                <!-- Ürün Tablosu / Listesi -->
                <div class="multi-product-table-wrapper">
                    <table class="table table-hover mb-0 multi-product-table" id="multiProductTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">Seç</th>
                                <th style="width: 140px;">Stok Kodu</th>
                                <th>Ürün Adı / Açıklama</th>
                                <th style="width: 100px;" class="text-center">Birim</th>
                                <th style="width: 140px;" class="text-right">Satış Fiyatı</th>
                                <th style="width: 140px;" class="text-right">Alış Fiyatı</th>
                                <th style="width: 150px;" class="text-center">Miktar</th>
                            </tr>
                        </thead>
                        <tbody id="multiProductListBody">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                                    <div class="font-14 weight-500">Ürünler yükleniyor...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Modal Alt Bilgi & Butonlar (Footer) -->
            <div class="modal-footer quick-modal-footer">
                <button type="button" class="btn btn-quick-cancel" data-dismiss="modal" data-bs-dismiss="modal">
                    <i class="fa fa-times"></i> Vazgeç
                </button>
                <button type="button" id="btnAddSelectedProductsToOffer" class="btn btn-quick-submit" disabled>
                    <i class="fa fa-plus-circle"></i> <span>Seçilenleri Teklife Aktar (0)</span>
                </button>
            </div>

        </div>
    </div>
</div>

<style>
/* Geniş Ekran Modal Boyutlandırması */
.multi-product-dialog {
    max-width: 94vw !important;
    width: 1350px !important;
    margin: 1.5rem auto !important;
}

@media (min-width: 1400px) {
    .multi-product-dialog {
        width: 1380px !important;
    }
}

.multi-product-table-wrapper {
    max-height: calc(78vh - 160px);
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.02);
}

.multi-product-table {
    font-size: 13.5px;
    width: 100%;
}

.multi-product-table thead {
    position: sticky;
    top: 0;
    background: #f1f5f9;
    z-index: 10;
    border-bottom: 2px solid #cbd5e1;
}

.multi-product-table thead th {
    padding: 12px 14px;
    font-weight: 700;
    color: #334155;
    white-space: nowrap;
    border-top: none;
}

.multi-product-table tbody tr {
    transition: background 0.15s ease;
}

.multi-product-table tbody tr.table-active,
.multi-product-table tbody tr:hover {
    background: var(--theme-primary-light, #eff6ff) !important;
}

.multi-product-table tbody td {
    padding: 10px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

/* Dark Mode Desteği */
.dark-mode .multi-product-table-wrapper {
    background: #1e293b !important;
    border-color: #334155 !important;
}

.dark-mode .multi-product-table thead {
    background: #0f172a !important;
    border-bottom-color: #334155 !important;
}

.dark-mode .multi-product-table thead th {
    color: #cbd5e1 !important;
}

.dark-mode .multi-product-table tbody td {
    border-bottom-color: #334155 !important;
    color: #f1f5f9 !important;
}

.dark-mode .multi-product-table tbody tr.table-active,
.dark-mode .multi-product-table tbody tr:hover {
    background: #334155 !important;
}
</style>
