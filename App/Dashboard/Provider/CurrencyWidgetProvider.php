<?php

namespace App\Dashboard\Provider;

use App\Dashboard\AbstractDashboardWidgetProvider;
use App\Dashboard\WidgetCache;
use RuntimeException;

class CurrencyWidgetProvider extends AbstractDashboardWidgetProvider
{
    public function definition(int $userId): array
    {
        return [
            'id' => 'widget_currency_rates', 'title' => 'Döviz Kurları',
            'subtitle' => 'TCMB gösterge niteliğindeki güncel kurlar', 'icon' => 'fa fa-exchange',
            'badge' => 'Piyasa', 'badge_class' => 'soft-emerald', 'default_w' => 4, 'default_h' => 5,
            'min_w' => 4, 'min_h' => 5, 'is_allowed' => true, 'default_order' => 3,
        ];
    }

    public function data(int $userId): array
    {
        $cache = new WidgetCache();
        $cached = $cache->get('tcmb_today_rates', 1800);
        if ($cached !== null) {
            $cached['cached'] = true;
            return $cached;
        }

        try {
            $ch = curl_init('https://www.tcmb.gov.tr/kurlar/today.xml');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 7, CURLOPT_USERAGENT => 'AydinogullariYSC-Dashboard/1.0']);
            $xmlText = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($xmlText === false || $status !== 200) {
                throw new RuntimeException($error ?: 'TCMB HTTP ' . $status);
            }

            $xml = simplexml_load_string($xmlText, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
            if ($xml === false) {
                throw new RuntimeException('TCMB XML yanıtı okunamadı.');
            }
            $rates = [];
            foreach (['USD' => 'ABD Doları', 'EUR' => 'Euro', 'GBP' => 'İngiliz Sterlini'] as $code => $name) {
                $nodes = $xml->xpath("/Tarih_Date/Currency[@CurrencyCode='{$code}']");
                if (!$nodes) {
                    continue;
                }
                $rates[] = ['code' => $code, 'name' => $name,
                    'buying' => (string)$nodes[0]->ForexBuying, 'selling' => (string)$nodes[0]->ForexSelling];
            }
            $result = ['rates' => $rates, 'date' => (string)($xml['Tarih'] ?? date('d.m.Y')), 'cached' => false];
            $cache->put('tcmb_today_rates', $result);
            return $result;
        } catch (\Throwable $exception) {
            $stale = $cache->get('tcmb_today_rates', PHP_INT_MAX, true);
            if ($stale !== null) {
                $stale['cached'] = true;
                $stale['stale'] = true;
                return $stale;
            }
            throw $exception;
        }
    }
}
