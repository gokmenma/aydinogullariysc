<?php

$ois = @$_GET["oid"];

if (@$_GET["mode"] == "delete" and @$_GET["fid"]) {
  $cek = $ac->prepare("SELECT * FROM files WHERE id = ?");
  $cek->execute(array($_GET["fid"]));
  $cca = $cek->fetch(PDO::FETCH_ASSOC);

  unlink($cca["filename"]);

  $deleteit = $ac->prepare("DELETE FROM files WHERE id = ?");
  $deleteit->execute(array($_GET["fid"]));

  header("Location: index.php?p=edit-offer&type=fileupload&st=delete&oid=$ois");
}

?>
<div class="content pd-20 bg-white border-radius-16 box-shadow mb-30">
  <?php
  if (@$_GET["st"] == "newsuccess") {



  ?>
    <div class="alert alert-success" role="alert">

    </div>
  <?php
  }
  if (@$_GET["type"] == "delete") {
  ?>
    <div class="alert alert-success" role="alert">
      Silme işlemi başarılı!
    </div>
  <?php
  }
  ?>
  <div class="clearfix mb-20">
    <div class="pull-left">
      <h5 class="text-blue">Teklif Dökümanları: #<?php echo $ois; ?></h5>
    </div>
  </div>
  <table class="data-table stripe hover">
    <thead>
      <tr>
        <th scope="col">#Sıra</th>
        <th>#Teklif No:</th>
        <th>Dosya Adı</th>
        <th>Yüklenme Tarihi</th>
        <th>İşlemler</th>
      </tr>
    </thead>
    <tbody> <?php if (permtrue("seradd")) { ?>
        <input type="button" value="Yeni Dosya" data-toggle="modal" data-target="#exampleModal" type="button" class="btn btn-warning float-right mb-3">
      <?php } ?>
      <!-- Button trigger modal -->

      <!-- Modal -->
      <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Teklife Dosya Yükle</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">

              <script src="include/dropzone/dist/dropzone.js"></script>
              <link rel="stylesheet" type="text/css" href="include/dropzone/dist/dropzone.css">
              <input type="hidden" name="request" value="<?php echo $ois ?>">

              <form action="uploadoffer.php?oid='<?php echo $ois ?>'" method="POST" class="dropzone border-radius-16 mb-3" id="myAvesomeDropzone">


                <!-- <form method="POST" class="dropzone border-radius-16 mb-3" id="myAvesomeDropzone"> -->
              </form>

            </div>
            <div class="modal-footer mb-2">
              <!-- <button class="btn btn-secondary" data-dismiss="modal">Vazgeç</button> -->
              <a href="index.php?p=edit-offer&type=fileupload&insert=update&ccs=083y3&oid=<?php echo $ois; ?>&stx=updreg" class="btn btn-primary">Kapat</a>
               <!-- <button data-dismiss="modal" class="btn btn-primary">Kapat</button>  -->

            </div>
          </div>
        </div>
      </div>


      <?php
      $cq = $ac->prepare("SELECT * FROM files WHERE oid = ? ORDER by id DESC");
      $cq->execute(array($ois));
      $kx = 1;
      while ($as = $cq->fetch(PDO::FETCH_ASSOC)) {


      ?>
        <tr>
          <td scope="row"><?php echo $kx; ?></td>
          <td scope="row">#<?php echo $ois; ?></td>
          <td><?php echo $as["filename"]; ?></td>
          <td><?php echo $as["regdate"]; ?></td>
          <td>

            <a href="projects/offers/<?php echo $as["filename"]; ?>"><span class="badge badge-success">İndir</span></a>

            &nbsp;
            <a onClick="return confirm('<?php echo $as["filename"]; ?> isimli dosyayı sistemden tamamen silmek istediğinize emin misiniz?')" href="index.php?p=edit-offer&type=fileupload&mode=delete&code=04md177&reg=true&md=active&oid=<?php echo $ois; ?>&fid=<?php echo $as["id"]; ?>"><span class="badge badge-danger">Sil</span></a><?php  ?>
          </td>

        </tr>
      <?php
        $kx = $kx + 1;
      } ?>
    </tbody>
  </table>
</div>


<script type="text/javascript">
  Dropzone.autoDiscover = false;

  var myDropzone = new Dropzone(".dropzone", {
    maxFilesize: 10,
    acceptedFiles: ".jpeg,.jpg,.png,.gif",
    addRemoveLinks: true,
    removedfile: function(file) {
      var fileName = file.name;

      $.ajax({
        type: 'POST',
        url: 'uploadoffer.php',
        data: {
          name: fileName,
          request: 2
        },
        success: function(data) {
          console.log('success: ' + data);
        }
      });

      var _ref;
      return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
    },
    error: function(file, errorMessage) {
      // Dosya yüklenirken bir hata oluştuğunda yapılacak işlemler
      console.error('Dosya yüklenirken hata oluştu: ' + errorMessage);
    }
  });
</script>