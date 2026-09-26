<?php

namespace App\Dashboard;

interface DashboardWidgetProviderInterface
{
    public function definition(int $userId): array;

    public function data(int $userId): array;
}
