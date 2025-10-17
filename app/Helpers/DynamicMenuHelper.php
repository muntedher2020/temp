<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class DynamicMenuHelper
{
    /**
     * Get dynamic menu items from config
     */
    public static function getMenuItems()
    {
        return config('dynamic-menu.menu_items', []);
    }

    /**
     * Render menu group (parent with children)
     */
    public static function renderMenuGroup($item)
    {
        $permission = $item['permission'];
        $title = $item['title'];
        $icon = $item['icon'];
        $activeRoutes = $item['active_routes'] ?? [];
        $children = $item['children'] ?? [];

        // Check if any child route is active for 'active open' class
        $isActiveOpen = self::isAnyRouteActive($activeRoutes);
        $activeClass = $isActiveOpen ? 'active open' : '';

        $html = "@can('{$permission}')\n";
        $html .= "                <li class=\"menu-item {$activeClass}\">\n";
        $html .= "                    <a href=\"javascript:void(0);\" class=\"menu-link menu-toggle\">\n";
        $html .= "                        <i class='menu-icon tf-icons {$icon}'></i>\n";
        $html .= "                        <span class=\"menu-title\">{$title}</span>\n";
        $html .= "                    </a>\n";
        $html .= "                    <ul class=\"menu-sub\">\n";

        // Render children
        foreach ($children as $child) {
            if ($child['type'] === 'item') {
                $html .= self::renderMenuItem($child, true);
            }
        }

        $html .= "                    </ul>\n";
        $html .= "                </li>\n";
        $html .= "            @endcan\n";

        return $html;
    }

    /**
     * Render single menu item
     */
    public static function renderMenuItem($item, $isChild = false)
    {
        $permission = $item['permission'];
        $title = $item['title'];
        $route = $item['route'];
        $icon = $item['icon'];

        // التعامل مع كلا الصيغتين - active_route (مفرد) أو active_routes (مصفوفة)
        $activeRoute = $item['active_route'] ?? ($item['active_routes'][0] ?? $route);

        $indent = $isChild ? '                        ' : '                ';
        $activeClass = "{{ request()->is('{$activeRoute}') ? 'active' : '' }}";

        $html = "{$indent}@can('{$permission}')\n";
        $html .= "{$indent}    <li class=\"menu-item {$activeClass}\">\n";
        $html .= "{$indent}        <a href=\"{{ Route('{$route}') }}\" class=\"menu-link\">\n";
        $html .= "{$indent}            <i class='{$icon}'></i>\n";
        $html .= "{$indent}            <div>{$title}</div>\n";
        $html .= "{$indent}        </a>\n";
        $html .= "{$indent}    </li>\n";
        $html .= "{$indent}@endcan\n";

        return $html;
    }

    /**
     * Check if any route in the array is currently active
     */
    private static function isAnyRouteActive($routes)
    {
        foreach ($routes as $route) {
            if (request()->is($route)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate complete menu HTML
     */
    public static function renderCompleteMenu()
    {
        $menuItems = self::getMenuItems();
        $html = '';

        foreach ($menuItems as $item) {
            if ($item['type'] === 'group') {
                $html .= self::renderMenuGroup($item) . "\n\n";
            } elseif ($item['type'] === 'item') {
                $html .= self::renderMenuItem($item) . "\n\n";
            }
        }

        return $html;
    }

    /**
     * Add new menu item dynamically
     */
    public static function addMenuItem($type, $permission, $title, $route = null, $icon = null, $activeRoute = null, $children = [])
    {
        $configPath = config_path('dynamic-menu.php');
        $config = include $configPath;

        $newItem = [
            'type' => $type,
            'permission' => $permission,
            'title' => $title,
            'icon' => $icon,
        ];

        if ($type === 'item') {
            $newItem['route'] = $route;
            $newItem['active_routes'] = is_array($activeRoute) ? $activeRoute : [$activeRoute]; // تحديث لاستخدام active_routes
        } elseif ($type === 'group') {
            $newItem['active_routes'] = is_array($activeRoute) ? $activeRoute : [$activeRoute];
            $newItem['children'] = $children;
        }

        $config['menu_items'][] = $newItem;

        // Write back to file
        $configContent = "<?php\n\nreturn " . var_export($config, true) . ";";
        file_put_contents($configPath, $configContent);
    }

    /**
     * Remove menu item by permission (مع دعم للوحدات الأساسية)
     */
    public static function removeMenuItem($permission)
    {
        $configPath = config_path('dynamic-menu.php');
        $config = include $configPath;

        foreach ($config['menu_items'] as $index => &$item) {
            // Remove main item if permission matches (بما في ذلك العناصر التي لها basic_group_id)
            if ($item['permission'] === $permission ||
                strtolower($item['permission']) === strtolower($permission)) {

                // إذا كان العنصر له basic_group_id، فهذا يعني أنه وحدة أساسية
                if (isset($item['basic_group_id'])) {
                    Log::info("حذف وحدة أساسية من القائمة الديناميكية: {$permission} (Basic Group ID: {$item['basic_group_id']})");
                }

                unset($config['menu_items'][$index]);
                continue;
            }

            // Check children if it's a group
            if ($item['type'] === 'group' && isset($item['children'])) {
                $originalChildrenCount = count($item['children']);

                // Filter out children with matching permission
                $item['children'] = array_filter($item['children'], function($child) use ($permission) {
                    return $child['permission'] !== $permission &&
                           strtolower($child['permission']) !== strtolower($permission);
                });

                // Reindex children array
                $item['children'] = array_values($item['children']);

                // Remove from active_routes if a child was removed
                if (count($item['children']) < $originalChildrenCount) {
                    $item['active_routes'] = array_filter($item['active_routes'], function($route) use ($permission) {
                        return $route !== $permission &&
                               strtolower($route) !== strtolower($permission);
                    });
                    $item['active_routes'] = array_values($item['active_routes']);
                }
            }
        }

        // Reindex array
        $config['menu_items'] = array_values($config['menu_items']);

        // Write back to file
        $configContent = "<?php\n\nreturn " . var_export($config, true) . ";";
        file_put_contents($configPath, $configContent);
    }

    /**
     * Remove entire menu group
     */
    public static function removeMenuGroup($permission)
    {
        $configPath = config_path('dynamic-menu.php');
        $config = include $configPath;

        // Remove any group with matching permission
        foreach ($config['menu_items'] as $index => $item) {
            if ($item['type'] === 'group' && $item['permission'] === $permission) {
                unset($config['menu_items'][$index]);
            }
        }

        // Reindex array
        $config['menu_items'] = array_values($config['menu_items']);

        // Write back to file
        $configContent = "<?php\n\nreturn " . var_export($config, true) . ";";
        file_put_contents($configPath, $configContent);
    }

    /**
     * Add menu item to "المشروع" group specifically
     */
    public static function addMenuItemToProject($permission, $title, $icon, $route = null)
    {
        $configPath = config_path('dynamic-menu.php');
        $config = include $configPath;

        $route = $route ?: $permission; // Use permission as route if not provided

        $newItem = [
            'type' => 'item',
            'permission' => $permission,
            'title' => $title,
            'route' => $route,
            'icon' => $icon,
            'active_routes' => [$route] // استخدام active_routes بدلاً من active_route
        ];

        // Find "المشروع" group
        $projectGroupFound = false;
        foreach ($config['menu_items'] as &$item) {
            if ($item['type'] === 'group' && $item['title'] === 'المشروع') {
                // Check if item already exists to prevent duplicates
                $itemExists = false;
                foreach ($item['children'] as $child) {
                    if ($child['permission'] === $permission || $child['route'] === $route) {
                        $itemExists = true;
                        break;
                    }
                }

                if (!$itemExists) {
                    // Add to existing active routes
                    if (!in_array($route, $item['active_routes'])) {
                        $item['active_routes'][] = $route;
                    }

                    // Add to children
                    $item['children'][] = $newItem;
                }

                $projectGroupFound = true;
                break;
            }
        }

        // If "المشروع" group doesn't exist, create it
        if (!$projectGroupFound) {
            $projectGroup = [
                'type' => 'group',
                'permission' => 'project',
                'title' => 'المشروع',
                'icon' => 'mdi mdi-folder-outline',
                'active_routes' => [$route],
                'children' => [$newItem]
            ];

            // Insert at the beginning of menu items
            array_unshift($config['menu_items'], $projectGroup);
        }

        // Write back to file
        $configContent = "<?php\n\nreturn " . var_export($config, true) . ";";
        file_put_contents($configPath, $configContent);
    }

    /**
     * Clean duplicate menu items
     */
    public static function cleanDuplicateItems()
    {
        $configPath = config_path('dynamic-menu.php');
        $config = include $configPath;

        foreach ($config['menu_items'] as &$item) {
            if ($item['type'] === 'group' && isset($item['children'])) {
                // Remove duplicate children based on permission
                $seenPermissions = [];
                $cleanChildren = [];

                foreach ($item['children'] as $child) {
                    if (!in_array($child['permission'], $seenPermissions)) {
                        $seenPermissions[] = $child['permission'];
                        $cleanChildren[] = $child;
                    }
                }

                $item['children'] = $cleanChildren;

                // Clean active_routes from duplicates
                $item['active_routes'] = array_unique($item['active_routes']);
                $item['active_routes'] = array_values($item['active_routes']);
            }
        }

        // Write back to file
        $configContent = "<?php\n\nreturn " . var_export($config, true) . ";";
        file_put_contents($configPath, $configContent);
    }

    /**
     * Add new menu group
     */
    public static function addMenuGroup($permission, $title, $icon, $activeRoutes = [])
    {
        $configPath = config_path('dynamic-menu.php');
        $config = include $configPath;

        // Check if group already exists
        foreach ($config['menu_items'] as $item) {
            if ($item['type'] === 'group' && $item['permission'] === $permission) {
                return; // Group already exists
            }
        }

        $newGroup = [
            'type' => 'group',
            'permission' => $permission,
            'title' => $title,
            'icon' => $icon,
            'active_routes' => is_array($activeRoutes) ? $activeRoutes : [$permission],
            'children' => []
        ];

        $config['menu_items'][] = $newGroup;

        // Write back to file
        $configContent = "<?php\n\nreturn " . var_export($config, true) . ";";
        file_put_contents($configPath, $configContent);
    }

    /**
     * Add menu item to specific group
     */
    public static function addMenuItemToGroup($groupPermission, $permission, $title, $icon, $route = null)
    {
        $configPath = config_path('dynamic-menu.php');
        $config = include $configPath;

        $route = $route ?: $permission;

        $newItem = [
            'type' => 'item',
            'permission' => $permission,
            'title' => $title,
            'route' => $route,
            'icon' => $icon,
            'active_routes' => [$route] // استخدام active_routes بدلاً من active_route
        ];

        // Find the target group
        $groupFound = false;
        foreach ($config['menu_items'] as &$item) {
            if ($item['type'] === 'group' && $item['permission'] === $groupPermission) {
                // Check if item already exists to prevent duplicates
                $itemExists = false;
                foreach ($item['children'] as $child) {
                    if ($child['permission'] === $permission || $child['route'] === $route) {
                        $itemExists = true;
                        break;
                    }
                }

                if (!$itemExists) {
                    // Add to existing active routes
                    if (!in_array($route, $item['active_routes'])) {
                        $item['active_routes'][] = $route;
                    }

                    // Add to children
                    $item['children'][] = $newItem;
                }

                $groupFound = true;
                break;
            }
        }

        if (!$groupFound) {
            throw new \Exception("Group with permission '{$groupPermission}' not found");
        }

        // Write back to file
        $configContent = "<?php\n\nreturn " . var_export($config, true) . ";";
        file_put_contents($configPath, $configContent);
    }
}
