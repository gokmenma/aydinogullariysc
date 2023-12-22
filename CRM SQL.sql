-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 13 Tem 2020, 13:57:29
-- Sunucu sürümü: 10.4.10-MariaDB
-- PHP Sürümü: 7.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `crmtemiz`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cgroups`
--

DROP TABLE IF EXISTS `cgroups`;
CREATE TABLE IF NOT EXISTS `cgroups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(155) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `statu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grp` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf32_turkish_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf32_turkish_ci DEFAULT NULL,
  `company` varchar(100) COLLATE utf32_turkish_ci DEFAULT NULL,
  `sector` varchar(100) COLLATE utf32_turkish_ci DEFAULT NULL,
  `address` varchar(250) COLLATE utf32_turkish_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf32_turkish_ci DEFAULT NULL,
  `cdesc` text COLLATE utf32_turkish_ci DEFAULT NULL,
  `gsm` varchar(155) COLLATE utf32_turkish_ci DEFAULT NULL,
  `gsm2` varchar(155) COLLATE utf32_turkish_ci DEFAULT NULL,
  `yetkili` varchar(255) COLLATE utf32_turkish_ci DEFAULT NULL,
  `sunvan` varchar(255) COLLATE utf32_turkish_ci DEFAULT NULL,
  `vdaire` varchar(255) COLLATE utf32_turkish_ci DEFAULT NULL,
  `vno` varchar(75) COLLATE utf32_turkish_ci DEFAULT NULL,
  `regdate` timestamp NULL DEFAULT current_timestamp(),
  `reg_date` varchar(255) COLLATE utf32_turkish_ci DEFAULT NULL,
  `creativer` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `entry_categories`
--

DROP TABLE IF EXISTS `entry_categories`;
CREATE TABLE IF NOT EXISTS `entry_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `files`
--

DROP TABLE IF EXISTS `files`;
CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `oid` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `inexps`
--

DROP TABLE IF EXISTS `inexps`;
CREATE TABLE IF NOT EXISTS `inexps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf32_turkish_ci NOT NULL,
  `title` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `descs` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `cat` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `sdate` varchar(100) COLLATE utf32_turkish_ci NOT NULL,
  `pay` varchar(150) COLLATE utf32_turkish_ci NOT NULL,
  `pay_method` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `logs`
--

DROP TABLE IF EXISTS `logs`;
CREATE TABLE IF NOT EXISTS `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL,
  `dates` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `clock` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `message` text COLLATE utf32_turkish_ci NOT NULL,
  `author` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `logs`
--

INSERT INTO `logs` (`id`, `type`, `dates`, `clock`, `message`, `author`) VALUES
(1, 1, '12-07-2020', '19:22:56', 'panelden çıkış yaptı.', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `mail_logs`
--

DROP TABLE IF EXISTS `mail_logs`;
CREATE TABLE IF NOT EXISTS `mail_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tomail` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `type` varchar(100) COLLATE utf32_turkish_ci NOT NULL,
  `datest` timestamp NOT NULL DEFAULT current_timestamp(),
  `statu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `mainservices`
--

DROP TABLE IF EXISTS `mainservices`;
CREATE TABLE IF NOT EXISTS `mainservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stitle` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `sdesc` text COLLATE utf32_turkish_ci NOT NULL,
  `regdate` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `color` varchar(100) COLLATE utf32_turkish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `missions`
--

DROP TABLE IF EXISTS `missions`;
CREATE TABLE IF NOT EXISTS `missions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `mdesc` varchar(255) NOT NULL,
  `regdate` varchar(255) NOT NULL,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastdate` varchar(255) NOT NULL,
  `authors` varchar(100) NOT NULL,
  `creativer` int(11) NOT NULL,
  `urgency` varchar(100) NOT NULL,
  `okeydate` varchar(255) NOT NULL,
  `deleted` varchar(11) NOT NULL DEFAULT 'no',
  `statu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notes`
--

DROP TABLE IF EXISTS `notes`;
CREATE TABLE IF NOT EXISTS `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `title` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `dates` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `lastdate` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `creativer` int(11) NOT NULL,
  `urgency` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `descs` text COLLATE utf32_turkish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `note_categories`
--

DROP TABLE IF EXISTS `note_categories`;
CREATE TABLE IF NOT EXISTS `note_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `regdate` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `offermatters`
--

DROP TABLE IF EXISTS `offermatters`;
CREATE TABLE IF NOT EXISTS `offermatters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `xid` int(11) NOT NULL,
  `oid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `price` varchar(255) NOT NULL,
  `total` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `offers`
--

DROP TABLE IF EXISTS `offers`;
CREATE TABLE IF NOT EXISTS `offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL,
  `total_price` int(11) NOT NULL,
  `mycompany` varchar(150) COLLATE utf32_turkish_ci NOT NULL,
  `authors` varchar(50) COLLATE utf32_turkish_ci NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `reg_date` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `tax` int(7) NOT NULL,
  `creativer` int(11) NOT NULL,
  `notes` text COLLATE utf32_turkish_ci NOT NULL,
  `currency` varchar(11) COLLATE utf32_turkish_ci NOT NULL,
  `statu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ofstats`
--

DROP TABLE IF EXISTS `ofstats`;
CREATE TABLE IF NOT EXISTS `ofstats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `description` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `color` varchar(100) COLLATE utf32_turkish_ci NOT NULL,
  `icon` varchar(90) COLLATE utf32_turkish_ci NOT NULL,
  `button` varchar(50) COLLATE utf32_turkish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

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

DROP TABLE IF EXISTS `pages`;
CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `p_title` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `p_link` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=77 DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

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
(76, 'Dosya Kategorisini Düzenle', 'edit-fcategory', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `saleid` int(11) NOT NULL,
  `lastdate` varchar(50) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pay_method` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `okey` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pay_methods`
--

DROP TABLE IF EXISTS `pay_methods`;
CREATE TABLE IF NOT EXISTS `pay_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `iban` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `author` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `accountno` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `branchcode` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `currency` varchar(11) COLLATE utf32_turkish_ci NOT NULL,
  `total` int(255) NOT NULL,
  `last_action` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `pcategories`
--

DROP TABLE IF EXISTS `pcategories`;
CREATE TABLE IF NOT EXISTS `pcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `xxid` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `icon` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `statu` int(11) NOT NULL DEFAULT 1,
  `perm` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

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

DROP TABLE IF EXISTS `perms`;
CREATE TABLE IF NOT EXISTS `perms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `p_title` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `cadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT '0',
  `cedit` varchar(11) COLLATE utf32_turkish_ci DEFAULT '0',
  `cdelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `sadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `sedit` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `sreminder` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `sdelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `pmadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `pmview` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `pmdelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `oadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `oview` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `oedit` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `odelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `padd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `pedit` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `pdelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `payadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `payview` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `paydelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `seradd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `seredit` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `serdelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `sercategory` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `exadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `exview` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `excategory` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `exdelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `todoadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `todoedit` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `tododelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `noteadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `noteedit` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `notedelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `uadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `uedit` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `udelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `uperm` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `misadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `mistake` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `allmisview` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `mlogview` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `setview` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `fadd` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `fview` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  `fdelete` varchar(11) COLLATE utf32_turkish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `perms`
--

INSERT INTO `perms` (`id`, `p_title`, `cadd`, `cedit`, `cdelete`, `sadd`, `sedit`, `sreminder`, `sdelete`, `pmadd`, `pmview`, `pmdelete`, `oadd`, `oview`, `oedit`, `odelete`, `padd`, `pedit`, `pdelete`, `payadd`, `payview`, `paydelete`, `seradd`, `seredit`, `serdelete`, `sercategory`, `exadd`, `exview`, `excategory`, `exdelete`, `todoadd`, `todoedit`, `tododelete`, `noteadd`, `noteedit`, `notedelete`, `uadd`, `uedit`, `udelete`, `uperm`, `misadd`, `mistake`, `allmisview`, `mlogview`, `setview`, `fadd`, `fview`, `fdelete`) VALUES
(1, 'Genel Yönetici', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on', 'on');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pcid` int(11) NOT NULL,
  `poid` int(11) NOT NULL,
  `pcreativer` int(11) NOT NULL,
  `ptitle` text COLLATE utf32_turkish_ci NOT NULL,
  `pdesc` text COLLATE utf32_turkish_ci NOT NULL,
  `pregdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `preg_date` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `pstart_date` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `pfinal_date` varchar(100) COLLATE utf32_turkish_ci NOT NULL,
  `pauthors` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `pnotes` text COLLATE utf32_turkish_ci NOT NULL,
  `pstatu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `reminders`
--

DROP TABLE IF EXISTS `reminders`;
CREATE TABLE IF NOT EXISTS `reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL,
  `sid` int(11) NOT NULL,
  `dates` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `mail` int(11) NOT NULL,
  `sms` int(11) NOT NULL,
  `type` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `statu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL,
  `sid` int(11) NOT NULL,
  `price` varchar(100) COLLATE utf32_turkish_ci NOT NULL,
  `pay_type` int(11) NOT NULL,
  `pay_method` int(11) NOT NULL,
  `descs` text COLLATE utf32_turkish_ci NOT NULL,
  `start_date` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `end_date` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `recalls` int(11) NOT NULL,
  `statu` int(11) NOT NULL,
  `deleted` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mid` int(11) NOT NULL,
  `stitle` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `sdesc` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `var` text COLLATE utf32_turkish_ci NOT NULL,
  `val` text COLLATE utf32_turkish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `var`, `val`) VALUES
(1, 'site_title', 'AhmetCakmak.NET'),
(3, 'panel_url', 'http://crm.ahmetcakmak.net'),
(4, 'login_active', '1'),
(5, 'total_money', '0'),
(6, 'max_sr', '7'),
(7, 'company_name', 'Şirket İsmi'),
(8, 'logo', 'src/images/aaaaffff.png'),
(9, 'company_address', 'Şirket Adres Bilgisi'),
(10, 'company_city', 'Şirket Şehir'),
(11, 'company_phone1', '05551555555'),
(16, 'notestext', '<ol><li>dsafdsfsfasfdfaf</li></ol>'),
(12, 'company_phone2', '0505050505'),
(13, 'amounts_number', '10'),
(14, 'system_statu', '1'),
(15, 'tax_num', '18'),
(17, 'mail_host', 'mail.ahmetcakmak.net'),
(18, 'mail_username', 'info@ahmetcakmak.net'),
(19, 'mail_password', 'PAROLAPAROLAPAROLA'),
(20, 'mail_port', '587'),
(21, 'mail_from', 'info@ahmetcakmak.net'),
(22, 'mail_name', 'Mail'),
(23, 'admin_mail', 'ahmetcakmakyt@gmail.com'),
(26, 'sms_username', 'Kullanıcı Adı '),
(27, 'sms_pass', 'Başlık '),
(28, 'sms_title', 'PAROLA'),
(29, 'reminder_view', '0'),
(30, 'sale_view', '0'),
(32, 'sms_active', 'on'),
(33, 'sms_viewx', '0');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `todolist`
--

DROP TABLE IF EXISTS `todolist`;
CREATE TABLE IF NOT EXISTS `todolist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `description` text COLLATE utf32_turkish_ci NOT NULL,
  `regdate` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_date` text COLLATE utf32_turkish_ci NOT NULL,
  `creativer` int(11) NOT NULL,
  `okey` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `units`
--

DROP TABLE IF EXISTS `units`;
CREATE TABLE IF NOT EXISTS `units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `regdate` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `upfiles`
--

DROP TABLE IF EXISTS `upfiles`;
CREATE TABLE IF NOT EXISTS `upfiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL,
  `filename` varchar(100) NOT NULL,
  `size` varchar(50) NOT NULL,
  `creativer` int(11) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `upfile_categories`
--

DROP TABLE IF EXISTS `upfile_categories`;
CREATE TABLE IF NOT EXISTS `upfile_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `regdate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` text COLLATE utf32_turkish_ci NOT NULL,
  `password` text COLLATE utf32_turkish_ci NOT NULL,
  `avatar_link` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `email` text COLLATE utf32_turkish_ci NOT NULL,
  `gsm` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `city` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `address` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `name` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `surname` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `regdate` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `creativer` int(11) NOT NULL,
  `facebook` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `twitter` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `instagram` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `linkedin` varchar(255) COLLATE utf32_turkish_ci NOT NULL,
  `perm` int(11) NOT NULL,
  `permission` int(11) NOT NULL,
  `statu` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf32 COLLATE=utf32_turkish_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `avatar_link`, `email`, `gsm`, `city`, `address`, `name`, `surname`, `regdate`, `creativer`, `facebook`, `twitter`, `instagram`, `linkedin`, `perm`, `permission`, `statu`) VALUES
(1, 'admin', '7363a0d0604902af7b70b271a0b96480', 'vendors/images/photo2.jpg', 'info@ahmetcakmak.net', '05079747767', 'Şehir', 'Adres', 'Yönetici', 'Hesabı', '21-06-2020', 1, '#', '#', '#', '#', 1, 1, 1);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
