<?php

	if($_POST){
		$nt = @$_POST["note"];
		$ins = $ac->prepare("UPDATE settings SET val = ? WHERE id = ?");
		$ins->execute(array($nt,16));
		
	}

if(@$_GET["st"] == "newsuccess" ){
		?>
	<div class="alert alert-success" role="alert">
									Notlar kaydedildi.
								</div>
		<?php
	}
?>


<form action="" method="POST">

	<div class="html-editor pd-20 bg-white border-radius-4 box-shadow mb-30">
					<h3 class="weight-500">Notlar - To Do List 	</h3>
					<p>Notlarınızı listeler halinde ekleyebilirsiniz.</p>
					<textarea name="note" class="textarea_editor form-control border-radius-0" placeholder="Bir şeyler yaz ..."><?php echo set("notestext");?></textarea><br>
				<input type="submit" value="Değişiklikleri Kaydet" style="float:right" class="col-md-12 form-control btn-outline-success">
				<br><br></div>

</form>