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
