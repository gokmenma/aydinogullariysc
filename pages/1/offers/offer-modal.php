
<!-- Modal -->
<div class="modal show" id="staticBackdrop">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="staticBackdropLabel">Listeden ürün seçiniz!</h6>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php generateProductSelect("productName[]", "") ?>
                    <input type="hidden" id="rowID">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-danger" onclick="getProductInfoOffer()">Seç</button>
                </div>
            </div>
        </div>
    </div>