<?php

return array (
  'menu_items' => 
  array (
    0 => 
    array (
      'type' => 'group',
      'basic_group_id' => 219,
      'permission' => 'Projects',
      'title' => 'المشروع',
      'icon' => 'mdi mdi-contacts',
      'active_routes' => 
      array (
        0 => 'Projects',
      ),
      'children' => 
      array (
      ),
    ),
    1 => 
    array (
      'type' => 'group',
      'basic_group_id' => 221,
      'permission' => 'Settings',
      'title' => 'الاعدادات',
      'icon' => 'mdi mdi-cog',
      'active_routes' => 
      array (
        0 => 'Settings',
      ),
      'children' => 
      array (
      ),
    ),
  ),
  'templates' => 
  array (
    'group' => 
    array (
      'class' => 'menu-item',
      'link_class' => 'menu-link menu-toggle',
      'sub_class' => 'menu-sub',
    ),
    'item' => 
    array (
      'class' => 'menu-item',
      'link_class' => 'menu-link',
    ),
  ),
);
