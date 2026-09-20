<!-- Excel'den Yükle Modalı -->
<div class="modal fade" id="uploadfromxlsModal" tabindex="-1" role="dialog" aria-labelledby="uploadfromxlsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-light border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mr-3" style="width: 38px; height: 38px;">
                        <i class="fa fa-file-excel-o"></i>
                    </div>
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0" id="uploadfromxlsModalTitle">Excel'den Cihaz Yükle</h5>
                        <small class="text-muted">Excel listesindeki cihazları rapora aktarın</small>
                    </div>
                </div>
                <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close" style="outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group mb-3">
                    <label class="font-weight-600 text-dark mb-2">Excel Dosyası Seçin (.xls, .xlsx)</label>
                    <input type="file" name="file_name" id="file_name" class="form-control" accept=".xls,.xlsx" style="height: auto; padding: 10px; border-radius: 10px;">
                    <div id="lblWarning" class="alert alert-warning py-2 px-3 mt-2 font-13" style="display:none;"></div>
                </div>

                <div class="p-3 bg-light rounded border d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-info-circle text-primary font-18 mr-2"></i>
                        <span class="font-13 text-secondary">Örnek veri şablonunu indirin:</span>
                    </div>
                    <a href="pages/1/reports/ysc/sablon.xlsx" class="btn btn-sm btn-outline-primary font-12" download>
                        <i class="fa fa-download mr-1"></i> Şablonu İndir
                    </a>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-3 px-4">
                <button type="button" class="btn btn-secondary px-4 font-13" data-dismiss="modal" style="border-radius: 8px;">Vazgeç</button>
                <button type="button" class="btn btn-success px-4 font-13" id="uploadFromXlsButton" data-dismiss="modal" style="border-radius: 8px;">
                    <i class="fa fa-upload mr-1"></i> Dosyayı Yükle
                </button>
            </div>
        </div>
    </div>
</div>