<?php

permcontrol("indocview");
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['id'];
    $query = "DELETE FROM evraktakip WHERE id = :id";
    $statement = $ac->prepare($query);
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
</head>
<body>
<div class="pd-20 bg-white border-radius-4 box-shadow mb-30">
    <h4>Giden Evrak Listesi</h2><br>

    <table class="data-table select-row table-bordered table-hover">
        <thead>
            <tr>
                <th>Sıra</th>
                <th>Firma</th>
                <th>Evrak Türü</th>
                <th>Kategori</th>
                <th>Adet</th>
                <th>Teslim Alan</th>
                <th>Teslim Eden</th>
                <th>Teslim Tarihi</th>
                <th>Açıklama</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT e.*, u_teslimalan.username AS teslim_alan_username, u_teslimeden.username AS teslim_eden_username
                      FROM evraktakip e 
                      LEFT JOIN users u_teslimalan ON e.teslimalan = u_teslimalan.id 
                      LEFT JOIN users u_teslimeden ON e.teslimeden = u_teslimeden.id 
                      WHERE e.evrakturu = 'Gelen'";
            $result = $ac->query($query);
            $counter = 1;
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>{$counter}</td>"; // Sıra Numarası
                echo "<td>{$row['firma']}</td>";
                echo "<td>{$row['evrakturu']}</td>";
                echo "<td>{$row['kategori']}</td>";
                echo "<td>{$row['adet']}</td>";
                echo "<td>{$row['teslim_alan_username']}</td>";  
                echo "<td>{$row['teslim_eden_username']}</td>";  
                echo "<td>{$row['teslimtarihi']}</td>";
                echo "<td>{$row['aciklama']}</td>";
                echo "<td><button class='deleteButton badge badge-danger' data-id='{$row['id']}'>Sil</button></td>";
                echo "</tr>";
                $counter++;
            }
            ?>
        </tbody>
    </table>
</div>
    <script>
        $(document).ready(function() {
            $('#myTable').DataTable();

            $('.deleteButton').click(function() {
                var id = $(this).data('id');
                if (confirm('Bu veriyi silmek istediğinize emin misiniz?')) {
                    $.ajax({
                        type: 'POST',
                        url: 'index.php?p=new-outdocument', // Bu dosyanın adını doğru belirttiğinizden emin olun
                        data: { action: 'delete', id: id },
                        success: function() {
                            location.reload();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
