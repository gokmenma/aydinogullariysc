<!-- <?php
if(isset($_POST['content'])) {
    $content = $_POST['content'];   
}
?> -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="vendors/styles/style.css">
</head>
<body>
<style>
    @media print {


        @page {
            size: A4;
            margin: 10mm 10mm;
        }

        .btn {
            display: none !important;
        }

    }
</style>
    <?php echo $content; ?>
   
</body>
</html>


