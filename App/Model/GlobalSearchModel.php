<?php

namespace App\Model;

use PDO;
use App\Model\BaseModel;
use App\Helper\Security;

class GlobalSearchModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct('customers');
    }

    /**
     * Tüm modüllerde (Teklif, Ürün, Firma, Servis, Keşif, Rapor) küresel arama yapar
     *
     * @param string $query Arama kelimesi
     * @param string $category Modül filtresi ('all', 'offers', 'products', 'customers', 'services', 'kesifler', 'reports')
     * @param int $limit Modül başına maksimum sonuç
     * @return array
     */
    public function search(string $query, string $category = 'all', int $limit = 8): array
    {
        $rawQuery = trim($query);
        if (mb_strlen($rawQuery, 'UTF-8') < 1) {
            return [
                'counts' => [
                    'all'       => 0,
                    'offers'    => 0,
                    'products'  => 0,
                    'customers' => 0,
                    'services'  => 0,
                    'kesifler'  => 0,
                    'reports'   => 0
                ],
                'results' => []
            ];
        }

        $param = '%' . $rawQuery . '%';
        $results = [
            'offers'    => [],
            'products'  => [],
            'customers' => [],
            'services'  => [],
            'kesifler'  => [],
            'reports'   => []
        ];

        // 1. TEKLİFLER (Offers)
        if ($category === 'all' || $category === 'offers') {
            $sql = "SELECT 
                        o.id,
                        o.offerNumber,
                        o.offer_subject,
                        o.total_price,
                        o.tl_toplam_karsilik,
                        o.currency,
                        o.statu,
                        o.reg_date,
                        o.offer_date,
                        c.company as customer_name
                    FROM offers o
                    LEFT JOIN customers c ON c.id = o.cid
                    WHERE (
                        o.offerNumber LIKE :q1
                        OR o.offer_subject LIKE :q2
                        OR o.description LIKE :q3
                        OR o.notes LIKE :q4
                        OR c.company LIKE :q5
                    )
                    ORDER BY o.id DESC
                    LIMIT " . (int)$limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':q1' => $param,
                ':q2' => $param,
                ':q3' => $param,
                ':q4' => $param,
                ':q5' => $param
            ]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $statusText = 'Taslak';
                $statusClass = 'badge-secondary';
                if ((int)$row['statu'] === 1) {
                    $statusText = 'Bekliyor';
                    $statusClass = 'badge-warning';
                } elseif ((int)$row['statu'] === 2) {
                    $statusText = 'Onaylandı';
                    $statusClass = 'badge-success';
                } elseif ((int)$row['statu'] === 3) {
                    $statusText = 'Reddedildi';
                    $statusClass = 'badge-danger';
                }

                $curr = !empty($row['currency']) ? $row['currency'] : 'TL';
                $formattedPrice = '';
                if (!empty($row['total_price'])) {
                    $formattedPrice = number_format((float)$row['total_price'], 2, ',', '.') . ' ' . $curr;
                }

                $results['offers'][] = [
                    'id'          => (int)$row['id'],
                    'type'        => 'offer',
                    'type_label'  => 'Teklif',
                    'title'       => $row['offerNumber'] ?: 'TK#' . $row['id'],
                    'subtitle'    => ($row['customer_name'] ? $row['customer_name'] : 'Müşteri Belirtilmemiş') . ($row['offer_subject'] ? ' · ' . $row['offer_subject'] : ''),
                    'badge'       => $statusText,
                    'badge_class' => $statusClass,
                    'extra_info'  => $formattedPrice,
                    'date'        => !empty($row['offer_date']) ? date('d.m.Y', strtotime($row['offer_date'])) : (!empty($row['reg_date']) ? date('d.m.Y', strtotime($row['reg_date'])) : ''),
                    'url'         => 'index.php?p=offers/offer-manage&id=' . (int)$row['id'],
                    'view_url'    => 'index.php?p=offer-view&id=' . (int)$row['id'],
                    'edit_url'    => 'index.php?p=offers/offer-manage&id=' . (int)$row['id'],
                    'icon'        => 'fa-file-text-o',
                    'initial'     => 'TK',
                    'color_theme' => 'purple'
                ];
            }
        }

        // 2. ÜRÜN & HİZMETLER (Products)
        if ($category === 'all' || $category === 'products') {
            $sql = "SELECT 
                        p.ID as id,
                        p.Adi,
                        p.StokKodu,
                        p.Barkod,
                        p.RafKodu,
                        p.SatisFiyati,
                        p.SatisParaBirimi,
                        p.Durum,
                        p.UrunGrubu,
                        u.title as unit_title
                    FROM products p
                    LEFT JOIN units u ON u.id = p.Birimi
                    WHERE (
                        p.Adi LIKE :q1
                        OR p.StokKodu LIKE :q2
                        OR p.Barkod LIKE :q3
                        OR p.RafKodu LIKE :q4
                        OR p.Aciklama LIKE :q5
                        OR p.UrunGrubu LIKE :q6
                    )
                    ORDER BY p.ID DESC
                    LIMIT " . (int)$limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':q1' => $param,
                ':q2' => $param,
                ':q3' => $param,
                ':q4' => $param,
                ':q5' => $param,
                ':q6' => $param
            ]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $statusText = ((int)$row['Durum'] === 1 || $row['Durum'] === null || $row['Durum'] === '') ? 'Aktif' : 'Pasif';
                $statusClass = $statusText === 'Aktif' ? 'badge-success' : 'badge-secondary';

                $curr = !empty($row['SatisParaBirimi']) ? $row['SatisParaBirimi'] : 'TL';
                $priceStr = '';
                if ($row['SatisFiyati'] !== null && $row['SatisFiyati'] !== '') {
                    $priceStr = number_format((float)$row['SatisFiyati'], 2, ',', '.') . ' ' . $curr;
                }

                $details = [];
                if (!empty($row['StokKodu'])) {
                    $details[] = 'Stok: ' . $row['StokKodu'];
                }
                if (!empty($row['unit_title'])) {
                    $details[] = $row['unit_title'];
                }
                if (!empty($row['RafKodu'])) {
                    $details[] = 'Raf: ' . $row['RafKodu'];
                }

                $encId = Security::encrypt($row['id']);

                $results['products'][] = [
                    'id'          => (int)$row['id'],
                    'enc_id'      => $encId,
                    'type'        => 'product',
                    'type_label'  => 'Ürün',
                    'title'       => $row['Adi'] ?: 'İsimsiz Ürün #' . $row['id'],
                    'subtitle'    => implode(' · ', $details) ?: 'Detay bilgisi yok',
                    'badge'       => $statusText,
                    'badge_class' => $statusClass,
                    'extra_info'  => $priceStr,
                    'date'        => '',
                    'url'         => 'index.php?p=products/manage&id=' . $encId,
                    'edit_url'    => 'index.php?p=products/manage&id=' . $encId,
                    'icon'        => 'fa-cube',
                    'initial'     => 'ÜR',
                    'color_theme' => 'amber'
                ];
            }
        }

        // 3. FİRMALAR (Customers / Companies)
        if ($category === 'all' || $category === 'customers') {
            $sql = "SELECT 
                        c.id,
                        c.company,
                        c.yetkili,
                        c.email,
                        c.gsm,
                        c.gsm2,
                        c.city,
                        c.ilce,
                        c.sector,
                        c.reg_date,
                        c.regdate
                    FROM customers c
                    WHERE c.deleted_at IS NULL
                      AND (
                        c.company LIKE :q1
                        OR c.yetkili LIKE :q2
                        OR c.email LIKE :q3
                        OR c.gsm LIKE :q4
                        OR c.gsm2 LIKE :q5
                        OR c.city LIKE :q6
                        OR c.ilce LIKE :q7
                        OR c.sector LIKE :q8
                      )
                    ORDER BY c.id DESC
                    LIMIT " . (int)$limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':q1' => $param,
                ':q2' => $param,
                ':q3' => $param,
                ':q4' => $param,
                ':q5' => $param,
                ':q6' => $param,
                ':q7' => $param,
                ':q8' => $param
            ]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $location = '';
                if (!empty($row['city'])) {
                    $location = $row['city'] . (!empty($row['ilce']) ? ' / ' . $row['ilce'] : '');
                }

                $details = [];
                if (!empty($row['yetkili'])) {
                    $details[] = $row['yetkili'];
                }
                if (!empty($location)) {
                    $details[] = $location;
                }
                if (!empty($row['gsm'])) {
                    $details[] = $row['gsm'];
                }

                $results['customers'][] = [
                    'id'          => (int)$row['id'],
                    'type'        => 'customer',
                    'type_label'  => 'Firma',
                    'title'       => $row['company'] ?: 'Firma #' . $row['id'],
                    'subtitle'    => implode(' · ', $details) ?: 'İletişim bilgisi yok',
                    'badge'       => !empty($row['sector']) ? $row['sector'] : 'Müşteri',
                    'badge_class' => 'badge-info',
                    'extra_info'  => !empty($row['email']) ? $row['email'] : '',
                    'date'        => !empty($row['regdate']) ? date('d.m.Y', strtotime($row['regdate'])) : '',
                    'url'         => 'index.php?p=customers/manage&id=' . (int)$row['id'],
                    'edit_url'    => 'index.php?p=customers/manage&id=' . (int)$row['id'],
                    'icon'        => 'fa-building-o',
                    'initial'     => 'Fİ',
                    'color_theme' => 'blue'
                ];
            }
        }

        // 4. SERVİSLER (Projects / Services)
        if ($category === 'all' || $category === 'services') {
            $sql = "SELECT 
                        p.id,
                        p.service_number,
                        p.pdesc,
                        p.pstart_date,
                        p.pstatu,
                        p.contract_statu,
                        p.region,
                        c.company as customer_name,
                        u.title as service_type
                    FROM projects p
                    LEFT JOIN customers c ON c.id = p.pcid
                    LEFT JOIN units u ON u.id = p.servicestype
                    WHERE (
                        p.service_number LIKE :q1
                        OR c.company LIKE :q2
                        OR u.title LIKE :q3
                        OR p.pdesc LIKE :q4
                        OR p.pnotes LIKE :q5
                        OR p.pauthors LIKE :q6
                        OR p.address LIKE :q7
                    )
                    ORDER BY p.id DESC
                    LIMIT " . (int)$limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':q1' => $param,
                ':q2' => $param,
                ':q3' => $param,
                ':q4' => $param,
                ':q5' => $param,
                ':q6' => $param,
                ':q7' => $param
            ]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $statusText = 'Açık';
                $statusClass = 'badge-primary';
                if ((string)$row['pstatu'] === '2' || mb_strtolower((string)$row['pstatu']) === 'tamamlandı') {
                    $statusText = 'Tamamlandı';
                    $statusClass = 'badge-success';
                } elseif ((string)$row['pstatu'] === '3' || mb_strtolower((string)$row['pstatu']) === 'iptal') {
                    $statusText = 'İptal';
                    $statusClass = 'badge-danger';
                }

                $details = [];
                if (!empty($row['customer_name'])) {
                    $details[] = $row['customer_name'];
                }
                if (!empty($row['service_type'])) {
                    $details[] = $row['service_type'];
                }
                if (!empty($row['region'])) {
                    $details[] = $row['region'];
                }

                $results['services'][] = [
                    'id'          => (int)$row['id'],
                    'type'        => 'service',
                    'type_label'  => 'Servis',
                    'title'       => $row['service_number'] ?: 'Servis #' . $row['id'],
                    'subtitle'    => implode(' · ', $details) ?: 'Açıklama yok',
                    'badge'       => $statusText,
                    'badge_class' => $statusClass,
                    'extra_info'  => !empty($row['service_type']) ? $row['service_type'] : '',
                    'date'        => !empty($row['pstart_date']) ? $row['pstart_date'] : '',
                    'url'         => 'index.php?p=service/manage&id=' . (int)$row['id'],
                    'edit_url'    => 'index.php?p=service/manage&id=' . (int)$row['id'],
                    'icon'        => 'fa-wrench',
                    'initial'     => 'SE',
                    'color_theme' => 'emerald'
                ];
            }
        }

        // 5. KEŞİFLER (Kesifler)
        if ($category === 'all' || $category === 'kesifler') {
            $sql = "SELECT 
                        k.id,
                        k.firma,
                        k.yapilacak_is,
                        k.kesif_sonu_notu,
                        k.gidecek_kisi,
                        k.kesif_tarihi,
                        k.durum,
                        k.konum
                    FROM kesifler k
                    WHERE k.silinme_tarihi IS NULL
                      AND (
                        k.firma LIKE :q1
                        OR k.yapilacak_is LIKE :q2
                        OR k.kesif_sonu_notu LIKE :q3
                        OR k.gidecek_kisi LIKE :q4
                        OR k.konum LIKE :q5
                      )
                    ORDER BY k.id DESC
                    LIMIT " . (int)$limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':q1' => $param,
                ':q2' => $param,
                ':q3' => $param,
                ':q4' => $param,
                ':q5' => $param
            ]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $statusText = !empty($row['durum']) ? $row['durum'] : 'Beklemede';
                $statusClass = 'badge-warning';
                if (mb_stripos($statusText, 'tamam') !== false) {
                    $statusClass = 'badge-success';
                } elseif (mb_stripos($statusText, 'iptal') !== false) {
                    $statusClass = 'badge-danger';
                }

                $details = [];
                if (!empty($row['yapilacak_is'])) {
                    $details[] = $row['yapilacak_is'];
                }
                if (!empty($row['gidecek_kisi'])) {
                    $details[] = 'Görevli: ' . $row['gidecek_kisi'];
                }
                if (!empty($row['konum'])) {
                    $details[] = $row['konum'];
                }

                $results['kesifler'][] = [
                    'id'          => (int)$row['id'],
                    'type'        => 'kesif',
                    'type_label'  => 'Keşif',
                    'title'       => $row['firma'] ?: 'Keşif #' . $row['id'],
                    'subtitle'    => implode(' · ', $details) ?: 'Açıklama belirtilmedi',
                    'badge'       => $statusText,
                    'badge_class' => $statusClass,
                    'extra_info'  => !empty($row['gidecek_kisi']) ? $row['gidecek_kisi'] : '',
                    'date'        => !empty($row['kesif_tarihi']) ? date('d.m.Y', strtotime($row['kesif_tarihi'])) : '',
                    'url'         => 'index.php?p=kesif/list&action=view&id=' . (int)$row['id'],
                    'edit_url'    => 'index.php?p=kesif/list&action=edit&id=' . (int)$row['id'],
                    'icon'        => 'fa-search-plus',
                    'initial'     => 'KE',
                    'color_theme' => 'cyan'
                ];
            }
        }

        // 6. RAPORLAR (Reports)
        if ($category === 'all' || $category === 'reports') {
            $sql = "SELECT 
                        r.id,
                        r.report_number,
                        r.report_type,
                        r.servis_no,
                        r.isemrino,
                        r.control_date,
                        r.create_time,
                        c.company as customer_name,
                        COALESCE(rt.reportName, 'Rapor') as report_type_name,
                        rt.page_link
                    FROM reports r
                    LEFT JOIN customers c ON c.id = r.customer_id
                    LEFT JOIN report_types rt ON rt.id = r.report_type
                    WHERE (
                        r.report_number LIKE :q1
                        OR r.servis_no LIKE :q2
                        OR r.isemrino LIKE :q3
                        OR c.company LIKE :q4
                        OR r.notes LIKE :q5
                        OR r.warnings LIKE :q6
                        OR rt.reportName LIKE :q7
                    )
                    ORDER BY r.id DESC
                    LIMIT " . (int)$limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':q1' => $param,
                ':q2' => $param,
                ':q3' => $param,
                ':q4' => $param,
                ':q5' => $param,
                ':q6' => $param,
                ':q7' => $param
            ]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pageLink = !empty($row['page_link']) ? $row['page_link'] : 'ysc';
                $reportUrl = 'index.php?p=reports/' . $pageLink . '/report-view-' . $pageLink . '&id=' . (int)$row['id'];
                $editUrl   = 'index.php?p=reports/' . $pageLink . '/report-edit-' . $pageLink . '&id=' . (int)$row['id'];

                $details = [];
                if (!empty($row['customer_name'])) {
                    $details[] = $row['customer_name'];
                }
                if (!empty($row['report_type_name'])) {
                    $details[] = $row['report_type_name'];
                }
                if (!empty($row['servis_no'])) {
                    $details[] = 'Servis No: ' . $row['servis_no'];
                }

                $results['reports'][] = [
                    'id'          => (int)$row['id'],
                    'type'        => 'report',
                    'type_label'  => 'Rapor',
                    'title'       => ($row['report_number'] ? $row['report_number'] : 'Rapor #' . $row['id']),
                    'subtitle'    => implode(' · ', $details) ?: 'Detay yok',
                    'badge'       => !empty($row['report_type_name']) ? $row['report_type_name'] : 'Rapor',
                    'badge_class' => 'badge-indigo',
                    'extra_info'  => !empty($row['customer_name']) ? $row['customer_name'] : '',
                    'date'        => !empty($row['control_date']) ? date('d.m.Y', strtotime($row['control_date'])) : (!empty($row['create_time']) ? date('d.m.Y', strtotime($row['create_time'])) : ''),
                    'url'         => $reportUrl,
                    'edit_url'    => $editUrl,
                    'icon'        => 'fa-file-text',
                    'initial'     => 'RA',
                    'color_theme' => 'indigo'
                ];
            }
        }

        $counts = [
            'offers'    => count($results['offers']),
            'products'  => count($results['products']),
            'customers' => count($results['customers']),
            'services'  => count($results['services']),
            'kesifler'  => count($results['kesifler']),
            'reports'   => count($results['reports'])
        ];
        $counts['all'] = array_sum($counts);

        return [
            'query'   => $rawQuery,
            'counts'  => $counts,
            'results' => $results
        ];
    }
}
