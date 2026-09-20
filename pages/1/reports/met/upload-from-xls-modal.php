<!-- Excel Yükleme Modal -->
<div class="modal fade" id="uploadfromxlsModal" tabindex="-1" role="dialog" aria-labelledby="uploadfromxlsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title font-weight-bold text-dark d-flex align-items-center" id="uploadfromxlsModalTitle" style="font-size: 15px;">
                    <i class="fa fa-file-excel-o text-success mr-2 font-18"></i> Cihazları Excel'den Yükle
                </h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group mb-3">
                    <label class="font-weight-600 text-dark mb-1 font-13">Yüklenecek Excel Dosyası</label>
                    <input type="file" name="file_name" id="file_name" class="form-control" accept=".xls,.xlsx,.csv" style="height: 38px; padding: 5px 10px; font-size: 13px;">
                    <small class="text-muted mt-1 d-block font-12">Yalnızca .xlsx, .xls veya .csv uzantılı dosyalar desteklenir.</small>
                </div>
                
                <div class="alert alert-info py-2 px-3 d-flex align-items-center mb-2" style="border-radius: 8px; font-size: 13px;">
                    <i class="fa fa-info-circle mr-2 font-16 text-info"></i>
                    <div>
                        Örnek şablon dosyasını indirmek için 
                        <a href="templates/cihaz_kontrol_sablonu.xlsx" download class="font-weight-bold text-primary text-underline">buraya tıklayın</a>.
                    </div>
                </div>

                <div id="lblWarning" class="alert alert-danger py-2 px-3 font-13 mb-0" style="display: none; border-radius: 8px;"></div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary font-13 px-3" data-dismiss="modal" data-bs-dismiss="modal">
                    <i class="fa fa-times mr-1"></i> Vazgeç
                </button>
                <button type="button" class="btn btn-sm btn-primary font-13 px-3" id="uploadFromXlsButton">
                    <i class="fa fa-upload mr-1"></i> Yükle ve Aktar
                </button>
            </div>
        </div>
    </div>
</div>
