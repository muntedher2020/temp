<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        // قراءة التكوين الديناميكي للداشبورد
        $dashboardConfig = $this->getDashboardConfig();

        return view('content.dashboard.dashboard-main', [
            'dashboardWidgets' => $dashboardConfig
        ]);
    }

    private function getDashboardConfig()
    {
        $configFile = storage_path('app/dashboard_config.json');

        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            return $config ?? [];
        }

        // التكوين الافتراضي
        return [
            [
                'id' => 'default_users_stat',
                'type' => 'stat',
                'title' => 'إجمالي المستخدمين',
                'module' => 'users',
                'icon' => 'mdi-account-group',
                'color' => 'primary',
                'stat_type' => 'count'
            ],
            [
                'id' => 'default_recent_table',
                'type' => 'table',
                'title' => 'آخر العمليات',
                'module' => 'users',
                'columns' => ['name', 'email', 'created_at'],
                'limit' => 5
            ]
        ];
    }
}
