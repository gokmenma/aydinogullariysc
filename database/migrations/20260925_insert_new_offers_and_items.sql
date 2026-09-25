-- ==========================================================================
-- Aydınoğulları Teklif ve Teklif Kalemlerini YENİ KAYIT Olarak Ekleme SQL
-- Mevcut hiçbir kaydı silmez veya ezmez; her teklif yeni auto-increment ID alır
-- Toplam: 11 Teklif, 110 Teklif Kalemi
-- ==========================================================================

START TRANSACTION;

-- --------------------------------------------------------------------------
-- TEKLİF: TK3855 | Konu: KONVANSİYONEL ALGILAMA FİYAT TEKLİFİ | Tutar: 10000.00 TL | Eski ID: 4119
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3855', '2707', '.', 'PEŞİN', 'KONVANSİYONEL ALGILAMA FİYAT TEKLİFİ', '1', '', '2026-09-21 09:02:54', '10000.00', '', '', '21-09-2026', '0', '4', '2024-11-28', '4', '2026-09-21 09:10:19', '', 'Efektif Satış', '0', '0', '12', '', '11', '* Fiyatlarımıza KDV dahil değildir<br>* Döviz kuru fatura tarihinden bir gün öncesi TCMB satış kuru ile hesaplanır.<br>* Projelendirmenin tarafımızca çizilmesi&nbsp;(ücretli) kabul edildiğinde güncel mimari proje dijital ortamda ibraz edilmelidir.<br>* Panelin doğa şartlarından korunması müşteriye aittir.<br>* Panel besleme hattı müşteriye aittir.                        <br>*Gerekmesi durumunda personel yükseltici&nbsp;müşteriye aittir', '', '0', NULL, '', NULL, '7706.95', '10000', NULL, NULL, '48.6992', '55.8986', NULL, NULL, NULL, '2731.36', '0', '0', '156.2', '0', '4000', '156.2', '0', '1268.64', '0', '0', '0', '156.2', '0', '1268.64', '10000', '0', NULL, NULL);

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (5 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '2707', '', 'KONVANSİYONEL OPTİK DUMAN DEDEKTÖRÜ SENSOMAG S30', 'Adet', '6', '7.46', 'EUR', '13.05', 'EUR', '78.3', '1'),
  (@new_oid, '2707', '', 'KONVANSİYONEL SABIT ISI DEDEKTÖRÜ F10', 'Adet', '1', '7.08', 'EUR', '12.39', 'EUR', '12.39', '2'),
  (@new_oid, '2707', '', 'KONVANSİYONEL STANDART TABAN B24', 'Adet', '7', '2.93', 'EUR', '5.13', 'EUR', '35.91', '3'),
  (@new_oid, '2707', '', 'KABLO ALARM J-Y(St)….Lg 2 x 2 x 0,80', 'Mt', '40', '0.52', 'EUR', '0.74', 'EUR', '29.6', '4'),
  (@new_oid, '2707', '', 'MONTAJ - İŞÇİLİK - DEVREYE ALMA', 'SET', '1', '2500', 'TRY', '4000', 'TRY', '4000', '5');

-- --------------------------------------------------------------------------
-- TEKLİF: TK3856 | Konu: HİDFOROR BAKIM FİYAT TEKLİFİ 2 | Tutar: 40200.00 TL | Eski ID: 4120
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3856', '449', '.', '', 'HİDFOROR BAKIM FİYAT TEKLİFİ 2', '1', '', '2026-09-21 10:47:58', '40200.00', '', '', '21.09.2026', '0', '31', NULL, NULL, NULL, '', 'Döviz Alış', '0', '0', '12', '', '11', 'T.C.M.B. Döviz Satış kuru üzerinden Türk Lirasına çevrilip faturalandırılır.* Fiyatlarımıza %20 K.D.V. dahil değildir.<br>* Ödeme Türü :<br>* Ödeme Vadesi :&nbsp;Peşin&nbsp;<br>* Teklifin geçerlilik tarihi teklif tarihinden itibaren bir haftadır.&nbsp; <br>* Teslimattan 15 gün sonra yapılan iadeler kabul edilmemektedir.<br>* Vadesinde ödenmeyen faturalar için aylık %4 vade farkı faturası kesilecektir.<br>* Siparişinizi Onaylamanız; Genel Şartları kabul etmeniz ve sipariş verdiğiniz ürünleri almayı taahhüt etmeniz anlamına gelmektedir.', '', '20', NULL, '', NULL, '23700', '33500', NULL, NULL, '48.5776', '55.759', NULL, NULL, NULL, '0', '0', '0', '0', '0', '33500', '0', '0', '33500', '0', '0', '6700', '0', '0', '40200', '40200', '0', NULL, NULL);

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (1 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '449', '', 'GENLEŞME TANKI DEĞİŞİMİ-PİLOT POMPA TAMİRİ-MONTAJ DEMONTAJ SERVİS BEDELİ', 'SET', '1', '23700', 'TRY', '33500', 'TRY', '33500', '1');

-- --------------------------------------------------------------------------
-- TEKLİF: TK3858 | Konu: ADRESLİ ALGILAMA | Tutar: 2534657.17 TL | Eski ID: 4122
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3858', '2729', 'BERKEM ÖZÇELİK', '45', 'ADRESLİ ALGILAMA', '1', '', '2026-09-21 13:12:59', '2534657.17', '', '', '21-09-2026', '0', '1', '2024-08-28', '1', '2026-09-21 13:43:04', '', 'Efektif Satış', '0', '0', '12', '', '11', '* Fiyatlarımıza KDV dahil değildir<br>* Döviz kuru fatura tarihinden bir gün öncesi TCMB satış kuru ile hesaplanır.<br>* Projelendirmenin tarafımızca çizilmesi&nbsp;(ücretli) kabul edildiğinde güncel mimari proje dijital ortamda ibraz edilmelidir.<br>* Panelin doğa şartlarından korunması müşteriye aittir.<br>* Panel besleme hattı müşteriye aittir.<br>* Fiziksel hasar,yüksek voltaj ve elektriksel nedenler haricinde ve PERİYODİK KONTROLLERİN TARAFIMIZCA YAPILMASI DURUMUNDA Sistem iki yıl garantilidir.<br>* Her türlü inşai işlem müşteriye aittir.<br><br><br>', '', '20', '0', '', '77876.25', '1472861', '2112214.25', '135487.75', '0', '48.6992', '55.8986', NULL, NULL, NULL, '0', '0', '0', '37786.53', '0', '0', '37786.53', '0', '0', '7557.31', '0', '0', '45343.84', '0', '0', '2534657.17', '0', '28-08-2024', NULL);

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (21 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '2729', '', 'ALGILAMA SİSTEM ADRESLİ PANEL 4 LOOP IRIS TTE 4L', 'Adet', '1', '1200', 'EUR', '1600', 'EUR', '1600', '1'),
  (@new_oid, '2729', '', 'TEKRARLAMA TFT İRİS /SİMPO PANEL', 'Adet', '1', '420', 'EUR', '680', 'EUR', '680', '2'),
  (@new_oid, '2729', '', 'AKÜ 12 V 24 AH / KURU TİPİ', 'Adet', '4', '18', 'EUR', '24', 'EUR', '96', '3'),
  (@new_oid, '2729', '', 'İZOLE PLASTİK PANO KORUYUCU', 'Adet', '2', '26', 'EUR', '46', 'EUR', '92', '4'),
  (@new_oid, '2729', '', 'KONVANSİYONEL BEAM DEDEKTÖR', 'Adet', '17', '225', 'EUR', '325', 'EUR', '5525', '5'),
  (@new_oid, '2729', '', 'SensoIRIS MINP', 'Adet', '20', '20', 'EUR', '35', 'EUR', '700', '6'),
  (@new_oid, '2729', '', 'GÜÇ KAYNAĞI 24 V', 'Adet', '6', '35', 'EUR', '60', 'EUR', '360', '7'),
  (@new_oid, '2729', '', 'ADRESLİ PANEL ISI DEDEKTÖRÜ SENSOIRIS T110', 'Adet', '15', '12', 'EUR', '23', 'EUR', '345', '8'),
  (@new_oid, '2729', '', 'ADRESLİ PANEL OPTİK DUMAN DEDEKTÖRÜ SENSOIRIS S130', 'Adet', '129', '13', 'EUR', '24', 'EUR', '3096', '9'),
  (@new_oid, '2729', '', 'ADRESLİ PANEL KOMBİNE ISI VE OPTİK DUMAN DEDEKTÖRÜ SENSOIRIS M140', 'Adet', '1', '20', 'EUR', '35', 'EUR', '35', '10'),
  (@new_oid, '2729', '', 'SensoIRIS B124 STANDART TABAN', 'Adet', '145', '3.54', 'EUR', '5.5', 'EUR', '797.5', '11'),
  (@new_oid, '2729', '', 'ADRESLİ PANEL GAZ DEDEKTÖRÜ SENSOIRIS GAS', 'Adet', '6', '36', 'EUR', '62', 'EUR', '372', '12'),
  (@new_oid, '2729', '', 'ALGILAMA SİSTEM SİREN SF 50', 'Adet', '39', '9.5', 'EUR', '17', 'EUR', '663', '13'),
  (@new_oid, '2729', '', 'ADRESLİ PANEL BUTON SENSOIRIS MCP150', 'Adet', '53', '22', 'EUR', '38', 'EUR', '2014', '14'),
  (@new_oid, '2729', '', 'BUTON KAPAĞI CALLPOINT COVER ', 'Adet', '53', '3', 'EUR', '5.51', 'EUR', '292.03', '15'),
  (@new_oid, '2729', '', 'KABLO ALARM J-H(St)….Lg 1 x 2 x 1,50', 'Mt', '5950', '0.71', 'EUR', '0.86', 'EUR', '5117', '16'),
  (@new_oid, '2729', '', '2x1,5mm² LIHCH FE180 ', 'Mt', '2250', '0.75', 'EUR', '1.1', 'EUR', '2475', '17'),
  (@new_oid, '2729', '', 'KABLO KANALI -20LİK BORU VE AKSESUARLARI', 'Mt', '3000', '0.39', 'EUR', '0.75', 'EUR', '2250', '18'),
  (@new_oid, '2729', '', 'GALVANİZ BORU-FİTTİNGS', 'Mt', '320', '1.8', 'EUR', '3.6', 'EUR', '1152', '19'),
  (@new_oid, '2729', '', 'MONTAJ-DEVREYE ALMA-İŞÇİLİK', 'GRUP', '1', '8000', 'EUR', '9750', 'EUR', '9750', '20'),
  (@new_oid, '2729', '', 'PROJELENDİRME', 'GRUP', '1', '210', 'EUR', '375', 'EUR', '375', '21');

-- --------------------------------------------------------------------------
-- TEKLİF: TK3859 | Konu: ARGE KONVANSİYONEL ALGILAMA  | Tutar: 36893.08 TL | Eski ID: 4123
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3859', '1409', 'ONUR BEY', '', 'ARGE KONVANSİYONEL ALGILAMA ', '2', '', '2026-09-21 13:14:33', '36893.08', '', '', '21-09-2026', '0', '4', '2024-11-28', '4', '2026-09-23 09:28:09', '', 'Efektif Satış', '0', '0', '12', '', '11', '* Fiyatlarımıza KDV dahil değildir<br>* Döviz kuru fatura tarihinden bir gün öncesi TCMB satış kuru ile hesaplanır.<br>* Projelendirmenin tarafımızca çizilmesi&nbsp;(ücretli) kabul edildiğinde güncel mimari proje dijital ortamda ibraz edilmelidir.<br>* Panelin doğa şartlarından korunması müşteriye aittir.<br>* Panel besleme hattı müşteriye aittir.                        <br>*Personel yükseltici bedelleri fiyatlarımıza dahil değildir. Tarafınızdan temin edilecektir.<br>', '', '20', NULL, '', NULL, '18232.4', '30744.23', NULL, NULL, '48.8201', '55.9519', NULL, NULL, NULL, '0', '47.35', '0', '597.35', '0', '0', '550', '0', '0', '110', '0', '0', '660', '0', '0', '36893.08', '0', NULL, '2026-09-23 09:28:09');

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (5 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '1409', '', 'LINEAR DEDEKTÖR ( KABLO TİPİ)', 'Mt', '7', '6.9', 'EUR', '14.05', 'EUR', '98.35', '1'),
  (@new_oid, '1409', '', 'KABLO ALARM J-Y(St)….Lg 2 x 2 x 0,80', 'Mt', '50', '0.52', 'EUR', '0.74', 'EUR', '37', '2'),
  (@new_oid, '1409', '', 'KARE BUAT KUTUSU ', 'Adet', '7', '1.25', 'EUR', '3.5', 'EUR', '24.5', '3'),
  (@new_oid, '1409', '', 'KABLO KANALI ,PLASTİK BORU,AKSESUARLARI', 'Mt', '50', '0.39', 'EUR', '0.75', 'EUR', '37.5', '4'),
  (@new_oid, '1409', '', 'MONTAJ-DEVREYE ALMA-İŞÇİLİK-LİNEER DEDEKTÖR TEST', 'Set', '1', '12500', 'TRY', '400', 'EUR', '400', '5');

-- --------------------------------------------------------------------------
-- TEKLİF: TK3860 | Konu: 2026 OTOMATİK SÖNDÜRME SİSTEMLERİ PERİYODİK KONTROL | Tutar: 32400.00 TL | Eski ID: 4124
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3860', '120', 'BAHAR KANLIOĞLU', '', '2026 OTOMATİK SÖNDÜRME SİSTEMLERİ PERİYODİK KONTROL', '1', '', '2026-09-21 13:27:42', '32400.00', '', '', '21-09-2026', '0', '4', NULL, '4', '2026-09-21 13:36:35', '', 'Döviz Alış', '0', '0', '12', '', '11', 'T.C.M.B. Döviz Satış kuru üzerinden Türk Lirasına çevrilip faturalandırılır.* Fiyatlarımıza %20 K.D.V. dahil değildir.<br>* Ödeme Türü :<br>* Ödeme Vadesi :&nbsp;Peşin&nbsp;<br>* Teklifin geçerlilik tarihi teklif tarihinden itibaren bir haftadır.&nbsp; <br>* Teslimattan 15 gün sonra yapılan iadeler kabul edilmemektedir.<br>* Vadesinde ödenmeyen faturalar için aylık %4 vade farkı faturası kesilecektir.<br>* Siparişinizi Onaylamanız; Genel Şartları kabul etmeniz ve sipariş verdiğiniz ürünleri almayı taahhüt etmeniz anlamına gelmektedir.', '', '20', NULL, '', NULL, '8500', '27000', NULL, NULL, '48.5776', '55.759', NULL, NULL, NULL, '0', '0', '0', '0', '0', '27000', '0', '0', '27000', '0', '0', '5400', '0', '0', '32400', '32400', '0', NULL, NULL);

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (4 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '120', '', 'OTOMATİK TRAFO SÖNDÜRME SİSTEMİ PERİYODİK KONTROLLERİ ( 3 SİLİNDİR - CO2)', 'Adet', '1', '2500', 'TRY', '5500', 'TRY', '5500', '1'),
  (@new_oid, '120', '', 'OTOMATİK  BOYA HAZIRLAMA ODASI SÖNDÜRME SİSTEMİ PERİYODİK KONTROLLERİ - (8 SİLİNDİR-AZOT)', 'Adet', '1', '2000', 'TRY', '8500', 'TRY', '8500', '2'),
  (@new_oid, '120', '', 'OTOMATİK TEHLİKELİ MADDE ALANI SÖNDÜRME SİSTEMİ PERİYODİK KONTROLLERİ ( 1 SİLİNDİR - CO2  )', 'Adet', '1', '2500', 'TRY', '5500', 'TRY', '5500', '3'),
  (@new_oid, '120', '', 'OTOMATİK SÖNDÜRME SİSTEMLERİ PERİYODİK KONTROL RAPORLAMA ', 'SET', '3', '500', 'TRY', '2500', 'TRY', '7500', '4');

-- --------------------------------------------------------------------------
-- TEKLİF: TK3861 | Konu: MEKANİK YANGIN TESİSATI STANDART  | Tutar: 1195812.00 TL | Eski ID: 4125
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3861', '528', '.', '', 'MEKANİK YANGIN TESİSATI STANDART ', '1', 'ofc.20 s-a1.30.10 2 adet
ofc.11 s-a1.30.10 8 adet
ERGÜN ABİ 500.000 TL (DOLAP-HİDROFOR-SU DEPOSU-MANLİFT HARİÇ)

', '2026-09-21 13:31:59', '1195812.00', '', '', '21-09-2026', '0', '31', NULL, '4', '2026-09-22 11:53:49', '', 'Döviz Alış', '0', '0', '12', '', '11', '* Fiyatlarımıza KDV dahil değildir.<br>* Hidrofor dairesi elektrik işlemleri müşteriye aittir.<br>* Hidroforun doğa şartlarından korunması müşteriye aittir.<br>* Hidrofor ve Deponun zemin işlemleri müşteriye aittir.<br>* Depo besleme hattı fiyatlara dahil değildir.                        <br>*Tüp bölmeli yangın dolapları tüp hariç tekliflendirilmiştir.&nbsp; Mevcut tüpler yangın dolapları içerisinde konumlandırılacaktır.<br>*Kimyasal depo alanı içerisinde sprink ysc tekliflendirilmiştir.<br>*Hidrofor dairesi kapama işlemleri fiyat teklifimize dahil değildir. Uygun koşulların sağlanması tarafınızdan sağlanacaktır.                        <br>*Ödeme koşulları: %30 peşinat kalanı iş tesliminde 30-60 vadeli&nbsp;', '', '20', NULL, '', NULL, '759019', '996510', NULL, NULL, '48.679', '55.8851', NULL, NULL, NULL, '0', '0', '0', '0', '0', '996510', '0', '0', '996510', '0', '0', '199302', '0', '0', '1195812', '1195812', '0', NULL, NULL);

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (15 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '528', '', '12m3 DİZEL+ELEKTRİK+JOKEY HİDROFOR GRUBU(GENLEŞME TANKI DAHİL)', 'Set', '1', '148000', 'TRY', '186500', 'TRY', '186500', '1'),
  (@new_oid, '528', '', '12 TON PE YANGIN SU REZERV DEPOSU', 'Set', '1', '35000', 'TRY', '44500', 'TRY', '44500', '2'),
  (@new_oid, '528', '', 'SAC KAPAKLI 1\" VANALI 30 MT TÜP BÖLMELİ YANGIN DOLABI', 'Adet', '8', '6615', 'TRY', '9950', 'TRY', '79600', '3'),
  (@new_oid, '528', '', ' 1\" VANALI 30 MT CAM KAPAKLI STANDART DEKORATİF YANGIN DOLABI', 'Adet', '2', '6534', 'TRY', '11250', 'TRY', '22500', '4'),
  (@new_oid, '528', '', '2\" İTFAİYE SU ALMA AĞZI', 'Adet', '7', '1850', 'TRY', '2450', 'TRY', '17150', '5'),
  (@new_oid, '528', '', '4\"21/2\" 21/2\"  İTFİAYE BAĞLANTI AĞZI', 'Adet', '1', '7000', 'TRY', '5400', 'TRY', '5400', '6'),
  (@new_oid, '528', '', '2\" SİYAH BORU', 'Mt', '310', '310', 'TRY', '270', 'TRY', '83700', '7'),
  (@new_oid, '528', '', '4\" SİYAH BORU', 'Mt', '12', '737', 'TRY', '615', 'TRY', '7380', '8'),
  (@new_oid, '528', '', 'FİTTİNGS BEDELİ ', 'Set', '1', '45497', 'TRY', '51540', 'TRY', '51540', '9'),
  (@new_oid, '528', '', 'BORULARIN BOYANMASI', 'Mt', '322', '70', 'TRY', '95', 'TRY', '30590', '10'),
  (@new_oid, '528', '', 'BORU ASKI SABİTLEME VE KONSOLLAMA', 'SET', '1', '25000', 'TRY', '34500', 'TRY', '34500', '11'),
  (@new_oid, '528', '', 'LİFT-MENLİFT-PERSONEL TAŞIYICI', 'SET', '1', '9400', 'TRY', '13500', 'TRY', '13500', '12'),
  (@new_oid, '528', '', '6 KG SPRİNKER KÖPÜKLÜ CİHAZ', 'Adet', '2', '1350', 'TRY', '2600', 'TRY', '5200', '13'),
  (@new_oid, '528', '', 'SPRİNKLER CİHAZ MONTAJ MALZEME-İŞÇİLİK', 'Adet', '2', '500', 'TRY', '975', 'TRY', '1950', '14'),
  (@new_oid, '528', '', 'İŞÇİLİK-NAKLİYE-HİDROFOR SERVİSİ-DEVREYE ALMA', 'GRUP', '1', '279000', 'TRY', '412500', 'TRY', '412500', '15');

-- --------------------------------------------------------------------------
-- TEKLİF: TK3862 | Konu: 2026 PROJELENDİRME | Tutar: 108000.00 TL | Eski ID: 4126
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3862', '2747', 'HÜSEYİN BEY', '', '2026 PROJELENDİRME', '1', '', '2026-09-21 13:45:19', '108000.00', '', '', '21-09-2026', '0', '4', NULL, '4', '2026-09-21 16:11:05', '', 'Döviz Alış', '0', '0', '12', '', '11', 'T.C.M.B. Döviz Satış kuru üzerinden Türk Lirasına çevrilip faturalandırılır.* Fiyatlarımıza %20 K.D.V. dahil değildir.<br>* Ödeme Türü :<br>* Ödeme Vadesi :&nbsp;Peşin&nbsp;<br>* Teklifin geçerlilik tarihi teklif tarihinden itibaren bir haftadır.&nbsp; <br>* Teslimattan 15 gün sonra yapılan iadeler kabul edilmemektedir.<br>* Vadesinde ödenmeyen faturalar için aylık %4 vade farkı faturası kesilecektir.<br>* Siparişinizi Onaylamanız; Genel Şartları kabul etmeniz ve sipariş verdiğiniz ürünleri almayı taahhüt etmeniz anlamına gelmektedir.', '', '20', NULL, '', NULL, '30000', '90000', NULL, NULL, '48.679', '55.8851', NULL, NULL, NULL, '0', '0', '0', '0', '0', '90000', '0', '0', '90000', '0', '0', '18000', '0', '0', '108000', '108000', '0', NULL, NULL);

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (2 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '2747', '', 'MİMARİ PROJELENDİRME BEDELİ ( MİMAR ONAYLI ) ', 'SET', '1', '15000', 'TRY', '50000', 'TRY', '50000', '1'),
  (@new_oid, '2747', '', 'İTFAİYE TAKİP VE DANIŞMANLIK HİZMETİ', 'SET', '1', '15000', 'TRY', '40000', 'TRY', '40000', '2');

-- --------------------------------------------------------------------------
-- TEKLİF: TK3863 | Konu: AHŞAP MAKİNE  BACA SÖNDÜRME SİSTEMİ-3 SET | Tutar: 1396770.48 TL | Eski ID: 4127
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3863', '2735', 'ONUR BEY', '30', 'AHŞAP MAKİNE  BACA SÖNDÜRME SİSTEMİ-3 SET', '1', '', '2026-09-21 13:57:27', '1396770.48', '', '', '21-09-2026', '0', '1', '2024-11-23', '1', '2026-09-21 17:36:46', '', 'Efektif Satış', '0', '0', '12', '', '11', 'T.C.M.B. Döviz Satış kuru üzerinden Türk Lirasına çevrilip faturalandırılır.<br>* Fiyatlarımıza %20 K.D.V. dahil değildir.<br><br><br>', '', '20', NULL, '', NULL, '492992', '1163975.38', NULL, NULL, '48.8009', '56.025', NULL, NULL, NULL, '0', '0', '0', '20776', '0', '0', '20776', '0', '0', '4155.2', '0', '0', '24931.2', '0', '0', '1396770.48', '0', NULL, NULL);

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (17 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '2735', '', '45 KG CO2 GAZLI YANGIN SÖNDÜRME CİHAZI PNOMATİK VALFLİ-TPED', 'Adet', '4', '250', 'EUR', '390', 'EUR', '1560', '1'),
  (@new_oid, '2735', '', 'CO2 GAZI', 'Adet', '180', '0.95', 'EUR', '1.75', 'EUR', '315', '2'),
  (@new_oid, '2735', '', 'SİLİNDİR BAĞLANTI SETİ', 'Adet', '4', '20', 'EUR', '30', 'EUR', '120', '3'),
  (@new_oid, '2735', '', 'PİLOT SİLİNDİR', 'Adet', '3', '50', 'EUR', '290', 'EUR', '870', '4'),
  (@new_oid, '2735', '', '1/2” BOŞALTMA HORTUMU', 'Adet', '4', '6', 'EUR', '18', 'EUR', '72', '5'),
  (@new_oid, '2735', '', '3/8” PNOMATİK TAHRİK HORTUMU', 'Adet', '1', '4', 'EUR', '12', 'EUR', '12', '6'),
  (@new_oid, '2735', '', '1 ” SCH 40 BORU', 'Mt', '180', '5', 'EUR', '11', 'EUR', '1980', '7'),
  (@new_oid, '2735', '', '1 ” HİDROLİK FİTTİNGS 350 BAR', 'SET', '8', '25', 'EUR', '215', 'EUR', '1720', '8'),
  (@new_oid, '2735', '', 'CO2 NOZUL', 'Adet', '18', '7.5', 'EUR', '14', 'EUR', '252', '9'),
  (@new_oid, '2735', '', 'OTOMATİK SÖNDÜRME PANELİ-TELETEK', 'Adet', '3', '222', 'EUR', '400', 'EUR', '1200', '10'),
  (@new_oid, '2735', '', 'J Tipi Fe-Const Kelepçeli Termokupl', 'Adet', '34', '7', 'EUR', '17', 'EUR', '578', '11'),
  (@new_oid, '2735', '', 'ISI KONTROL CİHAZI PT100 1R 230Vac 77X35', 'Adet', '34', '30', 'EUR', '48', 'EUR', '1632', '12'),
  (@new_oid, '2735', '', 'ISI KONTROL CİHAZI GÖSTERGE PANEL KUTUSU', 'Adet', '34', '5', 'EUR', '7', 'EUR', '238', '13'),
  (@new_oid, '2735', '', 'FLAŞÖRLÜ SİREN', 'Adet', '6', '10', 'EUR', '22', 'EUR', '132', '14'),
  (@new_oid, '2735', '', 'MANUEL DURDURMA BUTONU', 'Adet', '3', '13', 'EUR', '35', 'EUR', '105', '15'),
  (@new_oid, '2735', '', 'KABLO ALARM J-Y(St)….Lg 2 x 2 x 0,80', 'Mt', '1450', '0.65', 'EUR', '1.2', 'EUR', '1740', '16'),
  (@new_oid, '2735', '', 'MONTAJ-DEVREYE ALMA-İŞÇİLİK', 'GRUP', '3', '1000', 'EUR', '2750', 'EUR', '8250', '17');

-- --------------------------------------------------------------------------
-- TEKLİF: TK3864 | Konu: SİLO  SÖNDÜRME SİSTEMİ-3 SET | Tutar: 1630166.15 TL | Eski ID: 4128
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3864', '2735', 'ONUR BEY', '30', 'SİLO  SÖNDÜRME SİSTEMİ-3 SET', '1', '', '2026-09-21 13:57:40', '1630166.15', '', '', '21-09-2026', '0', '1', '2024-11-23', '1', '2026-09-21 17:38:26', '', 'Efektif Satış', '0', '0', '12', '', '11', 'T.C.M.B. Döviz Satış kuru üzerinden Türk Lirasına çevrilip faturalandırılır.<br>* Fiyatlarımıza %20 K.D.V. dahil değildir.<br><br><br>', '', '20', NULL, '', NULL, '711243', '1358471.75', NULL, NULL, '48.8009', '56.025', NULL, NULL, NULL, '0', '0', '0', '24247.6', '0', '0', '24247.6', '0', '0', '4849.52', '0', '0', '29097.12', '0', '0', '1630166.15', '0', NULL, NULL);

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (17 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '2735', '', 'BASKIN ALARM VANASI SETİ 3\"', 'Adet', '3', '1750', 'EUR', '2259', 'EUR', '6777', '1'),
  (@new_oid, '2735', '', 'İZLEME ANAHTARLI KELEBEK VANA 3\"', 'Adet', '3', '135', 'EUR', '195', 'EUR', '585', '2'),
  (@new_oid, '2735', '', 'TEST DRENAJ VANASI 1\"', 'Adet', '3', '50', 'EUR', '145', 'EUR', '435', '3'),
  (@new_oid, '2735', '', '1\" GALVANİZ YANGIN BORUSU', 'Mt', '48', '5', 'EUR', '9', 'EUR', '432', '4'),
  (@new_oid, '2735', '', '2\" GALVANİZ YANGIN BORUSU', 'Mt', '72', '6', 'EUR', '11.5', 'EUR', '828', '5'),
  (@new_oid, '2735', '', 'DELUGE SPRİNKLER 1/2\"', 'Adet', '18', '8', 'EUR', '17', 'EUR', '306', '6'),
  (@new_oid, '2735', '', 'MANOMETRE', 'Adet', '3', '4.2', 'EUR', '7.2', 'EUR', '21.6', '7'),
  (@new_oid, '2735', '', 'KONSOLLAMA-FİTTİNGS-YARDIMCI MALZEMELER', 'SET', '1', '450', 'EUR', '850', 'EUR', '850', '8'),
  (@new_oid, '2735', '', 'SİLO GİRİŞ İŞLEMLERİ - YALITIM', 'Adet', '18', '7.5', 'EUR', '19', 'EUR', '342', '9'),
  (@new_oid, '2735', '', 'OTOMATİK SÖNDÜRME PANELİ-TELETEK', 'Adet', '3', '222', 'EUR', '400', 'EUR', '1200', '10'),
  (@new_oid, '2735', '', 'TERMOKUPL 1XPT100 ÇAP 6mm BOY 25cm REKORLU', 'Adet', '18', '15', 'EUR', '34', 'EUR', '612', '11'),
  (@new_oid, '2735', '', 'ISI KONTROL CİHAZI PT100 1R 230Vac 77X35', 'Adet', '18', '30', 'EUR', '48', 'EUR', '864', '12'),
  (@new_oid, '2735', '', 'ISI KONTROL CİHAZI GÖSTERGE PANEL KUTUSU', 'Adet', '18', '5', 'EUR', '7', 'EUR', '126', '13'),
  (@new_oid, '2735', '', 'FLAŞÖRLÜ SİREN', 'Adet', '6', '9.5', 'EUR', '22', 'EUR', '132', '14'),
  (@new_oid, '2735', '', 'MANUEL DURDURMA BUTONU', 'Adet', '3', '12.5', 'EUR', '35', 'EUR', '105', '15'),
  (@new_oid, '2735', '', 'KABLO ALARM J-Y(St)….Lg 2 x 2 x 0,80', 'Mt', '1360', '0.6', 'EUR', '1.2', 'EUR', '1632', '16'),
  (@new_oid, '2735', '', 'MONTAJ-DEVREYE ALMA-İŞÇİLİK', 'GRUP', '3', '1000', 'EUR', '3000', 'EUR', '9000', '17');

-- --------------------------------------------------------------------------
-- TEKLİF: TK3865 | Konu: KONVANSİYONEL ALGILAMA AYDINLATMA YÖNLENDİRME UPAK 4 KISMI REVİZYONLARI | Tutar: 288994.09 TL | Eski ID: 4129
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3865', '2729', 'BERKEM ÖZÇELİK', '45', 'KONVANSİYONEL ALGILAMA AYDINLATMA YÖNLENDİRME UPAK 4 KISMI REVİZYONLARI', '1', '', '2026-09-21 15:09:05', '288994.09', '', '', '21-09-2026', '0', '31', '2024-11-28', '31', '2026-09-21 17:44:29', '', 'Efektif Satış', '0', '0', '12', '', '11', '* Fiyatlarımıza KDV dahil değildir<br>* Döviz kuru fatura tarihinden bir gün öncesi TCMB satış kuru ile hesaplanır.<br>* Projelendirmenin tarafımızca çizilmesi&nbsp;(ücretli) kabul edildiğinde güncel mimari proje dijital ortamda ibraz edilmelidir.<br>* Panelin doğa şartlarından korunması müşteriye aittir.<br>* Panel besleme hattı müşteriye aittir.', '', '20', NULL, '', NULL, '132533.84', '240828.5', NULL, NULL, '48.8009', '56.025', NULL, NULL, NULL, '0', '0', '0', '4308.31', '0', '0', '4308.31', '0', '0', '861.66', '0', '0', '5169.97', '0', '0', '288994.09', '0', NULL, NULL);

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (14 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '2729', '', 'KONVANSİYONEL OPTİK DUMAN DEDEKTÖRÜ SENSOMAG S30', 'Adet', '21', '7.46', 'EUR', '13.05', 'EUR', '274.05', '1'),
  (@new_oid, '2729', '', 'KONVANSİYONEL STANDART TABAN B24', 'Adet', '21', '2.93', 'EUR', '5.13', 'EUR', '107.73', '2'),
  (@new_oid, '2729', '', 'KONVANSİYONEL BUTON SENSOMAG MCP50', 'Adet', '11', '7.2', 'EUR', '13.72', 'EUR', '150.92', '3'),
  (@new_oid, '2729', '', 'KONVANSİYONEL FLAŞÖRLÜ SİREN SF50', 'Adet', '3', '8.8', 'EUR', '17', 'EUR', '51', '4'),
  (@new_oid, '2729', '', 'BUTON KAPAĞI CALLPOINT COVER ', 'Adet', '11', '3', 'EUR', '5.51', 'EUR', '60.61', '5'),
  (@new_oid, '2729', '', 'KABLO ALARM J-Y(St)….Lg 2 x 2 x 0,80', 'Mt', '550', '0.52', 'EUR', '0.74', 'EUR', '407', '6'),
  (@new_oid, '2729', '', 'SİREN KABLOSU J-H(St)….Lg1 x 2 x 1,00', 'Mt', '150', '0.49', 'EUR', '0.92', 'EUR', '138', '7'),
  (@new_oid, '2729', '', 'KABLO KANALI ,PLASTİK BORU,AKSESUARLARI', 'Mt', '650', '0.39', 'EUR', '0.75', 'EUR', '487.5', '8'),
  (@new_oid, '2729', '', 'SPOT AYDINLATMA ARMATÜR-TWINLIGHT ECO 2X4,5 180 DK', 'Adet', '6', '30', 'USD', '45', 'EUR', '270', '9'),
  (@new_oid, '2729', '', 'YÖNLENDİRME ARMATÜRÜ-S LITE 30CM 180 DK EKO(TAVAN TİPİ)', 'Adet', '6', '25', 'USD', '35', 'EUR', '210', '10'),
  (@new_oid, '2729', '', 'YÖNLENDİRME ARMATÜRÜ-Z-LİTE S-1901 SÜREKLİ 180 DK 20 LED', 'Adet', '3', '15', 'USD', '24.5', 'EUR', '73.5', '11'),
  (@new_oid, '2729', '', 'KABLO 3X1,5 TTR', 'Mt', '400', '0.45', 'EUR', '0.82', 'EUR', '328', '12'),
  (@new_oid, '2729', '', 'LİFT-MENLİFT-PERSONEL TAŞIYICI-İSKELE', 'Set', '1', '0', 'TRY', '0', 'EUR', '0', '13'),
  (@new_oid, '2729', '', 'MONTAJ-DEVREYE ALMA-İŞÇİLİK', 'Set', '1', '50000', 'TRY', '1750', 'EUR', '1750', '14');

-- --------------------------------------------------------------------------
-- TEKLİF: TK3866 | Konu: ACİL AYDINLATMA VE YÖNLENDİRME | Tutar: 412459.33 TL | Eski ID: 4130
-- --------------------------------------------------------------------------
INSERT INTO `offers` (`offerNumber`, `cid`, `company_authors`, `payment_period`, `offer_subject`, `statu`, `description`, `created_at`, `total_price`, `mycompany`, `authors`, `offer_date`, `tax`, `creativer`, `regdate`, `updater`, `updated_at`, `notes`, `currency`, `dollar`, `euro`, `offer_header`, `offer_header_content`, `offer_footer`, `offer_footer_content`, `file`, `Kdv`, `iskonto`, `subdescription`, `buyTotal`, `tl_alis_toplam`, `tl_satis_toplam`, `saleTotal`, `amountTotal`, `curDollar`, `curEuro`, `DolarTotal`, `EuroTotal`, `TLTotal`, `tl_iskonto`, `euro_iskonto`, `dolar_iskonto`, `euro_alt_toplam`, `dolar_alt_toplam`, `tl_alt_toplam`, `euro_ara_toplam`, `dolar_ara_toplam`, `tl_ara_toplam`, `euro_kdv`, `dolar_kdv`, `tl_kdv`, `euro_kdvli_toplam`, `dolar_kdvli_toplam`, `tl_kdvli_toplam`, `tl_toplam_karsilik`, `is_template`, `reg_date`, `onay_tarihi`)
VALUES ('TK3866', '2044', 'ÖMER BEY', 'NAKİT', 'ACİL AYDINLATMA VE YÖNLENDİRME', '1', '', '2026-09-21 15:19:47', '412459.33', '', '', '18-11-2025', '0', '31', '2024-11-23', '1', '2026-09-21 15:19:47', '', 'Döviz Alış', '0', '0', '12', '', '11', '* Fiyatlarımıza KDV dahil değildir<br>* Döviz kuru fatura tarihinden bir gün öncesi TCMB satış kuru ile hesaplanır.<br>* Projelendirmenin tarafımızca çizilmesi&nbsp;(ücretli) kabul edildiğinde güncel mimari proje dijital ortamda ibraz edilmelidir.<br>* Fiziksel hasar,yüksek voltaj ve elektriksel nedenler haricinde ve PERİYODİK KONTROLLERİN TARAFIMIZCA YAPILMASI DURUMUNDA Sistem iki yıl garantilidir.<br>* Her türlü inşai işlem müşteriye aittir.', '', '20', NULL, '', NULL, '195817.98', '343716.12', NULL, NULL, '43.5397', '51.7129', NULL, NULL, NULL, '0', '0', '0', '7000.5', '0', '2000', '7000.5', '0', '2000', '1400.1', '0', '400', '8400.6', '0', '2400', '412459.33', '0', NULL, NULL);

SET @new_oid = LAST_INSERT_ID();

-- Teklif Kalemleri (9 kalem)
INSERT INTO `offermatters` (`oid`, `xid`, `stokKodu`, `title`, `unit`, `amount`, `buyprice`, `buycur`, `saleprice`, `salecur`, `total_price`, `satirno`)
VALUES
  (@new_oid, '2044', '', 'SPOT AYDINLATMA ARMATÜR-TWINLIGHT ECO 2X4,5 180 DK', 'Adet', '30', '30', 'USD', '45', 'EUR', '1350', '1'),
  (@new_oid, '2044', '', 'YÖNLENDİRME ARMATÜRÜ-Z-LİTE S-1901 SÜREKLİ 180 DK 20 LED', 'Adet', '21', '15', 'USD', '24.5', 'EUR', '514.5', '2'),
  (@new_oid, '2044', '', 'YÖNLENDİRME ARMATÜRÜ-S LITE 30CM 180 DK EKO(TAVAN TİPİ)', 'Adet', '24', '25', 'USD', '35', 'EUR', '840', '3'),
  (@new_oid, '2044', '', 'AYDINLATMA ARMATÜRÜ Z-LİTE A-1801 ORTAM AYDINLATMA ', 'Adet', '12', '15', 'USD', '24.5', 'EUR', '294', '4'),
  (@new_oid, '2044', '', 'YÖNLENDİRME ARMATÜRÜ CT 30CM 180 DK EKO', 'Adet', '1', '3', 'EUR', '15', 'EUR', '15', '5'),
  (@new_oid, '2044', '', 'KABLO 3X1,5 TTR', 'Mt', '1750', '0.45', 'EUR', '0.82', 'EUR', '1435', '6'),
  (@new_oid, '2044', '', 'KABLO KANALI-20 LİK YANMAZ BORU-AKSESUAR', 'Mt', '400', '0.4', 'EUR', '0.88', 'EUR', '352', '7'),
  (@new_oid, '2044', '', 'MONTAJ-DEVREYE ALMA-İŞÇİLİK', 'GRUP', '1', '1300', 'EUR', '2200', 'EUR', '2200', '8'),
  (@new_oid, '2044', '', 'LİFT-MENLİFT-PERSONEL TAŞIYICI', 'Set', '1', '1500', 'TRY', '2000', 'TRY', '2000', '9');

COMMIT;
