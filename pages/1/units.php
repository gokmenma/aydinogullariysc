<?php

if($_POST && $_GET["mode"] == "new"){
	$title = $_POST["title"];
	$ekle = $ac->prepare("INSERT INTO units SET
	title = ?,
	regdate = ?,
    creator = ?,
	statu = ?");
	$ekle->execute(array($title,TODAY." - ".date("H:i:s")."",sesset("id"),1));
	header("Location:index.php?p=units&st=newsuccess");
}

	$xid = @$_GET["id"];
	if($xid && @$_GET["mode"] == "delete" && @$_GET["code"] == "04md177"){	

			$pdq = $ac->prepare("DELETE FROM units WHERE id = ?");
			$pdq->execute(array($xid));
			header("Location: index.php?p=units&type=delete&code=0882md25");
		}
?>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
    <?php
	if(@$_GET["st"] == "newsuccess" )
	{
		showAlert("success", "İşlem Başarı ile tamamlandı!");
	}
	if(@$_GET["type"] == "delete")
	{
		showAlert("alert", "Kayıt Başarı ile silindi!");
	}
	?>
    <form method="POST" action="index.php?p=units&mode=new&code=38&cc=087s3">
        <div class="clearfix mb-20">
            <div class="pull-left">
                <h5 class="text-blue">Teklif Birimleri Sayfası</h5>
            </div>

        </div>
        <div class="form-group row">

            <label class="col-md-2">
                <font color="red">(*)</font>Yeni Birim Adı:
            </label>
            <div class="input-group col-md-8">
                <input name="title" placeholder="örn: Adet"="" class="form-control" type="text">
            </div>
            <div class="input-group col-md-2">
                <button type="submit" style="float:right;" type="button" class="btn btn-success">Ekle</button>
            </div>
        </div>
    </form>
    <table class="data-table select-row table-bordered table-hover">
        <thead>
            <tr>
                <th width="15" scope="col">#Sıra</th>
                <th>Birim Adı</th>
                <th>Eklenme Tarihi</th>
                <th class="datatable-nosort">İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php
    		$cq = $ac->prepare("SELECT * FROM units WHERE statu = '1' ");
    		$cq->execute();
    		$kx = 1;
    		while($as = $cq->fetch(PDO::FETCH_ASSOC)){
    	?>
            <tr>
                <td scope="row"><?php echo $kx;?></td>
                <td><?php echo $as["title"]; ?></td>
                <td><?php echo $as["regdate"];?></td>
                <td>
                    <a onClick="return confirm('Silmek istediğinize emin misiniz?')"
                        href="index.php?p=units&mode=delete&code=04md177&md=active&id=<?php echo $as["id"];?>"><span
                            class="badge badge-danger">Sil</span></a>
                </td>
            </tr>
            <?php 
$kx = $kx+1;
} ?>
        </tbody>
    </table>
</div>