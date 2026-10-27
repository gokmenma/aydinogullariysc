<?php

declare(strict_types=1);

namespace App\Model;

use PDO;
use App\Model\BaseModel;
use App\Helper\Helper;

class DefineModel extends BaseModel
{
    protected $table = 'units';

    public const TYPE_UNIT = 'unit';
    public const TYPE_SERVICE_TYPE = 'service_type';
    public const TYPE_PAYMENT_TYPE = 'payment_type';
    public const TYPE_SERVICE_STATUS = 'service_status';
    public const TYPE_SERVICE_REGION = 'service_region';
    public const TYPE_OFFER_TEMPLATE = 'offer_template';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Gelen id'den birim isimlerini getir (Geriye dönük uyumluluk)
     */
    public function getUnitName($id): ?string
    {
        $unit = $this->find($id);
        return $unit ? ($unit->title ?? null) : null;
    }

    /**
     * Tanım türü meta bilgisi
     */
    public static function getTypeMeta(string $type): ?array
    {
        $map = [
            self::TYPE_UNIT => [
                'statu' => 1,
                'label' => 'Birim',
                'table' => 'units',
                'badge' => 'blue',
                'default_note' => 'Birim Tanımı',
            ],
            self::TYPE_SERVICE_TYPE => [
                'statu' => 2,
                'label' => 'Servis Konusu',
                'table' => 'units',
                'badge' => 'purple',
                'default_note' => 'Servis Konusu Tanımı',
            ],
            self::TYPE_PAYMENT_TYPE => [
                'statu' => 3,
                'label' => 'Tahsilat Türü',
                'table' => 'units',
                'badge' => 'emerald',
                'default_note' => 'Tahsilat Türü Tanımı',
            ],
            self::TYPE_SERVICE_STATUS => [
                'statu' => 4,
                'label' => 'Servis Durumu',
                'table' => 'units',
                'badge' => 'amber',
                'default_note' => 'Servis Durumu Tanımı',
            ],
            self::TYPE_SERVICE_REGION => [
                'statu' => 5,
                'label' => 'Servis Bölgesi',
                'table' => 'units',
                'badge' => 'cyan',
                'default_note' => 'Servis Bölgesi Tanımı',
            ],
            self::TYPE_OFFER_TEMPLATE => [
                'statu' => 0,
                'label' => 'Teklif Şablonu',
                'table' => 'offertemplate',
                'badge' => 'indigo',
                'default_note' => 'Teklif Şablonu',
            ],
        ];

        return $map[$type] ?? null;
    }

    /**
     * Statu kodundan type string bulucu
     */
    public static function resolveTypeFromStatu(int $statu): string
    {
        return match ($statu) {
            1 => self::TYPE_UNIT,
            2 => self::TYPE_SERVICE_TYPE,
            3 => self::TYPE_PAYMENT_TYPE,
            4 => self::TYPE_SERVICE_STATUS,
            5 => self::TYPE_SERVICE_REGION,
            default => 'other',
        };
    }

    /**
     * Tüm tanımlar için KPI / istatistik sayıları
     */
    public function getKpis(): array
    {
        $kpi = [
            'total_count' => 0,
            'unit_count' => 0,
            'service_type_count' => 0,
            'payment_type_count' => 0,
            'service_status_count' => 0,
            'service_region_count' => 0,
            'offer_template_count' => 0,
        ];

        try {
            // Units tablosu istatistikleri
            $stmt = $this->db->query("SELECT statu, COUNT(*) as cnt FROM units GROUP BY statu");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $statu = (int) $row['statu'];
                $count = (int) $row['cnt'];
                match ($statu) {
                    1 => $kpi['unit_count'] = $count,
                    2 => $kpi['service_type_count'] = $count,
                    3 => $kpi['payment_type_count'] = $count,
                    4 => $kpi['service_status_count'] = $count,
                    5 => $kpi['service_region_count'] = $count,
                    default => null,
                };
            }

            // Teklif şablonları
            $stmtTemplate = $this->db->query("SELECT COUNT(*) FROM offertemplate");
            $kpi['offer_template_count'] = (int) ($stmtTemplate->fetchColumn() ?? 0);

            $kpi['total_count'] = $kpi['unit_count']
                + $kpi['service_type_count']
                + $kpi['payment_type_count']
                + $kpi['service_status_count']
                + $kpi['service_region_count']
                + $kpi['offer_template_count'];
        } catch (\PDOException $e) {
            error_log("DefineModel::getKpis error: " . $e->getMessage());
        }

        return $kpi;
    }

    /**
     * Tanımları listele (Filtre, arama, sayfalama)
     */
    public function getDefinitions(string $type = 'all', string $search = '', int $limit = 50, int $offset = 0): array
    {
        $items = [];
        $total = 0;
        $search = trim($search);

        try {
            if ($type === self::TYPE_OFFER_TEMPLATE) {
                // Sadece teklif şablonları
                $where = "1=1";
                $params = [];
                if ($search !== '') {
                    $where .= " AND (o.Title LIKE ? OR o.Content LIKE ? OR o.State LIKE ?)";
                    $searchTerm = "%{$search}%";
                    $params = [$searchTerm, $searchTerm, $searchTerm];
                }

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM offertemplate o WHERE {$where}");
                $countStmt->execute($params);
                $total = (int) $countStmt->fetchColumn();

                $sql = "SELECT o.id, o.Title as title, '' as colour, o.regDate as regdate,
                               'offer_template' as type, o.State as state, o.Content as content,
                               'Teklif Şablonu' as type_label, 'indigo' as badge_theme,
                               o.creator, u.username as creator_name, u.Unvan as creator_title
                        FROM offertemplate o
                        LEFT JOIN users u ON u.id = o.creator
                        WHERE {$where}
                        ORDER BY o.id DESC
                        LIMIT ? OFFSET ?";
                $paramsWithLimit = array_merge($params, [$limit, $offset]);
                $stmt = $this->db->prepare($sql);
                foreach ($paramsWithLimit as $idx => $val) {
                    $stmt->bindValue($idx + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
                $stmt->execute();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $items[] = [
                        'id' => (int) $row['id'],
                        'title' => (string) ($row['title'] ?? ''),
                        'colour' => (string) ($row['colour'] ?? ''),
                        'regdate' => (string) ($row['regdate'] ?? ''),
                        'type' => 'offer_template',
                        'type_label' => 'Teklif Şablonu',
                        'state' => (string) ($row['state'] ?? 'Header'),
                        'content' => (string) ($row['content'] ?? ''),
                        'note' => (string) ($row['state'] === 'Header' ? 'Üst Bilgi Şablonu' : 'Alt Bilgi Şablonu'),
                        'badge_theme' => 'indigo',
                        'creator' => (int) ($row['creator'] ?? 0),
                        'creator_name' => (string) ($row['creator_name'] ?? $row['creator_title'] ?? ''),
                    ];
                }
            } elseif ($type !== 'all' && ($meta = self::getTypeMeta($type))) {
                // Belirli bir units statu türü
                $statu = (int) $meta['statu'];
                $where = "un.statu = ?";
                $params = [$statu];

                if ($search !== '') {
                    $where .= " AND (un.title LIKE ? OR un.note LIKE ?)";
                    $searchTerm = "%{$search}%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM units un WHERE {$where}");
                $countStmt->execute($params);
                $total = (int) $countStmt->fetchColumn();

                $sql = "SELECT un.id, un.title, un.colour, un.regdate, un.statu, un.note, un.creator,
                               u.username as creator_name, u.Unvan as creator_title
                        FROM units un
                        LEFT JOIN users u ON u.id = un.creator
                        WHERE {$where}
                        ORDER BY un.id DESC
                        LIMIT ? OFFSET ?";
                $paramsWithLimit = array_merge($params, [$limit, $offset]);
                $stmt = $this->db->prepare($sql);
                foreach ($paramsWithLimit as $idx => $val) {
                    $stmt->bindValue($idx + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
                $stmt->execute();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $items[] = [
                        'id' => (int) $row['id'],
                        'title' => (string) ($row['title'] ?? ''),
                        'colour' => (string) ($row['colour'] ?? ''),
                        'regdate' => (string) ($row['regdate'] ?? ''),
                        'type' => $type,
                        'type_label' => $meta['label'],
                        'statu' => (int) $row['statu'],
                        'state' => '',
                        'content' => '',
                        'note' => (string) ($row['note'] ?? ''),
                        'badge_theme' => $meta['badge'],
                        'creator' => (int) ($row['creator'] ?? 0),
                        'creator_name' => (string) ($row['creator_name'] ?? $row['creator_title'] ?? ''),
                    ];
                }
            } else {
                // Tümü ('all')
                $whereUnits = "1=1";
                $paramsUnits = [];
                if ($search !== '') {
                    $whereUnits .= " AND (un.title LIKE ? OR un.note LIKE ?)";
                    $searchTerm = "%{$search}%";
                    $paramsUnits = [$searchTerm, $searchTerm];
                }

                $whereTpl = "1=1";
                $paramsTpl = [];
                if ($search !== '') {
                    $whereTpl .= " AND (o.Title LIKE ? OR o.Content LIKE ? OR o.State LIKE ?)";
                    $searchTerm = "%{$search}%";
                    $paramsTpl = [$searchTerm, $searchTerm, $searchTerm];
                }

                $cntUnits = $this->db->prepare("SELECT COUNT(*) FROM units un WHERE {$whereUnits}");
                $cntUnits->execute($paramsUnits);
                $totalUnits = (int) $cntUnits->fetchColumn();

                $cntTpl = $this->db->prepare("SELECT COUNT(*) FROM offertemplate o WHERE {$whereTpl}");
                $cntTpl->execute($paramsTpl);
                $totalTpl = (int) $cntTpl->fetchColumn();

                $total = $totalUnits + $totalTpl;

                // Units kayıtları
                $sqlUnits = "SELECT un.id, un.title, un.colour, un.regdate, un.statu, un.note, un.creator,
                                    u.username as creator_name, u.Unvan as creator_title
                             FROM units un
                             LEFT JOIN users u ON u.id = un.creator
                             WHERE {$whereUnits}
                             ORDER BY un.id DESC";
                $stmtUnits = $this->db->prepare($sqlUnits);
                $stmtUnits->execute($paramsUnits);
                $allUnits = $stmtUnits->fetchAll(PDO::FETCH_ASSOC);

                // Offertemplate kayıtları
                $sqlTpl = "SELECT o.id, o.Title as title, '' as colour, o.regDate as regdate,
                                  o.State as state, o.Content as content, o.creator,
                                  u.username as creator_name, u.Unvan as creator_title
                           FROM offertemplate o
                           LEFT JOIN users u ON u.id = o.creator
                           WHERE {$whereTpl}
                           ORDER BY o.id DESC";
                $stmtTpl = $this->db->prepare($sqlTpl);
                $stmtTpl->execute($paramsTpl);
                $allTpl = $stmtTpl->fetchAll(PDO::FETCH_ASSOC);

                $merged = [];
                foreach ($allUnits as $row) {
                    $resolvedType = self::resolveTypeFromStatu((int) $row['statu']);
                    $meta = self::getTypeMeta($resolvedType) ?? [
                        'label' => 'Tanım',
                        'badge' => 'blue',
                    ];
                    $merged[] = [
                        'id' => (int) $row['id'],
                        'title' => (string) ($row['title'] ?? ''),
                        'colour' => (string) ($row['colour'] ?? ''),
                        'regdate' => (string) ($row['regdate'] ?? ''),
                        'type' => $resolvedType,
                        'type_label' => $meta['label'],
                        'statu' => (int) $row['statu'],
                        'state' => '',
                        'content' => '',
                        'note' => (string) ($row['note'] ?? ''),
                        'badge_theme' => $meta['badge'],
                        'creator' => (int) ($row['creator'] ?? 0),
                        'creator_name' => (string) ($row['creator_name'] ?? $row['creator_title'] ?? ''),
                        '_sort_key' => (int) $row['id'],
                    ];
                }

                foreach ($allTpl as $row) {
                    $merged[] = [
                        'id' => (int) $row['id'],
                        'title' => (string) ($row['title'] ?? ''),
                        'colour' => '',
                        'regdate' => (string) ($row['regdate'] ?? ''),
                        'type' => self::TYPE_OFFER_TEMPLATE,
                        'type_label' => 'Teklif Şablonu',
                        'statu' => 0,
                        'state' => (string) ($row['state'] ?? 'Header'),
                        'content' => (string) ($row['content'] ?? ''),
                        'note' => (string) ($row['state'] === 'Header' ? 'Üst Bilgi Şablonu' : 'Alt Bilgi Şablonu'),
                        'badge_theme' => 'indigo',
                        'creator' => (int) ($row['creator'] ?? 0),
                        'creator_name' => (string) ($row['creator_name'] ?? $row['creator_title'] ?? ''),
                        '_sort_key' => (int) $row['id'],
                    ];
                }

                // ID'ye göre azalan sırala
                usort($merged, static function ($a, $b) {
                    return $b['_sort_key'] <=> $a['_sort_key'];
                });

                // Sayfalama (Pagination slice)
                $sliced = array_slice($merged, $offset, $limit);
                foreach ($sliced as &$item) {
                    unset($item['_sort_key']);
                }
                unset($item);

                $items = $sliced;
            }
        } catch (\PDOException $e) {
            error_log("DefineModel::getDefinitions error: " . $e->getMessage());
        }

        return [
            'items' => $items,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Tanım Ekleme / Güncelleme
     */
    public function saveDefinition(array $data, int $userId, string $username = ''): array
    {
        $id = (int) ($data['id'] ?? 0);
        $type = (string) ($data['type'] ?? self::TYPE_UNIT);
        $title = trim((string) ($data['title'] ?? ''));
        $colour = trim((string) ($data['colour'] ?? ''));
        $note = trim((string) ($data['note'] ?? ''));
        $state = trim((string) ($data['state'] ?? 'Header'));
        $content = (string) ($data['content'] ?? '');

        if ($title === '') {
            return ['success' => false, 'message' => 'Lütfen tanım başlığını / adını giriniz.'];
        }

        $nowDate = date('d-m-Y - H:i:s');
        $isUpdate = $id > 0;

        try {
            if ($type === self::TYPE_OFFER_TEMPLATE) {
                // Teklif şablonu tablosu
                $templateDate = date('Y-m-d');
                if (!in_array($state, ['Header', 'Footer'], true)) {
                    $state = 'Header';
                }

                if ($isUpdate) {
                    $stmt = $this->db->prepare(
                        "UPDATE offertemplate SET State = ?, Title = ?, Content = ?, regDate = ?, creator = ? WHERE id = ?"
                    );
                    $stmt->execute([$state, $title, $content, $templateDate, $userId, $id]);
                    $targetId = $id;
                } else {
                    $stmt = $this->db->prepare(
                        "INSERT INTO offertemplate (State, Title, Content, regDate, creator) VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([$state, $title, $content, $templateDate, $userId]);
                    $targetId = (int) $this->db->lastInsertId();
                }

                if (function_exists('audit_log')) {
                    audit_log(
                        $isUpdate ? 'update' : 'create',
                        'offer_template',
                        ($isUpdate ? 'Teklif şablonu güncellendi: ' : 'Yeni teklif şablonu eklendi: ') . $title,
                        'offertemplate',
                        (string) $targetId,
                        [
                            'id' => $targetId,
                            'title' => $title,
                            'state' => $state,
                            'user_id' => $userId,
                            'username' => $username,
                        ]
                    );
                }

                return [
                    'success' => true,
                    'message' => $isUpdate ? 'Teklif şablonu başarıyla güncellendi.' : 'Teklif şablonu başarıyla oluşturuldu.',
                    'id' => $targetId,
                ];
            }

            // Units tablosu
            $meta = self::getTypeMeta($type);
            if (!$meta) {
                return ['success' => false, 'message' => 'Geçersiz tanım türü seçildi.'];
            }

            $statuCode = (int) $meta['statu'];
            if ($note === '') {
                $note = $meta['default_note'];
            }

            if ($isUpdate) {
                $stmt = $this->db->prepare(
                    "UPDATE units SET title = ?, colour = ?, note = ? WHERE id = ? AND statu = ?"
                );
                $stmt->execute([$title, $colour, $note, $id, $statuCode]);
                $targetId = $id;
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO units (title, colour, regdate, statu, creator, note) VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$title, $colour, $nowDate, $statuCode, $userId, $note]);
                $targetId = (int) $this->db->lastInsertId();
            }

            if (function_exists('audit_log')) {
                audit_log(
                    $isUpdate ? 'update' : 'create',
                    'definitions',
                    ($isUpdate ? "{$meta['label']} güncellendi: " : "Yeni {$meta['label']} eklendi: ") . $title,
                    'units',
                    (string) $targetId,
                    [
                        'id' => $targetId,
                        'title' => $title,
                        'type' => $type,
                        'statu' => $statuCode,
                        'user_id' => $userId,
                        'username' => $username,
                    ]
                );
            }

            return [
                'success' => true,
                'message' => "{$meta['label']} başarıyla " . ($isUpdate ? 'güncellendi.' : 'eklendi.'),
                'id' => $targetId,
            ];
        } catch (\PDOException $e) {
            error_log("DefineModel::saveDefinition error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Veritabanı kayıt işlemi sırasında bir hata oluştu.'];
        }
    }

    /**
     * Tanım Silme
     */
    public function deleteDefinition(string $type, int $id, int $userId, string $username = ''): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Geçersiz kayıt kimliği.'];
        }

        try {
            if ($type === self::TYPE_OFFER_TEMPLATE) {
                $checkStmt = $this->db->prepare("SELECT * FROM offertemplate WHERE id = ?");
                $checkStmt->execute([$id]);
                $item = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$item) {
                    return ['success' => false, 'message' => 'Silinmek istenen teklif şablonu bulunamadı.'];
                }

                $delStmt = $this->db->prepare("DELETE FROM offertemplate WHERE id = ?");
                $delStmt->execute([$id]);

                if (function_exists('audit_log')) {
                    audit_log(
                        'delete',
                        'offer_template',
                        'Teklif şablonu silindi: ' . ($item['Title'] ?? '#' . $id),
                        'offertemplate',
                        (string) $id,
                        [
                            'id' => $id,
                            'title' => $item['Title'] ?? '',
                            'state' => $item['State'] ?? '',
                            'user_id' => $userId,
                            'username' => $username,
                        ]
                    );
                }

                return ['success' => true, 'message' => 'Teklif şablonu başarıyla silindi.'];
            }

            $meta = self::getTypeMeta($type);
            if (!$meta) {
                return ['success' => false, 'message' => 'Geçersiz tanım türü.'];
            }

            $statuCode = (int) $meta['statu'];
            $checkStmt = $this->db->prepare("SELECT * FROM units WHERE id = ? AND statu = ?");
            $checkStmt->execute([$id, $statuCode]);
            $item = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                return ['success' => false, 'message' => "Silinmek istenen {$meta['label']} bulunamadı."];
            }

            $delStmt = $this->db->prepare("DELETE FROM units WHERE id = ? AND statu = ?");
            $delStmt->execute([$id, $statuCode]);

            if (function_exists('audit_log')) {
                audit_log(
                    'delete',
                    'definitions',
                    "{$meta['label']} silindi: " . ($item['title'] ?? '#' . $id),
                    'units',
                    (string) $id,
                    [
                        'id' => $id,
                        'title' => $item['title'] ?? '',
                        'type' => $type,
                        'statu' => $statuCode,
                        'user_id' => $userId,
                        'username' => $username,
                    ]
                );
            }

            return ['success' => true, 'message' => "{$meta['label']} başarıyla silindi."];
        } catch (\PDOException $e) {
            error_log("DefineModel::deleteDefinition error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Silme işlemi sırasında veritabanı hatası oluştu.'];
        }
    }
}
