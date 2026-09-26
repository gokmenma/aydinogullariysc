<?php

namespace App\Dashboard\Provider;

use App\Dashboard\AbstractDashboardWidgetProvider;
use PDO;

class TodayWorkWidgetProvider extends AbstractDashboardWidgetProvider
{
    public function definition(int $userId): array
    {
        return [
            'id' => 'widget_today_work', 'title' => 'Bugünkü İşlerim',
            'subtitle' => 'Bugüne planlanan görev ve servisler', 'icon' => 'fa fa-sun-o',
            'badge' => 'Bugün', 'badge_class' => 'soft-amber', 'default_w' => 4, 'default_h' => 5,
            'min_w' => 4, 'min_h' => 5, 'is_allowed' => true, 'default_order' => 4,
        ];
    }

    public function data(int $userId): array
    {
        $items = [];
        $missionDate = $this->dateExpression('lastdate');
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM missions WHERE deleted = 'no' AND statu = 0 AND {$this->assignedExpression('authors')} AND {$missionDate} = CURDATE()");
        $countStmt->execute([(string)$userId]);
        $missionCount = (int)$countStmt->fetchColumn();
        $stmt = $this->db->prepare("SELECT id, title FROM missions WHERE deleted = 'no' AND statu = 0 AND {$this->assignedExpression('authors')} AND {$missionDate} = CURDATE() ORDER BY id DESC LIMIT 4");
        $stmt->execute([(string)$userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = ['type' => 'Görev', 'title' => $row['title'], 'url' => 'index.php?p=view-mission&mid=' . (int)$row['id'], 'icon' => 'fa-check-square-o'];
        }

        $serviceDate = $this->dateExpression('pstart_date');
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM projects p WHERE {$this->assignedExpression('p.pauthors')} AND {$serviceDate} = CURDATE()");
        $countStmt->execute([(string)$userId]);
        $serviceCount = (int)$countStmt->fetchColumn();
        $stmt = $this->db->prepare("SELECT p.id, p.service_number, c.company FROM projects p LEFT JOIN customers c ON c.id = p.pcid WHERE {$this->assignedExpression('p.pauthors')} AND {$serviceDate} = CURDATE() ORDER BY p.id DESC LIMIT 4");
        $stmt->execute([(string)$userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = ['type' => 'Servis', 'title' => trim(($row['service_number'] ?: 'Servis') . ' · ' . ($row['company'] ?: 'Firma belirtilmedi')), 'url' => 'index.php?p=service/list&id=' . (int)$row['id'], 'icon' => 'fa-wrench'];
        }

        return ['items' => array_slice($items, 0, 6), 'mission_count' => $missionCount, 'service_count' => $serviceCount];
    }
}
