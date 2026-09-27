<?php

use App\Model\ProductModel;
use App\Helper\Security;

$Products = new ProductModel();

$enc_id = $_GET["id"] ?? 0;
$id = is_numeric($enc_id) ? (int)$enc_id : (int)Security::decrypt($enc_id);
permcontrol($id > 0 ? 'productedit' : 'productadd');

$product = $Products->find($id);

// Yeni kayıt değil ve ürün yoksa listeye dön
if (!$product && $id != 0) {
    header("Location: index.php?p=products/list");
}

?>

<style>
.products-general-grid {
    grid-template-columns: 4fr 4fr 2fr !important;
}
@media (max-width: 991px) {
    .products-general-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="products-manage-wrapper">
    <!-- Header Card -->
    <div class="premium-header-card animate-fade-in">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fa fa-cube"></i>
                </div>
                <div class="header-title">
                    <h4><?php echo $pdat["p_title"]; ?></h4>
                    <?php if ($id != 0): ?>
                        <span class="header-number-badge">
                            <i class="fa fa-tag"></i> Stok Kodu: <?php echo htmlspecialchars($product->StokKodu ?? ''); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="header-actions">
                <a href="index.php?p=products/list" class="btn-header btn-header-list">
                    <i class="fa fa-list"></i> Ürün Listesi
                </a>
                <button type="button" id="submitButton" class="btn-header btn-header-save">
                    <i class="fa fa-save"></i> Kaydet
                </button>
            </div>
        </div>
    </div>

    <form action="" method="POST" id="productForm">
        <input type="hidden" name="id" id="id" class="form-control mb-3" value="<?php echo $enc_id ?>">

        <!-- Kart 1: Genel Bilgiler -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-blue">
                    <i class="fa fa-info-circle"></i>
                </div>
                <div>
                    <h5>Genel Bilgiler</h5>
                    <p>Ürün veya hizmetin temel kimlik bilgileri</p>
                </div>
            </div>
            <div class="form-grid products-general-grid">
                <div class="form-field">
                    <label for="urunAdi"><font color="red">(*)</font> Ürün/Hizmet Adı</label>
                    <input required id="urunAdi" name="urunAdi" value="<?php echo htmlspecialchars($product->Adi ?? '', ENT_QUOTES) ?>" class="form-control" type="text" placeholder="Ürün veya hizmet adı giriniz">
                </div>
                <div class="form-field">
                    <label for="StokKodu">Stok Kodu</label>
                    <input id="StokKodu" name="StokKodu" value="<?php echo htmlspecialchars($product->StokKodu ?? '', ENT_QUOTES) ?>" class="form-control" type="text" placeholder="Stok kodu giriniz">
                </div>
                <div class="form-field">
                    <label for="Birimi"><font color="red">(*)</font> Birimi</label>
                    <?php OlcuBirimleriValID('Birimi', $product->Birimi ?? 0) ?>
                </div>
            </div>
        </div>

        <!-- Kart 2: Fiyat Tanımları -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-green">
                    <i class="fa fa-money"></i>
                </div>
                <div>
                    <h5>Fiyat Tanımları</h5>
                    <p>Alış ve satış fiyatlandırma detayları</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="AlisFiyati">Alış Fiyatı</label>
                    <div class="input-group">
                        <input id="AlisFiyati" name="AlisFiyati" value="<?php echo htmlspecialchars($product->AlisFiyati ?? '', ENT_QUOTES) ?>" class="form-control" type="text" placeholder="0.00" style="flex: 2 !important;">
                        <div style="flex: 1 !important;">
                            <?php ParaBirimleri('AlisParaBirimi', $product->AlisParaBirimi ?? '', "curAlis") ?>
                        </div>
                    </div>
                </div>
                <div class="form-field">
                    <label for="SatisFiyati">Satış Fiyatı</label>
                    <div class="input-group">
                        <input id="SatisFiyati" name="SatisFiyati" value="<?php echo htmlspecialchars($product->SatisFiyati ?? '', ENT_QUOTES) ?>" class="form-control" type="text" placeholder="0.00" style="flex: 2 !important;">
                        <div style="flex: 1 !important;">
                            <?php ParaBirimleri('SatisParaBirimi', $product->SatisParaBirimi ?? '', "curSatis") ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Açıklama ve Ek Detaylar -->
        <div class="form-card mb-4 animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-purple">
                    <i class="fa fa-align-left"></i>
                </div>
                <div>
                    <h5>Açıklama ve Ek Detaylar</h5>
                    <p>Ürün veya hizmete ait ek notlar ve açıklamalar</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-field full-width">
                    <label for="aciklama">Açıklama</label>
                    <textarea id="aciklama" name="aciklama" class="form-control" placeholder="Açıklama yazınız..."><?php echo htmlspecialchars($product->Aciklama ?? '', ENT_QUOTES) ?></textarea>
                </div>
            </div>
        </div>
    </form>

    <?php if ($id != 0): ?>
        <!-- Kart 4: Sistem Bilgileri (Düzenleme Modunda Göster) -->
        <div class="form-card animate-fade-in">
            <div class="form-card-header">
                <div class="card-icon card-icon-orange">
                    <i class="fa fa-history"></i>
                </div>
                <div>
                    <h5>Sistem Bilgileri</h5>
                    <p>Kayıt detayları ve işlem geçmişi</p>
                </div>
            </div>
            <div class="audit-trail-card">
                <div class="audit-row">
                    <div class="audit-icon"><i class="fa fa-calendar"></i></div>
                    <div style="display:flex; flex-direction:column;">
                        <span class="audit-label">Eklenme Tarihi</span>
                        <span class="audit-value"><?php echo $product->OlusturmaTarihi ?? '' ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
