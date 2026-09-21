<?php

namespace App\Helper;

use PDO;

/**
 * DataTableFilter
 * 
 * DataTables Server-Side Processing için TableFilter JSON ve metin filtrelerini
 * güvenli PDO sorgu koşullarına dönüştüren merkezi yardımcı sınıf.
 */
class DataTableFilter
{
    /**
     * DataTables search değerini parse eder (JSON veya düz metin).
     *
     * @param mixed $rawSearch
     * @return array|null Filtre yapısı veya boş ise null
     */
    public static function parse($rawSearch): ?array
    {
        if ($rawSearch === null) {
            return null;
        }

        $rawSearch = trim((string)$rawSearch);
        if ($rawSearch === '') {
            return null;
        }

        // JSON formatında mı kontrol et
        if (($rawSearch[0] === '{' || $rawSearch[0] === '[') && ($decoded = json_decode($rawSearch, true)) !== null && is_array($decoded)) {
            // Select filtre formatı
            if (isset($decoded['values']) && is_array($decoded['values']) && count($decoded['values']) > 0) {
                return [
                    'type' => 'select',
                    'values' => array_values($decoded['values']),
                    'logic' => 'or'
                ];
            }

            // Kural bazlı filtre formatı (text, number, date)
            if (isset($decoded['rules']) && is_array($decoded['rules']) && count($decoded['rules']) > 0) {
                return [
                    'type' => $decoded['type'] ?? 'text',
                    'rules' => $decoded['rules'],
                    'logic' => (strtolower($decoded['logic'] ?? '') === 'and') ? 'and' : 'or'
                ];
            }

            return null;
        }

        // Düz metin araması (Klasik DataTables araması)
        return [
            'type' => 'text',
            'rules' => [
                [
                    'operator' => 'contains',
                    'value' => $rawSearch
                ]
            ],
            'logic' => 'or'
        ];
    }

    /**
     * Belirtilen SQL sütun ifadesi için filtre koşulu ve PDO parametrelerini üretir.
     *
     * @param string $colExpr SQL sütun veya ifade adı (örn: "p.service_number", "c.company")
     * @param array $filter parse() ile elde edilen filtre dizisi
     * @param array &$params PDO parametre dizisi
     * @param string $paramPrefix Benzersiz parametre öneki
     * @param string $columnType 'text', 'number', 'date', 'datetime'
     * @return string SQL WHERE koşulu parçası
     */
    public static function buildCondition(string $colExpr, array $filter, array &$params, string $paramPrefix = 'f_', string $columnType = 'text'): string
    {
        $type = $filter['type'] ?? $columnType;

        // Select filtre (IN (...))
        if ($type === 'select' && !empty($filter['values']) && is_array($filter['values'])) {
            $inKeys = [];
            foreach ($filter['values'] as $i => $val) {
                $pKey = ":{$paramPrefix}sel_{$i}";
                $params[$pKey] = $val;
                $inKeys[] = $pKey;
            }
            return "{$colExpr} IN (" . implode(', ', $inKeys) . ")";
        }

        // Kural bazlı filtre (text, number, date)
        if (!empty($filter['rules']) && is_array($filter['rules'])) {
            $conditions = [];
            $logic = (strtolower($filter['logic'] ?? '') === 'or') ? ' OR ' : ' AND ';

            foreach ($filter['rules'] as $idx => $rule) {
                $op = $rule['operator'] ?? 'contains';
                $val = trim((string)($rule['value'] ?? ''));
                $pKey = ":{$paramPrefix}r_{$idx}";

                if ($op === 'empty') {
                    $conditions[] = "({$colExpr} IS NULL OR {$colExpr} = '' OR {$colExpr} = '-')";
                    continue;
                }
                if ($op === 'not_empty') {
                    $conditions[] = "({$colExpr} IS NOT NULL AND {$colExpr} != '' AND {$colExpr} != '-')";
                    continue;
                }

                if ($val === '') {
                    continue;
                }

                switch ($op) {
                    case 'contains':
                        if ($type === 'date' || $type === 'datetime') {
                            $conditions[] = "(DATE_FORMAT({$colExpr}, '%d.%m.%Y') LIKE {$pKey} OR DATE_FORMAT({$colExpr}, '%Y-%m-%d') LIKE {$pKey} OR {$colExpr} LIKE {$pKey})";
                        } else {
                            $conditions[] = "{$colExpr} LIKE {$pKey}";
                        }
                        $params[$pKey] = "%{$val}%";
                        break;

                    case 'not_contains':
                        $conditions[] = "({$colExpr} NOT LIKE {$pKey} OR {$colExpr} IS NULL)";
                        $params[$pKey] = "%{$val}%";
                        break;

                    case 'starts':
                        $conditions[] = "{$colExpr} LIKE {$pKey}";
                        $params[$pKey] = "{$val}%";
                        break;

                    case 'ends':
                        $conditions[] = "{$colExpr} LIKE {$pKey}";
                        $params[$pKey] = "%{$val}";
                        break;

                    case 'equals':
                        if ($type === 'date' || $type === 'datetime') {
                            $parsedDate = self::normalizeDate($val);
                            if ($parsedDate) {
                                $conditions[] = "DATE({$colExpr}) = {$pKey}";
                                $params[$pKey] = $parsedDate;
                            } else {
                                $conditions[] = "DATE_FORMAT({$colExpr}, '%d.%m.%Y') = {$pKey}";
                                $params[$pKey] = $val;
                            }
                        } else {
                            $conditions[] = "{$colExpr} = {$pKey}";
                            $params[$pKey] = $val;
                        }
                        break;

                    case 'gt':
                    case 'after':
                        if ($type === 'date' || $type === 'datetime') {
                            $parsedDate = self::normalizeDate($val);
                            $targetDate = $parsedDate ?: $val;
                            $conditions[] = "DATE({$colExpr}) > {$pKey}";
                            $params[$pKey] = $targetDate;
                        } else {
                            $conditions[] = "{$colExpr} > {$pKey}";
                            $params[$pKey] = is_numeric($val) ? (float)$val : $val;
                        }
                        break;

                    case 'gte':
                        if ($type === 'date' || $type === 'datetime') {
                            $parsedDate = self::normalizeDate($val);
                            $targetDate = $parsedDate ?: $val;
                            $conditions[] = "DATE({$colExpr}) >= {$pKey}";
                            $params[$pKey] = $targetDate;
                        } else {
                            $conditions[] = "{$colExpr} >= {$pKey}";
                            $params[$pKey] = is_numeric($val) ? (float)$val : $val;
                        }
                        break;

                    case 'lt':
                    case 'before':
                        if ($type === 'date' || $type === 'datetime') {
                            $parsedDate = self::normalizeDate($val);
                            $targetDate = $parsedDate ?: $val;
                            $conditions[] = "DATE({$colExpr}) < {$pKey}";
                            $params[$pKey] = $targetDate;
                        } else {
                            $conditions[] = "{$colExpr} < {$pKey}";
                            $params[$pKey] = is_numeric($val) ? (float)$val : $val;
                        }
                        break;

                    case 'lte':
                        if ($type === 'date' || $type === 'datetime') {
                            $parsedDate = self::normalizeDate($val);
                            $targetDate = $parsedDate ?: $val;
                            $conditions[] = "DATE({$colExpr}) <= {$pKey}";
                            $params[$pKey] = $targetDate;
                        } else {
                            $conditions[] = "{$colExpr} <= {$pKey}";
                            $params[$pKey] = is_numeric($val) ? (float)$val : $val;
                        }
                        break;
                }
            }

            if (count($conditions) === 1) {
                return $conditions[0];
            }
            if (count($conditions) > 1) {
                return '(' . implode($logic, $conditions) . ')';
            }
        }

        return '';
    }

    /**
     * Türkçe veya standart tarih formatlarını Y-m-d formatına çevirir.
     *
     * @param string $val
     * @return string|null
     */
    public static function normalizeDate(string $val): ?string
    {
        $val = trim($val);
        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/', $val, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/^(\d{4})[.\/-](\d{1,2})[.\/-](\d{1,2})$/', $val, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }
        return null;
    }
}
