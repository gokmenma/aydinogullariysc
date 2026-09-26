<?php

namespace App\Dashboard\Provider;

use App\Dashboard\AbstractDashboardWidgetProvider;
use PDO;

class UpcomingWorkWidgetProvider extends AbstractDashboardWidgetProvider
{
    public function definition(int $userId): array
    {
        return [
            'id' => 'widget_upcoming_work', 'title' => 'Geciken ve Yaklaşan İşler',
            'subtitle' => 'Aksiyon gerektiren tarih ve iş yükü', 'icon' => 'fa fa-exclamation-triangle',
            'badge' => 'Takip', 'badge_class' => 'soft-purple', 'default_w' => 4, 'default_h' => 5,
            'min_w' => 4, 'min_h' => 5, 'is_allowed' => true, 'default_order' => 5,
        ];
    }

    public function data(int $userId): array
    {
        $date = $this->dateExpression('lastdate');
        $assigned = $this->assignedExpression('authors');
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM missions WHERE deleted = 'no' AND statu = 0 AND {$assigned} AND {$date} IS NOT NULL AND {$date} < CURDATE()");
        $countStmt->execute([(string)$userId]);
        $overdueCount = (int)$countStmt->fetchColumn();
        $stmt = $this->db->prepare("SELECT id, title, {$date} AS due_date FROM missions WHERE deleted = 'no' AND statu = 0 AND {$assigned} AND {$date} IS NOT NULL AND {$date} < CURDATE() ORDER BY due_date ASC LIMIT 4");
        $stmt->execute([(string)$userId]);
        $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM missions WHERE deleted = 'no' AND statu = 0 AND {$assigned} AND {$date} BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
        $stmt->execute([(string)$userId]);
        $upcomingMissions = (int)$stmt->fetchColumn();

        $serviceDate = $this->dateExpression('pstart_date');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM projects p WHERE {$this->assignedExpression('p.pauthors')} AND {$serviceDate} BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
        $stmt->execute([(string)$userId]);

        return ['overdue' => $overdue, 'overdue_count' => $overdueCount, 'upcoming_mission_count' => $upcomingMissions, 'upcoming_service_count' => (int)$stmt->fetchColumn()];
    }
}
