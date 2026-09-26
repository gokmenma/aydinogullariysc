<?php

namespace App\Dashboard;

use PDO;

abstract class AbstractDashboardWidgetProvider implements DashboardWidgetProviderInterface
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    protected function hasAnyPermission(array $permissions): bool
    {
        if (!isset($_SESSION['lid']) || !function_exists('permtrue')) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (permtrue($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function dateExpression(string $column): string
    {
        return "COALESCE(STR_TO_DATE(REPLACE({$column}, '.', '-'), '%d-%m-%Y'), STR_TO_DATE({$column}, '%Y-%m-%d'))";
    }

    protected function assignedExpression(string $column): string
    {
        return "FIND_IN_SET(?, REPLACE(REPLACE(COALESCE({$column}, ''), '|', ','), ' ', '')) > 0";
    }
}
