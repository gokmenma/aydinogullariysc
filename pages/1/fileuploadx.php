<?php
    
    $ois = @$_GET["oid"];
    if(@$_GET["insert"] == "new"){
       ?>
       <script src="include/dropzone/dist/dropzone.js"></script>
        <link rel="stylesheet" type="text/css" href="include/dropzone/dist/dropzone.css">
        <div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
             <a href="index.php?p=edit-offer&type=fileupload&oid=<?php echo $ois; ?>"><button style="float:right;" type="button" class="btn btn-success">Yüklenen dosyalar</button></a><br><br>
            <form action="upfileof.php?oid=<?php echo $ois; ?>" class="dropzone" id="my-awesome-dropzone"></form>

        </div>
       <?php
    }else{



    if(@$_GET["mode"] == "delete" AND @$_GET["fid"]){
        $cek = $ac->prepare("SELECT * FROM files WHERE id = ?");
        $cek->execute(array($_GET["fid"]));
        $cca = $cek->fetch(PDO::FETCH_ASSOC);

        unlink("projects/offers/".$cca["filename"]);

        $deleteit = $ac->prepare("DELETE FROM files WHERE id = ?");
        $deleteit->execute(array($_GET["fid"]));
        header("Location: index.php?p=edit-offer&type=fileupload&st=delete&oid=$ois");
    }

   
    

?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
    <?php
    if(@$_GET["st"] == "newsuccess" ){



?>
<div class="alert alert-success" role="alert">
                                
                            </div>
    <?php
}
    if(@$_GET["type"] == "delete"){
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
      <th>#Teklif No</th>
      <th>Dosya Adı</th>
      <th>Yüklenme Tarihi</th>
      <th>İşlemler</th>
    </tr>
  </thead>
  <tbody>   <?php if(permtrue("seradd")){?>
    <a href="index.php?p=edit-offer&type=fileupload&oid=<?php echo $ois; ?>&insert=new&ccs=083y3"><button style="float:right;" type="button" class="btn btn-success">Yeni Dosya Yükle</button></a> <?php } ?><br><br>
        <?php
            $cq = $ac->prepare("SELECT * FROM files WHERE oid = ? ORDER by id DESC");
            $cq->execute(array($ois));
            $kx = 1;
            while($as = $cq->fetch(PDO::FETCH_ASSOC)){


        ?>
        <tr>
      <td scope="row"><?php echo $kx;?></td>
      <td scope="row">#<?php echo $ois;?></td>
      <td><?php echo $as["filename"];?></td>
      <td><?php echo $as["regdate"];?></td>
      <td>
        
        <a href="projects/offers/<?php echo $as["filename"]; ?>"><span class="badge badge-success">İndir</span></a>
     
        &nbsp;
       <a onClick="return confirm('<?php echo $as["filename"]; ?> isimli dosyayı sistemden tamamen silmek istediğinize emin misiniz?')"href="index.php?p=edit-offer&type=fileupload&mode=delete&code=04md177&reg=true&md=active&oid=<?php echo $ois; ?>&fid=<?php echo $as["id"];?>"><span class="badge badge-danger">Sil</span></a><?php  ?></td>

    </tr>
<?php 
$kx = $kx+1;
} ?>
  </tbody>
</table>
</div><?php  } ?>