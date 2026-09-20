<!-- Çoklu Satır Ekleme Modal -->
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="multiRowModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title font-weight-bold text-dark d-flex align-items-center" id="multiRowModalTitle" style="font-size: 15px;">
                    <i class="fa fa-plus-circle text-primary mr-2 font-18"></i> Çoklu Satır Ekle
                </h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group mb-0">
                    <label for="eklenecek_satir_sayisi" class="font-weight-600 text-dark mb-1 font-13">Eklenecek Satır Sayısı</label>
                    <input type="number" min="1" max="100" class="form-control text-center font-weight-bold font-15" id="eklenecek_satir_sayisi" value="5" placeholder="Örn: 10">
                    <small class="text-muted mt-1 d-block font-12">Tek seferde en fazla 100 satır eklenebilir.</small>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary font-13 px-3" data-dismiss="modal" data-bs-dismiss="modal">
                    <i class="fa fa-times mr-1"></i> Vazgeç
                </button>
                <button type="button" class="btn btn-sm btn-primary font-13 px-3" id="addMultiRowModal">
                    <i class="fa fa-plus mr-1"></i> Satırları Ekle
                </button>
            </div>
        </div>
    </div>
</div>
