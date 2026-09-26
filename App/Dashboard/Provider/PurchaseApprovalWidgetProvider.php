<?php

namespace App\Dashboard\Provider;

use App\Dashboard\AbstractDashboardWidgetProvider;
use App\Model\PurchaseModel;

class PurchaseApprovalWidgetProvider extends AbstractDashboardWidgetProvider
{
    public function definition(int $userId): array
    {
        return [
            'id' => 'widget_purchase_approvals', 'title' => 'Satın Alma ve Onaylar',
            'subtitle' => 'Talep, onay ve tamamlanma özeti', 'icon' => 'fa fa-shopping-cart',
            'badge' => 'Satın Alma', 'badge_class' => 'soft-blue', 'default_w' => 12, 'default_h' => 4,
            'min_w' => 3, 'min_h' => 3, 'is_allowed' => $this->hasAnyPermission(['purchase_dashboard', 'purchaseadd']), 'default_order' => 6,
        ];
    }

    public function data(int $userId): array
    {
        $model = new PurchaseModel();
        $summary = $model->getDashboardSummary();
        return ['pending' => (int)$summary->pending_count, 'approved' => (int)$summary->approved_count,
            'completed' => (int)$summary->completed_count, 'rejected' => (int)$summary->rejected_count,
            'total' => (int)$summary->total_count, 'completion_rate' => (float)$summary->completion_rate];
    }
}
