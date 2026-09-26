<?php

namespace App\Dashboard;

use App\Dashboard\Provider\CurrencyWidgetProvider;
use App\Dashboard\Provider\PurchaseApprovalWidgetProvider;
use App\Dashboard\Provider\TodayWorkWidgetProvider;
use App\Dashboard\Provider\UpcomingWorkWidgetProvider;
use PDO;
use Throwable;

class DashboardWidgetRegistry
{
    /** @var DashboardWidgetProviderInterface[] */
    private array $providers;

    public function __construct(PDO $db)
    {
        $this->providers = [
            new CurrencyWidgetProvider($db),
            new TodayWorkWidgetProvider($db),
            new UpcomingWorkWidgetProvider($db),
            new PurchaseApprovalWidgetProvider($db),
        ];
    }

    public function definitions(int $userId): array
    {
        $definitions = [];
        foreach ($this->providers as $provider) {
            $definition = $provider->definition($userId);
            $definitions[$definition['id']] = $definition;
        }
        return $definitions;
    }

    public function data(int $userId, array $widgetIds = []): array
    {
        $result = [];
        foreach ($this->providers as $provider) {
            $definition = $provider->definition($userId);
            if (empty($definition['is_allowed']) || ($widgetIds !== [] && !in_array($definition['id'], $widgetIds, true))) {
                continue;
            }
            try {
                $result[$definition['id']] = array_merge(['available' => true], $provider->data($userId));
            } catch (Throwable $exception) {
                error_log('Dashboard widget [' . $definition['id'] . '] hatası: ' . $exception->getMessage());
                $result[$definition['id']] = ['available' => false, 'message' => 'Veri şu anda alınamadı.'];
            }
        }
        return $result;
    }
}
