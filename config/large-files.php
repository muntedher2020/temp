<?php

return [
    /*
    |--------------------------------------------------------------------------
    | إعدادات الملفات الكبيرة
    |--------------------------------------------------------------------------
    |
    | هذه الإعدادات تحكم في كيفية التعامل مع الملفات الكبيرة في النظام
    |
    */

    // حد الملف الكبير بالميجابايت
    'large_file_threshold_mb' => env('LARGE_FILE_THRESHOLD_MB', 2),

    // الحد الأقصى لحجم الملف المسموح بالميجابايت
    'max_file_size_mb' => env('MAX_FILE_SIZE_MB', 50),

    // إعدادات PHP للملفات الكبيرة
    'php_settings' => [
        'max_execution_time' => env('PHP_MAX_EXECUTION_TIME', 1800), // 30 دقيقة
        'memory_limit' => env('PHP_MEMORY_LIMIT', '512M'),
        'memory_limit_large' => env('PHP_MEMORY_LIMIT_LARGE', '1024M'), // للملفات الكبيرة جداً
        'max_input_time' => env('PHP_MAX_INPUT_TIME', 600), // 10 دقائق
        'max_input_vars' => env('PHP_MAX_INPUT_VARS', 5000),
        'upload_max_filesize' => env('PHP_UPLOAD_MAX_FILESIZE', '50M'),
        'post_max_size' => env('PHP_POST_MAX_SIZE', '100M'),
    ],

    // إعدادات المعالجة
    'processing' => [
        'chunk_size' => env('PROCESSING_CHUNK_SIZE', 1000), // عدد السجلات في الدفعة الواحدة
        'enable_chunking' => env('ENABLE_CHUNKING', true),
        'enable_progress_tracking' => env('ENABLE_PROGRESS_TRACKING', true),
        'cleanup_temp_files' => env('CLEANUP_TEMP_FILES', true),
    ],

    // رسائل التحذير وتقديرات الوقت
    'warnings' => [
        'show_large_file_warning' => env('SHOW_LARGE_FILE_WARNING', true),
        'warning_threshold_mb' => env('WARNING_THRESHOLD_MB', 5),
        'time_estimates' => [
            'small' => '30 ثانية - 1 دقيقة',    // أقل من 1 MB
            'medium' => '1-3 دقائق',             // 1-5 MB
            'large' => '3-8 دقائق',              // 5-10 MB
            'very_large' => '8-15 دقيقة',        // 10-20 MB
            'huge' => '15-30 دقيقة (يُنصح بالتقسيم)', // أكبر من 20 MB
        ],
    ],

    // إعدادات Logging
    'logging' => [
        'log_large_file_operations' => env('LOG_LARGE_FILE_OPERATIONS', true),
        'log_performance_metrics' => env('LOG_PERFORMANCE_METRICS', true),
        'log_memory_usage' => env('LOG_MEMORY_USAGE', true),
        'detailed_error_logging' => env('DETAILED_ERROR_LOGGING', true),
    ],

    // إعدادات الأمان
    'security' => [
        'allowed_extensions' => ['xlsx', 'csv', 'xls'],
        'allowed_mime_types' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
            'application/vnd.ms-excel', // xls
            'text/csv', // csv
            'application/csv',
            'text/plain'
        ],
        'scan_for_malware' => env('SCAN_FOR_MALWARE', false),
        'validate_file_structure' => env('VALIDATE_FILE_STRUCTURE', true),
    ],

    // إعدادات التحسين
    'optimization' => [
        'enable_gc_collection' => env('ENABLE_GC_COLLECTION', true),
        'disable_output_buffering' => env('DISABLE_OUTPUT_BUFFERING', true),
        'optimize_queries' => env('OPTIMIZE_QUERIES', true),
        'use_bulk_insert' => env('USE_BULK_INSERT', true),
        'batch_size' => env('BATCH_SIZE', 500),
    ],

    // إعدادات middleware
    'middleware' => [
        'enabled' => env('LARGE_FILE_MIDDLEWARE_ENABLED', true),
        'auto_detect' => env('LARGE_FILE_AUTO_DETECT', true),
        'apply_to_routes' => [
            'livewire.*',
            'data-management.*',
            'import.*',
            'export.*',
        ],
    ],

    // إعدادات التجربة المستخدم
    'user_experience' => [
        'show_progress_bar' => env('SHOW_PROGRESS_BAR', true),
        'show_time_estimates' => env('SHOW_TIME_ESTIMATES', true),
        'show_file_splitting_guide' => env('SHOW_FILE_SPLITTING_GUIDE', true),
        'enable_retry_mechanism' => env('ENABLE_RETRY_MECHANISM', true),
        'auto_split_suggestions' => env('AUTO_SPLIT_SUGGESTIONS', true),
    ],
];
