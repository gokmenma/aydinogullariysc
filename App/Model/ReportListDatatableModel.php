<?php

namespace App\Model;

use PDO;
use App\Helper\Date;

class ReportListDatatableModel
{
    private PDO $db;

    private const FILLING_VIEW = 'sqlsonkullanmatarihi';
    private const CONTROL_VIEW = 'sqlvalidity_date';
    private const MAX_LENGTH = 10000;

    public function __construct(?PDO $db = null)
    {
        if ($db instanceof PDO) {
            $this->db = $db;
            return;
        }
        global $ac;
        if (!($ac instanceof PDO)) {
            throw new \RuntimeException('Veritabanı bağlantısı bulunamadı.');
        }
        $this->db = $ac;
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, ['filling', 'control'], true);
    }

    private function config(string $type): array
    {
        if ($type === 'control') {
            return [
                'columns' => [
                    1 => ['firma_adi', 'text'],
                    2 => ['report_number', 'text'],
                    3 => ['validity_sort', 'date'],
                    4 => ['ay', 'number'],
                    5 => ['yil', 'number'],
                ],
                'search' => ['firma_adi', 'report_number', 'validity_date', 'ay', 'yil'],
                'counts' => [1 => 'firma_adi', 2 => 'report_number', 4 => 'ay', 5 => 'yil'],
                'default_order' => 'report_id ASC',
            ];
        }
        return [
            'columns' => [
                1 => ['firma_adi', 'text'],
                2 => ['report_number', 'text'],
                3 => ['cihaz_no', 'text'],
                4 => ['bulundugu_bolge', 'text'],
                5 => ['ay', 'number'],
                6 => ['yil', 'number'],
            ],
            'search' => ['firma_adi', 'report_number', 'cihaz_no', 'bulundugu_bolge', 'ay', 'yil'],
            'counts' => [1 => 'firma_adi', 2 => 'report_number', 3 => 'cihaz_no', 4 => 'bulundugu_bolge', 5 => 'ay', 6 => 'yil'],
            'default_order' => 'report_id ASC, content_id ASC',
        ];
    }

    private function baseQuery(string $type, array $period): array
    {
        $conditions = [];
        $params = [];

        if (($period['year'] ?? '') !== '') {
            $conditions[] = 'yil = ?';
            $params[] = (string) $period['year'];
        }
        if (($period['month'] ?? '') !== '') {
            $conditions[] = 'ay = ?';
            $params[] = Date::setMonth((string) $period['month']);
        }

        if ($type === 'control') {
            $normalized = "STR_TO_DATE(REPLACE(REPLACE(validity_date, '.', '-'), '/', '-'), '%d-%m-%Y')";
            if (($period['date_from'] ?? '') !== '') {
                $conditions[] = $normalized . ' >= ?';
                $params[] = $period['date_from'];
            }
            if (($period['date_to'] ?? '') !== '') {
                $conditions[] = $normalized . ' <= ?';
                $params[] = $period['date_to'];
            }
            $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
            $sql = 'SELECT report_id, report_number, firma_adi, validity_date, ay, yil, ' . $normalized . ' AS validity_sort'
                . ' FROM ' . self::CONTROL_VIEW . $where . ' GROUP BY firma_adi';
            return [$sql, $params];
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $sql = 'SELECT content_id, report_id, report_number, firma_adi, bulundugu_bolge, cihaz_no, ay, yil'
            . ' FROM ' . self::FILLING_VIEW . $where;
        return [$sql, $params];
    }

    public function getSummary(string $type, array $period): array
    {
        [$base, $params] = $this->baseQuery($type, $period);
        $sql = 'SELECT COUNT(*) AS total_count,'
            . " COUNT(DISTINCT CASE WHEN firma_adi IS NOT NULL AND firma_adi <> '' THEN firma_adi END) AS unique_companies,"
            . " COUNT(DISTINCT CASE WHEN report_number IS NOT NULL AND report_number <> '' THEN report_number END) AS unique_reports"
            . ' FROM (' . $base . ') t';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total_count'] ?? 0),
            'companies' => (int) ($row['unique_companies'] ?? 0),
            'reports' => (int) ($row['unique_reports'] ?? 0),
        ];
    }

    public function getPage(string $type, array $period, array $request, bool $withCounts = false): array
    {
        $config = $this->config($type);
        [$base, $baseParams] = $this->baseQuery($type, $period);

        $where = [];
        $params = [];

        $search = trim((string) ($request['search'] ?? ''));
        if ($search !== '') {
            $parts = [];
            foreach ($config['search'] as $column) {
                $parts[] = $column . ' LIKE ?';
                $params[] = '%' . $search . '%';
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        foreach ((array) ($request['column_search'] ?? []) as $index => $raw) {
            $index = (int) $index;
            if (!isset($config['columns'][$index]) || !is_string($raw) || trim($raw) === '') {
                continue;
            }
            [$column, $colType] = $config['columns'][$index];
            $condition = $this->columnCondition($column, $colType, $raw, $params);
            if ($condition !== null) {
                $where[] = $condition;
            }
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $totalStmt = $this->db->prepare('SELECT COUNT(*) FROM (' . $base . ') t');
        $totalStmt->execute($baseParams);
        $total = (int) $totalStmt->fetchColumn();

        $filtered = $total;
        if ($where) {
            $filteredStmt = $this->db->prepare('SELECT COUNT(*) FROM (' . $base . ') t' . $whereSql);
            $filteredStmt->execute(array_merge($baseParams, $params));
            $filtered = (int) $filteredStmt->fetchColumn();
        }

        $orderSql = $config['default_order'];
        $orderIndex = $request['order_column'] ?? null;
        if ($orderIndex !== null && isset($config['columns'][(int) $orderIndex])) {
            $dir = strtolower((string) ($request['order_dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
            $orderSql = $config['columns'][(int) $orderIndex][0] . ' ' . $dir . ', ' . $config['default_order'];
        }

        $start = max(0, (int) ($request['start'] ?? 0));
        $length = (int) ($request['length'] ?? 25);
        if ($length < 1 || $length > self::MAX_LENGTH) {
            $length = self::MAX_LENGTH;
        }

        $sql = 'SELECT * FROM (' . $base . ') t' . $whereSql
            . ' ORDER BY ' . $orderSql . ' LIMIT ' . $start . ', ' . $length;
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($baseParams, $params));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = ['rows' => $rows, 'total' => $total, 'filtered' => $filtered, 'counts' => []];

        if ($withCounts) {
            foreach ($config['counts'] as $index => $column) {
                $countStmt = $this->db->prepare(
                    'SELECT ' . $column . ' AS v, COUNT(*) AS c FROM (' . $base . ') t'
                    . ' WHERE ' . $column . " IS NOT NULL AND " . $column . " <> '' GROUP BY " . $column . ' ORDER BY c DESC LIMIT 300'
                );
                $countStmt->execute($baseParams);
                $result['counts'][$index] = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }
        }

        return $result;
    }

    private function toIsoDate(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return $value;
        }
        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/', $value, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        return '';
    }

    private function columnCondition(string $column, string $type, string $raw, array &$params): ?string
    {
        $json = json_decode($raw, true);
        if (!is_array($json) || (!isset($json['rules']) && !isset($json['values']))) {
            return $this->textRule($column, 'contains', trim($raw), $params);
        }

        if (!empty($json['values']) && is_array($json['values'])) {
            $parts = [];
            foreach ($json['values'] as $value) {
                $value = (string) $value;
                if ($type === 'date') {
                    $iso = $this->toIsoDate($value);
                    if ($iso === '') {
                        continue;
                    }
                    $parts[] = $column . ' = ?';
                    $params[] = $iso;
                } else {
                    $parts[] = $column . ' = ?';
                    $params[] = $value;
                }
            }
            return $parts ? '(' . implode(' OR ', $parts) . ')' : null;
        }

        if (empty($json['rules']) || !is_array($json['rules'])) {
            return null;
        }

        $logic = (isset($json['logic']) && strtolower((string) $json['logic']) === 'or') ? ' OR ' : ' AND ';
        $conditions = [];
        foreach ($json['rules'] as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $operator = (string) ($rule['operator'] ?? 'contains');
            $value = trim((string) ($rule['value'] ?? ''));

            if ($operator === 'empty') {
                $conditions[] = '(' . $column . " IS NULL OR " . $column . " = '')";
                continue;
            }
            if ($operator === 'not_empty') {
                $conditions[] = '(' . $column . " IS NOT NULL AND " . $column . " <> '')";
                continue;
            }
            if ($value === '') {
                continue;
            }

            $condition = match ($type) {
                'date' => $this->dateRule($column, $operator, $value, $params),
                'number' => $this->numberRule($column, $operator, $value, $params),
                default => $this->textRule($column, $operator, $value, $params),
            };
            if ($condition !== null) {
                $conditions[] = $condition;
            }
        }

        return $conditions ? '(' . implode($logic, $conditions) . ')' : null;
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function textRule(string $column, string $operator, string $value, array &$params): ?string
    {
        $like = $this->escapeLike($value);
        switch ($operator) {
            case 'equals':
                $params[] = $value;
                return $column . ' = ?';
            case 'starts':
                $params[] = $like . '%';
                return $column . ' LIKE ?';
            case 'ends':
                $params[] = '%' . $like;
                return $column . ' LIKE ?';
            case 'not_contains':
                $params[] = '%' . $like . '%';
                return '(' . $column . ' IS NULL OR ' . $column . ' NOT LIKE ?)';
            case 'contains':
                $params[] = '%' . $like . '%';
                return $column . ' LIKE ?';
        }
        return null;
    }

    private function numberRule(string $column, string $operator, string $value, array &$params): ?string
    {
        if ($operator === 'contains') {
            return $this->textRule($column, 'contains', $value, $params);
        }
        $digits = preg_replace('/[^0-9-]/', '', $value);
        if ($digits === '' || !is_numeric($digits)) {
            return null;
        }
        $symbols = ['equals' => '=', 'gt' => '>', 'lt' => '<', 'gte' => '>=', 'lte' => '<='];
        if (!isset($symbols[$operator])) {
            return null;
        }
        $params[] = (int) $digits;
        return 'CAST(' . $column . ' AS SIGNED) ' . $symbols[$operator] . ' ?';
    }

    private function dateRule(string $column, string $operator, string $value, array &$params): ?string
    {
        $iso = $this->toIsoDate($value);
        if ($iso === '') {
            return null;
        }
        $symbols = ['equals' => '=', 'after' => '>', 'before' => '<', 'gt' => '>', 'lt' => '<', 'gte' => '>=', 'lte' => '<='];
        if (!isset($symbols[$operator])) {
            return null;
        }
        $params[] = $iso;
        return $column . ' ' . $symbols[$operator] . ' ?';
    }
}
