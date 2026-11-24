<?php
namespace App\Model;

use PDO;

class PurchaseHistoryModel extends HistoryModel
{
    protected string $module = 'purchases';
    protected array $entityTypes = ['purchase', 'purchases', 'purchase_demand'];
    protected array $fieldLabels = [
        'siparisNo' => 'Kayıt No',
        'companyID' => 'Firma',
        'currency' => 'Para Birimi',
        'deadline' => 'Termin',
        'payment_period' => 'Ödeme Dönemi',
        'payment_date' => 'Ödeme Tarihi',
        'description1' => 'Açıklama',
        'description2' => 'Ek Açıklama',
        'altToplam' => 'Ara Toplam',
        'vadeGun' => 'Vade (Gün)',
        'DolarTotal' => 'Dolar Toplam',
        'EuroTotal' => 'Euro Toplam',
        'TLTotal' => 'TL Toplam',
        'Kdv' => 'KDV',
        'iskonto' => 'İskonto',
        'ToplamTL' => 'Genel Toplam (TL)',
        'state' => 'Durum',
        'invoice_date' => 'Fatura Tarihi',
        'invoice_number' => 'Fatura No',
        'type' => 'Kayıt Türü',
        'aciliyeti' => 'Aciliyet',
        'project_id' => 'Proje / Servis',
    ];

    public function getSummary(int $purchaseId): array
    {
        $stmt = $this->db->prepare("SELECT p.id, p.siparisNo, p.type, p.state, p.ToplamTL, c.company
                                    FROM purchases p LEFT JOIN customers c ON c.id = p.companyID
                                    WHERE p.id = ? LIMIT 1");
        $stmt->execute([$purchaseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $states = [0 => ['Bekliyor', 'badge-warning'], 1 => ['Onaylandı', 'badge-info'], 2 => ['Tamamlandı', 'badge-success']];
        $state = $states[(int) ($row['state'] ?? 0)] ?? ['-', 'badge-secondary'];
        return [
            'id' => (int) ($row['id'] ?? 0),
            'number' => $row['siparisNo'] ?? '',
            'company_name' => $row['company'] ?? '',
            'total_price' => (float) ($row['ToplamTL'] ?? 0),
            'statu_label' => $state[0],
            'statu_badge_class' => $state[1],
        ];
    }

    public static function diffItems(array $before, array $after): array
    {
        $key = static fn($i) => mb_strtolower(trim((string) ($i['stokKodu'] ?? '')) . '|' . trim((string) ($i['product'] ?? '')));
        $fields = ['amount' => 'Miktar', 'unit' => 'Birim', 'price' => 'Fiyat', 'currency' => 'Para Birimi', 'rowdescription' => 'Açıklama'];

        $old = [];
        foreach ($before as $i) {
            $old[$key($i)][] = $i;
        }
        $new = [];
        foreach ($after as $i) {
            $new[$key($i)][] = $i;
        }

        $result = [];
        foreach ($new as $k => $list) {
            foreach ($list as $idx => $item) {
                $name = trim((string) ($item['product'] ?? '')) ?: '(adsız kalem)';
                if (!isset($old[$k][$idx])) {
                    $result[] = ['type' => 'added', 'item' => $name];
                    continue;
                }
                $diffs = [];
                foreach ($fields as $f => $label) {
                    $o = $old[$k][$idx][$f] ?? '';
                    $n = $item[$f] ?? '';
                    $same = is_numeric($o) && is_numeric($n) ? abs((float) $o - (float) $n) < 0.000001 : (string) $o === (string) $n;
                    if (!$same) {
                        $diffs[] = ['field' => $label, 'old' => (string) $o, 'new' => (string) $n];
                    }
                }
                if ($diffs) {
                    $result[] = ['type' => 'changed', 'item' => $name, 'changes' => $diffs];
                }
            }
        }
        foreach ($old as $k => $list) {
            foreach ($list as $idx => $item) {
                if (!isset($new[$k][$idx])) {
                    $result[] = ['type' => 'removed', 'item' => trim((string) ($item['product'] ?? '')) ?: '(adsız kalem)'];
                }
            }
        }
        return array_slice($result, 0, 100);
    }
}
