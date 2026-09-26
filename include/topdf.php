<?php         
    
    require('include/fpdf/fpdf.php');


    $oid = $_GET["oid"];

 $qx = $ac->prepare("SELECT * FROM offers WHERE id = ?");
 $qx->execute(array($oid));
 $qq = $qx->fetch(PDO::FETCH_ASSOC);


    class PDF extends FPDF
    {

        // Sayfa başlığı
        function Header()
        {      
            
            $ayen = "fasd";
            // Logo ayarlanır
            $this->Image('favicon.png',10,6,20);
            
            // Yazı rengi ayarlanır
            $this->SetTextColor(0,0,140);
            
            // Satır 25 pixel içeriden başlasın
            $this->Cell(25);

            // Satıra yazı yazılır
            $this->Write (2, set("site_title")); 
            
            // 4 pixel aşağıda yeni satıra geç
            $this->Ln(4);
            
            // Satır 25 pixel içeriden başlasın
            $this->Cell(25);  
            
            // Arial italic 8
            $this->SetFont('Arial','',8); 
            // Yazı rengi ayarlanır 
            $this->SetTextColor(51,0,102);
            // Satıra yazı yazılır
            $this->Write (10, $ayen);
            // 15 pixel aşağıda yeni satıra geç
            $this->Ln(5);
            
            // Satır 25 pixel içeriden başlasın 
            $this->Cell(25);  

            // Satıra yazı yazılır
            $this->Write (10, 'http://istanbulz.com', 'http://istanbulz.com/');
            // 10 pixel aşağıda yeni satıra geç
            $this->Ln(10);
            
            // X koordinatı
            $x = $this->GetX();
            // Y Koordinatı 
            $y = $this->GetY();
            // Düz çizgi çizilir
            $this->Line( $x, $y , $x + 185, $y ); 
            
            // 5 pixel aşağıda yeni satıra geç 
            $this->Ln(5);
        }

        // Sayfa Altı
        function Footer()
        {
            // 15 pıxel sayfa altından yukarıda başla
            $this->SetY(-15);
            // Arial italic 8
            $this->SetFont('Arial','I',8);
            // Sayfa Numarası
            $this->Cell(0,10,'Sayfa '.$this->PageNo().'/{nb}',0,0,'C');
        }
        
        // Renkli tablo
        function FancyTable($header, $data)
        {
            
            // Renkler ve yükseklikler ayarlanır
            $this->SetFillColor(51,153,255);
            $this->SetTextColor(255);
            $this->SetDrawColor(128,0,0);
            $this->SetLineWidth(.3);

            // Satırın X ve Y koordinatları alınır
            $posX = $this->GetX();
            $posY = $this->GetY();
            
            // Yazı fontu ayarlanır
            $this->SetFont('Arial','',12); 
            
            // Tablonun en üst satırı (başlık kısmı)
            // Her kolonun pixel boyutu ayarlanır
            $w = array(30, 25, 25);
            for($i=0;$i<count($header);$i++)
            {          
                $this->MultiCell($w[$i],6,$header[$i],1,'C',true);
                // Bir sonraki hücrenin X koordinatı bir önceki kolonun pixel sayısı eklenerek hesaplanır
                $posX +=  $w[$i];
                
                $this->SetXY($posX, $posY);
            }
                
            $this->Ln(12);
            
            $this->SetFillColor(224,235,255);
            $this->SetTextColor(0);

            // Bilgiler
            $fill = false;
            foreach($data as $row)
            {
                $this->Cell($w[0],6,$row[0],'LR',0,'C',$fill);
                $this->Cell($w[1],6,$row[1],'LR',0,'R',$fill);
                $this->Cell($w[2],6,$row[2],'LR',0,'R',$fill);
                $this->Ln();
                $fill = !$fill;
            }
            // Satır kapatılır
            $this->Cell(array_sum($w),0,'','T');
        }  
    }
    
    // Pdf nesnesi oluşturulur
    $pdf = new PDF();
    
    // Sayfa altında numaraları göstermek için kullanılır
    $pdf->AliasNbPages();
   
    // font ayarlanır
    $pdf->SetFont('Arial','',14);
    
    

    // sayfa eklenir
    $pdf->AddPage();
    // Başlıklar ve bilgiler tabloya yollanır

    
    $pdf->Ln(); 
    $kt1 = $qq["ktitle1"];
    $kp1 = $qq["kprice1"];
    $ka1 = $qq["kamount1"];   
    
        $pdf->Cell(0,10,"$kt1"  ,0,1);
    
    
    $pdf->Output();     
?>