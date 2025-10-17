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
        1 => 'Employees',
        2 => 'Trainers',
        3 => 'Courses',
        4 => 'CourseCandidates',
      ),
      'children' => 
      array (
        0 => 
        array (
          'type' => 'item',
          'permission' => 'Employees',
          'title' => 'الموظفين',
          'route' => 'Employees',
          'icon' => 'mdi mdi-account-tie',
          'active_routes' => 
          array (
            0 => 'Employees',
          ),
        ),
        1 => 
        array (
          'type' => 'item',
          'permission' => 'Trainers',
          'title' => 'المدربين',
          'route' => 'Trainers',
          'icon' => 'mdi mdi-card-account-details-star-outline',
          'active_routes' => 
          array (
            0 => 'Trainers',
          ),
        ),
        2 => 
        array (
          'type' => 'item',
          'permission' => 'Courses',
          'title' => 'الدورات التدريبية',
          'route' => 'Courses',
          'icon' => 'mdi mdi-view-column-outline',
          'active_routes' => 
          array (
            0 => 'Courses',
          ),
        ),
        3 => 
        array (
          'type' => 'item',
          'permission' => 'CourseCandidates',
          'title' => 'المتدربين والمرشحين',
          'route' => 'CourseCandidates',
          'icon' => 'mdi mdi-account-box-multiple-outline',
          'active_routes' => 
          array (
            0 => 'CourseCandidates',
          ),
        ),
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
        1 => 'EducationalLevels',
        2 => 'Departments',
        3 => 'JobTitles',
        4 => 'JobGrades',
        5 => 'TrainingInstitutions',
        6 => 'TrainingDomains',
        7 => 'Venues',
      ),
      'children' => 
      array (
        0 => 
        array (
          'type' => 'item',
          'permission' => 'EducationalLevels',
          'title' => 'التحصيل العلمي',
          'route' => 'EducationalLevels',
          'icon' => 'mdi mdi-school-outline',
          'active_routes' => 
          array (
            0 => 'EducationalLevels',
          ),
        ),
        1 => 
        array (
          'type' => 'item',
          'permission' => 'Departments',
          'title' => 'الاقسام',
          'route' => 'Departments',
          'icon' => 'mdi mdi-office-building',
          'active_routes' => 
          array (
            0 => 'Departments',
          ),
        ),
        2 => 
        array (
          'type' => 'item',
          'permission' => 'JobTitles',
          'title' => 'العنوان الوظيفي',
          'route' => 'JobTitles',
          'icon' => 'mdi mdi-card-account-details-outline',
          'active_routes' => 
          array (
            0 => 'JobTitles',
          ),
        ),
        3 => 
        array (
          'type' => 'item',
          'permission' => 'JobGrades',
          'title' => 'الدرجة الوظيفية',
          'route' => 'JobGrades',
          'icon' => 'mdi mdi-numeric-10-box-multiple-outline',
          'active_routes' => 
          array (
            0 => 'JobGrades',
          ),
        ),
        4 => 
        array (
          'type' => 'item',
          'permission' => 'TrainingInstitutions',
          'title' => 'مؤسسة المدرب',
          'route' => 'TrainingInstitutions',
          'icon' => 'mdi mdi-office-building-outline',
          'active_routes' => 
          array (
            0 => 'TrainingInstitutions',
          ),
        ),
        5 => 
        array (
          'type' => 'item',
          'permission' => 'TrainingDomains',
          'title' => 'المجال التدريبي',
          'route' => 'TrainingDomains',
          'icon' => 'mdi mdi-hub-outline',
          'active_routes' => 
          array (
            0 => 'TrainingDomains',
          ),
        ),
        6 => 
        array (
          'type' => 'item',
          'permission' => 'Venues',
          'title' => 'مكان انعقاد الدورة',
          'route' => 'Venues',
          'icon' => 'mdi mdi-select-place',
          'active_routes' => 
          array (
            0 => 'Venues',
          ),
        ),
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