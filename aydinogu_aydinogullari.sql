-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 08 Oca 2024, 23:24:18
-- Sunucu sürümü: 10.6.16-MariaDB
-- PHP Sürümü: 8.1.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `aydinogu_aydinogullari`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cgroups`
--

CREATE TABLE `cgroups` (
  `id` int(11) NOT NULL,
  `title` varchar(155) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `statu` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Tablo döküm verisi `cgroups`
--

INSERT INTO `cgroups` (`id`, `title`, `regdate`, `statu`) VALUES
(1, 'Bilişim Teknolojileri', '2023-12-11 12:27:23', 1),
(3, 'Okul', '2023-12-11 12:27:57', 1),
(4, 'Temizlik Şirketi', '2023-12-11 12:28:05', 1),
(6, 'Hastane Hizmetleri', '2023-12-23 14:32:55', 1),
(97, 'şirket işlemleri', '2023-12-31 21:00:00', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `grp` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `address` varchar(250) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `cdesc` text DEFAULT NULL,
  `gsm` varchar(155) DEFAULT NULL,
  `gsm2` varchar(155) DEFAULT NULL,
  `yetkili` varchar(255) DEFAULT NULL,
  `sunvan` varchar(255) DEFAULT NULL,
  `vdaire` varchar(255) DEFAULT NULL,
  `vno` varchar(75) DEFAULT NULL,
  `regdate` timestamp NULL DEFAULT current_timestamp(),
  `reg_date` varchar(255) DEFAULT NULL,
  `creativer` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `customers`
--

INSERT INTO `customers` (`id`, `grp`, `name`, `email`, `company`, `sector`, `address`, `city`, `cdesc`, `gsm`, `gsm2`, `yetkili`, `sunvan`, `vdaire`, `vno`, `regdate`, `reg_date`, `creativer`) VALUES
(1, 3, 'Ertan GÜNEŞ yeniş', 'ertan@gmail.com', 'Denemebilişim', 'bilişim', 'gdfgdhsddfgdfhsd', 'Tekirdağ', '', '05558522244', '', 'Ertan GÜNEŞ', 'Yönetici', '', '', '2023-12-11 12:29:44', '11-12-2023', 1),
(34, NULL, 'Mehmet Ali Gökmen yeni eklendi', 'beyzade83@hotmail.com', 'km', '', 'km', 'turkey', '', '', '+905079432723', '', '', '', '', '2023-12-31 16:53:32', '31-12-2023', 1),
(40, NULL, 'Mehmet Ali Gökmen yeni eklendi', 'beyzade83@hotmail.com', 'km', '', 'km', 'turkey', '', '', '+905079432723', '', '', '', '', '2023-12-31 16:58:29', '31-12-2023', 1),
(41, NULL, 'Mehmet Ali Gökmen yeni eklendi', 'beyzade83@hotmail.com', 'km', '', 'km', 'turkey', '', '', '+905079432723', '', '', '', '', '2023-12-31 16:58:49', '31-12-2023', 1),
(42, NULL, 'Mehmet Ali Gökmen yeni eklendi', 'beyzade83@hotmail.com', 'km', '', 'km', 'turkey', '', '', '+905079432723', '', '', '', '', '2023-12-31 16:59:11', '31-12-2023', 1),
(43, NULL, 'Mehmet Ali Gökmen yeni eklendi', 'beyzade83@hotmail.com', 'km', '', 'km', 'turkey', '', '', '+905079432723', '', '', '', '', '2023-12-31 17:03:28', '31-12-2023', 1),
(44, NULL, 'Mehmet Ali Gökmen yeni eklendi', 'beyzade83@hotmail.com', 'km', '', 'km', 'turkey', '', '', '+905079432723', '', '', '', '', '2023-12-31 17:25:13', '31-12-2023', 1),
(45, NULL, 'Mehmet Ali Gökmen', 'aydinogullariyansonchz@gmail.com', 'Aydinoğulları Yangın Söndürme', 'Yangın Söndürme Cihaz', 'Bursa', 'Bursa', 'yeni eklenen müşteri', '05079432723', '+905079432723', '', '', '', '', '2024-01-01 12:59:34', '01-01-2024', 1),
(46, NULL, 'Mehmet Ali Gökmen ikinci firma', 'aydinogullariyansonchz@gmail.com', '', 'Yangın Söndürme Cihaz', 'Bursa', 'Bursa', '', '05079432723', '+905079432723', '', '', '', '', '2024-01-01 13:01:55', '01-01-2024', 1),
(47, NULL, 'Mehmet Ali Gökmen ikinci firma', 'aydinogullariyansonchz@gmail.com', '', 'Yangın Söndürme Cihaz', 'Bursa', 'Bursa', '', '05079432723', '+905079432723', '', '', '', '', '2024-01-01 13:03:32', '01-01-2024', 1),
(48, NULL, 'Mehmet Ali Gökmen ikinci firma', 'aydinogullariyansonchz@gmail.com', '', 'Yangın Söndürme Cihaz', 'Bursa', 'Bursa', '', '05079432723', '+905079432723', '', '', '', '', '2024-01-01 13:04:40', '01-01-2024', 1),
(49, NULL, 'Mehmet Ali Gökmen ikinci firma', 'aydinogullariyansonchz@gmail.com', '', 'Yangın Söndürme Cihaz', 'Bursa', 'Bursa', '', '05079432723', '+905079432723', '', '', '', '', '2024-01-01 13:05:15', '01-01-2024', 1),
(50, NULL, 'Mehmet Ali Gökmen ikinci firma', 'aydinogullariyansonchz@gmail.com', '', 'Yangın Söndürme Cihaz', 'Bursa', 'Bursa', '', '05079432723', '+905079432723', '', '', '', '', '2024-01-01 13:06:31', '01-01-2024', 1),
(51, NULL, 'Mehmet Ali Gökmen ikinci firma', 'aydinogullariyansonchz@gmail.com', '', 'Yangın Söndürme Cihaz', 'Bursa', 'Bursa', '', '05079432723', '+905079432723', '', '', '', '', '2024-01-01 13:07:56', '01-01-2024', 1),
(52, NULL, 'Mehmet Ali Gökmen ikinci firma', 'aydinogullariyansonchz@gmail.com', '', 'Yangın Söndürme Cihaz', 'Bursa', 'Bursa', '', '05079432723', '+905079432723', '', '', '', '', '2024-01-01 13:10:18', '01-01-2024', 1),
(53, NULL, 'Mehmet Ali Gökmen ikinci firma', 'aydinogullariyansonchz@gmail.com', '', 'Yangın Söndürme Cihaz', 'Bursa', 'Bursa', '', '05079432723', '+905079432723', '', '', '', '', '2024-01-01 13:11:51', '01-01-2024', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `documents`
--

CREATE TABLE `documents` (
  `ID` int(11) NOT NULL,
  `d_cname` varchar(255) CHARACTER SET utf32 COLLATE utf32_turkish_ci NOT NULL,
  `createtime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `uid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `documents`
--

INSERT INTO `documents` (`ID`, `d_cname`, `createtime`, `uid`) VALUES
(1, 'dfsdfaf', '0000-00-00 00:00:00', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `entry_categories`
--

CREATE TABLE `entry_categories` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `evraktakip`
--

CREATE TABLE `evraktakip` (
  `id` int(11) NOT NULL,
  `firma` varchar(100) DEFAULT NULL,
  `evrakturu` varchar(25) DEFAULT NULL,
  `kategori` varchar(100) DEFAULT NULL,
  `adet` varchar(11) DEFAULT NULL,
  `teslimalan` varchar(50) DEFAULT NULL,
  `teslimeden` varchar(50) DEFAULT NULL,
  `teslimtarihi` varchar(11) DEFAULT NULL,
  `aciklama` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `evraktakip`
--

INSERT INTO `evraktakip` (`id`, `firma`, `evrakturu`, `kategori`, `adet`, `teslimalan`, `teslimeden`, `teslimtarihi`, `aciklama`) VALUES
(10, 'Deneme', 'Giden', 'Excel dosyası teslim', '3', '1', '1', '01-01-2024', 'Deneme 1 2'),
(8, 'Deneme4', 'Gelen', 'Excel dosyası teslim', '3', '1', '1', '06-01-2024', 'asdfsd');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `oid` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Tablo döküm verisi `files`
--

INSERT INTO `files` (`id`, `pid`, `oid`, `filename`, `regdate`) VALUES
(48, 0, 41, 'files/projects/offers/Ekran görüntüsü 2024-01-07 131953.jpg', '2024-01-07 18:39:12'),
(47, 0, 41, 'files/projects/offers/Kübra Giden.jpg', '2024-01-07 18:37:44'),
(44, 0, 41, 'files/projects/offers/Kübra Giden.jpg', '2024-01-07 18:33:45'),
(43, 0, 41, 'files/projects/offers/Kübra Giden.jpg', '2024-01-07 18:32:41');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `indocument_categories`
--

CREATE TABLE `indocument_categories` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `indocument_categories`
--

INSERT INTO `indocument_categories` (`id`, `title`, `type`) VALUES
(1, 'Excel evrak teslimi', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `inexps`
--

CREATE TABLE `inexps` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `descs` varchar(255) NOT NULL,
  `cat` varchar(255) NOT NULL,
  `sdate` varchar(100) NOT NULL,
  `pay` varchar(150) NOT NULL,
  `pay_method` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `dates` varchar(255) NOT NULL,
  `clock` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `author` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `logs`
--

INSERT INTO `logs` (`id`, `type`, `dates`, `clock`, `message`, `author`) VALUES
(1, 1, '12-07-2020', '19:22:56', 'panelden çıkış yaptı.', 1),
(2, 1, '11-12-2023', '15:19:34', 'panelden çıkış yaptı.', 1),
(3, 1, '11-12-2023', '16:28:24', 'panelden çıkış yaptı.', 1),
(4, 1, '12-12-2023', '17:15:54', 'panelden çıkış yaptı.', 1),
(5, 1, '13-12-2023', '21:57:36', 'panelden çıkış yaptı.', 1),
(6, 1, '14-12-2023', '00:03:45', 'panelden çıkış yaptı.', 1),
(7, 1, '14-12-2023', '00:06:51', 'panelden çıkış yaptı.', 1),
(8, 1, '15-12-2023', '21:33:17', 'panelden çıkış yaptı.', 1),
(9, 1, '15-12-2023', '21:35:13', 'panelden çıkış yaptı.', 8),
(10, 1, '15-12-2023', '22:12:43', 'panelden çıkış yaptı.', 1),
(11, 1, '15-12-2023', '22:15:49', 'panelden çıkış yaptı.', 8),
(12, 1, '15-12-2023', '23:54:15', 'panelden çıkış yaptı.', 1),
(13, 1, '16-12-2023', '22:56:43', 'panelden çıkış yaptı.', 1),
(14, 1, '16-12-2023', '23:19:03', 'panelden çıkış yaptı.', 1),
(15, 1, '17-12-2023', '21:22:49', 'panelden çıkış yaptı.', 1),
(16, 1, '17-12-2023', '22:24:06', 'panelden çıkış yaptı.', 1),
(17, 1, '17-12-2023', '22:24:44', 'panelden çıkış yaptı.', 12),
(18, 1, '17-12-2023', '22:42:18', 'panelden çıkış yaptı.', 1),
(19, 1, '17-12-2023', '23:37:56', 'panelden çıkış yaptı.', 1),
(20, 1, '18-12-2023', '11:20:02', 'panelden çıkış yaptı.', 1),
(21, 1, '18-12-2023', '11:25:10', 'panelden çıkış yaptı.', 1),
(22, 1, '18-12-2023', '11:33:12', 'panelden çıkış yaptı.', 1),
(23, 1, '18-12-2023', '11:40:46', 'panelden çıkış yaptı.', 1),
(24, 1, '18-12-2023', '20:37:54', 'panelden çıkış yaptı.', 1),
(25, 1, '18-12-2023', '20:41:38', 'panelden çıkış yaptı.', 13),
(26, 1, '26-12-2023', '22:00:00', 'panelden çıkış yaptı.', 1),
(27, 1, '28-12-2023', '00:12:03', 'panelden çıkış yaptı.', 15),
(28, 1, '28-12-2023', '00:12:45', 'panelden çıkış yaptı.', 15),
(29, 1, '28-12-2023', '00:13:26', 'panelden çıkış yaptı.', 15),
(30, 1, '28-12-2023', '00:13:31', 'panelden çıkış yaptı.', 15),
(31, 1, '30-12-2023', '20:51:46', 'panelden çıkış yaptı.', 1),
(32, 1, '30-12-2023', '21:15:16', 'panelden çıkış yaptı.', 1),
(33, 1, '31-12-2023', '18:11:54', 'panelden çıkış yaptı.', 92);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `mail_logs`
--

CREATE TABLE `mail_logs` (
  `id` int(11) NOT NULL,
  `tomail` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `datest` timestamp NOT NULL DEFAULT current_timestamp(),
  `statu` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `mainservices`
--

CREATE TABLE `mainservices` (
  `id` int(11) NOT NULL,
  `stitle` varchar(255) NOT NULL,
  `sdesc` text NOT NULL,
  `regdate` varchar(255) NOT NULL,
  `color` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `mainservices`
--

INSERT INTO `mainservices` (`id`, `stitle`, `sdesc`, `regdate`, `color`) VALUES
(1, 'Yangın Söndürme Ekipmanları', '', '11-12-2023 - 15:36:04', 'black');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `missioncategory`
--

CREATE TABLE `missioncategory` (
  `id` int(11) NOT NULL,
  `categoryName` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `missioncategory`
--

INSERT INTO `missioncategory` (`id`, `categoryName`) VALUES
(1, 'Destek'),
(2, 'Telefon Görüşmesi'),
(3, 'Yapılacak Görev'),
(4, 'Genel'),
(13, 'Sayfa yenilenmeden eklendi'),
(14, 'Sayfa yenilenmeden yeni bir kategori eklendi'),
(15, 'yeni bir kategori'),
(16, 'yrn, katedfda '),
(17, 'yeni bir kategori ekledim'),
(18, 'dgsdfgsdfg sfg s'),
(19, 'fgsdfgsdfg sdfg sf sdfgsdfgsdf'),
(20, 'yeni eklenen kategori'),
(21, 'dfdf'),
(22, 'adf');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `missions`
--

CREATE TABLE `missions` (
  `id` int(11) NOT NULL,
  `FirmaAdi` varchar(255) DEFAULT NULL,
  `categoryName` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `mdesc` varchar(255) NOT NULL,
  `regdate` varchar(255) NOT NULL,
  `startdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastdate` varchar(255) NOT NULL,
  `authors` varchar(100) NOT NULL,
  `creativer` int(11) NOT NULL,
  `urgency` varchar(100) NOT NULL,
  `okeydate` varchar(255) NOT NULL,
  `deleted` varchar(11) NOT NULL DEFAULT 'no',
  `statu` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Tablo döküm verisi `missions`
--

INSERT INTO `missions` (`id`, `FirmaAdi`, `categoryName`, `title`, `mdesc`, `regdate`, `startdate`, `lastdate`, `authors`, `creativer`, `urgency`, `okeydate`, `deleted`, `statu`) VALUES
(25, NULL, '1', 'Son Kullanıcı Kaydı', '', '30-12-2023', '0000-00-00 00:00:00', '', '1|', 1, 'Orta', '-', 'no', 0),
(26, NULL, '2', 'Son Kullanıcı Kaydı denemesi', '', '30-12-2023', '0000-00-00 00:00:00', '', '1|92|52|', 1, 'Orta', '-', 'no', 0),
(23, NULL, '4', 'yeni ir kullanıcı ekleme', '', '30-12-2023', '0000-00-00 00:00:00', '', '1|52|', 1, 'Yüksek', '-', 'no', 0),
(24, NULL, '2', 'Son Kullanıcı Kaydı', '', '30-12-2023', '0000-00-00 00:00:00', '', '1|52|', 1, 'Orta', '-', 'no', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `dates` varchar(255) NOT NULL,
  `lastdate` varchar(255) NOT NULL,
  `creativer` int(11) NOT NULL,
  `urgency` varchar(255) NOT NULL,
  `descs` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `notes`
--

INSERT INTO `notes` (`id`, `category`, `title`, `dates`, `lastdate`, `creativer`, `urgency`, `descs`) VALUES
(1, '1', 'Deneme', '12-12-2023', '21-12-2023', 1, 'Yüksek', 'Meb ile telefon görüşmesi yapılacak'),
(2, '1', '', '29-12-2023', '', 1, 'Yüksek', ''),
(3, '1', '', '29-12-2023', '', 1, 'Yüksek', ''),
(4, '1', 'Hastane Hizmetleri fdfdfd', '29-12-2023', '', 1, 'Orta', ''),
(5, '1', 'deneme note', '29-12-2023', '', 1, 'Orta', ''),
(6, '1', 'Hastane Hizmetleri', '13-12-2023', '', 1, 'Yüksek', 'mehmet Ali <b><u>Gökmen</u></b>'),
(7, '1', 'Hastane Hizmetleri', '13-12-2023', '28-12-2023', 1, 'Yüksek', 'demene');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `note_categories`
--

CREATE TABLE `note_categories` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `regdate` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Tablo döküm verisi `note_categories`
--

INSERT INTO `note_categories` (`id`, `title`, `regdate`) VALUES
(1, 'Telefon Görüşmesi', '11-12-2023');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `offermatters`
--

CREATE TABLE `offermatters` (
  `id` int(11) NOT NULL,
  `xid` int(11) NOT NULL,
  `oid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `price` varchar(255) NOT NULL,
  `total` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Tablo döküm verisi `offermatters`
--

INSERT INTO `offermatters` (`id`, `xid`, `oid`, `title`, `unit`, `amount`, `price`, `total`) VALUES
(1, 1, 1, 'Yangın Söndürme Tüpü', 'Adet', '1', '3', '3'),
(2, 1, 2, 'Yangın Söndürme Tüpü', 'Adet', '1', '3', '3'),
(3, 1, 3, 'Yangın Söndürme Tüpü', 'Adet', '1', '3', '3'),
(4, 1, 4, 'Yangın Söndürme Tüpü', 'Adet', '3', '5', '15'),
(5, 1, 5, 'Yangın Söndürme Tüpü', 'Adet', '10', '5', '50'),
(6, 1, 6, 'Yangın Söndürme Tüpü', 'Adet', '3', '5', '15'),
(7, 1, 7, 'Yangın Söndürme Tüpü', 'Adet', '10', '4', '40'),
(8, 1, 8, 'Yangın Söndürme Tüpü', 'Takım', '2', '100', '200'),
(9, 1, 9, 'Yangın Söndürme Tüpü', 'Adet', '34', '3434', '116756'),
(20, 1, 43, 'Deneme Ürünü454', 'Adet', '34', '44', '1496'),
(19, 1, 43, 'Deneme Ürünü', 'Adet', '89', '44', '3916'),
(83, 0, 41, 'Deneme Ürünü', 'Adet', '12', '4', '48'),
(21, 1, 44, 'Deneme Ürünü', 'Adet', '89', '44', '3916'),
(22, 1, 45, 'Deneme Ürünü454', 'Adet', '89', '44', '3916'),
(23, 1, 46, 'Deneme Ürünü', 'Adet', '89', '44', '3916'),
(84, 0, 41, 'Deneme Ürünü454', 'Adet', '12', '9', '108');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `offers`
--

CREATE TABLE `offers` (
  `id` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `total_price` int(11) NOT NULL,
  `mycompany` varchar(150) NOT NULL,
  `authors` varchar(50) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `reg_date` varchar(255) NOT NULL,
  `tax` int(7) NOT NULL,
  `creativer` int(11) NOT NULL,
  `notes` text NOT NULL,
  `currency` varchar(11) NOT NULL,
  `statu` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `offers`
--

INSERT INTO `offers` (`id`, `cid`, `total_price`, `mycompany`, `authors`, `regdate`, `reg_date`, `tax`, `creativer`, `notes`, `currency`, `statu`) VALUES
(48, 47, 3916, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|92|', '2024-01-04 20:51:36', '04-01-2024', 20, 1, '', 'TL', 0),
(36, 52, 184, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|92|', '2024-01-02 19:12:15', '02-01-2024', 20, 1, '', 'TL', 0),
(37, 53, 270, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|92|', '2024-01-02 19:28:16', '02-01-2024', 20, 1, '', 'TL', 0),
(38, 53, 3916, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|92|', '2024-01-02 19:28:56', '02-01-2024', 20, 1, '', 'TL', 0),
(39, 49, 5480, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|92|', '2024-01-02 19:29:55', '02-01-2024', 20, 1, '', 'TL', 0),
(40, 53, 4238, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|92|', '2024-01-02 19:31:42', '02-01-2024', 20, 1, '', 'TL', 0),
(41, 52, 156, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|92|52|', '2024-01-02 19:56:17', '02-01-2024', 20, 1, 'deneme ikkinvşi ol dadfadf', 'tl', 0),
(42, 47, 0, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|', '2024-01-03 18:59:49', '03-01-2024', 20, 1, '', 'TL', 0),
(43, 47, 5412, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|', '2024-01-03 19:15:20', '03-01-2024', 20, 1, '', 'TL', 0),
(44, 47, 3916, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|', '2024-01-03 19:15:28', '03-01-2024', 20, 1, '', 'TL', 0),
(45, 46, 3916, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|92|52|', '2024-01-03 19:30:08', '03-01-2024', 20, 1, '', 'TL', 0),
(46, 45, 3916, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|92|52|', '2024-01-03 19:30:55', '03-01-2024', 20, 1, '', 'TL', 0),
(47, 47, 3916, 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti', '1|92|52|', '2024-01-04 20:02:53', '04-01-2024', 20, 1, '', 'TL', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ofstats`
--

CREATE TABLE `ofstats` (
  `id` int(11) NOT NULL,
  `sid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `color` varchar(100) NOT NULL,
  `icon` varchar(90) NOT NULL,
  `button` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `ofstats`
--

INSERT INTO `ofstats` (`id`, `sid`, `title`, `description`, `color`, `icon`, `button`) VALUES
(1, 0, 'Bekleyen Teklif', 'Teklif hazırlandı, henüz müşteriye gönderilmedi.', 'blue', 'icon-copy ion-alert-circled', 'info'),
(2, 1, 'Müşteriye İletildi', 'Müşteriye iletildi, geri dönüş bekleniyor.', 'grey', 'icon-copy ion-clock', 'secondary'),
(3, 2, 'Teklif Onaylandı', 'Teklif Onaylandı, çalışmak için proje oluşturulması bekleniyor.', 'green', 'icon-copy ion-checkmark-circled', 'success'),
(4, 3, 'Çalışmaya Başlandı', 'Proje üzerine çalışmaya başlanıldı.', 'grey', 'icon-copy ion-android-settings', 'info'),
(5, 4, 'Teklif Reddedildi', 'Teklif, müşteri tarafından reddedildi.', 'red', 'icon-copy ion-close-circled', 'danger'),
(6, 5, 'İş / Proje Tamamlandı!', 'İş teslim edildi, ödeme alındı, proje tamam!', 'grey', 'icon-copy ion-checkmark-round', 'success');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `p_title` varchar(255) NOT NULL,
  `p_link` varchar(255) NOT NULL,
  `pid` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `pages`
--

INSERT INTO `pages` (`id`, `p_title`, `p_link`, `pid`) VALUES
(55, 'Mail Kayıtları', 'mail-logs', 15),
(34, 'Ödeme Hesapları', 'pay-methods-list', 14),
(3, 'Proje Oluştur', 'new-project', 2),
(4, 'Projeleri Listele', 'all-projects', 2),
(31, 'Ana Sayfa', 'home', 9),
(32, 'Tüm Projelerim', 'all-projects', 10),
(6, 'Yeni Müşteri', 'new-customer', 3),
(7, 'Müşteri Listesi', 'customer-list', 3),
(8, 'Kategorileri Düzenle', 'categories', 4),
(9, 'Ürün & Hizmetler', 'products', 4),
(29, 'Ürün/Hizmet Bilgisi', 'edit-product', 0),
(10, 'Ekip Üyesi Ekle', 'new-user', 5),
(11, 'Ekip Üyeleri', 'all-users', 5),
(35, 'Yeni Ödeme Hesabı', 'new-pay-method', 14),
(13, 'Yeni Oluştur', 'new-task', 6),
(14, 'Yapılacaklar Listesi', 'todolist', 6),
(53, 'Gelir/Gider Kategorileri', 'entry-expense-categories', 7),
(17, 'Gelir-Gider Listesi', 'income-expense-list', 7),
(30, 'Ekip Üyesi Düzenle', 'edit-user', 0),
(19, 'Yeni Teklif Girişi', 'new-offer', 8),
(20, 'Teklifleri Görüntüle', 'all-offers', 8),
(21, 'Teklif Görüntüle', 'offer', 0),
(22, 'Teklif Sil', 'deleteoffer', 0),
(23, 'Ayarlar', 'settings', 0),
(24, 'Teklifi Düzenle', 'edit-offer', 0),
(25, 'Projeyi Düzenle', 'edit-project', 0),
(26, 'Müşteri Düzenle', 'edit-customer', 0),
(28, 'Ürün/Hizmet Oluştur', 'new-product', 4),
(36, 'Satış Yap', 'new-sales', 11),
(37, 'Satışları Görüntüle', 'sales', 11),
(38, 'Satışı Görüntüle&Düzenle', 'edit-sale', 0),
(39, 'Notlar', 'all-notes', 12),
(40, 'Hesap Bilgileri', 'edit-pay-method', 0),
(44, 'Aylık Ödemeler', 'monthly-payments', 13),
(43, 'Hatırlatıcı Oluştur', 'reminder-planner', 0),
(45, 'Yıllık Ödemeler', 'yearly-payments', 13),
(46, 'Not Oluştur', 'new-note', 12),
(47, 'Not Düzenle', 'edit-note', 0),
(48, 'Düzenle', 'edit-task', 0),
(49, 'Yeni Ödeme', 'new-payment', 0),
(50, 'Ödeme Görüntüle', 'info-payment', 0),
(51, 'Gelir Ekle', 'new-entry', 7),
(52, 'Gider Ekle', 'new-expense', 7),
(54, 'Tek Seferlik Ödemeler', 'for-once-payments', 13),
(57, 'İzin Ayarları', 'permission-settings', 5),
(58, 'Yeni Pozisyon Oluştur', 'new-perm', 0),
(59, 'İzin Düzenle', 'edit-perm', 0),
(60, 'Teklif Birimleri', 'units', 0),
(61, 'Not Kategori Ayarları', 'note-categories', 12),
(62, 'Görev Oluştur', 'new-mission', 0),
(63, 'Görevlerim', 'my-missions', 0),
(64, 'Görev Görüntüle', 'view-mission', 0),
(65, 'Tüm Görevler', 'all-missions', 0),
(66, 'Verdiğim Görevler', 'mygmissions', 0),
(67, 'Müşteri Grupları', 'customer-groups', 0),
(68, 'Mail Gönder', 'send-mail', 0),
(69, 'SMS Gönder', 'send-sms', 0),
(70, 'Müşteriye Ait Ödemeler', 'view-sales-c', 0),
(71, 'Teklif Birimini Düzenle', 'edit-unit', 0),
(72, 'Müşteri Grubunu Düzenle', 'edit-cgroup', 0),
(73, 'Dosya Yükle', 'new-file', 0),
(74, 'Dosyaları Görüntüle', 'all-files', 0),
(75, 'Dosya Kategorileri', 'file-categories', 0),
(76, 'Dosya Kategorisini Düzenle', 'edit-fcategory', 0),
(77, 'Giden Evrak Listesi', 'view-outdocument', 70),
(78, 'Evrak Ekle', 'new-indocument', 71),
(79, 'Yeni Servis Ekle', 'new-service', 81),
(80, 'Servisleri Listele', 'all-services', 82),
(83, 'Gelen Evrak Listesi', 'view-indocument', 70),
(85, 'Kategori Düzenle', 'indocument-categories', 70);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `saleid` int(11) NOT NULL,
  `lastdate` varchar(50) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pay_method` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `okey` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Tablo döküm verisi `payments`
--

INSERT INTO `payments` (`id`, `pid`, `cid`, `saleid`, `lastdate`, `regdate`, `pay_method`, `type`, `okey`) VALUES
(3, 1, 1, 3, '08-12-2023', '2023-12-17 18:33:12', 1, 'yillik', 1),
(4, 1, 1, 4, '04-12-2023', '2023-12-17 17:15:09', 1, 'yillik', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pay_methods`
--

CREATE TABLE `pay_methods` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `iban` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `accountno` varchar(255) NOT NULL,
  `branchcode` varchar(255) NOT NULL,
  `currency` varchar(11) NOT NULL,
  `total` int(255) NOT NULL,
  `last_action` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `pay_methods`
--

INSERT INTO `pay_methods` (`id`, `title`, `iban`, `author`, `accountno`, `branchcode`, `currency`, `total`, `last_action`) VALUES
(12, 'df', '', '', '', '', 'tl', 0, '19-12-2023 - 23:39:37'),
(13, 'sdff', '', '', '', '', 'tl', 0, '19-12-2023 - 23:50:08'),
(14, 'sdfsdf', '', '', '', '', 'tl', 0, '19-12-2023 - 23:57:05'),
(15, 'sfgsfdgsdf', '', '', '', '', 'tl', 0, '20-12-2023 - 00:03:36'),
(16, 'denmeme', '', '', '', '', 'tl', 0, '20-12-2023 - 00:09:22');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pcategories`
--

CREATE TABLE `pcategories` (
  `id` int(11) NOT NULL,
  `xxid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `statu` int(11) NOT NULL DEFAULT 1,
  `perm` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `pcategories`
--

INSERT INTO `pcategories` (`id`, `xxid`, `title`, `icon`, `statu`, `perm`) VALUES
(15, 16, 'Mail İşlemleri', 'fa fa-wpexplorer', 1, '1'),
(2, 4, 'Projeler', 'fa fa-table', 1, '1'),
(3, 2, 'Müşteriler', 'fa fa-pencil', 1, '1'),
(4, 5, 'Hizmet&Fiyatlar', 'fa fa-paint-brush', 1, '1'),
(5, 8, 'Ekip', 'fa fa-clone', 1, '1'),
(6, 7, 'Yapılacaklar', 'fa fa-calendar', 1, '1'),
(7, 6, 'Gelir-Gider Takibi', 'fa fa-calculator', 1, '1'),
(8, 3, 'Teklifler', 'fa fa-file-o', 1, '1'),
(11, 2, 'Satış', 'fa fa-arrow-right', 1, '1'),
(12, 7, 'Notlar', 'fa fa-sticky-note-o', 1, '1'),
(13, 4, 'Ödemeler', 'fa fa-dollar', 1, '1'),
(14, 2, 'Kasa', 'fa fa-calculator', 1, '1');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `perms`
--

CREATE TABLE `perms` (
  `id` int(11) NOT NULL,
  `p_title` varchar(255) NOT NULL,
  `cadd` varchar(11) DEFAULT '0',
  `cedit` varchar(11) DEFAULT '0',
  `cdelete` varchar(11) DEFAULT NULL,
  `sadd` varchar(11) DEFAULT NULL,
  `sedit` varchar(11) DEFAULT NULL,
  `sreminder` varchar(11) DEFAULT NULL,
  `sdelete` varchar(11) DEFAULT NULL,
  `pmadd` varchar(11) DEFAULT NULL,
  `pmview` varchar(11) DEFAULT NULL,
  `pmdelete` varchar(11) DEFAULT NULL,
  `oadd` varchar(11) DEFAULT NULL,
  `oview` varchar(11) DEFAULT NULL,
  `oedit` varchar(11) DEFAULT NULL,
  `odelete` varchar(11) DEFAULT NULL,
  `padd` varchar(11) DEFAULT NULL,
  `pedit` varchar(11) DEFAULT NULL,
  `pdelete` varchar(11) DEFAULT NULL,
  `payadd` varchar(11) DEFAULT NULL,
  `payview` varchar(11) DEFAULT NULL,
  `paydelete` varchar(11) DEFAULT NULL,
  `seradd` varchar(11) DEFAULT NULL,
  `seredit` varchar(11) DEFAULT NULL,
  `serdelete` varchar(11) DEFAULT NULL,
  `sercategory` varchar(11) DEFAULT NULL,
  `exadd` varchar(11) DEFAULT NULL,
  `exview` varchar(11) DEFAULT NULL,
  `excategory` varchar(11) DEFAULT NULL,
  `exdelete` varchar(11) DEFAULT NULL,
  `todoadd` varchar(11) DEFAULT NULL,
  `todoedit` varchar(11) DEFAULT NULL,
  `tododelete` varchar(11) DEFAULT NULL,
  `noteadd` varchar(11) DEFAULT NULL,
  `noteedit` varchar(11) DEFAULT NULL,
  `notedelete` varchar(11) DEFAULT NULL,
  `uadd` varchar(11) DEFAULT NULL,
  `uedit` varchar(11) DEFAULT NULL,
  `udelete` varchar(11) DEFAULT NULL,
  `uperm` varchar(11) DEFAULT NULL,
  `misadd` varchar(11) DEFAULT NULL,
  `mistake` varchar(11) DEFAULT NULL,
  `allmisview` varchar(11) DEFAULT NULL,
  `mlogview` varchar(11) DEFAULT NULL,
  `setview` varchar(11) DEFAULT NULL,
  `fadd` varchar(11) DEFAULT NULL,
  `fview` varchar(11) DEFAULT NULL,
  `fdelete` varchar(11) DEFAULT NULL,
  `docdelete` varchar(11) DEFAULT NULL,
  `indocview` varchar(11) DEFAULT NULL,
  `docinadd` varchar(11) DEFAULT NULL,
  `outdocview` varchar(11) DEFAULT NULL,
  `servadd` varchar(11) DEFAULT NULL,
  `servview` varchar(11) DEFAULT NULL,
  `servdelete` varchar(11) DEFAULT NULL,
  `indoccategories` varchar(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `perms`
--

INSERT INTO `perms` (`id`, `p_title`, `cadd`, `cedit`, `cdelete`, `sadd`, `sedit`, `sreminder`, `sdelete`, `pmadd`, `pmview`, `pmdelete`, `oadd`, `oview`, `oedit`, `odelete`, `padd`, `pedit`, `pdelete`, `payadd`, `payview`, `paydelete`, `seradd`, `seredit`, `serdelete`, `sercategory`, `exadd`, `exview`, `excategory`, `exdelete`, `todoadd`, `todoedit`, `tododelete`, `noteadd`, `noteedit`, `notedelete`, `uadd`, `uedit`, `udelete`, `uperm`, `misadd`, `mistake`, `allmisview`, `mlogview`, `setview`, `fadd`, `fview`, `fdelete`, `docdelete`, `indocview`, `docinadd`, `outdocview`, `servadd`, `servview`, `servdelete`, `indoccategories`) VALUES
(1, 'Genel Yönetici', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on'),
(25, 'User', NULL, NULL, NULL, NULL, 'on', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'on', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'on', NULL, NULL, NULL, NULL, 'on', NULL, 'on', 'on', 'on', NULL, NULL, NULL, NULL, NULL, 'on', 'on', NULL, NULL, 'on', NULL, NULL, NULL, NULL, NULL, 'NULL', NULL, NULL, NULL, NULL),
(26, 'İşçi', NULL, NULL, NULL, NULL, 'on', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'on', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'NULL', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `products`
--

CREATE TABLE `products` (
  `ID` int(11) NOT NULL,
  `Adi` varchar(255) DEFAULT NULL,
  `Turu` varchar(255) DEFAULT NULL,
  `TedarikciID` bigint(16) DEFAULT NULL,
  `StokKodu` varchar(16) DEFAULT NULL,
  `UrunGrubu` varchar(255) DEFAULT NULL,
  `Birimi` varchar(50) DEFAULT NULL,
  `AlisFiyati` varchar(16) DEFAULT NULL,
  `AlisParaBirimi` varchar(16) DEFAULT NULL,
  `AlisKDV` int(16) DEFAULT NULL,
  `AlisIskonto` int(16) DEFAULT NULL,
  `SatisFiyati` decimal(16,0) DEFAULT NULL,
  `SatisParaBirimi` varchar(16) DEFAULT NULL,
  `SatisKDV` int(16) DEFAULT NULL,
  `SatisIskonto` int(16) DEFAULT NULL,
  `ExtraMaliyet` decimal(16,0) DEFAULT NULL,
  `Barkod` varchar(16) DEFAULT NULL,
  `Teslimat` date DEFAULT NULL,
  `RafKodu` varchar(16) DEFAULT NULL,
  `MinStok` int(10) DEFAULT NULL,
  `Aciklama` varchar(255) DEFAULT NULL,
  `Durum` varchar(16) DEFAULT NULL,
  `OlusturmaTarihi` varchar(10) NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_turkish_ci;

--
-- Tablo döküm verisi `products`
--

INSERT INTO `products` (`ID`, `Adi`, `Turu`, `TedarikciID`, `StokKodu`, `UrunGrubu`, `Birimi`, `AlisFiyati`, `AlisParaBirimi`, `AlisKDV`, `AlisIskonto`, `SatisFiyati`, `SatisParaBirimi`, `SatisKDV`, `SatisIskonto`, `ExtraMaliyet`, `Barkod`, `Teslimat`, `RafKodu`, `MinStok`, `Aciklama`, `Durum`, `OlusturmaTarihi`) VALUES
(25, 'Deneme Ürünü', 'Ürün', NULL, 'stk1515', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(33, 'Deneme Ürünü', 'Hizmet', NULL, 'stk1515', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(36, 'Deneme Ürünü', 'Hizmet', NULL, 'stk1515', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(40, 'Deneme Ürünü', 'Hizmet', NULL, 'stk1515', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(43, 'Deneme Ürünü454', 'Ürün', NULL, 'stk1515', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(44, 'Deneme Ürünü454', 'Ürün', 1, 'stk1515', 'Yangın Söndürme Cihazı', 'Adet', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(46, 'Deneme Ürünü', 'Hizmet', 1, 'stk1515', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(47, 'Deneme Ürünü', 'Ürün', 1, 'stk1515', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(48, 'Deneme Ürünü', 'Ürün', 1, 'stk1515', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(49, 'Deneme Ürünü', NULL, NULL, 'stk1515', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(50, 'Deneme Ürünü', 'Ürün', NULL, 'stk1515', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(52, 'Deneme Ürünü', NULL, NULL, 'stk78987', 'Yangın Söndürme Cihazı', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(53, 'Deneme Ürünü', 'Ürün', 1, 'stk78987', 'Yangın Söndürme Cihazı', 'Adet', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '25-12-2023'),
(54, 'Deneme Ürünü', 'Ürün', 1, 'stk99999', 'Yangın Söndürme Cihazı', NULL, '25', 'TL', 0, 2434, NULL, 'TL', 0, 345, NULL, '345', '0000-00-00', 'Raf Kodu', 345, '	', 'Aktif', '25-12-2023'),
(55, 'Deneme Ürünü', NULL, 1, 'stk99999', 'Yangın Söndürme Cihazı', NULL, '25', 'TL', 0, 2434, NULL, 'TL', 0, 345, NULL, '345', '0000-00-00', 'Raf Kodu', 345, '	', 'Aktif', '25-12-2023'),
(56, 'Yangın Söndürme', NULL, 1, 'stk1000', 'Yangın Söndürme Cihazı', NULL, '25', 'TL', 0, 2434, NULL, 'TL', 0, 345, NULL, '345', '0000-00-00', 'Raf Kodu', 345, '	', 'Aktif', '25-12-2023'),
(57, 'Yangın Söndürme Cihazı 23', 'Ürün', 1, 'stk1515', 'Yangın Söndürme Cihazı', NULL, '25', 'TL', 20, 2434, NULL, 'TL', 20, 345, NULL, '345', '0000-00-00', '34', 18, '	', 'Aktif', '04-01-2024');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `pcid` int(11) NOT NULL,
  `poid` int(11) NOT NULL,
  `pcreativer` int(11) NOT NULL,
  `ptitle` text NOT NULL,
  `pdesc` text NOT NULL,
  `pregdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `preg_date` varchar(255) NOT NULL,
  `pstart_date` varchar(255) NOT NULL,
  `pfinal_date` varchar(100) NOT NULL,
  `pauthors` varchar(255) NOT NULL,
  `pnotes` text NOT NULL,
  `pstatu` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `projects`
--

INSERT INTO `projects` (`id`, `pcid`, `poid`, `pcreativer`, `ptitle`, `pdesc`, `pregdate`, `preg_date`, `pstart_date`, `pfinal_date`, `pauthors`, `pnotes`, `pstatu`) VALUES
(1, 1, 6, 1, 'Eğitim', 'Makine kullanım eğitimi', '2023-12-12 13:44:59', '12-12-2023', '', '', '1|', '', 1),
(2, 1, 8, 1, 'Mal Teslimat', 'adasd', '2023-12-17 19:14:57', '17-12-2023', '18-12-2023', '23-12-2023', '8|', 'asdasdad', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `reminders`
--

CREATE TABLE `reminders` (
  `id` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `sid` int(11) NOT NULL,
  `dates` varchar(255) NOT NULL,
  `mail` int(11) NOT NULL,
  `sms` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `statu` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `reminders`
--

INSERT INTO `reminders` (`id`, `cid`, `sid`, `dates`, `mail`, `sms`, `type`, `statu`) VALUES
(1, 1, 1, '10-12-2023', 1, 0, 'satishatirlatici', 0),
(2, 1, 1, '18-12-2023', 1, 0, 'satishatirlatici', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `sid` int(11) NOT NULL,
  `price` varchar(100) NOT NULL,
  `pay_type` int(11) NOT NULL,
  `pay_method` int(11) NOT NULL,
  `descs` text NOT NULL,
  `start_date` varchar(255) NOT NULL,
  `end_date` varchar(255) NOT NULL,
  `recalls` int(11) NOT NULL,
  `statu` int(11) NOT NULL,
  `deleted` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `sales`
--

INSERT INTO `sales` (`id`, `cid`, `sid`, `price`, `pay_type`, `pay_method`, `descs`, `start_date`, `end_date`, `recalls`, `statu`, `deleted`) VALUES
(1, 1, 1, '200', 1, 1, 'test', '11-12-2023', '15-12-2023', 0, 1, 0),
(2, 1, 1, '20', 1, 1, 'asdf', '11-12-2023', '13-12-2023', 0, 1, 0),
(3, 1, 1, '20', 2, 1, '', '08-12-2023', '14-02-2024', 0, 1, 0),
(4, 1, 1, '20', 2, 1, '', '04-12-2023', '04-12-2023', 0, 1, 0),
(5, 1, 1, '6787', 3, 1, '', '06-12-2023', '21-12-2023', 0, 1, 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `Firma` varchar(255) NOT NULL,
  `SubeAdresi` varchar(255) DEFAULT NULL,
  `Cihaz` varchar(255) DEFAULT NULL,
  `Marka` varchar(255) DEFAULT NULL,
  `Model` varchar(255) DEFAULT NULL,
  `Kategori` varchar(255) DEFAULT NULL,
  `BaslamaTarihi` varchar(10) DEFAULT NULL,
  `BitisTarihi` varchar(10) DEFAULT NULL,
  `Personel` varchar(255) DEFAULT NULL,
  `SikayetTanim` text DEFAULT NULL,
  `CihazSeriNo` varchar(50) DEFAULT NULL,
  `CihazUretimTarihi` date DEFAULT NULL,
  `GarantiSuresiGun` int(11) DEFAULT NULL,
  `Ucret` decimal(16,0) DEFAULT NULL,
  `TahsilatTuru` varchar(25) DEFAULT NULL,
  `Aciklama` text DEFAULT NULL,
  `Sonuc` varchar(255) DEFAULT NULL,
  `Ozellikler` varchar(255) DEFAULT NULL,
  `Durum` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `services`
--

INSERT INTO `services` (`id`, `Firma`, `SubeAdresi`, `Cihaz`, `Marka`, `Model`, `Kategori`, `BaslamaTarihi`, `BitisTarihi`, `Personel`, `SikayetTanim`, `CihazSeriNo`, `CihazUretimTarihi`, `GarantiSuresiGun`, `Ucret`, `TahsilatTuru`, `Aciklama`, `Sonuc`, `Ozellikler`, `Durum`) VALUES
(38, 'karamanoğulları yetkili servis', 'dsffs', '', 'sdf', '', 'dafadfadf', '22-12-2023', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, 'karamanoğulları yetkili servis', 'dsffs', '', 'sdf', '', 'dafadfadf', '22-12-2023', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 'karamanoğulları yetkili servis', 'dsffs', '', 'sdf', '', 'dafadfadf', '22-12-2023', '20-12-2023', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `var` text NOT NULL,
  `val` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `var`, `val`) VALUES
(1, 'site_title', ' Aydınoğulları'),
(3, 'panel_url', 'http://aydınogullariysc.com.tr'),
(4, 'login_active', '1'),
(5, 'total_money', '0'),
(6, 'max_sr', '7'),
(7, 'company_name', 'Aydınoğulları Yangın Söndürme San. ve Tic.Ltd.Şti'),
(8, 'logo', 'src/images/logo.png'),
(9, 'company_address', 'Alaaddinbey mah. 648 sok. Alişan Plaza No:1A/5 Nilüfer/BURSA'),
(10, 'company_city', 'BURSA'),
(11, 'company_phone1', '02244436021'),
(16, 'notestext', '<ol><li>dsafdsfsfasfdfaf</li></ol>'),
(12, 'company_phone2', '02244436022'),
(13, 'amounts_number', '10'),
(14, 'system_statu', '1'),
(15, 'tax_num', '18'),
(17, 'mail_host', ''),
(18, 'mail_username', 'info@aydinogullariysc.com'),
(19, 'mail_password', ''),
(20, 'mail_port', ''),
(21, 'mail_from', 'info@ahmetcakmak.net'),
(22, 'mail_name', 'Mail'),
(23, 'admin_mail', 'info@aydinogullariysc.com'),
(26, 'sms_username', NULL),
(27, 'sms_pass', NULL),
(28, 'sms_title', NULL),
(29, 'reminder_view', '1'),
(30, 'sale_view', '1'),
(32, 'sms_active', ''),
(33, 'sms_viewx', '0');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `todolist`
--

CREATE TABLE `todolist` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `regdate` varchar(255) NOT NULL,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_date` text NOT NULL,
  `creativer` int(11) NOT NULL,
  `okey` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `todolist`
--

INSERT INTO `todolist` (`id`, `title`, `description`, `regdate`, `reg_date`, `last_date`, `creativer`, `okey`) VALUES
(1, 'Yangın Tüpü değişimi', 'Ahi Evran Mtal tüp değişimleri yapılacak.', '08-12-2023', '2023-12-12 11:42:12', '21-12-2023', 1, 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `regdate` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Tablo döküm verisi `units`
--

INSERT INTO `units` (`id`, `title`, `regdate`) VALUES
(1, 'Adet', '11-12-2023 - 15:35:16'),
(2, 'Takım', '11-12-2023 - 15:35:20'),
(3, 'Tane', '11-12-2023 - 15:35:24');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `upfiles`
--

CREATE TABLE `upfiles` (
  `id` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `filename` varchar(100) NOT NULL,
  `size` varchar(50) NOT NULL,
  `creativer` int(11) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Tablo döküm verisi `upfiles`
--

INSERT INTO `upfiles` (`id`, `cid`, `filename`, `size`, `creativer`, `regdate`) VALUES
(1, 1, '46_logo.png', '5742', 1, '2023-12-11 12:30:59'),
(2, 1, '83_PHP PDO Projesi.pdf', '5693244', 13, '2023-12-18 17:40:57');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `upfile_categories`
--

CREATE TABLE `upfile_categories` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Tablo döküm verisi `upfile_categories`
--

INSERT INTO `upfile_categories` (`id`, `title`, `regdate`) VALUES
(1, 'Resimler', '2023-12-11 12:30:43'),
(2, 'Teklifler', '2023-12-11 12:30:48'),
(3, 'Excel Dosyaları', '2023-12-21 16:51:09');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` text NOT NULL,
  `tckimlikno` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `avatar_link` varchar(255) NOT NULL,
  `email` text NOT NULL,
  `gsm` varchar(255) NOT NULL,
  `gsm2` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `regdate` varchar(255) NOT NULL,
  `creativer` int(11) NOT NULL,
  `giristarihi` varchar(255) NOT NULL,
  `dogumtarihi` varchar(255) NOT NULL,
  `cikistarihi` varchar(255) NOT NULL,
  `perm` int(11) NOT NULL,
  `permission` int(11) NOT NULL,
  `statu` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `tckimlikno`, `password`, `avatar_link`, `email`, `gsm`, `gsm2`, `city`, `address`, `name`, `surname`, `regdate`, `creativer`, `giristarihi`, `dogumtarihi`, `cikistarihi`, `perm`, `permission`, `statu`) VALUES
(1, 'admin', '0', '7363a0d0604902af7b70b271a0b96480', 'vendors/images/photo2.jpg', 'deneme@aydinogullariysc.com', '05079747767', '', 'Şehir', 'Adres', 'Yönetici', 'Hesabı', '21-06-2020', 1, '#', '#', '#', 1, 1, 1),
(52, 'gkmn', '56456546456', '09bd162f55810682d3bc5fb761e24b70', 'vendors/images/photo2.jpg', 'beyzade83@hotmail.com', '+905079432723', '+905079432723', 'turkey', 'km', 'Mehmet Ali', 'Gökmen', '30-12-2023', 52, '', '', '', 1, 26, 1),
(92, 'Magokmen', '32450401908', '71dd795d44ba9fe6ca4c3aef0a07c65d', 'vendors/images/photo2.jpg', 'beyzade83@hotmail.com', '+905079432723', '+905079432723', 'turkey', 'km', 'Mehmet Ali', 'Gökmen', '30-12-2023', 1, '30-12-2023', '', '', 1, 1, 1);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `cgroups`
--
ALTER TABLE `cgroups`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`ID`);

--
-- Tablo için indeksler `entry_categories`
--
ALTER TABLE `entry_categories`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `evraktakip`
--
ALTER TABLE `evraktakip`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `indocument_categories`
--
ALTER TABLE `indocument_categories`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `inexps`
--
ALTER TABLE `inexps`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `mail_logs`
--
ALTER TABLE `mail_logs`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `mainservices`
--
ALTER TABLE `mainservices`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `missioncategory`
--
ALTER TABLE `missioncategory`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `note_categories`
--
ALTER TABLE `note_categories`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `offermatters`
--
ALTER TABLE `offermatters`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `ofstats`
--
ALTER TABLE `ofstats`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `pay_methods`
--
ALTER TABLE `pay_methods`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `pcategories`
--
ALTER TABLE `pcategories`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `perms`
--
ALTER TABLE `perms`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ID`);

--
-- Tablo için indeksler `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `todolist`
--
ALTER TABLE `todolist`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `upfiles`
--
ALTER TABLE `upfiles`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `upfile_categories`
--
ALTER TABLE `upfile_categories`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `cgroups`
--
ALTER TABLE `cgroups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- Tablo için AUTO_INCREMENT değeri `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- Tablo için AUTO_INCREMENT değeri `documents`
--
ALTER TABLE `documents`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `entry_categories`
--
ALTER TABLE `entry_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `evraktakip`
--
ALTER TABLE `evraktakip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- Tablo için AUTO_INCREMENT değeri `indocument_categories`
--
ALTER TABLE `indocument_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `inexps`
--
ALTER TABLE `inexps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Tablo için AUTO_INCREMENT değeri `mail_logs`
--
ALTER TABLE `mail_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `mainservices`
--
ALTER TABLE `mainservices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `missioncategory`
--
ALTER TABLE `missioncategory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Tablo için AUTO_INCREMENT değeri `missions`
--
ALTER TABLE `missions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `note_categories`
--
ALTER TABLE `note_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `offermatters`
--
ALTER TABLE `offermatters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- Tablo için AUTO_INCREMENT değeri `offers`
--
ALTER TABLE `offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- Tablo için AUTO_INCREMENT değeri `ofstats`
--
ALTER TABLE `ofstats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `pay_methods`
--
ALTER TABLE `pay_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Tablo için AUTO_INCREMENT değeri `pcategories`
--
ALTER TABLE `pcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Tablo için AUTO_INCREMENT değeri `perms`
--
ALTER TABLE `perms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- Tablo için AUTO_INCREMENT değeri `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Tablo için AUTO_INCREMENT değeri `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Tablo için AUTO_INCREMENT değeri `todolist`
--
ALTER TABLE `todolist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `upfiles`
--
ALTER TABLE `upfiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `upfile_categories`
--
ALTER TABLE `upfile_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
