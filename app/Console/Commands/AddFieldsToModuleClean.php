<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\System\ModuleField;

class AddFieldsToModuleClean extends Command
{
    protected $signature = 'hmvc:add-fields-clean {module : اسم الوحدة} {--fields-file= : مسار ملف JSON يحتوي على بيانات الحقول} {--advanced-features= : ميزات متقدمة بصيغة JSON} {--regenerate : إعادة إنشاء الوحدة كاملة بدلاً من التعديل} {--force-from-model : إجبار قراءة الحقول من Model بدلاً من الملف المحفوظ}';
    protected $description = 'إضافة حقول جديدة للوحدة - مبني على مولد الوحدات';

    private $migrationName = null;
    private $sessionId;
    private $logChannel;

    public function __construct()
    {
        parent::__construct();
        $this->sessionId = 'hmvc_' . Carbon::now()->format('Y_m_d_H_i_s') . '_' . uniqid();
        $this->initializeLogging();
    }

    /**
     * تهيئة نظام التسجيل
     */
    private function initializeLogging()
    {
        $logPath = storage_path('logs/hmvc-operations');
        if (!File::exists($logPath)) {
            File::makeDirectory($logPath, 0755, true);
        }

        $this->logChannel = 'hmvc-operations';

        // إعداد قناة لوج مخصصة
        config(['logging.channels.hmvc-operations' => [
            'driver' => 'single',
            'path' => $logPath . '/hmvc-operations-' . Carbon::now()->format('Y-m-d') . '.log',
            'level' => 'debug',
            'replace_placeholders' => true,
        ]]);
    }

    /**
     * تسجيل عملية في اللوج
     */
    private function logOperation($level, $message, $context = [])
    {
        $context['session_id'] = $this->sessionId;
        $context['timestamp'] = Carbon::now()->toISOString();

        Log::channel($this->logChannel)->$level($message, $context);

        // عرض في الكونسول أيضاً
        $this->info("📝 LOG: {$message}");
    }

    public function handle()
    {
        $moduleName = $this->argument('module');
        $fieldsFile = $this->option('fields-file');
        $advancedFeatures = $this->option('advanced-features');
        $regenerate = $this->option('regenerate');

        $this->logOperation('info', 'بدء عملية معالجة الوحدة', [
            'module_name' => $moduleName,
            'fields_file' => $fieldsFile,
            'advanced_features' => $advancedFeatures,
            'regenerate_mode' => $regenerate,
            'command_arguments' => $this->arguments(),
            'command_options' => $this->options()
        ]);

        if ($regenerate) {
            $this->logOperation('info', 'تم اختيار وضع إعادة الإنشاء الكامل');
            return $this->handleRegenerateModule($moduleName, $fieldsFile, $advancedFeatures);
        }

        $this->info("🚀 بدء إضافة الحقول للوحدة: {$moduleName}");

        try {
            // تحديد مصدر البيانات
            $data = null;
            $fieldsPath = null;

            if ($fieldsFile && File::exists($fieldsFile)) {
                // استخدام الملف المحدد
                $fileContent = File::get($fieldsFile);

                $data = json_decode($fileContent, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error("❌ خطأ في تحليل JSON: " . json_last_error_msg());
                    return 1;
                }

                $fieldsPath = $fieldsFile;
                $this->info("📄 قراءة البيانات من: {$fieldsFile}");
            } else {
                // استخدام الملف الافتراضي
                $fieldsPath = storage_path("app/pending_fields_{$moduleName}.json");

                if (!File::exists($fieldsPath)) {
                    $this->error("❌ لا توجد حقول معلقة للوحدة: {$moduleName}");
                    return 1;
                }

                $fileContent = File::get($fieldsPath);
                $data = json_decode($fileContent, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error("❌ خطأ في تحليل JSON: " . json_last_error_msg());
                }
                $this->info("📄 قراءة البيانات من الملف الافتراضي");
            }

            // دمج الميزات المتقدمة إن وجدت
            if ($advancedFeatures) {
                $advancedData = json_decode($advancedFeatures, true);
                if ($advancedData) {
                    $data['advanced_features'] = array_merge($data['advanced_features'] ?? [], $advancedData);
                    $this->info("🔧 تم دمج الميزات المتقدمة");
                }
            }

            $fields = $data['fields'] ?? [];
            $advancedFeatures = $data['advanced_features'] ?? [];

            if (empty($fields)) {
                $this->warn("⚠️ لا توجد حقول للإضافة");
                return 1;
            }

            $this->info("📝 سيتم إضافة " . count($fields) . " حقل");

            // عملية الإضافة
            $this->updateModel($moduleName, $fields);
            $this->createMigration($moduleName, $fields);
            $this->updateLivewireComponent($moduleName, $fields);

            $this->updateViews($moduleName, $fields, $advancedFeatures);
            $this->runMigration();

            // تحديث Blade Views للحقول الجديدة
            $this->updateBladeViewsLikeGenerator($moduleName, $fields);

            // حفظ الحقول الجديدة في قاعدة البيانات
            try {
                // إنشاء اسم الجدول والحصول على الاسم العربي
                $tableName = Str::snake(Str::plural($moduleName));
                $moduleArabicName = $this->getModuleArabicNameFromConfig($moduleName);

                ModuleField::saveFieldsFromGenerator($moduleName, $fields, 'admin', $tableName, $moduleArabicName);

                // تحديث معلومات الوحدة الأساسية لجميع الحقول الموجودة
                ModuleField::updateModuleInfo($moduleName, $tableName, $moduleArabicName);

                $this->info("💾 تم حفظ الحقول الجديدة في قاعدة البيانات");
            } catch (\Exception $e) {
                $this->warn("⚠️ فشل في حفظ الحقول في قاعدة البيانات: " . $e->getMessage());
            }

            // معالجة الحقول المحسوبة بعد إضافة الحقول في modals
            $this->processCalculatedFields($moduleName, $fields);

            // حذف ملف البيانات المعلقة (إذا كان الملف الافتراضي)
            if (!$fieldsFile && File::exists($fieldsPath)) {
                File::delete($fieldsPath);
            }

            // حذف الملف المؤقت إن وجد
            if ($fieldsFile && strpos($fieldsFile, 'tmp_fields_') !== false && File::exists($fieldsFile)) {
                File::delete($fieldsFile);
            }

            // 4. تحديث Blade Views للحقول الجديدة
        $this->updateBladeViewsLikeGenerator($moduleName, $fields);

        // 5. إصلاح الحقول المحسوبة تلقائياً
        $this->fixCalculatedFieldsConfiguration($moduleName);

        $this->info("✅ تم الانتهاء من إضافة " . count($fields) . " حقل بنجاح للوحدة {$moduleName}");
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ خطأ في إضافة الحقول: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * إعادة إنشاء الوحدة كاملة مع الحقول الجديدة
     */
    private function handleRegenerateModule($moduleName, $fieldsFile, $advancedFeatures)
    {
        $this->info("🔄 بدء إعادة إنشاء الوحدة: {$moduleName}");
        $this->logOperation('info', 'بدء عملية إعادة الإنشاء الكاملة للوحدة', [
            'module_name' => $moduleName,
            'fields_file' => $fieldsFile
        ]);

        try {
            // 1. جمع الحقول الحالية من Model
            $this->logOperation('debug', 'بدء جمع الحقول الحالية من Model');
            $existingFields = $this->getExistingFieldsFromModel($moduleName);
            $this->info("📋 تم العثور على " . count($existingFields) . " حقل موجود");
            $this->logOperation('info', 'تم العثور على الحقول الحالية', [
                'existing_fields_count' => count($existingFields),
                'existing_fields' => $existingFields
            ]);

            // 2. قراءة الحقول الجديدة
            $this->logOperation('debug', 'بدء قراءة الحقول الجديدة من الملف', ['fields_file' => $fieldsFile]);
            $newFields = $this->getNewFieldsFromFile($fieldsFile, $moduleName);
            $this->info("📝 سيتم إضافة " . count($newFields) . " حقل جديد");
            $this->logOperation('info', 'تم قراءة الحقول الجديدة', [
                'new_fields_count' => count($newFields),
                'new_fields' => $newFields
            ]);

            // 3. دمج الحقول
            $this->logOperation('debug', 'بدء دمج الحقول القديمة والجديدة');
            $allFields = $this->mergeFields($existingFields, $newFields);
            $this->info("🔗 إجمالي الحقول: " . count($allFields));
            $this->logOperation('info', 'تم دمج الحقول بنجاح', [
                'total_fields_count' => count($allFields),
                'all_fields' => $allFields
            ]);

            // 4. جمع معلومات الوحدة
            $this->logOperation('debug', 'بدء جمع معلومات الوحدة');
            $moduleInfo = $this->getModuleInfo($moduleName);
            $this->logOperation('info', 'تم جمع معلومات الوحدة', $moduleInfo);

            // 5. حذف الملفات القديمة (Livewire + Views فقط، احتفظ بـ Model)
            $this->logOperation('debug', 'بدء حذف الملفات القديمة');
            $this->backupAndDeleteOldFiles($moduleName);

            // 5.5. حفظ تكوين الحقول الحالي لاستخدامه لاحقاً
            $this->logOperation('debug', 'بدء حفظ تكوين الحقول');
            $this->saveModuleFieldsConfiguration($moduleName, $allFields);

            // 5.6. حفظ نسخة احتياطية من التكوين السابق للمقارنة
            $this->saveFieldsBackup($moduleName, $existingFields, $allFields);

            // 6. تنظيف migration files المكررة قبل إعادة الإنشاء
            $this->logOperation('debug', 'بدء تنظيف migration files القديمة');
            $this->cleanupOldMigrations($moduleName);

            // 7. إعادة إنشاء الوحدة باستخدام مولد الوحدات
            $this->logOperation('debug', 'بدء إعادة إنشاء الوحدة باستخدام مولد الوحدات');
            $this->regenerateModuleWithFields($moduleName, $allFields, $moduleInfo, $advancedFeatures);

            // 7. تطبيق تصحيحات ما بعد الإنشاء للحقول المخصصة
            $this->logOperation('debug', 'بدء تطبيق تصحيحات ما بعد الإنشاء');
            $this->applyPostGenerationFixes($moduleName, $allFields);

            // 8. إعادة إنشاء Routes للـ PDF والطباعة المباشرة
            $this->logOperation('debug', 'بدء إعادة إنشاء routes الـ PDF والطباعة');
            $this->addPdfRoutesToWebPhp($moduleName);

            $this->info("✅ تم إعادة إنشاء الوحدة {$moduleName} بنجاح مع " . count($allFields) . " حقل");
            $this->logOperation('info', 'تم إكمال عملية إعادة الإنشاء بنجاح', [
                'module_name' => $moduleName,
                'total_fields' => count($allFields),
                'success' => true
            ]);
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ خطأ في إعادة إنشاء الوحدة: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            $this->logOperation('error', 'فشل في عملية إعادة الإنشاء', [
                'module_name' => $moduleName,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'success' => false
            ]);
            return 1;
        }
    }

    /**
     * جمع الحقول الحالية من Model و Migration والتكوين المحفوظ
     */
    /**
     * قراءة الحقول من Migration الحالي فقط
     */
    private function getFieldsFromCurrentMigration($moduleName)
    {
        $this->logOperation('debug', 'بدء قراءة الحقول من Migration الحالي', ['module_name' => $moduleName]);

        // البحث عن أحدث Migration للوحدة
        $migrationFiles = collect(File::files(database_path('migrations')))
            ->filter(function ($file) use ($moduleName) {
                $filename = $file->getFilename();
                return str_contains(strtolower($filename), 'create_' . strtolower(Str::plural($moduleName)) . '_table');
            })
            ->sortByDesc(function ($file) {
                return $file->getFilename();
            });

        if ($migrationFiles->isEmpty()) {
            $this->logOperation('warning', 'لم يتم العثور على Migration للوحدة', ['module_name' => $moduleName]);
            return [];
        }

        $latestMigration = $migrationFiles->first();
        $migrationPath = $latestMigration->getPathname();

        $this->logOperation('debug', 'تم العثور على Migration', [
            'migration_file' => $migrationPath,
            'module_name' => $moduleName
        ]);

        $migrationContent = File::get($migrationPath);
        $fields = [];

        // استخراج محتويات دالة create من Migration
        if (preg_match('/Schema::create\([\'"]' . strtolower(Str::plural($moduleName)) . '[\'"],\s*function\s*\([^)]*\)\s*{(.*?)}\s*\);/s', $migrationContent, $matches)) {
            $schemaContent = $matches[1];

            // استخراج تعريفات الحقول باستخدام Pattern محسن
            preg_match_all('/\$table->(\w+)\([\'"](\w+)[\'"].*?\)->.*?comment\([\'"](.+?)[\'"].*?\);/m', $schemaContent, $fieldMatches, PREG_SET_ORDER);

            foreach ($fieldMatches as $match) {
                $fieldType = $match[1];
                $fieldName = $match[2];
                $fieldComment = isset($match[3]) ? $match[3] : '';

                // تجاهل الحقول الأساسية
                if (in_array($fieldName, ['id', 'user_id', 'created_at', 'updated_at', 'timestamps']) ||
                    in_array($fieldType, ['id', 'timestamps', 'foreign'])) {
                    continue;
                }

                // تحويل نوع قاعدة البيانات إلى نوع حقل
                $mappedType = $this->mapDatabaseTypeToFieldType($fieldType);
                $arabicName = $fieldComment ?: $this->generateArabicName($fieldName);

                $fields[] = [
                    'name' => $fieldName,
                    'ar_name' => $arabicName,
                    'comment' => $arabicName,
                    'type' => $mappedType,
                    'required' => $this->determineIfFieldRequired($fieldName, $mappedType),
                    'searchable' => true,
                    'max' => $this->determineFieldMaxLength($mappedType, $fieldName),
                    'unique' => false,
                    // إضافة خصائص العرض الافتراضية
                    'show_in_table' => true,
                    'show_in_search' => true,
                    'show_in_forms' => true,
                    'arabic_only' => false,
                    'numeric_only' => $mappedType === 'number' || $mappedType === 'integer',
                ];
            }
        }

        $this->logOperation('info', 'تم استخراج الحقول من Migration', [
            'fields_count' => count($fields),
            'fields' => $fields
        ]);

        return $fields;
    }

    private function getExistingFieldsFromModel($moduleName)
    {
        $this->logOperation('debug', 'بدء البحث عن الحقول الموجودة في النموذج', ['module_name' => $moduleName]);

        // أولاً: محاولة قراءة من قاعدة البيانات (الطريقة الجديدة)
        $forceFromModel = $this->option('force-from-model') ?? false;

        if (!$forceFromModel) {
            try {
                $fieldsFromDb = ModuleField::getFieldsForGenerator($moduleName);

                if (!empty($fieldsFromDb)) {
                    $this->logOperation('info', 'تم تحميل الحقول من قاعدة البيانات', [
                        'fields_count' => count($fieldsFromDb)
                    ]);

                    $this->info("✅ تم تحميل " . count($fieldsFromDb) . " حقل من قاعدة البيانات");

                    // إصلاح تلقائي للحقول المحسوبة المعطوبة
                    $fieldsFromDb = $this->autoFixCalculatedFields($moduleName, $fieldsFromDb);

                    return $fieldsFromDb;
                }
            } catch (\Exception $e) {
                $this->logOperation('warning', 'فشل في تحميل الحقول من قاعدة البيانات', [
                    'error' => $e->getMessage()
                ]);
                $this->warn("⚠️ فشل في تحميل الحقول من قاعدة البيانات: " . $e->getMessage());
            }

            // ثانياً: محاولة قراءة التكوين المحفوظ (JSON)
            $savedConfig = $this->loadModuleFieldsConfiguration($moduleName);
            if (!empty($savedConfig)) {
                $this->info("📋 تم استرداد تكوين الحقول من الملف المحفوظ");
                $this->logOperation('info', 'تم استرداد تكوين الحقول من الملف المحفوظ', [
                    'config_fields_count' => count($savedConfig),
                    'saved_config' => $savedConfig
                ]);
                return $savedConfig;
            }
        } else {
            $this->info("🔍 إجبار قراءة الحقول من Model مباشرة (تجاهل الملف المحفوظ)");
            $this->logOperation('info', 'تم إجبار قراءة الحقول من Model مباشرة');
        }

        $this->logOperation('debug', 'لم يتم العثور على تكوين محفوظ، سيتم استخراج الحقول من Model');

        // إذا لم يوجد تكوين محفوظ، استخدم الطريقة القديمة
        $modelPaths = [
            base_path("app/Models/{$moduleName}.php"),
            base_path("app/Models/{$moduleName}/{$moduleName}.php"),
            base_path("app/Models/" . Str::plural($moduleName) . "/" . Str::plural($moduleName) . ".php"),
        ];

        $this->logOperation('debug', 'البحث عن ملف النموذج', ['search_paths' => $modelPaths]);

        $modelPath = null;
        foreach ($modelPaths as $path) {
            if (File::exists($path)) {
                $modelPath = $path;
                $this->logOperation('debug', 'تم العثور على ملف النموذج', ['model_path' => $modelPath]);
                break;
            }
        }

        if (!$modelPath) {
            $this->warn("⚠️ Model غير موجود للوحدة: {$moduleName}");
            return [];
        }

        // بدلاً من قراءة fillable، اقرأ من Migration الحالي مباشرة
        $fields = [];

        // الطريقة الجديدة: قراءة من Migration الحالي فقط
        $migrationFields = $this->getFieldsFromCurrentMigration($moduleName);

        if (!empty($migrationFields)) {
            $this->info("📊 تم قراءة الحقول من Migration الحالي");
            $this->logOperation('info', 'تم قراءة الحقول من Migration الحالي', [
                'migration_fields_count' => count($migrationFields),
                'migration_fields' => $migrationFields
            ]);
            return $migrationFields;
        }

        $this->warn("⚠️ لم يتم العثور على Migration صالح للوحدة: {$moduleName}");

        // Fallback: استخدام طريقة fillable القديمة كخيار أخير
        $modelContent = File::get($modelPath);

        if (preg_match('/protected\s+\$fillable\s*=\s*\[(.*?)\];/s', $modelContent, $matches)) {
            $fillableContent = $matches[1];

            // استخراج أسماء الحقول
            preg_match_all("/['\"]([^'\"]+)['\"]/", $fillableContent, $fieldMatches);

            // جمع معلومات الحقول من Migration الموجود
            $migrationFieldsInfo = $this->getFieldsInfoFromMigration($moduleName);

            foreach ($fieldMatches[1] as $fieldName) {
                // تجاهل الحقول الأساسية
                if (in_array($fieldName, ['user_id', 'id', 'created_at', 'updated_at'])) {
                    continue;
                }

                // استخدام معلومات من Migration إذا كانت متوفرة
                if (isset($migrationFieldsInfo[$fieldName])) {
                    $fieldInfo = $migrationFieldsInfo[$fieldName];
                    $fieldType = $this->mapDatabaseTypeToFieldType($fieldInfo['type']);
                    $comment = $fieldInfo['comment'] ?: $this->generateArabicName($fieldName);
                } else {
                    // في حالة عدم وجود معلومات في Migration، نستخدم التخمين
                    $fieldType = $this->guessFieldTypeFromDatabase($moduleName, $fieldName);
                    $comment = $this->generateArabicName($fieldName);
                }

                // تحديد إذا كان الحقل مطلوباً
                $isRequired = $this->determineIfFieldRequired($fieldName, $fieldType);

                $fields[] = [
                    'name' => $fieldName,
                    'ar_name' => $comment,
                    'comment' => $comment,
                    'type' => $fieldType,
                    'required' => $isRequired,
                    'searchable' => true,
                    'max' => $this->determineFieldMaxLength($fieldType, $fieldName),
                    'unique' => false,
                    // إضافة خصائص العرض الافتراضية
                    'show_in_table' => true,
                    'show_in_search' => true,
                    'show_in_forms' => true,
                    'arabic_only' => false,
                    'numeric_only' => $fieldType === 'number' || $fieldType === 'integer',
                ];
            }
        }

        return $fields;
    }

    /**
     * تحديد إذا كان الحقل مطلوباً
     */
    private function determineIfFieldRequired($fieldName, $fieldType)
    {
        // الحقول التي عادة ما تكون مطلوبة
        $usuallyRequired = [
            'name', 'title', 'email', 'phone', 'section_name',
            'fullname', 'code', 'status'
        ];

        // الحقول التي عادة ما تكون اختيارية
        $usuallyOptional = [
            'description', 'notes', 'vacation_add', 'address',
            'comment', 'details'
        ];

        if (in_array($fieldName, $usuallyRequired)) {
            return true;
        }

        if (in_array($fieldName, $usuallyOptional)) {
            return false;
        }

        // افتراضياً، نجعل معظم الحقول مطلوبة
        return true;
    }

    /**
     * تحديد الطول الأقصى للحقل
     */
    private function determineFieldMaxLength($fieldType, $fieldName)
    {
        if ($fieldType === 'number') {
            return null;
        }

        // أطوال مخصصة للحقول
        $customLengths = [
            'email' => 100,
            'phone' => 20,
            'code' => 10,
            'name' => 100,
            'title' => 200,
            'section_name' => 100
        ];

        return $customLengths[$fieldName] ?? 255;
    }    /**
     * تخمين نوع الحقل من قاعدة البيانات
     */
    private function guessFieldTypeFromDatabase($moduleName, $fieldName)
    {
        try {
            $tableName = Str::snake(Str::plural($moduleName));

            // محاولة الحصول على معلومات العمود من قاعدة البيانات
            $columns = DB::select("SHOW COLUMNS FROM {$tableName}");

            foreach ($columns as $column) {
                if ($column->Field === $fieldName) {
                    $type = strtolower($column->Type);

                    if (strpos($type, 'varchar') !== false || strpos($type, 'text') !== false) {
                        return 'text';
                    }
                    if (strpos($type, 'int') !== false) {
                        return 'number';
                    }
                    if (strpos($type, 'date') !== false) {
                        return 'date';
                    }
                    if (strpos($type, 'boolean') !== false || strpos($type, 'tinyint(1)') !== false) {
                        return 'checkbox';
                    }
                }
            }
        } catch (\Exception $e) {
            // في حالة فشل الاستعلام، نستخدم القيمة الافتراضية
        }

        return 'text'; // القيمة الافتراضية
    }

    /**
     * توليد اسم عربي للحقل
     */
    /**
     * البحث عن الاسم العربي للوحدة من dynamic-menu.php
     */
    private function getModuleArabicNameFromConfig($moduleName)
    {
        try {
            $dynamicMenuPath = config_path('dynamic-menu.php');

            if (!File::exists($dynamicMenuPath)) {
                return $this->generateArabicName($moduleName);
            }

            $menuConfig = include $dynamicMenuPath;

            if (!isset($menuConfig['menu_items']) || !is_array($menuConfig['menu_items'])) {
                return $this->generateArabicName($moduleName);
            }

            // البحث في الوحدات المباشرة
            foreach ($menuConfig['menu_items'] as $group) {
                if (isset($group['children']) && is_array($group['children'])) {
                    foreach ($group['children'] as $item) {
                        if (isset($item['route']) && isset($item['title'])) {
                            // مقارنة مع حالات مختلفة
                            if (strtolower($item['route']) === strtolower($moduleName) ||
                                $item['route'] === $moduleName ||
                                $item['route'] === ucfirst(strtolower($moduleName))) {

                                $this->logOperation('debug', 'تم العثور على الاسم العربي في dynamic-menu', [
                                    'module' => $moduleName,
                                    'matched_route' => $item['route'],
                                    'arabic_name' => $item['title']
                                ]);
                                return $item['title'];
                            }
                        }
                    }
                }

                // البحث في المستوى الأول أيضاً
                if (isset($group['route']) && isset($group['title'])) {
                    if (strtolower($group['route']) === strtolower($moduleName) ||
                        $group['route'] === $moduleName ||
                        $group['route'] === ucfirst(strtolower($moduleName))) {

                        $this->logOperation('debug', 'تم العثور على الاسم العربي في dynamic-menu (مستوى أول)', [
                            'module' => $moduleName,
                            'matched_route' => $group['route'],
                            'arabic_name' => $group['title']
                        ]);
                        return $group['title'];
                    }
                }
            }

            $this->logOperation('warning', 'لم يتم العثور على الاسم العربي في dynamic-menu، استخدام اسم افتراضي', [
                'module' => $moduleName
            ]);

            return $this->generateArabicName($moduleName);

        } catch (\Exception $e) {
            $this->logOperation('error', 'خطأ في البحث عن الاسم العربي', [
                'module' => $moduleName,
                'error' => $e->getMessage()
            ]);

            return $this->generateArabicName($moduleName);
        }
    }

    private function generateArabicName($fieldName)
    {
        // خريطة الأسماء العربية للحقول فقط (ليس الوحدات)
        $fieldNames = [
            'name' => 'الاسم',
            'full_name' => 'الاسم الكامل',
            'email' => 'البريد الإلكتروني',
            'phone' => 'الهاتف',
            'address' => 'العنوان',
            'age' => 'العمر',
            'date' => 'التاريخ',
            'time' => 'الوقت',
            'datetime' => 'التاريخ والوقت',
            'month_year' => 'الشهر والسنة',
            'now' => 'الآن',
            'salary' => 'الراتب',
            'position' => 'المنصب',
            'department' => 'القسم',
            'section_name' => 'اسم القسم',
            'vacation_add' => 'نوع الخدمة',
            'vacation_type' => 'بيان الإجازة',
            'kok' => 'سيليلب',
            'fullname' => 'الاسم الكامل',
            'description' => 'الوصف',
            'notes' => 'الملاحظات',
            'status' => 'الحالة',
            'type' => 'النوع',
            'code' => 'الكود',
            'title' => 'العنوان',
            'content' => 'المحتوى',
            'skills' => 'المهارات',
            'experience_years' => 'سنوات الخبرة'
        ];

        // البحث في أسماء الحقول
        $lowerFieldName = strtolower($fieldName);
        if (isset($fieldNames[$lowerFieldName])) {
            return $fieldNames[$lowerFieldName];
        }

        // إذا لم يوجد، إرجاع الاسم كما هو مع تكبير الحرف الأول
        return ucfirst($fieldName);
    }

    /**
     * قراءة الحقول الجديدة من الملف
     */
    private function getNewFieldsFromFile($fieldsFile, $moduleName)
    {
        $this->logOperation('debug', 'بدء قراءة الحقول الجديدة من الملف', [
            'fields_file' => $fieldsFile,
            'module_name' => $moduleName
        ]);

        if ($fieldsFile && File::exists($fieldsFile)) {
            try {
                $data = json_decode(File::get($fieldsFile), true);

                // التحقق من صيغة JSON
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->logOperation('error', 'خطأ في صيغة ملف JSON', [
                        'json_error' => json_last_error_msg(),
                        'file' => $fieldsFile
                    ]);
                    return [];
                }

                // دعم كلا الصيغتين: array مباشرة أو مع مفتاح fields
                $fields = [];
                if (isset($data['fields']) && is_array($data['fields'])) {
                    $fields = $data['fields'];
                } elseif (is_array($data)) {
                    $fields = $data;
                }

                $this->logOperation('info', 'تم قراءة الحقول الجديدة من الملف', [
                    'fields_count' => count($fields),
                    'fields_data' => $fields,
                    'file_format' => isset($data['fields']) ? 'object_with_fields_key' : 'direct_array'
                ]);

                return $fields;

            } catch (\Exception $e) {
                $this->logOperation('error', 'خطأ في قراءة ملف الحقول', [
                    'error' => $e->getMessage(),
                    'file' => $fieldsFile
                ]);
                return [];
            }
        }

        // استخدام الملف الافتراضي
        $fieldsPath = storage_path("app/pending_fields_{$moduleName}.json");
        $this->logOperation('debug', 'البحث عن ملف الحقول الافتراضي', [
            'default_fields_path' => $fieldsPath
        ]);

        if (File::exists($fieldsPath)) {
            try {
                $data = json_decode(File::get($fieldsPath), true);
                $fields = $data['fields'] ?? $data ?? [];

                $this->logOperation('info', 'تم قراءة الحقول من الملف الافتراضي', [
                    'fields_count' => count($fields),
                    'fields_data' => $fields
                ]);

                return $fields;
            } catch (\Exception $e) {
                $this->logOperation('error', 'خطأ في قراءة الملف الافتراضي', [
                    'error' => $e->getMessage(),
                    'file' => $fieldsPath
                ]);
            }
        } else {
            $this->logOperation('warning', 'لم يتم العثور على أي ملف حقول', [
                'searched_files' => [$fieldsFile, $fieldsPath]
            ]);
        }

        return [];
    }

    /**
     * دمج الحقول الحالية مع الحقول الجديدة
     */
    private function mergeFields($existingFields, $newFields)
    {
        $this->logOperation('debug', 'بدء عملية دمج الحقول', [
            'existing_count' => count($existingFields),
            'new_count' => count($newFields)
        ]);

        $allFields = $existingFields;
        $fieldsAnalysis = [
            'preserved_fields' => [],
            'new_fields' => [],
            'modified_fields' => [],
            'total_before' => count($existingFields),
            'total_after' => 0
        ];

        // تحليل الحقول الموجودة
        foreach ($existingFields as $existingField) {
            $fieldsAnalysis['preserved_fields'][] = [
                'name' => $existingField['name'],
                'type' => $existingField['type'],
                'required' => $existingField['required'] ?? false,
                'ar_name' => $existingField['ar_name'] ?? $existingField['comment'] ?? '',
                'has_options' => isset($existingField['select_options']) || isset($existingField['options']),
                'has_relation' => isset($existingField['relation_table']) || isset($existingField['related_table'])
            ];
        }

        // إضافة الحقول الجديدة (تجنب التكرار)
        foreach ($newFields as $newField) {
            $exists = false;
            $existingFieldData = null;

            foreach ($existingFields as $existingField) {
                if ($existingField['name'] === $newField['name']) {
                    $exists = true;
                    $existingFieldData = $existingField;
                    break;
                }
            }

            if (!$exists) {
                // حقل جديد تماماً
                $allFields[] = $newField;
                $fieldsAnalysis['new_fields'][] = [
                    'name' => $newField['name'],
                    'type' => $newField['type'],
                    'required' => $newField['required'] ?? false,
                    'ar_name' => $newField['ar_name'] ?? $newField['arabic_name'] ?? '',
                    'has_options' => isset($newField['select_options']) || isset($newField['options']),
                    'has_relation' => isset($newField['relation_table']) || isset($newField['related_table'])
                ];

                $this->logOperation('info', 'حقل جديد تمت إضافته', [
                    'field_name' => $newField['name'],
                    'field_type' => $newField['type'],
                    'field_required' => $newField['required'] ?? false,
                    'field_details' => $newField
                ]);
            } else {
                // حقل موجود - فحص التغييرات
                $changes = $this->compareFields($existingFieldData, $newField);
                if (!empty($changes)) {
                    $fieldsAnalysis['modified_fields'][] = [
                        'name' => $newField['name'],
                        'changes' => $changes
                    ];

                    $this->logOperation('warning', 'تم اكتشاف تغييرات في حقل موجود', [
                        'field_name' => $newField['name'],
                        'changes_detected' => $changes,
                        'old_field' => $existingFieldData,
                        'new_field' => $newField
                    ]);
                }
            }
        }

        $fieldsAnalysis['total_after'] = count($allFields);

        // تسجيل ملخص العملية
        $this->logOperation('info', 'ملخص عملية دمج الحقول', [
            'fields_analysis' => $fieldsAnalysis,
            'preserved_count' => count($fieldsAnalysis['preserved_fields']),
            'new_count' => count($fieldsAnalysis['new_fields']),
            'modified_count' => count($fieldsAnalysis['modified_fields']),
            'total_before' => $fieldsAnalysis['total_before'],
            'total_after' => $fieldsAnalysis['total_after']
        ]);

        return $allFields;
    }

    /**
     * مقارنة حقلين وإرجاع التغييرات
     */
    private function compareFields($existingField, $newField)
    {
        $changes = [];

        // الخصائص المهمة للمقارنة
        $keyProperties = [
            'type' => 'نوع الحقل',
            'required' => 'إجباري',
            'ar_name' => 'الاسم العربي',
            'arabic_name' => 'الاسم العربي',
            'comment' => 'التعليق',
            'max' => 'الحد الأقصى',
            'unique' => 'فريد',
            'searchable' => 'قابل للبحث'
        ];

        foreach ($keyProperties as $property => $arabicName) {
            $existingValue = $existingField[$property] ?? null;
            $newValue = $newField[$property] ?? null;

            // معالجة خاصة للاسم العربي
            if ($property === 'ar_name' && !isset($existingField['ar_name']) && isset($existingField['arabic_name'])) {
                $existingValue = $existingField['arabic_name'];
            }
            if ($property === 'arabic_name' && !isset($newField['arabic_name']) && isset($newField['ar_name'])) {
                $newValue = $newField['ar_name'];
            }

            if ($existingValue !== $newValue) {
                $changes[] = [
                    'property' => $property,
                    'property_ar' => $arabicName,
                    'old_value' => $existingValue,
                    'new_value' => $newValue
                ];
            }
        }

        // مقارنة خاصة للخيارات (options)
        if (isset($existingField['options']) || isset($existingField['select_options']) ||
            isset($newField['options']) || isset($newField['select_options'])) {
            $existingOptions = $existingField['select_options'] ?? $existingField['options'] ?? [];
            $newOptions = $newField['select_options'] ?? $newField['options'] ?? [];

            if (json_encode($existingOptions) !== json_encode($newOptions)) {
                $changes[] = [
                    'property' => 'select_options',
                    'property_ar' => 'الخيارات',
                    'old_value' => $existingOptions,
                    'new_value' => $newOptions
                ];
            }
        }

        // مقارنة خاصة للعلاقات (relation)
        $relationProperties = ['relation_table', 'relation_column'];
        foreach ($relationProperties as $prop) {
            if (isset($existingField[$prop]) || isset($newField[$prop])) {
                $existingValue = $existingField[$prop] ?? null;
                $newValue = $newField[$prop] ?? null;

                if ($existingValue !== $newValue) {
                    $changes[] = [
                        'property' => $prop,
                        'property_ar' => $prop === 'relation_table' ? 'جدول العلاقة' : 'عمود العلاقة',
                        'old_value' => $existingValue,
                        'new_value' => $newValue
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * جمع معلومات الوحدة (للحفاظ على الإعدادات)
     */
    private function getModuleInfo($moduleName)
    {
        // تحديد parent group بناءً على اسم الوحدة
        $parentGroup = 'employees'; // القيمة الافتراضية

        // يمكن إضافة منطق لتحديد parent group بذكاء
        $lowerModuleName = strtolower($moduleName);
        if (strpos($lowerModuleName, 'project') !== false) {
            $parentGroup = 'project';
        } elseif (strpos($lowerModuleName, 'employee') !== false) {
            $parentGroup = 'employees';
        } elseif (strpos($lowerModuleName, 'user') !== false) {
            $parentGroup = 'users';
        }

        return [
            'name' => $moduleName,
            'arabic_name' => $this->getModuleArabicNameFromConfig($moduleName),
            'parent_group' => $parentGroup,
            'permissions' => ['create', 'read', 'update', 'delete'],
            'has_soft_delete' => false,
            'has_user_relation' => true
        ];
    }

    /**
     * نسخ احتياطي وحذف الملفات القديمة
     */
    private function backupAndDeleteOldFiles($moduleName)
    {
        $this->info("🗑️ حذف الملفات القديمة للوحدة: {$moduleName}");

        // التعامل مع migrations القديمة أولاً
        $this->handleOldMigrations($moduleName);

        // حذف Routes الخاصة بـ PDF من web.php
        $this->removePdfRoutesFromWebPhp($moduleName);

        // مسارات الملفات المراد حذفها
        $filesToDelete = [
            // Livewire Component
            base_path("app/Http/Livewire/{$moduleName}/" . Str::singular($moduleName) . ".php"),
            base_path("app/Http/Livewire/" . Str::plural($moduleName) . "/" . Str::singular($moduleName) . ".php"),

            // PDF Controllers الجديدة في مجلد الوحدة
            base_path("app/Http/Controllers/{$moduleName}/" . Str::singular($moduleName) . "TcpdfExportController.php"),
            base_path("app/Http/Controllers/{$moduleName}/" . Str::singular($moduleName) . "PrintController.php"),

            // Views
            resource_path("views/livewire/" . strtolower($moduleName) . "/" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/" . strtolower(Str::singular($moduleName)) . ".blade.php"),

            // PDF Print Views - معالجة شاملة للأسماء
            resource_path("views/exports/" . strtolower($moduleName) . "_print.blade.php"),
            resource_path("views/exports/" . strtolower(Str::plural($moduleName)) . "_print.blade.php"),
            resource_path("views/exports/" . strtolower(Str::singular($moduleName)) . "_print.blade.php"),

            // Modals
            resource_path("views/livewire/" . strtolower($moduleName) . "/modals/add-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower($moduleName) . "/modals/edit-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/modals/add-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/modals/edit-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
        ];

        foreach ($filesToDelete as $file) {
            if (File::exists($file)) {
                File::delete($file);
                $this->info("🗑️ تم حذف: " . basename($file));
            }
        }

        // حذف مجلدات فارغة
        $dirsToClean = [
            base_path("app/Http/Livewire/{$moduleName}"),
            base_path("app/Http/Livewire/" . Str::plural($moduleName)),
            resource_path("views/livewire/" . strtolower($moduleName) . "/modals"),
            resource_path("views/livewire/" . strtolower($moduleName)),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/modals"),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName))),
        ];

        foreach ($dirsToClean as $dir) {
            if (File::exists($dir) && File::isDirectory($dir)) {
                $files = File::files($dir);
                if (empty($files)) {
                    File::deleteDirectory($dir);
                    $this->info("🗑️ تم حذف المجلد: " . basename($dir));
                }
            }
        }
    }

    /**
     * حذف Routes الخاصة بـ PDF Controllers من web.php
     */
    private function removePdfRoutesFromWebPhp($moduleName)
    {
        $webRoutePath = base_path('routes/web.php');

        if (!File::exists($webRoutePath)) {
            return;
        }

        $content = File::get($webRoutePath);
        $originalContent = $content;
        $singularName = Str::singular($moduleName);

        // حذف use statements للـ PDF Controllers
        $useStatements = [
            "use App\\Http\\Controllers\\{$moduleName}\\{$singularName}TcpdfExportController;",
            "use App\\Http\\Controllers\\{$moduleName}\\{$singularName}PrintController;"
        ];

        foreach ($useStatements as $useStatement) {
            $content = str_replace($useStatement . "\n", "", $content);
            $content = str_replace($useStatement, "", $content);
        }

        // حذف routes الخاصة بـ PDF
        $routePatterns = [
            "/Route::GET\('{$moduleName}\/export-pdf-tcpdf'[^;]+;\n?/",
            "/Route::GET\('{$moduleName}\/print-view'[^;]+;\n?/"
        ];

        foreach ($routePatterns as $pattern) {
            $content = preg_replace($pattern, "", $content);
        }

        // حفظ التغييرات إذا حدثت
        if ($originalContent !== $content) {
            File::put($webRoutePath, $content);
            $this->info("🗑️ تم حذف routes الخاصة بـ PDF من web.php");
        }
    }

    /**
     * إعادة إنشاء الوحدة باستخدام مولد الوحدات
     */
    private function regenerateModuleWithFields($moduleName, $fields, $moduleInfo, $advancedFeatures)
    {
        $this->info("🔨 إعادة إنشاء الوحدة باستخدام مولد الوحدات...");

        try {
            // استدعاء مولد الوحدات الصحيح
            $exitCode = Artisan::call('make:hmvc-module', [
                'name' => $moduleName,
                '--ar-name' => $moduleInfo['arabic_name'],
                '--fields' => json_encode($fields),
                '--type' => 'sub',
                '--parent-group' => $moduleInfo['parent_group']
            ]);

            $output = Artisan::output();
            $this->info("مخرجات مولد الوحدات:");
            $this->line($output);

            if ($exitCode === 0) {
                $this->info("✅ تم إعادة إنشاء الوحدة بنجاح");
            } else {
                $this->warn("⚠️ تم إعادة الإنشاء مع بعض التحذيرات");
                $this->warn("Exit Code: " . $exitCode);
            }

        } catch (\Exception $e) {
            $this->error("❌ خطأ في استدعاء مولد الوحدات: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * تحديث Model - مبني على مولد الوحدات
     */
    private function updateModel($moduleName, $fields)
    {
        $modelPaths = [
            base_path("app/Models/{$moduleName}.php"),
            base_path("app/Models/{$moduleName}/{$moduleName}.php"),
            base_path("app/Models/" . Str::plural($moduleName) . "/" . Str::plural($moduleName) . ".php"),
        ];

        $modelPath = null;
        foreach ($modelPaths as $path) {
            if (File::exists($path)) {
                $modelPath = $path;
                break;
            }
        }

        if (!$modelPath) {
            $this->warn("⚠️ Model غير موجود للوحدة: {$moduleName}");
            return;
        }

        $this->info("🔄 تحديث Model: " . basename($modelPath));

        $modelContent = File::get($modelPath);

        // التحقق من نوع النموذج
        if (preg_match('/protected\s+\$guarded\s*=\s*\[\s*\]/', $modelContent)) {
            // نموذج جديد يستخدم $guarded = []
            $this->info("✅ النموذج يستخدم \$guarded = [] - لا حاجة لتحديث fillable");
            $this->info("  الحقول الجديدة ستكون متاحة تلقائياً");
            return;
        }

        // نموذج قديم يستخدم $fillable array
        $newFillable = [];

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (!preg_match("/['\"]" . preg_quote($fieldName, '/') . "['\"].*,/", $modelContent)) {
                $newFillable[] = "'{$fieldName}'";
            }
        }

        if (!empty($newFillable)) {
            // إضافة الحقول لـ fillable
            $pattern = '/protected\s+\$fillable\s*=\s*\[(.*?)\];/s';
            if (preg_match($pattern, $modelContent, $matches)) {
                $currentFillable = trim($matches[1]);
                $newFields = implode(",\n        ", $newFillable);

                if (!empty($currentFillable)) {
                    $updatedFillable = rtrim($currentFillable, ',') . ",\n        " . $newFields;
                } else {
                    $updatedFillable = "\n        " . $newFields . "\n    ";
                }

                $newModelContent = preg_replace($pattern, "protected \$fillable = [{$updatedFillable}\n    ];", $modelContent);
                File::put($modelPath, $newModelContent);
                $this->info("✅ تم إضافة " . count($newFillable) . " حقل للـ fillable array");
            } else {
                $this->warn("⚠️ لم يتم العثور على fillable array في النموذج");
            }
        } else {
            $this->info("✅ جميع الحقول موجودة بالفعل في النموذج");
        }
    }

    /**
     * تحديد اسم الجدول الصحيح
     */
    private function getActualTableName($moduleName)
    {
        // تحقق من وجود الجدول مع أسماء مختلفة
        $possibleNames = [
            strtolower($moduleName) . 's',  // ProductionCapacitys -> productioncapacityss
            strtolower(Str::plural($moduleName)), // ProductionCapacitys -> productioncapacitys
            Str::snake(Str::plural($moduleName)),  // ProductionCapacitys -> production_capacitys
            Str::snake($moduleName),  // ProductionCapacitys -> production_capacitys
        ];

        try {
            $existingTables = \Illuminate\Support\Facades\Schema::getTableListing();

            foreach ($possibleNames as $tableName) {
                if (in_array($tableName, $existingTables)) {
                    return $tableName;
                }
            }
        } catch (\Exception $e) {
            // في حالة فشل الاستعلام، نستخدم الافتراضي
        }

        // إذا لم يوجد، نستخدم الافتراضي
        return Str::snake(Str::plural($moduleName));
    }

    /**
     * إنشاء Migration - مبني على مولد الوحدات
     */
    private function createMigration($moduleName, $fields)
    {
        $tableName = $this->getActualTableName($moduleName);
        $timestamp = date('Y_m_d_His');
        $migrationName = "add_new_fields_to_{$tableName}_table";
        $migrationPath = database_path("migrations/{$timestamp}_{$migrationName}.php");

        // حفظ اسم migration للاستخدام لاحقاً
        $this->migrationName = "{$timestamp}_{$migrationName}";

        $this->info("🔄 إنشاء Migration: {$migrationName}");

        $fieldArray = [];
        foreach ($fields as $field) {
            $fieldType = $this->getMigrationType($field['type']);
            $arabicName = $field['ar_name'] ?? $field['comment'] ?? $field['name'];

            // Handle size parameter for string and decimal types
            $sizeParam = '';
            if (!empty($field['max'])) {
                if ($fieldType === 'string') {
                    $sizeParam = ", {$field['max']}";
                } elseif ($fieldType === 'decimal') {
                    $sizeParts = explode(',', $field['max']);
                    $precision = $sizeParts[0] ?? 10;
                    $scale = $sizeParts[1] ?? 2;
                    $sizeParam = ", {$precision}, {$scale}";
                }
            }

            // For boolean fields, handle default values
            $defaultValue = '';
            if ($fieldType === 'boolean') {
                $defaultValue = '->default(false)';
            }

            $nullable = ($field['required'] ?? false) ? '' : '->nullable()';
            $unique = ($field['unique'] ?? false) ? '->unique()' : '';
            $commentSuffix = $arabicName ? "->comment('{$arabicName}')" : '';

            // إضافة قيود خاصة للنصوص العربية
            $charset = '';
            if (($field['arabic_only'] ?? false) && $fieldType === 'string') {
                $charset = "->charset('utf8mb4')->collation('utf8mb4_unicode_ci')";
            }

            $fieldArray[] = "\$table->{$fieldType}('{$field['name']}'{$sizeParam}){$defaultValue}{$nullable}{$unique}{$charset}{$commentSuffix}; // {$arabicName}";
        }

        $migrationFields = implode("\n            ", $fieldArray);

        $migrationContent = "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            {$migrationFields}
        });
    }

    public function down()
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {";

        // Generate drop statements
        foreach ($fields as $field) {
            $arabicName = $field['ar_name'] ?? $field['comment'] ?? $field['name'];
            $migrationContent .= "\n            \$table->dropColumn('{$field['name']}'); // {$arabicName}";
        }

        $migrationContent .= "
        });
    }
};";

        File::put($migrationPath, $migrationContent);
        $this->info("✅ تم إنشاء Migration بـ " . count($fields) . " حقل");
    }


    /**
     * تحويل نوع الحقل إلى نوع Migration
     */
    private function getMigrationType($fieldType)
    {
        switch ($fieldType) {
            case 'text': return 'text'; // نص طويل -> text في قاعدة البيانات
            case 'textarea': return 'text';
            case 'string': return 'string'; // نص قصير -> string في قاعدة البيانات
            case 'number': return 'bigInteger';
            case 'integer': return 'bigInteger';
            case 'numeric': return 'bigInteger';
            case 'select_numeric': return 'bigInteger';
            case 'calculated': return 'bigInteger'; // الحقول المحسوبة عادة رقمية
            case 'email': return 'string';
            case 'date': return 'date';
            case 'datetime': return 'dateTime';
            case 'time': return 'time';
            case 'month_year': return 'string';
            case 'checkbox': return 'boolean';
            case 'file': return 'string';
            case 'select': return 'string';
            default: return 'string';
        }
    }

    /**
     * معالجة الحقول المحسوبة للوحدة
     */
    private function processCalculatedFields($moduleName, $fields)
    {
        $this->info("🧮 بدء معالجة الحقول المحسوبة...");

        // البحث عن الحقول المحسوبة من قاعدة البيانات
        $calculatedFields = [];
        try {
            $dbFields = ModuleField::where('module_name', $moduleName)
                                 ->where('is_calculated', true)
                                 ->get();

            foreach ($dbFields as $dbField) {
                $calculatedFields[] = [
                    'name' => $dbField->field_name,
                    'type' => $dbField->field_type,
                    'ar_name' => $dbField->arabic_name,
                    'is_calculated' => true,
                    'calculation_formula' => $dbField->calculation_formula
                ];
            }

            if (!empty($calculatedFields)) {
                $this->info("🔍 تم العثور على " . count($calculatedFields) . " حقل محسوب");

                // تحديث الحقول المحسوبة في modals
                $this->updateCalculatedFieldsInModals($moduleName, $calculatedFields);

                // إضافة دوال الحساب في Livewire Component
                $this->addCalculationMethodsToComponent($moduleName, $calculatedFields);

                $this->info("✅ تم معالجة الحقول المحسوبة بنجاح");
            } else {
                $this->info("ℹ️ لا توجد حقول محسوبة في هذه الوحدة");
            }

        } catch (\Exception $e) {
            $this->warn("⚠️ خطأ في معالجة الحقول المحسوبة: " . $e->getMessage());
        }
    }

    /**
     * تحديث الحقول المحسوبة في modals
     */
    private function updateCalculatedFieldsInModals($moduleName, $calculatedFields)
    {
        $modalPaths = [
            resource_path("views/livewire/" . strtolower($moduleName) . "/modals/add-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower($moduleName) . "/modals/edit-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/modals/add-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/modals/edit-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
        ];

        foreach ($modalPaths as $modalPath) {
            if (File::exists($modalPath)) {
                $content = File::get($modalPath);

                foreach ($calculatedFields as $field) {
                    $fieldName = $field['name'];
                    $arabicName = $field['ar_name'];

                    // إضافة readonly styling
                    $content = $this->addReadonlyStyleToCalculatedField($content, $fieldName);

                    // إضافة wire:input للحساب التلقائي
                    $content = $this->addWireInputToCalculatedField($content, $fieldName);

                    // إضافة calculator icon
                    $content = $this->addCalculatorIconToField($content, $fieldName, $arabicName);
                }

                File::put($modalPath, $content);
                $this->info("✅ تم تحديث: " . basename($modalPath));
            }
        }
    }

    /**
     * إضافة دوال الحساب في Livewire Component
     */
    private function addCalculationMethodsToComponent($moduleName, $calculatedFields)
    {
        $componentPaths = [
            base_path("app/Http/Livewire/{$moduleName}/" . Str::singular($moduleName) . ".php"),
            base_path("app/Http/Livewire/" . Str::plural($moduleName) . "/" . Str::singular($moduleName) . ".php"),
        ];

        foreach ($componentPaths as $componentPath) {
            if (File::exists($componentPath)) {
                $content = File::get($componentPath);

                // التحقق من عدم وجود دالة calculateFields
                if (strpos($content, 'public function calculateFields()') === false) {
                    // إضافة دوال الحساب
                    $calculationMethods = $this->generateCalculationMethods($calculatedFields);

                    if (!empty($calculationMethods)) {
                        // البحث عن نهاية الكلاس وإضافة دوال الحساب قبلها
                        $lastClosingBrace = strrpos($content, '}');
                        if ($lastClosingBrace !== false) {
                            $content = substr($content, 0, $lastClosingBrace) . $calculationMethods . "\n}";
                            File::put($componentPath, $content);
                            $this->info("✅ تم إضافة دوال الحساب إلى: " . basename($componentPath));
                        }
                    }
                } else {
                    // تحديث المعادلات الحسابية الموجودة إذا لزم الأمر
                    $this->updateExistingCalculateFields($content, $calculatedFields, $componentPath);
                    $this->info("ℹ️ دالة calculateFields موجودة بالفعل في: " . basename($componentPath));
                }

                break;
            }
        }
    }

    /**
     * تحديث Livewire Component - مبني على مولد الوحدات
     */
    private function updateLivewireComponent($moduleName, $fields)
    {
        $componentPaths = [
            base_path("app/Http/Livewire/{$moduleName}/" . Str::singular($moduleName) . ".php"),
            base_path("app/Http/Livewire/" . Str::plural($moduleName) . "/" . Str::singular($moduleName) . ".php"),
        ];

        $componentPath = null;
        foreach ($componentPaths as $path) {
            if (File::exists($path)) {
                $componentPath = $path;
                break;
            }
        }

        if (!$componentPath) {
            $this->warn("⚠️ Livewire Component غير موجود للوحدة: {$moduleName}");
            return;
        }

        $this->info("🔄 تحديث Livewire Component: " . basename($componentPath));

        $content = File::get($componentPath);

        // 1. إضافة الخصائص بطريقة مولد الوحدات
        $content = $this->addPropertiesLikeGenerator($content, $fields);

        // 2. تحديث search array
        $content = $this->updateSearchArrayLikeGenerator($content, $fields);

        // 3. تحديث updatedSearch method
        $content = $this->updateSearchMethodLikeGenerator($content, $fields);

        // 4. تحديث validation rules - استخدام قاعدة البيانات
        $content = $this->updateValidationRulesFromDatabase($content, $fields, $moduleName);

        // 5. تحديث store/update data
        $content = $this->updateStoreUpdateDataLikeGenerator($content, $fields);

        // 6. تحديث render method للبحث
        $content = $this->updateRenderMethodLikeGenerator($content, $fields, $moduleName);

        // 7. إضافة دوال العمليات الحسابية
        $calculationMethods = $this->generateCalculationMethods($fields);
        if (!empty($calculationMethods)) {
            // البحث عن نهاية الكلاس وإضافة دوال الحساب قبلها
            $lastClosingBrace = strrpos($content, '}');
            if ($lastClosingBrace !== false) {
                $content = substr($content, 0, $lastClosingBrace) . $calculationMethods . "\n}";
                $this->info("✅ تم إضافة دوال العمليات الحسابية");
            }
        }

        // 8. إضافة دوال updated للحقول المرجعية للوقت
        $this->addUpdatedMethodsForTimeReferences($content, $fields, $moduleName, $componentPath);

        File::put($componentPath, $content);
        $this->info("✅ تم تحديث Livewire Component بـ " . count($fields) . " حقل");
    }

    /**
     * إضافة خصائص الحقول بطريقة مولد الوحدات
     */
    private function addPropertiesLikeGenerator($content, $fields)
    {
        $newProperties = [];

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $comment = $field['ar_name'] ?? $field['comment'] ?? $fieldName;

            // التحقق من عدم وجود الخاصية
            if (!preg_match("/public\s+\\\$" . preg_quote($fieldName) . "\s*[;=]/", $content)) {
                if ($field['type'] === 'checkbox' || $field['type'] === 'boolean') {
                    $newProperties[] = "    public \${$fieldName} = false; // {$comment} - Initialize as false for checkbox";
                } else {
                    $newProperties[] = "    public \${$fieldName}; // {$comment}";
                }

                // Add preview variable for file fields like generator
                if ($field['type'] === 'file') {
                    $newProperties[] = "    public \$previewFile{$fieldName}; // Preview for {$comment}";
                }
            }
        }

        if (!empty($newProperties)) {
            // البحث عن مكان إدراج الخصائص بعد آخر public property
            $patterns = [
                // محاولة إيجاد آخر public property قبل search array
                '/(public\s+\$\w+[^;]*;[^\n]*\n)(\s*public\s+\$search\s*=)/s',
                // محاولة إيجاد آخر public property قبل أي function
                '/(public\s+\$\w+[^;]*;[^\n]*\n)(\s*(?:public|protected|private)\s+function)/s',
                // محاولة إيجاد آخر public property
                '/(public\s+\$\w+[^;]*;[^\n]*)\n/s'
            ];

            $inserted = false;
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $propertiesString = implode("\n", $newProperties);
                    $content = preg_replace($pattern, "$1\n{$propertiesString}\n$2", $content, 1);
                    $this->info("✅ تم إضافة " . count($newProperties) . " خاصية جديدة");
                    $inserted = true;
                    break;
                }
            }

            if (!$inserted) {
                $this->warn("⚠️ لم يتم العثور على مكان مناسب لإدراج الخصائص");
            }
        }

        return $content;
    }

    /**
     * تحديث search array بطريقة مولد الوحدات
     */
    private function updateSearchArrayLikeGenerator($content, $fields)
    {
        $searchableFields = [];
        foreach ($fields as $field) {
            if (($field['searchable'] ?? true) && $field['type'] !== 'file') {
                $searchableFields[] = "'{$field['name']}' => ''";
            }
        }

        if (!empty($searchableFields)) {
            $pattern = '/public\s+\$search\s*=\s*\[(.*?)\];/s';
            if (preg_match($pattern, $content, $matches)) {
                $currentSearch = trim($matches[1]);
                $newFields = implode(",\n        ", $searchableFields);

                if (!empty($currentSearch)) {
                    $updatedSearch = rtrim($currentSearch, ',') . ",\n        " . $newFields;
                } else {
                    $updatedSearch = "\n        " . $newFields . "\n    ";
                }

                $newSearchArray = "public \$search = [{$updatedSearch}\n    ];";
                $content = preg_replace($pattern, $newSearchArray, $content);
                $this->info("✅ تم تحديث search array بـ " . count($searchableFields) . " حقل");
            }
        }

        return $content;
    }

    /**
     * تحديث updatedSearch method بطريقة مولد الوحدات
     */
    private function updateSearchMethodLikeGenerator($content, $fields)
    {
        $searchableFields = [];
        foreach ($fields as $field) {
            if (($field['searchable'] ?? true) && $field['type'] !== 'file') {
                $searchableFields[] = "'{$field['name']}'";
            }
        }

        if (!empty($searchableFields)) {
            $pattern = '/if\s*\(\s*in_array\s*\(\s*\$key\s*,\s*\[(.*?)\]\s*\)\s*\)/';
            if (preg_match($pattern, $content, $matches)) {
                $currentFields = $matches[1];
                $newFields = implode(', ', $searchableFields);

                $updatedFields = !empty(trim($currentFields)) ?
                    rtrim(trim($currentFields), ',') . ', ' . $newFields :
                    $newFields;

                $newCondition = "if (in_array(\$key, [{$updatedFields}]))";
                $content = str_replace($matches[0], $newCondition, $content);
                $this->info("✅ تم تحديث updatedSearch method");
            }
        }

        return $content;
    }

    /**
     * تحديث validation rules باستخدام قاعدة البيانات
     */
    private function updateValidationRulesFromDatabase($content, $fields, $moduleName)
    {
        $this->info("🔄 تحديث قواعد validation باستخدام قاعدة البيانات...");

        try {
            // الحصول على جميع حقول الوحدة من قاعدة البيانات
            $allModuleFields = ModuleField::getModuleFields($moduleName);

            if ($allModuleFields->isEmpty()) {
                $this->warn("⚠️ لا توجد حقول محفوظة في قاعدة البيانات للوحدة {$moduleName}");
                return $this->updateValidationRulesLikeGenerator($content, $fields, $moduleName);
            }

            // تحويل الحقول إلى format مولد الوحدات
            $allFields = $allModuleFields->map(function ($field) {
                return [
                    'name' => $field->field_name,
                    'type' => $field->field_type,
                    'ar_name' => $field->arabic_name,
                    'required' => $field->required,
                    'unique' => $field->unique,
                    'max' => $field->max_length,
                    'validation' => $field->validation_rules,
                    'arabic_only' => $field->arabic_only,
                    'numeric_only' => $field->numeric_only,
                    // إضافة خصائص النص الجديدة
                    'text_content_type' => $field->text_content_type,
                    // إضافة خصائص الأرقام الصحيحة الجديدة
                    'integer_type' => $field->integer_type,
                    'unsigned' => $field->unsigned,
                    // إضافة خصائص الأرقام العشرية الجديدة
                    'decimal_precision' => $field->decimal_precision,
                    'decimal_scale' => $field->decimal_scale,
                    // إضافة باقي الخصائص
                    'searchable' => $field->searchable,
                    'show_in_table' => $field->show_in_table,
                    'show_in_search' => $field->show_in_search,
                    'show_in_forms' => $field->show_in_forms,
                    'file_types' => $field->file_types,
                    'select_options' => $field->select_options,
                    'select_source' => $field->select_source,
                    'related_table' => $field->related_table,
                    'related_key' => $field->related_key,
                    'related_display' => $field->related_display,
                    'validation_messages' => $field->validation_messages,
                    'custom_attributes' => $field->custom_attributes
                ];
            })->toArray();

            $this->info("✅ تم تحميل " . count($allFields) . " حقل من قاعدة البيانات");

            // الآن استخدم هذه الحقول لبناء validation rules
            $tableName = Str::snake(Str::plural($moduleName));

            // Build validation rules arrays
            $storeRulesArray = [];
            $updateRulesArray = [];
            $messagesArray = [];

            foreach ($allFields as $field) {
                $fieldName = $field['name'];
                $arabicLabel = $field['ar_name'] ?? $fieldName;
                $fieldType = $field['type'] ?? 'text';

                // بناء store rules
                $storeRules = [];
                if ($field['required'] ?? false) {
                    $storeRules[] = 'required';
                } else {
                    $storeRules[] = 'nullable';
                }

                // إضافة unique rules
                if ($field['unique'] ?? false) {
                    $storeRules[] = "unique:{$tableName},{$fieldName}";
                }

                // إضافة قواعد حسب نوع الحقل
                if ($fieldType === 'email') {
                    $storeRules[] = 'email';
                } elseif ($fieldType === 'number') {
                    $storeRules[] = 'numeric';
                } elseif ($fieldType === 'date') {
                    $storeRules[] = 'date';
                } elseif ($fieldType === 'file') {
                    $storeRules[] = 'file';
                    $storeRules[] = 'mimes:jpeg,png,jpg,pdf';
                    $storeRules[] = 'max:10240';
                }

                // إضافة max length
                if (!empty($field['max'])) {
                    $storeRules[] = "max:{$field['max']}";
                }

                // إضافة regex للأحرف العربية (الطريقة القديمة)
                if ($field['arabic_only'] ?? false) {
                    $storeRules[] = 'regex:/^[\p{Arabic}\s]+$/u';
                }

                // إضافة قواعد نوع المحتوى النصي الجديدة
                if (isset($field['text_content_type'])) {
                    switch ($field['text_content_type']) {
                        case 'arabic_only':
                            $storeRules[] = 'regex:/^[\p{Arabic}\s]+$/u';
                            break;
                        case 'english_only':
                            $storeRules[] = 'regex:/^[a-zA-Z\s]+$/';
                            break;
                        case 'numeric_only':
                            $storeRules[] = 'regex:/^[0-9]+$/';
                            break;
                        case 'any':
                        default:
                            // لا نضيف قواعد إضافية
                            break;
                    }
                }

                // إضافة قواعد إضافية
                if (!empty($field['validation'])) {
                    $additionalRules = explode('|', $field['validation']);
                    $storeRules = array_merge($storeRules, $additionalRules);
                }

                $storeRulesArray[] = "'{$fieldName}' => '" . implode('|', $storeRules) . "'";

                // بناء update rules (نفس الشيء لكن مع unique مختلف)
                $updateRules = $storeRules;
                if ($field['unique'] ?? false) {
                    // استبدال unique rule للـ update
                    $singularName = strtolower(Str::singular($moduleName));
                    $updateRules = array_filter($updateRules, function($rule) {
                        return !str_starts_with($rule, 'unique:');
                    });
                    $updateRules[] = "unique:{$tableName},{$fieldName},'.(\\$this->{$singularName}Id ?? 'NULL').',id";
                }

                if (in_array($fieldName, ['full_name']) && ($field['unique'] ?? false)) {
                    // حالة خاصة للحقول المعقدة
                    $singularName = strtolower(Str::singular($moduleName));
                    $complexRule = implode('|', array_filter($updateRules, function($rule) {
                        return !str_starts_with($rule, 'unique:');
                    }));
                    $complexRule .= "|unique:{$tableName},{$fieldName},'.(\\$this->{$singularName}Id ?? 'NULL').',id";
                    if (in_array('max:255', $updateRules)) $complexRule .= '|max:255';
                    if (in_array('regex:/^[\p{Arabic}\s]+$/u', $updateRules)) $complexRule .= '|regex:/^[\p{Arabic}\s]+$/u';
                    if (in_array('regex:/^[a-zA-Z\s]+$/', $updateRules)) $complexRule .= '|regex:/^[a-zA-Z\s]+$/';
                    if (in_array('regex:/^[0-9]+$/', $updateRules)) $complexRule .= '|regex:/^[0-9]+$/';
                    $updateRulesArray[] = "'{$fieldName}' => '{$complexRule}'";
                } else {
                    $updateRulesArray[] = "'{$fieldName}' => '" . implode('|', $updateRules) . "'";
                }

                // بناء messages
                if ($field['required'] ?? false) {
                    $messagesArray[] = "'{$fieldName}.required' => 'يرجى إدخال {$arabicLabel}'";
                }
                if ($field['unique'] ?? false) {
                    $messagesArray[] = "'{$fieldName}.unique' => '{$arabicLabel} موجود بالفعل'";
                }
                if (!empty($field['max'])) {
                    $messagesArray[] = "'{$fieldName}.max' => '{$arabicLabel} يجب أن يكون أقل من {$field['max']} حرف'";
                }
                if ($fieldType === 'email') {
                    $messagesArray[] = "'{$fieldName}.email' => 'يرجى إدخال بريد إلكتروني صحيح'";
                }
                if ($fieldType === 'integer') {
                    $messagesArray[] = "'{$fieldName}.integer' => '{$arabicLabel} يجب أن يكون رقم صحيح'";
                    // Add detailed integer validation messages
                    $integerMessages = $this->getIntegerValidationMessages($field);
                    foreach ($integerMessages as $key => $message) {
                        $messagesArray[] = "'{$key}' => '{$message}'";
                    }
                }
                if ($fieldType === 'decimal') {
                    $messagesArray[] = "'{$fieldName}.numeric' => '{$arabicLabel} يجب أن يكون رقم صالح'";
                    // Add detailed decimal validation messages
                    $decimalMessages = $this->getDecimalValidationMessages($field);
                    foreach ($decimalMessages as $key => $message) {
                        $messagesArray[] = "'{$key}' => '{$message}'";
                    }
                }
                if ($field['arabic_only'] ?? false) {
                    $messagesArray[] = "'{$fieldName}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف عربية فقط'";
                }

                // إضافة رسائل نوع المحتوى النصي الجديدة
                if (isset($field['text_content_type'])) {
                    switch ($field['text_content_type']) {
                        case 'arabic_only':
                            $messagesArray[] = "'{$fieldName}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف عربية فقط'";
                            break;
                        case 'english_only':
                            $messagesArray[] = "'{$fieldName}.regex' => '{$arabicLabel} يجب أن يحتوي على أحرف إنجليزية فقط'";
                            break;
                        case 'numeric_only':
                            $messagesArray[] = "'{$fieldName}.regex' => '{$arabicLabel} يجب أن يحتوي على أرقام فقط'";
                            break;
                    }
                }
            }

            // Update or create validation methods
            $content = $this->addOrUpdateValidationMethod($content, 'getStoreRules', $storeRulesArray);
            $content = $this->addOrUpdateValidationMethod($content, 'getUpdateRules', $updateRulesArray);
            $content = $this->addOrUpdateValidationMethod($content, 'getValidationMessages', $messagesArray);

            // Update store and update methods to use new validation
            $content = $this->updateStoreUpdateMethodsForSeparateValidation($content);

            $this->info("✅ تم تحديث جميع قواعد validation من قاعدة البيانات");

        } catch (\Exception $e) {
            $this->error("❌ خطأ في تحديث validation من قاعدة البيانات: " . $e->getMessage());
            $this->info("🔄 استخدام الطريقة القديمة...");
            return $this->updateValidationRulesLikeGenerator($content, $fields, $moduleName);
        }

        return $content;
    }

    /**
     * تحديث validation rules بطريقة مولد الوحدات (منفصل للـ store و update)
     */
    private function updateValidationRulesLikeGenerator($content, $fields, $moduleName)
    {
        $tableName = Str::snake(Str::plural($moduleName));

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $arabicLabel = $field['ar_name'] ?? $field['comment'] ?? $fieldName;

            // Build validation rules based on field type
            $storeRules = [];
            $updateRules = [];
            $storeMessages = [];
            $updateMessages = [];

            if ($field['required'] ?? false) {
                if ($field['type'] === 'file') {
                    // File validation - required for store, nullable for update
                    $storeRules[] = "required|file|mimes:jpeg,png,jpg,pdf|max:10240";
                    $storeMessages[] = "'{$fieldName}.required' => 'يرجى اختيار {$arabicLabel}'";
                    $storeMessages[] = "'{$fieldName}.file' => '{$arabicLabel} يجب أن يكون ملف'";
                    $storeMessages[] = "'{$fieldName}.mimes' => '{$arabicLabel} يجب أن يكون من نوع صورة أو PDF'";
                    $storeMessages[] = "'{$fieldName}.max' => 'حجم {$arabicLabel} يجب ألا يزيد عن 10 ميجا'";

                    $updateRules[] = "nullable|file|mimes:jpeg,png,jpg,pdf|max:10240";
                    $updateMessages[] = "'{$fieldName}.file' => '{$arabicLabel} يجب أن يكون ملف'";
                    $updateMessages[] = "'{$fieldName}.mimes' => '{$arabicLabel} يجب أن يكون من نوع صورة أو PDF'";
                    $updateMessages[] = "'{$fieldName}.max' => 'حجم {$arabicLabel} يجب ألا يزيد عن 10 ميجا'";
                } elseif ($field['unique'] ?? false) {
                    // Unique field validation
                    $storeRules[] = "required|unique:{$tableName},{$fieldName}";
                    $storeMessages[] = "'{$fieldName}.required' => 'يرجى إدخال {$arabicLabel}'";
                    $storeMessages[] = "'{$fieldName}.unique' => '{$arabicLabel} موجود بالفعل'";

                    $singularName = strtolower(Str::singular($moduleName));
                    $updateRules[] = "required|unique:{$tableName},{$fieldName},'.(\\$this->{$singularName}Id ?? 'NULL').',id'";
                    $updateMessages[] = "'{$fieldName}.required' => 'يرجى إدخال {$arabicLabel}'";
                    $updateMessages[] = "'{$fieldName}.unique' => '{$arabicLabel} موجود بالفعل'";
                } else {
                    // Regular required field
                    $rules = ['required'];
                    if (!empty($field['max'])) {
                        $rules[] = "max:{$field['max']}";
                    }
                    if ($field['type'] === 'email') {
                        $rules[] = 'email';
                    }
                    if ($field['type'] === 'date') {
                        $rules[] = 'date';
                    }
                    if ($field['type'] === 'datetime') {
                        $rules[] = 'date';
                    }

                    $ruleString = implode('|', $rules);
                    $storeRules[] = $ruleString;
                    $updateRules[] = $ruleString;
                    $storeMessages[] = "'{$fieldName}.required' => 'يرجى إدخال {$arabicLabel}'";
                    $updateMessages[] = "'{$fieldName}.required' => 'يرجى إدخال {$arabicLabel}'";

                    if ($field['type'] === 'date') {
                        $storeMessages[] = "'{$fieldName}.date' => 'يرجى إدخال تاريخ صحيح في {$arabicLabel}'";
                        $updateMessages[] = "'{$fieldName}.date' => 'يرجى إدخال تاريخ صحيح في {$arabicLabel}'";
                    }
                }
            } else {
                // Optional field
                $rules = ['nullable'];
                if (!empty($field['max'])) {
                    $rules[] = "max:{$field['max']}";
                }
                if ($field['type'] === 'email') {
                    $rules[] = 'email';
                }
                if ($field['type'] === 'file') {
                    $rules[] = 'file|mimes:jpeg,png,jpg,pdf|max:10240';
                }

                $ruleString = implode('|', $rules);
                $storeRules = $updateRules = [$ruleString];
            }

            // Add validation rules to methods
            if (!empty($storeRules)) {
                $storeRuleString = implode('|', $storeRules);
                $storeValidationRule = "'{$fieldName}' => '{$storeRuleString}',";
                $content = $this->addValidationRuleToMethod($content, $storeValidationRule, 'store');

                // Add messages
                foreach ($storeMessages as $message) {
                    $content = $this->addValidationMessageToMethod($content, $message, 'store');
                }
            }

            if (!empty($updateRules)) {
                $updateRuleString = implode('|', $updateRules);
                $updateValidationRule = "'{$fieldName}' => '{$updateRuleString}',";
                $content = $this->addValidationRuleToMethod($content, $updateValidationRule, 'update');

                // Add messages
                foreach ($updateMessages as $message) {
                    $content = $this->addValidationMessageToMethod($content, $message, 'update');
                }
            }
        }

        return $content;
    }

    /**
     * تحديث store/update data بطريقة مولد الوحدات
     */
    private function updateStoreUpdateDataLikeGenerator($content, $fields)
    {
        foreach ($fields as $field) {
            $fieldName = $field['name'];

            // إضافة للـ store data
            $content = $this->addToDataArrayLikeGenerator($content, $fieldName, 'store', $field);

            // إضافة للـ update data
            $content = $this->addToDataArrayLikeGenerator($content, $fieldName, 'update', $field);

            // إضافة field assignment للـ Get method
            $content = $this->addFieldAssignment($content, $fieldName);
        }

        return $content;
    }

    /**
     * تحديث render method لإضافة شروط البحث للحقول الجديدة
     */
    private function updateRenderMethodLikeGenerator($content, $fields, $moduleName)
    {
        foreach ($fields as $field) {
            $fieldName = $field['name'];

            if (!($field['searchable'] ?? true) || $field['type'] === 'file') {
                continue;
            }

            // التحقق من عدم وجود شرط البحث مسبقاً
            if (strpos($content, "\$this->search['{$fieldName}']") !== false) {
                continue; // الشرط موجود بالفعل
            }

            if ($field['type'] === 'date') {
                $searchCondition = "->when(\$this->search['{$fieldName}'], function (\$query) {
                \$query->whereDate('{$fieldName}', \$this->search['{$fieldName}']);
            })";
            } else {
                $searchCondition = "->when(\$this->search['{$fieldName}'], function (\$query) {
                \$query->where('{$fieldName}', 'like', '%' . \$this->search['{$fieldName}'] . '%');
            })";
            }

            // البحث عن آخر ->when() condition وإضافة الشرط الجديد بعده
            $whenPattern = '/(->when\(\$this->search\[[\'"][^\'"]+[\'"].*?\}\))/s';
            if (preg_match_all($whenPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                // الحصول على آخر match
                $lastMatch = end($matches[0]);
                $insertPosition = $lastMatch[1] + strlen($lastMatch[0]);

                // إدراج الشرط الجديد
                $newContent = substr($content, 0, $insertPosition) .
                             "\n            " . $searchCondition .
                             substr($content, $insertPosition);
                $content = $newContent;
                $this->info("✅ تم إضافة شرط البحث: {$fieldName}");
            } else {
                // إذا لم توجد شروط when، ابحث عن orderBy وأضف قبلها
                $orderByPattern = '/(->orderBy\([^)]+\))/';
                if (preg_match($orderByPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    $insertPosition = $matches[0][1];
                    $newContent = substr($content, 0, $insertPosition) .
                                 "\n            " . $searchCondition . "\n            " .
                                 substr($content, $insertPosition);
                    $content = $newContent;
                    $this->info("✅ تم إضافة شرط البحث: {$fieldName}");
                }
            }
        }

        return $content;
    }

    /**
     * إضافة حقل لمصفوفة البيانات بطريقة مولد الوحدات
     */
    private function addToDataArrayLikeGenerator($content, $fieldName, $method, $field)
    {
        if ($method === 'store') {
            // For store method - look for create() call
            $pattern = "/(create\s*\(\s*array_merge\s*\(\s*\[)(.*?)(\],.*?\).*?\))/s";
            if (preg_match($pattern, $content, $matches)) {
                $data = $matches[2];
                if (strpos($data, "'{$fieldName}'") === false) {
                    $newField = "'{$fieldName}' => \$this->{$fieldName},";
                    $newData = rtrim(trim($data), ',') . ",\n                {$newField}";
                    $content = str_replace($matches[2], $newData, $content);
                }
            } else {
                // Try simple create pattern
                $pattern = "/(create\s*\(\s*\[)(.*?)(\s*\]\s*\))/s";
                if (preg_match($pattern, $content, $matches)) {
                    $data = $matches[2];
                    if (strpos($data, "'{$fieldName}'") === false) {
                        $newField = "'{$fieldName}' => \$this->{$fieldName},";
                        $newData = rtrim(trim($data), ',') . ",\n            {$newField}";
                        $content = str_replace($matches[2], $newData, $content);
                    }
                }
            }
        } else {
            // For update method
            $pattern = "/(\\\$updateData\s*=\s*\[)(.*?)(\s*\]\s*;)/s";
            if (preg_match($pattern, $content, $matches)) {
                $data = $matches[2];
                if (strpos($data, "'{$fieldName}'") === false) {
                    $newField = "'{$fieldName}' => \$this->{$fieldName},";
                    $newData = rtrim(trim($data), ',') . ",\n                {$newField}";
                    $content = str_replace($matches[2], $newData, $content);
                }
            }
        }

        return $content;
    }

    /**
     * تحديث Blade Views للحقول الجديدة
     */
    private function updateBladeViewsLikeGenerator($moduleName, $fields)
    {
        $this->info("🔄 تحديث Blade Views للوحدة: {$moduleName}");

        // 1. تحديث index view - إضافة أعمدة الجدول وحقول البحث
        $this->updateIndexView($moduleName, $fields);

        // 2. تحديث create/edit modals
        $this->updateModalsView($moduleName, $fields);
    }

    /**
     * تحديث index view لإضافة أعمدة الجدول وحقول البحث
     */
    private function updateIndexView($moduleName, $fields)
    {
        $viewPaths = [
            resource_path("views/livewire/" . strtolower($moduleName) . "/" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/backend/" . strtolower($moduleName) . "/index.blade.php"),
            resource_path("views/backend/" . strtolower(Str::plural($moduleName)) . "/index.blade.php"),
            resource_path("views/" . strtolower($moduleName) . "/index.blade.php"),
            resource_path("views/" . strtolower(Str::plural($moduleName)) . "/index.blade.php"),
        ];

        $viewPath = null;
        foreach ($viewPaths as $path) {
            if (File::exists($path)) {
                $viewPath = $path;
                break;
            }
        }

        if (!$viewPath) {
            $this->warn("⚠️ ملف العرض الرئيسي غير موجود للوحدة: {$moduleName}");
            $this->warn("تم البحث في المسارات التالية:");
            foreach ($viewPaths as $path) {
                $this->warn("  - " . $path);
            }
            return;
        }

        $this->info("✅ تم العثور على ملف العرض: " . basename($viewPath));

        $content = File::get($viewPath);

        // إضافة رؤوس أعمدة الجدول
        foreach ($fields as $field) {
            $arabicLabel = $field['ar_name'] ?? $field['comment'] ?? $field['name'];

            if ($field['type'] !== 'file') { // Skip file columns in table
                $headerColumn = "<th class=\"text-center\">{$arabicLabel}</th>";

                // البحث عن آخر <th> وإضافة العمود الجديد قبل عمود العمليات
                $pattern = '/(<th[^>]*>.*?<\/th>)(\s*<th[^>]*>\s*العمليات\s*<\/th>)/s';
                if (preg_match($pattern, $content) && strpos($content, $arabicLabel) === false) {
                    $content = preg_replace($pattern, "$1\n                        {$headerColumn}$2", $content, 1);
                }
            }
        }

        // إضافة خلايا البيانات في الجدول
        foreach ($fields as $field) {
            $fieldName = $field['name'];

            if ($field['type'] !== 'file') {
                if ($field['type'] === 'date') {
                    $dataCell = "<td class=\"text-center\">{{ \${$moduleName}->{$fieldName} ? \${$moduleName}->{$fieldName}->format('Y-m-d') : '--' }}</td>";
                } else {
                    $dataCell = "<td class=\"text-center\">{{ \${$moduleName}->{$fieldName} ?? '--' }}</td>";
                }

                // البحث عن آخر <td> وإضافة الخلية الجديدة قبل خلية العمليات
                $pattern = '/(<td[^>]*>.*?<\/td>)(\s*<td[^>]*>.*?العمليات.*?<\/td>)/s';
                if (preg_match($pattern, $content) && strpos($content, "\${$moduleName}->{$fieldName}") === false) {
                    $content = preg_replace($pattern, "$1\n                                {$dataCell}$2", $content, 1);
                }
            }
        }

        // إضافة حقول البحث
        foreach ($fields as $field) {
            if (!($field['searchable'] ?? true) || $field['type'] === 'file') {
                continue;
            }

            $fieldName = $field['name'];
            $arabicLabel = $field['ar_name'] ?? $field['comment'] ?? $fieldName;

            if ($field['type'] === 'date') {
                $searchInput = "
                <div class=\"col-md-3 mb-3\">
                    <label class=\"form-label\">{$arabicLabel}</label>
                    <input type=\"date\" class=\"form-control\" wire:model.debounce.300ms=\"search.{$fieldName}\" placeholder=\"البحث بـ{$arabicLabel}\">
                </div>";
            } else {
                $searchInput = "
                <div class=\"col-md-3 mb-3\">
                    <label class=\"form-label\">{$arabicLabel}</label>
                    <input type=\"text\" class=\"form-control\" wire:model.debounce.300ms=\"search.{$fieldName}\" placeholder=\"البحث بـ{$arabicLabel}\">
                </div>";
            }

            // البحث عن مكان إدراج حقول البحث
            $pattern = '/(<div class="row">.*?<\/div>)(\s*<div class="table-responsive">)/s';
            if (preg_match($pattern, $content) && strpos($content, "search.{$fieldName}") === false) {
                $content = preg_replace($pattern, "$1{$searchInput}$2", $content, 1);
            }
        }

        File::put($viewPath, $content);
        $this->info("✅ تم تحديث index view");
    }

    /**
     * تحديث create/edit modals لإضافة الحقول الجديدة
     */
    private function updateModalsView($moduleName, $fields)
    {
        $modalPaths = [
            // مسارات modals للوحدة مع التنويعات المختلفة
            resource_path("views/livewire/" . strtolower($moduleName) . "/modals/add-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower($moduleName) . "/modals/edit-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/modals/add-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/modals/edit-" . strtolower(Str::singular($moduleName)) . ".blade.php"),

            // مسارات مع kebab-case
            resource_path("views/livewire/" . Str::kebab($moduleName) . "/modals/add-" . Str::kebab(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . Str::kebab($moduleName) . "/modals/edit-" . Str::kebab(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . Str::kebab(Str::plural($moduleName)) . "/modals/add-" . Str::kebab(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . Str::kebab(Str::plural($moduleName)) . "/modals/edit-" . Str::kebab(Str::singular($moduleName)) . ".blade.php"),

            // مسارات backend أخرى محتملة
            resource_path("views/backend/" . strtolower($moduleName) . "/create.blade.php"),
            resource_path("views/backend/" . strtolower($moduleName) . "/edit.blade.php"),
            resource_path("views/" . strtolower($moduleName) . "/create.blade.php"),
            resource_path("views/" . strtolower($moduleName) . "/edit.blade.php"),
        ];

        $foundModals = [];
        foreach ($modalPaths as $modalPath) {
            if (File::exists($modalPath)) {
                $foundModals[] = $modalPath;
            }
        }

        if (empty($foundModals)) {
            $this->warn("⚠️ لم يتم العثور على ملفات Modal للوحدة: {$moduleName}");
            return;
        }

        foreach ($foundModals as $modalPath) {
            $this->info("🔄 تحديث Modal: " . basename($modalPath));

            $content = File::get($modalPath);
            $isEditModal = strpos($modalPath, 'edit') !== false;

            foreach ($fields as $field) {
                $fieldName = $field['name'];
                $arabicLabel = $field['ar_name'] ?? $field['comment'] ?? $fieldName;
                $required = ($field['required'] ?? false) ? 'required' : '';

                // تجنب إضافة الحقل إذا كان موجود بالفعل
                if (strpos($content, "wire:model=\"{$fieldName}\"") !== false ||
                    strpos($content, "wire:model='{$fieldName}'") !== false) {
                    continue;
                }

                // فحص إذا كان الحقل محسوب (من JSON أولاً، ثم من قاعدة البيانات)
                $isCalculated = false;
                $calculationFormula = '';

                // إعطاء أولوية لبيانات JSON
                if (isset($field['is_calculated']) && $field['is_calculated']) {
                    $isCalculated = true;
                    $calculationFormula = $field['calculation_formula'] ?? '';
                    $this->info("🧮 DEBUG: Field '{$fieldName}' found as calculated in JSON with formula: '{$calculationFormula}'");
                } else {
                    // إذا لم توجد في JSON، فحص قاعدة البيانات
                    try {
                        $dbField = ModuleField::where('module_name', $moduleName)
                                             ->where('field_name', $fieldName)
                                             ->first();
                        if ($dbField && $dbField->is_calculated) {
                            $isCalculated = true;
                            $calculationFormula = $dbField->calculation_formula;
                            $this->info("🧮 DEBUG: Field '{$fieldName}' found as calculated in database with formula: '{$calculationFormula}'");
                        } else {
                            $this->info("🧮 DEBUG: Field '{$fieldName}' is not calculated (JSON: " . json_encode($field['is_calculated'] ?? false) . ", DB: " . ($dbField ? $dbField->is_calculated : 'not found') . ")");
                        }
                    } catch (\Exception $e) {
                        // تجاهل الأخطاء
                        $this->info("🧮 DEBUG: Error checking database for field '{$fieldName}': " . $e->getMessage());
                    }
                }

                $fieldHtml = '';

                switch ($field['type']) {
                    case 'decimal':
                    case 'float':
                    case 'double':
                        if ($isCalculated) {
                            // حقل محسوب
                            $calculatorIcon = " <i class='mdi mdi-calculator text-success' title='حقل محسوب تلقائياً'></i>";
                            $fieldHtml = "
                            <div class=\"mb-3 col-md-6\">
                                        <div class=\"form-floating form-floating-outline\">
                                            <input wire:model='{$fieldName}' wire:input=\"calculateFields()\" type=\"text\"
                                                id=\"modal" . ucfirst($moduleName) . "{$fieldName}\" placeholder=\"123.45\"
                                                class=\"form-control @error('{$fieldName}') is-invalid is-filled @enderror bg-light text-muted\" readonly/>
                                            <label for=\"modal" . ucfirst($moduleName) . "{$fieldName}\">{$arabicLabel}{$calculatorIcon}</label>
                                        </div>
                                        @error('{$fieldName}')
                                            <small class='text-danger inputerror'> {{ \$message }} </small>
                                        @enderror
                                    </div>";
                        } else {
                            // حقل عادي
                            $fieldHtml = "
                            <div class=\"mb-3 col-md-6\">
                                        <div class=\"form-floating form-floating-outline\">
                                            <input wire:model='{$fieldName}' wire:input=\"calculateFields()\" type=\"text\"
                                                id=\"modal" . ucfirst($moduleName) . "{$fieldName}\" placeholder=\"123.45\"
                                                class=\"form-control @error('{$fieldName}') is-invalid is-filled @enderror\"/>
                                            <label for=\"modal" . ucfirst($moduleName) . "{$fieldName}\">{$arabicLabel}</label>
                                        </div>
                                        @error('{$fieldName}')
                                            <small class='text-danger inputerror'> {{ \$message }} </small>
                                        @enderror
                                    </div>";
                        }
                        break;

                    case 'text':
                    case 'string':
                        $fieldHtml = "
                <div class=\"col-md-12 mb-3\">
                    <label class=\"form-label\">{$arabicLabel} " . ($required ? '<span class="text-danger">*</span>' : '') . "</label>
                    <input type=\"text\" class=\"form-control\" wire:model=\"{$fieldName}\" placeholder=\"أدخل {$arabicLabel}\" {$required}>
                    @error('{$fieldName}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
                </div>";
                        break;

                    case 'email':
                        $fieldHtml = "
                <div class=\"col-md-12 mb-3\">
                    <label class=\"form-label\">{$arabicLabel} " . ($required ? '<span class="text-danger">*</span>' : '') . "</label>
                    <input type=\"email\" class=\"form-control\" wire:model=\"{$fieldName}\" placeholder=\"أدخل {$arabicLabel}\" {$required}>
                    @error('{$fieldName}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
                </div>";
                        break;

                    case 'number':
                        $fieldHtml = "
                <div class=\"col-md-12 mb-3\">
                    <label class=\"form-label\">{$arabicLabel} " . ($required ? '<span class="text-danger">*</span>' : '') . "</label>
                    <input type=\"number\" class=\"form-control\" wire:model=\"{$fieldName}\" placeholder=\"أدخل {$arabicLabel}\" {$required}>
                    @error('{$fieldName}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
                </div>";
                        break;

                    case 'date':
                        $fieldHtml = "
                <div class=\"col-md-12 mb-3\">
                    <label class=\"form-label\">{$arabicLabel} " . ($required ? '<span class="text-danger">*</span>' : '') . "</label>
                    <input type=\"date\" class=\"form-control flatpickr-date\" wire:model=\"{$fieldName}\" placeholder=\"أدخل {$arabicLabel}\" {$required}>
                    @error('{$fieldName}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
                </div>";
                        break;

                    case 'textarea':
                        $fieldHtml = "
                <div class=\"col-md-12 mb-3\">
                    <label class=\"form-label\">{$arabicLabel} " . ($required ? '<span class="text-danger">*</span>' : '') . "</label>
                    <textarea class=\"form-control\" wire:model=\"{$fieldName}\" rows=\"3\" placeholder=\"أدخل {$arabicLabel}\" {$required}></textarea>
                    @error('{$fieldName}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
                </div>";
                        break;

                    case 'checkbox':
                        $fieldHtml = "
                <div class=\"col-md-12 mb-3\">
                    <div class=\"form-check\">
                        <input class=\"form-check-input\" type=\"checkbox\" wire:model=\"{$fieldName}\" id=\"{$fieldName}\">
                        <label class=\"form-check-label\" for=\"{$fieldName}\">{$arabicLabel}</label>
                    </div>
                    @error('{$fieldName}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
                </div>";
                        break;

                    case 'file':
                        $fieldHtml = "
                <div class=\"col-md-12 mb-3\">
                    <label class=\"form-label\">{$arabicLabel} " . ($required ? '<span class=\"text-danger">*</span>' : '') . "</label>
                    <input type=\"file\" class=\"form-control\" wire:model=\"{$fieldName}\" {$required}>
                    @error('{$fieldName}') <span class=\"text-danger\">{{ \$message }}</span> @enderror
                </div>";
                        break;
                }

                if ($fieldHtml) {
                    // البحث عن المكان المناسب لإدراج الحقل
                    $insertPatterns = [
                        // قبل أزرار الحفظ (pattern 1)
                        '/(\s*<hr class="my-0">\s*<div class="text-center col-12 demo-vertical-spacing mb-n4">)/',
                        // قبل modal-footer (pattern 2)
                        '/(\s*<\/div>\s*<\/div>\s*<div class="modal-footer">)/',
                        // قبل نهاية form (pattern 3)
                        '/(\s*<\/form>\s*<\/div>)/',
                        // قبل نهاية أي container (pattern 4)
                        '/(\s*<\/div>\s*<\/div>\s*<\/div>)/'
                    ];

                    $inserted = false;
                    foreach ($insertPatterns as $pattern) {
                        if (preg_match($pattern, $content)) {
                            $content = preg_replace($pattern, $fieldHtml . "$1", $content, 1);
                            $inserted = true;
                            break;
                        }
                    }

                    if (!$inserted) {
                        // إذا لم نجد أي pattern، أدرج في نهاية الملف
                        $content = str_replace('</div>', $fieldHtml . '</div>', $content);
                    }
                }
            }

            File::put($modalPath, $content);
            $this->info("✅ تم تحديث " . basename($modalPath));
        }
    }

    /**
     * إضافة field assignment للـ Get method
     */
    private function addFieldAssignment($content, $fieldName)
    {
        // البحث عن GetEmployee أو Get method pattern
        $patterns = [
            '/(\$this->' . preg_quote($fieldName, '/') . '\s*=\s*\$this->\w+->' . preg_quote($fieldName, '/') . ';)/',
            '/(\$this->\w+\s*=\s*\$this->\w+->\w+;\s*(?=\n\s*\/\/|$))/m'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                if (strpos($content, "\$this->{$fieldName} = \$this->") === false) {
                    // إضافة assignment جديد بعد آخر assignment
                    $modelVariable = 'employee'; // افتراضي
                    if (preg_match('/\$this->(\w+)\s*=\s*\w+Model::find/', $content, $modelMatch)) {
                        $modelVariable = $modelMatch[1];
                    }

                    $newAssignment = "\$this->{$fieldName} = \$this->{$modelVariable}->{$fieldName};";
                    $content = str_replace($matches[0], $matches[0] . "\n        {$newAssignment}", $content);
                    $this->info("✅ تم إضافة field assignment: {$fieldName}");
                }
                break;
            }
        }

        return $content;
    }

    /**
     * إضافة validation rule لـ method محدد
     */
    private function addValidationRuleToMethod($content, $validationRule, $method)
    {
        // أنماط متعددة للبحث عن validation
        $patterns = [
            "/(function\s+{$method}\s*\([^)]*\).*?\\\$this->validate\s*\(\s*\[)(.*?)(\]\s*,\s*\[)/s",
            "/({$method}.*?\\\$this->validate\s*\(\s*\[)(.*?)(\]\s*,\s*\[)/s"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $beforeRules = $matches[1];
                $rules = trim($matches[2]);
                $afterRules = $matches[3];

                // التحقق من عدم وجود الحقل
                $fieldName = explode("'", $validationRule)[1];
                if (strpos($rules, "'{$fieldName}'") === false) {
                    $newRules = !empty($rules) ?
                        rtrim($rules, ',') . ",\n                {$validationRule}" :
                        "\n                {$validationRule}\n            ";

                    $newValidation = $beforeRules . $newRules . $afterRules;
                    $content = str_replace($matches[0], $newValidation, $content);
                    $this->info("✅ تم إضافة validation rule للـ {$method}: {$fieldName}");
                }
                break;
            }
        }
        return $content;
    }

    /**
     * إضافة validation message لـ method محدد
     */
    private function addValidationMessageToMethod($content, $validationMessage, $method)
    {
        // أنماط متعددة للبحث عن validation messages
        $patterns = [
            "/(function\s+{$method}\s*\([^)]*\).*?\\\$this->validate\s*\(.*?,\s*\[)(.*?)(\]\s*\))/s",
            "/({$method}.*?\\\$this->validate\s*\(.*?,\s*\[)(.*?)(\]\s*\))/s"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $beforeMessages = $matches[1];
                $messages = trim($matches[2]);
                $afterMessages = $matches[3];

                // التحقق من عدم وجود الرسالة
                $messageKey = explode("'", $validationMessage)[1];
                if (strpos($messages, $messageKey) === false) {
                    $newMessages = !empty($messages) ?
                        rtrim($messages, ',') . ",\n                {$validationMessage}" :
                        "\n                {$validationMessage}\n            ";

                    $newValidation = $beforeMessages . $newMessages . $afterMessages;
                    $content = str_replace($matches[0], $newValidation, $content);
                    $this->info("✅ تم إضافة validation message للـ {$method}");
                }
                break;
            }
        }
        return $content;
    }

    /**
     * تحديث العرض الرئيسي بطريقة مولد الوحدات
     */
    private function updateMainView($moduleName, $fields)
    {
        $viewPaths = [
            base_path("resources/views/livewire/" . strtolower($moduleName) . "/" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            base_path("resources/views/content/{$moduleName}/" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            base_path("resources/views/livewire/" . strtolower(Str::plural($moduleName)) . "/" . strtolower(Str::plural($moduleName)) . ".blade.php"),
        ];

        $viewPath = null;
        foreach ($viewPaths as $path) {
            if (File::exists($path)) {
                $viewPath = $path;
                break;
            }
        }

        if (!$viewPath) {
            $this->warn("⚠️ ملف العرض غير موجود للوحدة: {$moduleName}");
            return;
        }

        $this->info("🔄 تحديث ملف العرض: " . basename($viewPath));

        $content = File::get($viewPath);

        // 1. إضافة عناوين الجدول قبل عمود العمليات (بطريقة مولد الوحدات)
        $content = $this->addTableHeadersLikeGenerator($content, $fields);

        // 2. إضافة أعمدة البيانات قبل عمود العمليات (بطريقة مولد الوحدات)
        $content = $this->addTableColumnsLikeGenerator($content, $fields);

        // 3. إضافة حقول البحث قبل عمود العمليات (بطريقة مولد الوحدات)
        $content = $this->addSearchFieldsLikeGenerator($content, $fields);

        // 4. إضافة حقول للـ modals
        $content = $this->addModalFieldsLikeGenerator($moduleName, $content, $fields);

        File::put($viewPath, $content);
        $this->info("✅ تم تحديث ملف العرض بنجاح");
    }

    /**
     * إضافة عناوين الجدول بطريقة مولد الوحدات
     */
    private function addTableHeadersLikeGenerator($content, $fields)
    {
        foreach ($fields as $field) {
            $comment = $field['ar_name'] ?? $field['comment'] ?? $field['name'];

            // التحقق من عدم وجود العنوان
            if (!preg_match("/<th[^>]*class=\"text-center\"[^>]*>" . preg_quote($comment) . "<\/th>/", $content)) {
                $newHeader = "                                        <th class=\"text-center\">{$comment}</th>";

                // البحث عن عمود العمليات وإدراج قبله - بطرق متعددة
                $operationsPatterns = [
                    '/(\s*<th[^>]*class="text-center"[^>]*>العمليات<\/th>)/s',
                    '/(\s*<th[^>]*>العمليات<\/th>)/s',
                    '/(\s*<th[^>]*>العملية<\/th>)/s',
                    '/(\s*<th[^>]*>الإجراءات<\/th>)/s',
                ];

                foreach ($operationsPatterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $content = preg_replace($pattern, "\n{$newHeader}$1", $content, 1);
                        $this->info("✅ تم إضافة عنوان: {$comment}");
                        break;
                    }
                }
            }
        }

        return $content;
    }

    /**
     * إضافة أعمدة البيانات بطريقة مولد الوحدات
     */
    private function addTableColumnsLikeGenerator($content, $fields)
    {
        // اكتشاف اسم متغير البيانات من foreach loop
        $entityName = 'item';
        if (preg_match('/\@foreach\s*\(\s*\$\w+\s+as\s+\$(\w+)\s*\)/', $content, $matches)) {
            $entityName = $matches[1];
        }

        foreach ($fields as $field) {
            $fieldName = $field['name'];

            // التحقق من عدم وجود العمود
            if (!preg_match("/\\\$" . preg_quote($entityName) . "\['{$fieldName}'\]|\\\$" . preg_quote($entityName) . "->{$fieldName}/", $content)) {
                $newColumn = $this->generateColumnHtmlLikeGenerator($field, $entityName);

                // البحث عن عمود العمليات وإدراج قبله
                $operationsPatterns = [
                    '/(\s*<td[^>]*class="text-center"[^>]*>.*?العمليات.*?<\/td>)/s',
                    '/(\s*<td[^>]*>.*?العمليات.*?<\/td>)/s',
                    '/(\s*<td[^>]*>.*?العملية.*?<\/td>)/s',
                    '/(\s*<td[^>]*>.*?الإجراءات.*?<\/td>)/s',
                ];

                foreach ($operationsPatterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $content = preg_replace($pattern, "\n{$newColumn}$1", $content, 1);
                        $this->info("✅ تم إضافة عمود: {$fieldName}");
                        break;
                    }
                }
            }
        }

        return $content;
    }

    /**
     * إنشاء HTML للعمود بطريقة مولد الوحدات
     */
    private function generateColumnHtmlLikeGenerator($field, $entityName)
    {
        $fieldName = $field['name'];
        $fieldType = $field['type'];

        switch ($fieldType) {
            case 'checkbox':
            case 'boolean':
                $trueLabel = $field['checkbox_true_label'] ?? 'نعم';
                $falseLabel = $field['checkbox_false_label'] ?? 'لا';
                return "                                            <td class=\"text-center\">
                                                @if(\${$entityName}['{$fieldName}'] || \${$entityName}->{$fieldName})
                                                    <span class=\"badge bg-success\">{$trueLabel}</span>
                                                @else
                                                    <span class=\"badge bg-danger\">{$falseLabel}</span>
                                                @endif
                                            </td>";

            case 'date':
                return "                                            <td class=\"text-center\">
                                                @if(\${$entityName}['{$fieldName}'] ?? \${$entityName}->{$fieldName})
                                                    {{ \\Carbon\\Carbon::parse(\${$entityName}['{$fieldName}'] ?? \${$entityName}->{$fieldName})->format('Y/m/d') }}
                                                @else
                                                    -
                                                @endif
                                            </td>";

            case 'datetime':
                return "                                            <td class=\"text-center\">
                                                @if(\${$entityName}['{$fieldName}'] ?? \${$entityName}->{$fieldName})
                                                    {{ \\Carbon\\Carbon::parse(\${$entityName}['{$fieldName}'] ?? \${$entityName}->{$fieldName})->format('Y/m/d H:i') }}
                                                @else
                                                    -
                                                @endif
                                            </td>";

            case 'file':
                return "                                            <td class=\"text-center\">
                                                @if(\${$entityName}['{$fieldName}'] ?? \${$entityName}->{$fieldName})
                                                    <a href=\"{{ asset('storage/' . (\${$entityName}['{$fieldName}'] ?? \${$entityName}->{$fieldName})) }}\" target=\"_blank\" class=\"btn btn-sm btn-info\">
                                                        <i class=\"bx bx-file\"></i> عرض
                                                    </a>
                                                @else
                                                    <span class=\"text-muted\">لا يوجد</span>
                                                @endif
                                            </td>";

            case 'time':
                return "                                            <td class=\"text-center\">
                                                {{ \${$entityName}['{$fieldName}'] ?? \${$entityName}->{$fieldName} ?? '-' }}
                                            </td>";

            case 'month_year':
                return "                                            <td class=\"text-center\">
                                                {{ \${$entityName}['{$fieldName}'] ?? \${$entityName}->{$fieldName} ?? '-' }}
                                            </td>";

            default:
                return "                                            <td class=\"text-center\">
                                                {{ \${$entityName}['{$fieldName}'] ?? \${$entityName}->{$fieldName} ?? '-' }}
                                            </td>";
        }
    }

    /**
     * إضافة حقول البحث بطريقة مولد الوحدات
     */
    private function addSearchFieldsLikeGenerator($content, $fields)
    {
        foreach ($fields as $field) {
            if (!($field['searchable'] ?? true) || $field['type'] === 'file') {
                continue;
            }

            $fieldName = $field['name'];
            $comment = $field['ar_name'] ?? $field['comment'] ?? $fieldName;

            // تحديد نوع الحقل للبحث
            $inputType = 'text';
            $inputClasses = 'form-control text-center';
            $wireIgnore = '';

            if ($field['type'] === 'time') {
                $inputType = 'time';
            } elseif (in_array($field['type'], ['date', 'datetime', 'month_year'])) {
                $wireIgnore = ' wire:ignore';
                $inputClasses .= ' flatpickr-input';

                if ($field['type'] === 'datetime') {
                    $inputClasses .= ' flatpickr-datetime';
                } elseif ($field['type'] === 'month_year') {
                    $inputClasses .= ' flatpickr-month-year';
                } else {
                    $inputClasses .= ' flatpickr-date';
                }
            }

            // تخصيص placeholder للبحث حسب نوع الحقل
            $searchPlaceholder = $comment;
            if ($field['type'] === 'email') {
                $searchPlaceholder = 'name@example.com';
            } elseif ($field['type'] === 'integer' || $field['type'] === 'number') {
                $searchPlaceholder = '123';
            } elseif ($field['type'] === 'decimal') {
                $searchPlaceholder = '123.45';
            }

            // التحقق من عدم وجود حقل البحث
            if (!preg_match("/wire:model[^>]*search\.{$fieldName}/", $content)) {
                $newSearchField = "                                <th class=\"text-center\">
                                    <input{$wireIgnore} type=\"{$inputType}\" wire:model.debounce.300ms=\"search.{$fieldName}\"
                                           class=\"{$inputClasses}\" placeholder=\"{$searchPlaceholder}\"
                                           wire:key=\"search_{$fieldName}\">
                                </th>";

                // البحث عن مكان إدراج البحث (قبل <th></th> الخاص بالعمليات)
                $searchPatterns = [
                    '/(\s*<th[^>]*><\/th>\s*<\/tr>)/s',
                    '/(\s*<th><\/th>\s*<\/tr>)/s',
                ];

                foreach ($searchPatterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $content = preg_replace($pattern, "\n{$newSearchField}$1", $content, 1);
                        $this->info("✅ تم إضافة حقل بحث: {$comment}");
                        break;
                    }
                }
            }
        }

        return $content;
    }

    /**
     * إضافة حقول للـ modals بطريقة مولد الوحدات
     */
    private function addModalFieldsLikeGenerator($moduleName, $content, $fields)
    {
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $arabicName = $field['ar_name'] ?? $field['comment'] ?? $fieldName;

            // دمج معلومات الحقل مع معلومات قاعدة البيانات
            $enrichedField = $this->enrichFieldWithDatabaseInfo($field, $fieldName, $moduleName);

            // Generate field HTML for add modal
            $addFieldHtml = $this->generateModalFieldHtml($enrichedField, $arabicName, 'add', $moduleName);
            // Generate field HTML for edit modal
            $editFieldHtml = $this->generateModalFieldHtml($enrichedField, $arabicName, 'edit', $moduleName);

            // Try to find modals and add fields
            $content = $this->addFieldToModal($content, $addFieldHtml, 'add');
            $content = $this->addFieldToModal($content, $editFieldHtml, 'edit');
        }

        return $content;
    }

    /**
     * دمج معلومات الحقل مع معلومات قاعدة البيانات
     */
    private function enrichFieldWithDatabaseInfo($field, $fieldName, $moduleName)
    {
        try {
            // محاولة الحصول على معلومات الحقل من قاعدة البيانات
            $dbField = ModuleField::where('field_name', $fieldName)
                ->where('module_name', $moduleName)
                ->first();

            if ($dbField) {
                // دمج المعلومات (إعطاء الأولوية لقاعدة البيانات)
                $field['is_calculated'] = $dbField->is_calculated ?? ($field['is_calculated'] ?? false);
                $field['calculation_formula'] = $dbField->calculation_formula ?? ($field['calculation_formula'] ?? null);
                $field['required'] = $dbField->required ?? ($field['required'] ?? false);
                $field['unique'] = $dbField->unique ?? ($field['unique'] ?? false);

                // إضافة معلومات إضافية من قاعدة البيانات
                if ($dbField->is_calculated) {
                    $this->info("🧮 تم العثور على حقل محسوب من قاعدة البيانات: {$fieldName} = {$dbField->calculation_formula}");
                }
            } else {
                // إذا لم يوجد في قاعدة البيانات، استخدام قيم ملف JSON (الحالة الطبيعية عند إضافة حقول جديدة)
                $field['is_calculated'] = $field['is_calculated'] ?? false;
                $field['calculation_formula'] = $field['calculation_formula'] ?? null;

                // إضافة لوج للحقول المحسوبة الجديدة
                if ($field['is_calculated'] ?? false) {
                    $this->info("🆕 حقل محسوب جديد من JSON: {$fieldName} = " . ($field['calculation_formula'] ?? 'بدون معادلة'));
                }
            }
        } catch (\Exception $e) {
            // في حالة فشل الاستعلام، نستخدم قيم ملف JSON أو القيم الافتراضية
            $field['is_calculated'] = $field['is_calculated'] ?? false;
            $field['calculation_formula'] = $field['calculation_formula'] ?? null;
            $this->warn("⚠️ خطأ في قراءة معلومات الحقل من قاعدة البيانات: " . $e->getMessage());
        }

        return $field;
    }

    /**
     * إنشاء HTML للحقول في الـ modals
     */
    private function generateModalFieldHtml($field, $arabicName, $modalType = 'add', $moduleName = null)
    {
        $fieldName = $field['name'];
        $fieldType = $field['type'];
        $required = ($field['required'] ?? false) ? 'required' : '';

        // تحديد المعرف حسب نوع الـ modal
        $modalId = $modalType === 'edit' ? "modalEdit" : "modal";
        $modalPrefix = $modalType === 'edit' ? ucfirst($modalType) : '';

        switch ($fieldType) {
            case 'textarea':
                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <textarea wire:model.defer='{$fieldName}' class=\"form-control @error('{$fieldName}') is-invalid @enderror\"
                                        id=\"modal{$fieldName}\" placeholder=\"{$arabicName}\" {$required}></textarea>
                                    <label for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'date':
                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input wire:model.defer='{$fieldName}' type=\"date\" class=\"form-control @error('{$fieldName}') is-invalid @enderror\"
                                        id=\"modal{$fieldName}\" {$required}>
                                    <label for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'datetime':
                $fieldName = $field['name'];
                $isUsedInTimeCalc = $moduleName ? $this->isFieldUsedInTimeCalculation($fieldName, $moduleName) : false;
                $wireModel = $isUsedInTimeCalc ? "wire:model='{$fieldName}'" : "wire:model.defer='{$fieldName}'";
                $wireChange = $isUsedInTimeCalc ? " wire:change=\"calculateFields()\"" : "";

                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input {$wireModel} type=\"datetime-local\" class=\"form-control @error('{$fieldName}') is-invalid @enderror\"
                                        id=\"modal{$fieldName}\" {$required}{$wireChange}>
                                    <label for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'checkbox':
            case 'boolean':
                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-check form-check-primary\">
                                    <input class=\"form-check-input\" type=\"checkbox\" wire:model.defer='{$fieldName}' id=\"modal{$fieldName}\">
                                    <label class=\"form-check-label\" for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'file':
                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input wire:model.defer='{$fieldName}' type=\"file\" class=\"form-control @error('{$fieldName}') is-invalid @enderror\"
                                        id=\"modal{$fieldName}\" {$required} accept=\".jpg,.jpeg,.png,.pdf\">
                                    <label for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'select':
                $options = '';
                if (!empty($field['select_options'])) {
                    foreach ($field['select_options'] as $option) {
                        $options .= "\n                                        <option value=\"{$option}\">{$option}</option>";
                    }
                }

                // إضافة class للحقول الرقمية
                $numericClass = '';
                if (!empty($field['select_numeric_values'])) {
                    $numericClass = ' numeric-value';
                }

                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <select wire:model.defer='{$fieldName}' class=\"form-control{$numericClass} @error('{$fieldName}') is-invalid @enderror\"
                                        id=\"modal{$fieldName}\" {$required}>
                                        <option value=\"\">اختر {$arabicName}</option>{$options}
                                    </select>
                                    <label for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'time':
                $fieldName = $field['name'];
                $isUsedInTimeCalc = $moduleName ? $this->isFieldUsedInTimeCalculation($fieldName, $moduleName) : false;
                $wireModel = $isUsedInTimeCalc ? "wire:model='{$fieldName}'" : "wire:model.defer='{$fieldName}'";
                $wireChange = $isUsedInTimeCalc ? " wire:change=\"calculateFields()\"" : "";

                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input {$wireModel} type=\"time\" class=\"form-control @error('{$fieldName}') is-invalid @enderror\"
                                        id=\"modal{$fieldName}\" {$required}{$wireChange}>
                                    <label for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'month_year':
                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input wire:ignore wire:model.defer='{$fieldName}' type=\"text\" class=\"form-control flatpickr-input flatpickr-month-year @error('{$fieldName}') is-invalid @enderror\"
                                        id=\"modal{$fieldName}\" placeholder=\"{$arabicName}\" {$required}>
                                    <label for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'email':
                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input wire:model.defer='{$fieldName}' type=\"email\" class=\"form-control @error('{$fieldName}') is-invalid @enderror\"
                                        id=\"modal{$fieldName}\" placeholder=\"name@example.com\" {$required}>
                                    <label for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'integer':
            case 'number':
                // التحقق من الحقول المحسوبة
                if ($field['is_calculated'] ?? false) {
                    $wireInput = ' wire:input="calculateFields()"';
                    $readonlyClass = ' bg-light text-muted';
                    $readonly = ' readonly';
                    $calculatorIcon = " <i class='mdi mdi-calculator text-success' title='حقل محسوب تلقائياً'></i>";
                } else {
                    $wireInput = '';
                    $readonlyClass = '';
                    $readonly = '';
                    $calculatorIcon = '';
                }

                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input wire:model='{$fieldName}'{$wireInput} type=\"number\" class=\"form-control @error('{$fieldName}') is-invalid is-filled @enderror{$readonlyClass}\"
                                        id=\"{$modalId}{$modalPrefix}{$fieldName}\" placeholder=\"123\" {$required}{$readonly}>
                                    <label for=\"{$modalId}{$modalPrefix}{$fieldName}\">{$arabicName}{$calculatorIcon}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'decimal':
                // التحقق من الحقول المحسوبة
                if ($field['is_calculated'] ?? false) {
                    $wireInput = ' wire:input="calculateFields()"';
                    $readonlyClass = ' bg-light text-muted';
                    $readonly = ' readonly';
                    $calculatorIcon = " <i class='mdi mdi-calculator text-success' title='حقل محسوب تلقائياً'></i>";
                } else {
                    $wireInput = '';
                    $readonlyClass = '';
                    $readonly = '';
                    $calculatorIcon = '';
                }

                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input wire:model='{$fieldName}'{$wireInput} type=\"text\" class=\"form-control @error('{$fieldName}') is-invalid is-filled @enderror{$readonlyClass}\"
                                        id=\"{$modalId}{$modalPrefix}{$fieldName}\" placeholder=\"123.45\" {$required}{$readonly}>
                                    <label for=\"{$modalId}{$modalPrefix}{$fieldName}\">{$arabicName}{$calculatorIcon}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            case 'text':
                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <textarea wire:model.defer='{$fieldName}' class=\"form-control h-px-100 @error('{$fieldName}') is-invalid @enderror\"
                                        id=\"modal{$fieldName}\" placeholder=\"{$arabicName}\" {$required}></textarea>
                                    <label for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";

            default: // string
                $maxAttr = !empty($field['max']) ? "maxlength=\"{$field['max']}\"" : '';

                return "                            <div class=\"mb-3 col\">
                                <div class=\"form-floating form-floating-outline\">
                                    <input wire:model.defer='{$fieldName}' type=\"text\" class=\"form-control @error('{$fieldName}') is-invalid @enderror\"
                                        id=\"modal{$fieldName}\" placeholder=\"{$arabicName}\" {$required} {$maxAttr}>
                                    <label for=\"modal{$fieldName}\">{$arabicName}</label>
                                </div>
                                @error('{$fieldName}') <small class=\"text-danger\">{{ \$message }}</small> @enderror
                            </div>";
        }
    }

    /**
     * إضافة حقل للـ modal
     */
    private function addFieldToModal($content, $fieldHtml, $modalType)
    {
        // Try to find the modal form and add field
        $patterns = [
            "/{$modalType}.*?form.*?<div class=\"row\">(.*?)<\/div>.*?<\/form>/s",
            "/{$modalType}.*?<div class=\"row\">(.*?)<\/div>.*?<\/div>/s"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $formContent = $matches[1];
                if (strpos($formContent, $fieldHtml) === false) {
                    $newFormContent = $formContent . "\n" . $fieldHtml;
                    $content = str_replace($matches[1], $newFormContent, $content);
                    break;
                }
            }
        }

        return $content;
    }

    /**
     * تحديث Views - مبني على مولد الوحدات
     */
    private function updateViews($moduleName, $fields, $advancedFeatures)
    {
        $this->info("🔄 تحديث Views للوحدة: {$moduleName}");

        // تحديث العرض الرئيسي
        $this->updateMainView($moduleName, $fields);

        $this->info("✅ تم تحديث Views بـ " . count($fields) . " حقل");
    }

    /**
     * تشغيل Migration
     */
    private function runMigration()
    {
        try {
            $this->info("🔄 تشغيل Migration...");

            // تشغيل جميع migrations الجديدة
            $exitCode = Artisan::call('migrate', ['--force' => true]);

            if ($exitCode === 0) {
                $this->info("✅ تم تشغيل Migration بنجاح");
            } else {
                $output = Artisan::output();
                throw new \Exception("فشل في تشغيل Migration - كود الخروج: {$exitCode}, المخرجات: {$output}");
            }
        } catch (\Exception $e) {
            $this->error("❌ خطأ في تشغيل Migration: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * التعامل مع migrations القديمة للوحدة
     */
    private function handleOldMigrations($moduleName)
    {
        $tableName = strtolower(Str::plural($moduleName));
        $this->info("🔍 البحث عن migrations قديمة للجدول: {$tableName}");

        // البحث عن جميع migrations للوحدة
        $migrationPath = database_path('migrations');
        $migrationFiles = glob($migrationPath . '/*_create_' . $tableName . '_table.php');

        if (empty($migrationFiles)) {
            $this->info("📝 لم يتم العثور على migrations قديمة");
            return;
        }

        $this->info("🔍 تم العثور على " . count($migrationFiles) . " migration قديم");

        // إسقاط الجدول إذا كان موجوداً
        try {
            if (Schema::hasTable($tableName)) {
                $this->info("🗑️ إسقاط الجدول الموجود: {$tableName}");
                Schema::dropIfExists($tableName);
            }

            // حذف جميع migrations للجدول (سواء كان واحد أو أكثر)
            foreach ($migrationFiles as $migrationFile) {
                $migrationName = basename($migrationFile, '.php');

                // حذف السجل من جدول migrations
                try {
                    DB::table('migrations')->where('migration', $migrationName)->delete();
                    $this->info("🗑️ تم حذف سجل migration: {$migrationName}");
                } catch (\Exception $e) {
                    $this->warn("⚠️ لم يتم العثور على سجل migration: {$migrationName}");
                }

                // حذف الملف
                File::delete($migrationFile);
                $this->info("🗑️ تم حذف ملف migration: " . basename($migrationFile));
            }

            $this->info("✅ تم تنظيف جميع migrations والجدول القديم");

        } catch (\Exception $e) {
            $this->warn("⚠️ خطأ في إسقاط الجدول: " . $e->getMessage());
        }
    }

    /**
     * استخراج معلومات الحقول من Migration الموجود
     */
    private function getFieldsInfoFromMigration($moduleName)
    {
        $tableName = strtolower(Str::plural($moduleName));
        $migrationPath = database_path('migrations');
        $migrationFiles = glob($migrationPath . '/*_create_' . $tableName . '_table.php');

        if (empty($migrationFiles)) {
            return [];
        }

        // أخذ أحدث migration
        rsort($migrationFiles);
        $latestMigration = $migrationFiles[0];

        $migrationContent = File::get($latestMigration);
        $fieldsInfo = [];

        // استخراج تعريفات الحقول من Migration
        if (preg_match('/Schema::create.*?\{(.*?)\}/s', $migrationContent, $matches)) {
            $schemaContent = $matches[1];

            // البحث عن تعريفات الحقول
            preg_match_all('/\$table->(\w+)\(\'([^\']+)\'\).*?->comment\(\'([^\']*)\'\)/m', $schemaContent, $fieldMatches, PREG_SET_ORDER);

            foreach ($fieldMatches as $match) {
                $fieldType = $match[1]; // مثل: text, string, date
                $fieldName = $match[2]; // اسم الحقل
                $comment = $match[3];   // التعليق

                // تجاهل الحقول الأساسية
                if (in_array($fieldName, ['user_id', 'id', 'created_at', 'updated_at'])) {
                    continue;
                }

                $fieldsInfo[$fieldName] = [
                    'type' => $fieldType,
                    'comment' => $comment
                ];
            }

            // البحث عن حقول بدون comment
            preg_match_all('/\$table->(\w+)\(\'([^\']+)\'\)(?!.*comment)/m', $schemaContent, $noCommentMatches, PREG_SET_ORDER);

            foreach ($noCommentMatches as $match) {
                $fieldType = $match[1];
                $fieldName = $match[2];

                // تجاهل الحقول الأساسية والحقول التي لها comment بالفعل
                if (in_array($fieldName, ['user_id', 'id', 'created_at', 'updated_at']) || isset($fieldsInfo[$fieldName])) {
                    continue;
                }

                $fieldsInfo[$fieldName] = [
                    'type' => $fieldType,
                    'comment' => ''
                ];
            }
        }

        return $fieldsInfo;
    }

    /**
     * تحويل نوع قاعدة البيانات إلى نوع حقل للنموذج
     */
    private function mapDatabaseTypeToFieldType($dbType)
    {
        $mapping = [
            'string' => 'text',
            'text' => 'text',
            'date' => 'date',
            'datetime' => 'datetime',
            'time' => 'time',
            'integer' => 'number',
            'bigInteger' => 'number',
            'decimal' => 'number',
            'float' => 'number',
            'boolean' => 'checkbox',
            'json' => 'textarea',
            'longText' => 'textarea'
        ];

        return $mapping[$dbType] ?? 'text';
    }

    /**
     * حفظ تكوين الحقول للوحدة
     */
    private function saveModuleFieldsConfiguration($moduleName, $fields)
    {
        $this->logOperation('debug', 'بدء حفظ تكوين الحقول', [
            'module_name' => $moduleName,
            'fields_count' => count($fields)
        ]);

        $configPath = storage_path("app/hmvc-modules-config");

        if (!File::exists($configPath)) {
            File::makeDirectory($configPath, 0755, true);
            $this->logOperation('debug', 'تم إنشاء مجلد التكوين', ['config_path' => $configPath]);
        }

        $configFile = $configPath . "/{$moduleName}_fields.json";

        try {
            File::put($configFile, json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("💾 تم حفظ تكوين الحقول في: {$configFile}");
            $this->logOperation('info', 'تم حفظ تكوين الحقول بنجاح', [
                'config_file' => $configFile,
                'fields_saved' => $fields
            ]);
        } catch (\Exception $e) {
            $this->warn("⚠️ فشل في حفظ تكوين الحقول: " . $e->getMessage());
            $this->logOperation('error', 'فشل في حفظ تكوين الحقول', [
                'config_file' => $configFile,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * قراءة تكوين الحقول المحفوظ للوحدة
     */
    private function loadModuleFieldsConfiguration($moduleName)
    {
        // البحث في مجلدات متعددة
        $configPaths = [
            storage_path("app/hmvc-modules-config/{$moduleName}_fields.json"),
            storage_path("app/modules_config/{$moduleName}_fields.json"),
            storage_path("app/modules_config/" . strtolower($moduleName) . ".json"),
        ];

        foreach ($configPaths as $configFile) {
            if (File::exists($configFile)) {
                $this->logOperation('debug', 'تم العثور على ملف التكوين', ['config_file' => $configFile]);

                try {
                    $content = File::get($configFile);
                    $config = json_decode($content, true);

                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($config)) {
                        $this->logOperation('error', 'خطأ في تحليل JSON', ['error' => json_last_error_msg()]);
                        continue;
                    }

                    // إذا كان الملف يحتوي على مفتاح 'fields', استخدمه، وإلا استخدم المحتوى مباشرة
                    $fields = isset($config['fields']) ? $config['fields'] : $config;

                    $this->logOperation('info', 'تم تحميل تكوين الحقول بنجاح', [
                        'config_file' => $configFile,
                        'fields_count' => count($fields)
                    ]);

                    return $fields;
                } catch (\Exception $e) {
                    $this->logOperation('error', 'خطأ في قراءة ملف التكوين', [
                        'config_file' => $configFile,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }
        }

        $this->logOperation('debug', 'لم يتم العثور على أي ملف تكوين', ['searched_paths' => $configPaths]);
        return [];
    }

    /**
     * تطبيق تصحيحات ما بعد الإنشاء للحقول المخصصة
     */
    private function applyPostGenerationFixes($moduleName, $fields)
    {
        $this->info("🔧 تطبيق تصحيحات الحقول المخصصة...");
        $this->logOperation('debug', 'بدء تطبيق تصحيحات ما بعد الإنشاء', [
            'module_name' => $moduleName,
            'fields_to_fix' => $fields
        ]);

        // 1. حفظ الحقول في قاعدة البيانات مع الحفاظ على خصائص الحقول المحسوبة
        $this->saveFieldsToDatabase($moduleName, $fields);

        // 2. تصحيح ملفات العرض (Views)
        $this->fixViewFiles($moduleName, $fields);

        $this->info("✅ تم تطبيق جميع التصحيحات بنجاح");
        $this->logOperation('info', 'تم تطبيق جميع التصحيحات بنجاح');
    }

    /**
     * حفظ الحقول في قاعدة البيانات مع الحفاظ على خصائص الحقول المحسوبة
     */
    private function saveFieldsToDatabase($moduleName, $fields)
    {
        $this->info("💾 حفظ الحقول في قاعدة البيانات...");
        $this->logOperation('debug', 'بدء حفظ الحقول في قاعدة البيانات', [
            'module_name' => $moduleName,
            'fields_count' => count($fields)
        ]);

        try {
            // إنشاء اسم الجدول والحصول على الاسم العربي
            $tableName = Str::snake(Str::plural($moduleName));
            $moduleArabicName = $this->getModuleArabicNameFromConfig($moduleName);

            // استخدام دالة ModuleField للحفظ مع الحفاظ على جميع الخصائص
            ModuleField::saveFieldsFromGenerator($moduleName, $fields, 'regenerate', $tableName, $moduleArabicName);

            // تحديث معلومات الوحدة الأساسية لجميع الحقول الموجودة
            ModuleField::updateModuleInfo($moduleName, $tableName, $moduleArabicName);

            $this->info("✅ تم حفظ " . count($fields) . " حقل في قاعدة البيانات");
            $this->logOperation('info', 'تم حفظ الحقول في قاعدة البيانات بنجاح', [
                'module_name' => $moduleName,
                'fields_saved' => count($fields)
            ]);

        } catch (\Exception $e) {
            $this->warn("⚠️ فشل في حفظ الحقول في قاعدة البيانات: " . $e->getMessage());
            $this->logOperation('error', 'فشل في حفظ الحقول في قاعدة البيانات', [
                'module_name' => $moduleName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * تصحيح ملفات العرض للحقول المخصصة
     */
    private function fixViewFiles($moduleName, $fields)
    {
        $viewFiles = [
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/modals/add-" . strtolower(Str::singular($moduleName)) . ".blade.php"),
            resource_path("views/livewire/" . strtolower(Str::plural($moduleName)) . "/modals/edit-" . strtolower(Str::singular($moduleName)) . ".blade.php")
        ];

        $this->logOperation('debug', 'بدء تصحيح ملفات العرض', [
            'view_files' => $viewFiles,
            'fields_to_process' => count($fields)
        ]);

        foreach ($viewFiles as $viewFile) {
            if (File::exists($viewFile)) {
                $this->info("🔧 تصحيح الملف: " . basename($viewFile));
                $this->logOperation('debug', 'بدء تصحيح ملف عرض', ['view_file' => $viewFile]);
                $this->fixViewFile($viewFile, $fields);
            } else {
                $this->logOperation('warning', 'ملف العرض غير موجود', ['view_file' => $viewFile]);
            }
        }
    }

    /**
     * تصحيح ملف عرض واحد
     */
    private function fixViewFile($filePath, $fields)
    {
        $content = File::get($filePath);
        $originalContent = $content;
        $fixesApplied = [];

        foreach ($fields as $field) {
            if ($field['type'] === 'select' && (isset($field['select_options']) || isset($field['options']))) {
                // تصحيح حقل Select مع خيارات ثابتة أو مرتبط بقاعدة البيانات
                $beforeFix = $content;
                $content = $this->fixSelectField($content, $field);
                if ($beforeFix !== $content) {
                    $optionsData = $field['select_options'] ?? $field['options'] ?? [];
                    $fixesApplied[] = [
                        'field_name' => $field['name'],
                        'fix_type' => ($field['select_source'] ?? 'manual') === 'database' ? 'select_with_database' : 'select_with_options',
                        'options_count' => count($optionsData),
                        'select_source' => $field['select_source'] ?? 'manual'
                    ];
                }
            } elseif ($field['type'] === 'select_db') {
                // تصحيح حقل Select مرتبط بقاعدة البيانات (النوع القديم)
                $beforeFix = $content;
                $content = $this->fixSelectDbField($content, $field);
                if ($beforeFix !== $content) {
                    $fixesApplied[] = [
                        'field_name' => $field['name'],
                        'fix_type' => 'select_with_database',
                        'relation_table' => $field['relation_table'] ?? '',
                        'relation_column' => $field['relation_column'] ?? ''
                    ];
                }
            }
        }

        // حفظ الملف إذا حدثت تغييرات
        if ($originalContent !== $content) {
            File::put($filePath, $content);
            $this->logOperation('info', 'تم تطبيق تصحيحات على ملف العرض', [
                'file_path' => $filePath,
                'fixes_applied' => $fixesApplied,
                'total_fixes' => count($fixesApplied)
            ]);
        } else {
            $this->logOperation('debug', 'لم تكن هناك حاجة لتصحيحات على ملف العرض', [
                'file_path' => $filePath
            ]);
        }
    }

    /**
     * تصحيح حقل Select مع خيارات ثابتة
     */
    private function fixSelectField($content, $field)
    {
        $fieldName = $field['name'];
        $arabicName = $field['ar_name'];
        $options = $field['select_options'] ?? $field['options'] ?? [];

        // إنشاء خيارات HTML
        $optionsHtml = '';
        foreach ($options as $option) {
            // دعم كلا الصيغتين: array من strings أو array من objects
            if (is_array($option) && isset($option['key']) && isset($option['value'])) {
                // صيغة object مع key/value
                $optionsHtml .= "\n                                            <option value=\"{$option['key']}\">{$option['value']}</option>";
            } elseif (is_string($option)) {
                // صيغة string بسيط
                $optionsHtml .= "\n                                            <option value=\"{$option}\">{$option}</option>";
            }
        }

        // التحقق من نوع مصدر الخيارات
        $selectSource = $field['select_source'] ?? 'manual';

        if ($selectSource === 'database') {
            // للحقول المرتبطة بقاعدة البيانات، استخدم منطق قاعدة البيانات
            $tableName = $field['related_table'] ?? '';
            $columnName = $field['related_display'] ?? 'name';

            if ($tableName) {
                $modelClass = ucfirst(Str::singular($tableName));
                $pluralModelClass = ucfirst(Str::plural($tableName));

                $optionsHtml = "
                                            @if(class_exists('App\\Models\\{$pluralModelClass}\\{$pluralModelClass}'))
                                        @foreach(App\\Models\\{$pluralModelClass}\\{$pluralModelClass}::all() as \$item)
                                            <option value=\"{{ \$item->id }}\">{{ \$item->{$columnName} }}</option>
                                        @endforeach
                                    @elseif(class_exists('App\\Models\\{$modelClass}\\{$modelClass}'))
                                        @foreach(App\\Models\\{$modelClass}\\{$modelClass}::all() as \$item)
                                            <option value=\"{{ \$item->id }}\">{{ \$item->{$columnName} }}</option>
                                        @endforeach
                                    @endif";
            }
        }

        // البحث عن select field الموجود وتحديث options
        $selectPattern = '/(<select[^>]*wire:model\.defer=[\'"]\s*' . $fieldName . '\s*[\'"][^>]*>[\s\S]*?<option value="">اختر[^<]*<\/option>)([\s\S]*?)(<\/select>)/';

        if (preg_match($selectPattern, $content)) {
            $replacement = '$1' . $optionsHtml . "\n                                        " . '$3';
            $content = preg_replace($selectPattern, $replacement, $content, 1);
        } else {
            // إذا لم يجد select، ابحث عن input وحوله إلى select
            $inputPattern = '/(<div[^>]*class="form-floating[^"]*"[^>]*>\s*<input[^>]*wire:model\.defer=[\'"]\s*' . $fieldName . '\s*[\'"][^>]*>[\s\S]*?<label[^>]*>[^<]*<\/label>\s*<\/div>)/';

            $selectHtml = <<<HTML
<div class="form-floating form-floating-outline" wire:ignore>
                                        <select wire:model.defer='{$fieldName}'
                                            id="modalEmployee{$fieldName}"
                                            class="form-select @error('{$fieldName}') is-invalid is-filled @enderror">
                                            <option value="">اختر {$arabicName}</option>{$optionsHtml}
                                        </select>
                                        <label for="modalEmployee{$fieldName}">{$arabicName}</label>
                                    </div>
HTML;

            $content = preg_replace($inputPattern, $selectHtml, $content, 1);
        }

        return $content;
    }

    /**
     * تصحيح حقل Select مرتبط بقاعدة البيانات
     */
    private function fixSelectDbField($content, $field)
    {
        $fieldName = $field['name'];
        $arabicName = $field['ar_name'];
        $tableName = $field['related_table'] ?? $field['relation_table'] ?? '';
        $columnName = $field['related_display'] ?? $field['relation_column'] ?? 'name';

        // تحديد اسم النموذج المحتمل
        $modelClass = ucfirst(Str::singular($tableName));
        $pluralModelClass = ucfirst(Str::plural($tableName));

        // إنشاء HTML للـ select المرتبط بقاعدة البيانات
        $selectHtml = <<<HTML
<div class="form-floating form-floating-outline" wire:ignore>
                                        <select wire:model.defer='{$fieldName}'
                                            id="modalEmployee{$fieldName}"
                                            class="form-select @error('{$fieldName}') is-invalid is-filled @enderror">
                                            <option value="">اختر {$arabicName}</option>
                                            @if(class_exists('App\\Models\\{$pluralModelClass}\\{$pluralModelClass}'))
                                        @foreach(App\\Models\\{$pluralModelClass}\\{$pluralModelClass}::all() as \$item)
                                            <option value="{{ \$item->id }}">{{ \$item->{$columnName} }}</option>
                                        @endforeach
                                    @elseif(class_exists('App\\Models\\{$modelClass}\\{$modelClass}'))
                                        @foreach(App\\Models\\{$modelClass}\\{$modelClass}::all() as \$item)
                                            <option value="{{ \$item->id }}">{{ \$item->{$columnName} }}</option>
                                        @endforeach
                                    @endif
                                        </select>
                                        <label for="modalEmployee{$fieldName}">{$arabicName}</label>
                                    </div>
HTML;

        // البحث عن input field وتحويله إلى select
        $inputPattern = '/(<div[^>]*>\s*<input[^>]*wire:model\.defer=[\'"]\s*' . $fieldName . '\s*[\'"][^>]*>[\s\S]*?<\/div>)/';

        return preg_replace($inputPattern, $selectHtml, $content, 1);
    }

    /**
     * حفظ نسخة احتياطية من تكوين الحقول للمقارنة
     */
    private function saveFieldsBackup($moduleName, $beforeFields, $afterFields)
    {
        $backupPath = storage_path("app/hmvc-modules-backups");

        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y_m_d_H_i_s');
        $backupData = [
            'session_id' => $this->sessionId,
            'timestamp' => Carbon::now()->toISOString(),
            'module_name' => $moduleName,
            'operation' => 'field_modification',
            'before_fields' => $beforeFields,
            'after_fields' => $afterFields,
            'changes_summary' => [
                'fields_before_count' => count($beforeFields),
                'fields_after_count' => count($afterFields),
                'new_fields_added' => count($afterFields) - count($beforeFields),
                'detailed_comparison' => $this->generateDetailedComparison($beforeFields, $afterFields)
            ]
        ];

        $backupFile = $backupPath . "/{$moduleName}_backup_{$timestamp}.json";

        try {
            File::put($backupFile, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->logOperation('info', 'تم حفظ نسخة احتياطية من تكوين الحقول', [
                'backup_file' => $backupFile,
                'backup_data' => $backupData
            ]);
        } catch (\Exception $e) {
            $this->logOperation('error', 'فشل في حفظ النسخة الاحتياطية', [
                'backup_file' => $backupFile,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * توليد مقارنة مفصلة بين مجموعتي حقول
     */
    private function generateDetailedComparison($beforeFields, $afterFields)
    {
        $comparison = [
            'preserved_fields' => [],
            'modified_fields' => [],
            'new_fields' => [],
            'removed_fields' => []
        ];

        // البحث عن الحقول المحذوفة
        foreach ($beforeFields as $beforeField) {
            $found = false;
            foreach ($afterFields as $afterField) {
                if ($beforeField['name'] === $afterField['name']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $comparison['removed_fields'][] = $beforeField;
            }
        }

        // البحث عن الحقول الجديدة والمعدلة
        foreach ($afterFields as $afterField) {
            $found = false;
            $beforeField = null;

            foreach ($beforeFields as $before) {
                if ($before['name'] === $afterField['name']) {
                    $found = true;
                    $beforeField = $before;
                    break;
                }
            }

            if (!$found) {
                // حقل جديد
                $comparison['new_fields'][] = $afterField;
            } else {
                // حقل موجود - فحص التعديلات
                $changes = $this->compareFields($beforeField, $afterField);
                if (!empty($changes)) {
                    $comparison['modified_fields'][] = [
                        'field_name' => $afterField['name'],
                        'changes' => $changes,
                        'before' => $beforeField,
                        'after' => $afterField
                    ];
                } else {
                    $comparison['preserved_fields'][] = $afterField;
                }
            }
        }

        return $comparison;
    }

    /**
     * إضافة routes الـ PDF والطباعة المباشرة إلى web.php
     */
    private function addPdfRoutesToWebPhp($moduleName)
    {
        $webRoutePath = base_path('routes/web.php');

        if (!File::exists($webRoutePath)) {
            $this->warn("⚠️ ملف web.php غير موجود");
            return;
        }

        $content = File::get($webRoutePath);
        $singularName = Str::singular($moduleName);

        // التحقق من وجود use statements
        $useStatements = [
            "use App\\Http\\Controllers\\{$moduleName}\\{$singularName}TcpdfExportController;",
            "use App\\Http\\Controllers\\{$moduleName}\\{$singularName}PrintController;"
        ];

        $useStatementsToAdd = [];
        foreach ($useStatements as $useStatement) {
            if (strpos($content, $useStatement) === false) {
                $useStatementsToAdd[] = $useStatement;
            }
        }

        // إضافة use statements إذا لم تكن موجودة
        if (!empty($useStatementsToAdd)) {
            // البحث عن مكان إدراج use statements (بعد آخر use statement موجود)
            $pattern = '/(use\s+[^;]+;)(\s*\n)/';
            preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

            if (!empty($matches[0])) {
                $lastUseOffset = end($matches[0])[1] + strlen(end($matches[0])[0]);
                $newUseStatements = implode("\n", $useStatementsToAdd) . "\n";
                $content = substr_replace($content, $newUseStatements, $lastUseOffset, 0);
            }
        }

        // إضافة routes إذا لم تكن موجودة
        $routes = [
            "Route::GET('{$moduleName}/export-pdf-tcpdf', [{$singularName}TcpdfExportController::class, 'exportPdf'])->name('{$moduleName}.export.pdf.tcpdf');",
            "Route::GET('{$moduleName}/print-view', [{$singularName}PrintController::class, 'printView'])->name('{$moduleName}.print.view');"
        ];

        $routesToAdd = [];
        foreach ($routes as $route) {
            if (strpos($content, $route) === false) {
                $routesToAdd[] = $route;
            }
        }

        if (!empty($routesToAdd)) {
            // البحث عن نهاية الملف وإضافة routes قبل النهاية
            $newRoutes = "\n\n\n" . implode("\n", $routesToAdd);
            $content = rtrim($content) . $newRoutes;
        }

        // حفظ التغييرات إذا حدثت
        if (!empty($useStatementsToAdd) || !empty($routesToAdd)) {
            File::put($webRoutePath, $content);
            $this->info("✅ تم إضافة " . (count($useStatementsToAdd) + count($routesToAdd)) . " سطر لـ web.php");

            if (!empty($useStatementsToAdd)) {
                $this->info("📄 Use statements مضافة: " . count($useStatementsToAdd));
            }
            if (!empty($routesToAdd)) {
                $this->info("🚏 Routes مضافة: " . count($routesToAdd));
            }
        } else {
            $this->info("ℹ️ Routes الـ PDF موجودة مسبقاً في web.php");
        }
    }

    /**
     * إنشاء دوال العمليات الحسابية
     */
    protected function generateCalculationMethods($fields)
    {
        // التحقق من وجود حقول محسوبة
        $hasCalculatedFields = false;
        foreach ($fields as $field) {
            if ($field['is_calculated'] ?? false) {
                $hasCalculatedFields = true;
                break;
            }
        }

        if (!$hasCalculatedFields) {
            return '';
        }

        $calculatedFieldsMethods = "
    /**
     * حساب قيم الحقول المحسوبة
     */
    public function calculateFields()
    {
        try {";

        foreach ($fields as $field) {
            if ($field['is_calculated'] ?? false) {
                $fieldName = $field['name'];
                $calculationType = $field['calculation_type'] ?? 'none';

                if ($calculationType === 'time_diff') {
                    // حساب فرق الوقت
                    $timeFromField = $field['time_from_field'] ?? '';
                    $timeToField = $field['time_to_field'] ?? '';
                    $unit = $field['time_diff_unit'] ?? 'minutes';
                    $absoluteValue = $field['absolute_value'] ?? false;
                    $remainingOnly = $field['remaining_only'] ?? false;

                    $calculatedFieldsMethods .= "
            // حساب فرق الوقت {$field['ar_name']} ({$fieldName})
            if (\$this->{$timeFromField} && \$this->{$timeToField}) {
                try {
                    \$from = \\Carbon\\Carbon::createFromTimeString(\$this->{$timeFromField});
                    \$to = \\Carbon\\Carbon::createFromTimeString(\$this->{$timeToField});

                    switch ('{$unit}') {
                        case 'hours':
                            \$diff = \$from->diffInHours(\$to, false);
                            " . ($remainingOnly ? "\$diff = \$diff % 24; // الساعات المتبقية بعد الأيام الكاملة" : "") . "
                            break;
                        case 'minutes':
                        default:
                            \$diff = \$from->diffInMinutes(\$to, false);
                            " . ($remainingOnly ? "\$diff = \$diff % 60; // الدقائق المتبقية بعد الساعات الكاملة" : "") . "
                            break;
                    }

                    " . ($absoluteValue ? "\$diff = abs(\$diff); // قيمة مطلقة" : "") . "

                    \$this->{$fieldName} = \$diff;
                } catch (\\Exception \$e) {
                    \$this->{$fieldName} = 0;
                }
            } else {
                \$this->{$fieldName} = 0;
            }";
                } else {
                    // حساب المعادلة العادية
                    $formula = $field['calculation_formula'] ?? '';
                    $calculatedFieldsMethods .= "
            // حساب {$field['ar_name']} ({$fieldName})
            \$this->{$fieldName} = \$this->evaluateFormula('{$formula}');";
                }
            }
        }

        $calculatedFieldsMethods .= "
        } catch (\\Exception \$e) {
            \$this->dispatchBrowserEvent('error', [
                'message' => 'خطأ في حساب القيم: ' . \$e->getMessage(),
                'title' => 'خطأ في الحساب'
            ]);
        }
    }

    /**
     * تقييم معادلة الحساب
     */
    private function evaluateFormula(\$formula)
    {
        if (empty(\$formula)) {
            return 0;
        }

        try {
            // استبدال أسماء الحقول بقيمها الفعلية
            \$processedFormula = \$this->replaceFieldsInFormula(\$formula);

            // التحقق من وجود قسمة على صفر قبل التقييم
            if (\$this->hasDivisionByZero(\$processedFormula)) {
                return 0;
            }

            // تنظيف المعادلة من الأحرف غير المرغوب فيها
            \$sanitizedFormula = \$this->sanitizeFormula(\$processedFormula);

            // التحقق مرة أخرى بعد التنظيف
            if (empty(\$sanitizedFormula) || \$this->hasDivisionByZero(\$sanitizedFormula)) {
                return 0;
            }

            // تقييم المعادلة الرياضية
            \$result = eval(\"return \$sanitizedFormula;\");

            // التحقق من أن النتيجة رقم صحيح
            if (!is_numeric(\$result) || is_infinite(\$result) || is_nan(\$result)) {
                return 0;
            }

            return round(\$result, 2);
        } catch (\\Exception \$e) {
            return 0;
        } catch (\\DivisionByZeroError \$e) {
            return 0;
        } catch (\\ParseError \$e) {
            return 0;
        }
    }

    /**
     * التحقق من وجود قسمة على صفر في المعادلة
     */
    private function hasDivisionByZero(\$formula)
    {
        // البحث عن أنماط القسمة على صفر
        if (preg_match('/\\/\\s*0(?![0-9])/', \$formula)) {
            return true;
        }

        // البحث عن قسمة على صفر مع مسافات
        if (preg_match('/\\/\\s*0\\s*[\\+\\-\\*\\/\\)\\s]/', \$formula)) {
            return true;
        }

        // البحث عن قسمة على صفر في نهاية المعادلة
        if (preg_match('/\\/\\s*0\\s*$/', \$formula)) {
            return true;
        }

        return false;
    }

    /**
     * استبدال أسماء الحقول في المعادلة بقيمها
     */
    private function replaceFieldsInFormula(\$formula)
    {";

        foreach ($fields as $field) {
            // تضمين الحقول الرقمية والحقول select الرقمية
            $isNumericField = in_array($field['type'], ['integer', 'decimal']) ||
                             ($field['type'] === 'select' && ($field['select_numeric_values'] ?? false));

            if ($isNumericField) {
                $fieldName = $field['name'];
                $comment = ($field['type'] === 'select' && ($field['select_numeric_values'] ?? false)) ?
                          ' (قائمة منسدلة رقمية)' : '';

                $calculatedFieldsMethods .= "
        // التأكد من أن القيمة رقمية صحيحة للحقل {$fieldName}{$comment}
        \$value_{$fieldName} = \$this->{$fieldName};
        if (!is_numeric(\$value_{$fieldName}) || \$value_{$fieldName} === '' || \$value_{$fieldName} === null) {
            \$value_{$fieldName} = 0;
        }
        \$formula = str_replace('{$fieldName}', \$value_{$fieldName}, \$formula);";
            }
        }

        $calculatedFieldsMethods .= "
        return \$formula;
    }

    /**
     * تنظيف المعادلة من الأحرف غير المرغوب فيها
     */
    private function sanitizeFormula(\$formula)
    {
        // السماح بالأرقام والعمليات الحسابية والأقواس والمسافات والنقطة العشرية فقط
        return preg_replace('/[^0-9+\\-*\\/(). ]/', '', \$formula);
    }
";

        // إضافة دوال updated للحقول الرقمية وحقول select الرقمية
        foreach ($fields as $field) {
            // تضمين الحقول الرقمية والحقول select الرقمية
            $isNumericField = in_array($field['type'], ['integer', 'decimal']) ||
                             ($field['type'] === 'select' && ($field['select_numeric_values'] ?? false));

            if ($isNumericField) {
                $fieldName = $field['name'];
                $methodName = 'updated' . str_replace('_', '', ucwords($fieldName, '_'));
                $comment = ($field['type'] === 'select' && ($field['select_numeric_values'] ?? false)) ?
                          ' (قائمة منسدلة رقمية)' : '';

                $calculatedFieldsMethods .= "
    /**
     * حساب تلقائي عند تغيير حقل {$field['ar_name']}{$comment}
     */
    public function {$methodName}()
    {
        \$this->calculateFields();
    }
";
            }
        }

        return $calculatedFieldsMethods;
    }

    /**
     * إضافة أو تحديث دالة validation
     */
    private function addOrUpdateValidationMethod($content, $methodName, $rulesArray)
    {
        $rulesString = implode(",\n            ", $rulesArray);
        $newMethod = "
    private function {$methodName}()
    {
        return [
            {$rulesString}
        ];
    }";

        // البحث عن الدالة الموجودة
        $pattern = "/private\s+function\s+{$methodName}\s*\(\s*\)\s*\{.*?\}\s*\n/s";
        if (preg_match($pattern, $content)) {
            // استبدال الدالة الموجودة
            $content = preg_replace($pattern, $newMethod . "\n", $content);
        } else {
            // إضافة دالة جديدة قبل نهاية الكلاس
            $lastClosingBrace = strrpos($content, '}');
            if ($lastClosingBrace !== false) {
                $content = substr($content, 0, $lastClosingBrace) . $newMethod . "\n}";
            }
        }

        return $content;
    }

    /**
     * تحديث دوال store/update لاستخدام validation منفصل
     */
    private function updateStoreUpdateMethodsForSeparateValidation($content)
    {
        // تحديث store method
        $content = preg_replace(
            '/(\$this->validate\(\[.*?\]\);)/s',
            '$this->validate($this->getStoreRules(), $this->getValidationMessages());',
            $content
        );

        // تحديث update method
        $content = preg_replace(
            '/(\$this->validate\(\[.*?\]\);)(?=.*update)/s',
            '$this->validate($this->getUpdateRules(), $this->getValidationMessages());',
            $content
        );

        return $content;
    }

    /**
     * دمج معلومات الحقل مع معلومات قاعدة البيانات للحقول المحسوبة
     */
    private function enrichFieldWithCalculatedInfo($field, $fieldName, $moduleName)
    {
        try {
            // محاولة الحصول على معلومات الحقل من قاعدة البيانات
            $dbField = ModuleField::where('field_name', $fieldName)
                ->where('module_name', $moduleName)
                ->first();

            if ($dbField) {
                // دمج المعلومات
                $field['is_calculated'] = $dbField->is_calculated ?? false;
                $field['calculation_formula'] = $dbField->calculation_formula ?? null;
                $field['required'] = $dbField->required ?? ($field['required'] ?? false);
                $field['unique'] = $dbField->unique ?? ($field['unique'] ?? false);

                // إضافة معلومات إضافية للحقول المحسوبة
                if ($field['is_calculated']) {
                    $this->info("🧮 تم اكتشاف حقل محسوب: {$fieldName} بمعادلة: {$field['calculation_formula']}");
                }
            }
        } catch (\Exception $e) {
            // في حالة فشل الاستعلام، نستخدم القيم الافتراضية
            $field['is_calculated'] = false;
            $field['calculation_formula'] = null;
        }

        return $field;
    }

    /**
     * تحديث الحقول الرقمية لتشمل معالجة الحقول المحسوبة
     */
    private function updateNumericFieldsForCalculation($content, $fields, $moduleName)
    {
        foreach ($fields as $field) {
            if (in_array($field['type'], ['integer', 'decimal', 'number'])) {
                $fieldName = $field['name'];

                // إثراء معلومات الحقل من قاعدة البيانات
                $enrichedField = $this->enrichFieldWithCalculatedInfo($field, $fieldName, $moduleName);

                if ($enrichedField['is_calculated'] ?? false) {
                    // إضافة wire:input للحقول المحسوبة في الـ modals
                    $content = $this->addWireInputToCalculatedField($content, $fieldName);

                    // إضافة readonly styling للحقول المحسوبة
                    $content = $this->addReadonlyStyleToCalculatedField($content, $fieldName);

                    // إضافة calculator icon للحقول المحسوبة
                    $content = $this->addCalculatorIconToField($content, $fieldName, $enrichedField['ar_name'] ?? $fieldName);
                }
            }
        }

        return $content;
    }

    /**
     * إضافة wire:input للحقول المحسوبة
     */
    private function addWireInputToCalculatedField($content, $fieldName)
    {
        // البحث عن الحقل وإضافة wire:input
        $pattern = "/(wire:model([^=]*=['\"]" . preg_quote($fieldName, '/') . "['\"][^>]*))>/";
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, '$1 wire:input="calculateFields()">', $content);
            $this->info("✅ تم إضافة wire:input للحقل المحسوب: {$fieldName}");
        }

        return $content;
    }

    /**
     * إضافة readonly styling للحقول المحسوبة
     */
    private function addReadonlyStyleToCalculatedField($content, $fieldName)
    {
        // البحث عن input field مع wire:model للحقل المحدد
        $pattern = '/<input\s+([^>]*wire:model=[\'"]' . preg_quote($fieldName, '/') . '[\'"][^>]*)\s*\/?>/i';

        if (preg_match($pattern, $content, $matches)) {
            $inputAttributes = $matches[1];

            // إضافة readonly attribute إذا لم يكن موجوداً
            if (strpos($inputAttributes, 'readonly') === false) {
                $inputAttributes .= ' readonly';
            }

            // إضافة bg-light text-muted classes إذا لم تكن موجودة
            if (preg_match('/class=[\'"]([^\'"]*)[\'"]/i', $inputAttributes, $classMatch)) {
                $currentClasses = $classMatch[1];
                if (strpos($currentClasses, 'bg-light') === false) {
                    $newClasses = $currentClasses . ' bg-light text-muted';
                    $inputAttributes = str_replace($classMatch[0], 'class="' . $newClasses . '"', $inputAttributes);
                }
            }

            $newInput = '<input ' . $inputAttributes . '/>';
            $content = preg_replace($pattern, $newInput, $content);

            $this->info("✅ تم إضافة readonly styling للحقل المحسوب: {$fieldName}");
        }

        return $content;
    }

    /**
     * إضافة calculator icon للحقول المحسوبة
     */
    private function addCalculatorIconToField($content, $fieldName, $arabicName)
    {
        // البحث عن label الحقل باستخدام id
        $pattern = '/<label\s+for=[\'"]modal[^\']*' . preg_quote($fieldName, '/') . '[\'"][^>]*>([^<]*)<\/label>/i';

        if (preg_match($pattern, $content, $matches)) {
            $labelContent = trim($matches[1]);

            // إضافة الأيقونة إذا لم تكن موجودة
            if (strpos($labelContent, 'mdi-calculator') === false) {
                $calculatorIcon = " <i class='mdi mdi-calculator text-success' title='حقل محسوب تلقائياً'></i>";
                $newLabel = str_replace($matches[0],
                    str_replace($labelContent, $labelContent . $calculatorIcon, $matches[0]),
                    $content);
                $content = $newLabel;
                $this->info("✅ تم إضافة calculator icon للحقل المحسوب: {$fieldName}");
            }
        }

        return $content;
    }

    /**
     * تحديث دالة calculateFields الموجودة لإضافة حقول محسوبة جديدة
     */
    private function updateExistingCalculateFields($content, $calculatedFields, $componentPath)
    {
        foreach ($calculatedFields as $field) {
            $fieldName = $field['name'];
            $formula = $field['calculation_formula'];

            // التحقق من عدم وجود الحقل في دالة calculateFields
            if (strpos($content, '$this->' . $fieldName . ' = $this->evaluateFormula') === false) {
                // إضافة الحقل الجديد إلى دالة calculateFields
                $pattern = '/(public function calculateFields\(\).*?try.*?\{)(.*?)(\} catch)/s';
                if (preg_match($pattern, $content, $matches)) {
                    $calculationComment = "            // حساب {$field['ar_name']} ({$fieldName})";
                    $calculationLine = "            \$this->{$fieldName} = \$this->evaluateFormula('{$formula}');";

                    $newContent = $matches[1] . $matches[2] . "\n" . $calculationComment . "\n" . $calculationLine . "\n" . $matches[3];
                    $content = str_replace($matches[0], $newContent, $content);

                    File::put($componentPath, $content);
                    $this->info("✅ تم إضافة الحقل المحسوب {$fieldName} إلى دالة calculateFields");
                }
            }

            // إضافة updated{FieldName} function إذا لم تكن موجودة
            $updatedFunctionName = 'updated' . ucfirst($fieldName);
            if (strpos($content, "public function {$updatedFunctionName}()") === false) {
                $updatedFunction = "\n    /**\n     * حساب تلقائي عند تغيير حقل {$field['ar_name']}\n     */\n    public function {$updatedFunctionName}()\n    {\n        \$this->calculateFields();\n    }\n";

                // إضافة الدالة قبل إغلاق الكلاس
                $lastClosingBrace = strrpos($content, '}');
                if ($lastClosingBrace !== false) {
                    $content = substr($content, 0, $lastClosingBrace) . $updatedFunction . "\n}";
                    File::put($componentPath, $content);
                    $this->info("✅ تم إضافة دالة {$updatedFunctionName}");
                }
            }
        }
    }

    /**
     * إضافة دوال updated للحقول المرجعية للوقت
     */
    private function addUpdatedMethodsForTimeReferences(&$content, $fields, $moduleName, $componentPath)
    {
        // جمع جميع الحقول المرجعية للوقت
        $timeReferenceFields = [];

        foreach ($fields as $field) {
            if (($field['calculation_type'] ?? '') === 'time_diff') {
                $timeFromField = $field['time_from_field'] ?? '';
                $timeToField = $field['time_to_field'] ?? '';

                if ($timeFromField && !in_array($timeFromField, $timeReferenceFields)) {
                    $timeReferenceFields[] = $timeFromField;
                }
                if ($timeToField && !in_array($timeToField, $timeReferenceFields)) {
                    $timeReferenceFields[] = $timeToField;
                }
            }
        }

        if (empty($timeReferenceFields)) {
            return;
        }

        $hasChanges = false;
        foreach ($timeReferenceFields as $fieldName) {
            $updatedFunctionName = 'updated' . str_replace('_', '', ucwords($fieldName, '_'));

            // التحقق من عدم وجود الدالة
            if (strpos($content, "public function {$updatedFunctionName}()") === false) {
                $updatedFunction = "\n    /**\n     * حساب تلقائي عند تغيير حقل {$fieldName}\n     */\n    public function {$updatedFunctionName}()\n    {\n        \$this->calculateFields();\n    }\n";

                // إضافة الدالة قبل إغلاق الكلاس
                $lastClosingBrace = strrpos($content, '}');
                if ($lastClosingBrace !== false) {
                    $content = substr($content, 0, $lastClosingBrace) . $updatedFunction . "\n}";
                    $hasChanges = true;
                    $this->info("✅ تم إضافة دالة {$updatedFunctionName} للحقل المرجعي للوقت");
                }
            }
        }

        if ($hasChanges) {
            $this->info("✅ تم إضافة " . count($timeReferenceFields) . " دالة updated للحقول المرجعية للوقت");
        }
    }

    /**
     * فحص ما إذا كان الحقل يُستخدم في حساب الوقت
     */
    private function isFieldUsedInTimeCalculation($fieldName, $moduleName)
    {
        try {
            // البحث في الحقول المحسوبة للوقت
            $timeCalcFields = \App\Models\System\ModuleField::where('module_name', $moduleName)
                                          ->where('calculation_type', 'time_diff')
                                          ->where(function($query) use ($fieldName) {
                                              $query->where('time_from_field', $fieldName)
                                                    ->orWhere('time_to_field', $fieldName);
                                          })
                                          ->exists();

            return $timeCalcFields;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * إصلاح الحقول المحسوبة للوقت والتاريخ تلقائياً
     */
    private function fixCalculatedFieldsConfiguration($moduleName)
    {
        try {
            $this->info("🔧 فحص وإصلاح الحقول المحسوبة للوحدة: {$moduleName}");

            // إصلاح حقول حساب الوقت - إصلاح شامل
            $timeFields = \App\Models\System\ModuleField::where('module_name', $moduleName)
                                         ->where('calculation_type', 'time_diff')
                                         ->get();

            foreach ($timeFields as $field) {
                $needsUpdate = false;
                $updateData = [];

                // التأكد من is_time_calculated = true
                if (!$field->is_time_calculated) {
                    $updateData['is_time_calculated'] = true;
                    $needsUpdate = true;
                }

                // التأكد من وجود time_from_field و time_to_field
                if (empty($field->time_from_field) || empty($field->time_to_field)) {
                    // البحث عن حقول الوقت المتاحة
                    $timeFieldsInModule = \App\Models\System\ModuleField::where('module_name', $moduleName)
                                            ->where('field_type', 'time')
                                            ->pluck('field_name')
                                            ->toArray();

                    if (count($timeFieldsInModule) >= 2) {
                        $updateData['time_from_field'] = $timeFieldsInModule[0];
                        $updateData['time_to_field'] = $timeFieldsInModule[1];
                        $needsUpdate = true;
                        $this->info("🔧 ربط {$field->field_name} بالحقول: {$timeFieldsInModule[0]} → {$timeFieldsInModule[1]}");
                    }
                }

                // التأكد من time_diff_unit صحيح
                if (empty($field->time_diff_unit)) {
                    // تخمين الوحدة من اسم الحقل
                    if (strpos($field->field_name, 'hour') !== false) {
                        $updateData['time_diff_unit'] = 'hours';
                    } else {
                        $updateData['time_diff_unit'] = 'minutes';
                    }
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $field->update($updateData);
                    $this->info("✅ تم إصلاح حقل الوقت: {$field->field_name}");
                }
            }

            // إصلاح حقول حساب التاريخ
            $dateFields = \App\Models\System\ModuleField::where('module_name', $moduleName)
                                         ->where('calculation_type', 'date_diff')
                                         ->where('is_date_calculated', false)
                                         ->get();

            foreach ($dateFields as $field) {
                $field->update(['is_date_calculated' => true]);
                $this->info("✅ تم إصلاح حقل التاريخ: {$field->field_name}");
            }

            if ($timeFields->count() > 0 || $dateFields->count() > 0) {
                $this->info("🎯 تم فحص وإصلاح " . ($timeFields->count() + $dateFields->count()) . " حقل محسوب");
            } else {
                $this->info("✅ جميع الحقول المحسوبة تعمل بشكل صحيح");
            }

        } catch (\Exception $e) {
            $this->error("❌ خطأ في إصلاح الحقول المحسوبة: " . $e->getMessage());
        }
    }

    /**
     * إصلاح تلقائي للحقول المحسوبة المعطوبة
     */
    private function autoFixCalculatedFields($moduleName, $fields)
    {
        $this->info("🔧 فحص وإصلاح الحقول المحسوبة...");

        $fixedCount = 0;
        $timeFields = [];

        // جمع حقول الوقت المتاحة
        foreach ($fields as $field) {
            if ($field['type'] === 'time') {
                $timeFields[] = $field['name'];
            }
        }

        $this->info("⏰ تم العثور على " . count($timeFields) . " حقل وقت: " . implode(', ', $timeFields));

        // إصلاح الحقول المحسوبة
        foreach ($fields as &$field) {
            if ($field['is_time_calculated'] && $field['calculation_type'] === 'time_diff') {
                $needsFix = false;

                // فحص الحقول المرجعية
                if (empty($field['time_from_field']) || empty($field['time_to_field'])) {
                    $this->info("🔧 إصلاح الحقل: {$field['name']} - مراجع الوقت مفقودة");

                    if (count($timeFields) >= 2) {
                        $field['time_from_field'] = $timeFields[0];
                        $field['time_to_field'] = $timeFields[1];
                        $needsFix = true;
                        $this->info("  ✅ ربط بـ: {$timeFields[0]} -> {$timeFields[1]}");
                    }
                }

                // فحص وحدة القياس
                if (empty($field['time_diff_unit'])) {
                    // تحديد الوحدة بناءً على اسم الحقل
                    if (str_contains($field['name'], 'hour')) {
                        $field['time_diff_unit'] = 'hours';
                    } else {
                        $field['time_diff_unit'] = 'minutes';
                    }
                    $needsFix = true;
                    $this->info("  ✅ تعيين وحدة القياس: {$field['time_diff_unit']}");
                }

                if ($needsFix) {
                    $fixedCount++;
                    $this->info("  🔧 تم إصلاح الحقل: {$field['name']}");
                }
            }
        }

        if ($fixedCount > 0) {
            $this->info("✅ تم إصلاح {$fixedCount} حقل محسوب");
        } else {
            $this->info("✅ جميع الحقول المحسوبة سليمة");
        }

        return $fields;
    }

    /**
     * Get integer validation messages for specific integer type
     */
    private function getIntegerValidationMessages($field)
    {
        $messages = [];
        $arabicLabel = $field['ar_name'] ?? $field['name'];
        $fieldName = $field['name'];

        if (isset($field['integer_type'])) {
            $integerType = $field['integer_type'];
            $isSigned = !isset($field['unsigned']) || !$field['unsigned'];

            switch ($integerType) {
                case 'tinyint':
                    if ($isSigned) {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي -128";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 127";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 3 أرقام";
                    } else {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي 0";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 255";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 3 أرقام";
                    }
                    break;

                case 'smallint':
                    if ($isSigned) {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي -32,768";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 32,767";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 5 أرقام";
                    } else {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي 0";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 65,535";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 5 أرقام";
                    }
                    break;

                case 'int':
                    if ($isSigned) {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي -2,147,483,648";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 2,147,483,647";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 10 أرقام";
                    } else {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي 0";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 4,294,967,295";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 10 أرقام";
                    }
                    break;

                case 'bigint':
                    if ($isSigned) {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي -9,223,372,036,854,775,808";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 9,223,372,036,854,775,807";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 19 رقم";
                    } else {
                        $messages["{$fieldName}.min"] = "{$arabicLabel} يجب أن يكون أكبر من أو يساوي 0";
                        $messages["{$fieldName}.max"] = "{$arabicLabel} يجب أن يكون أقل من أو يساوي 18,446,744,073,709,551,615";
                        $messages["{$fieldName}.digits_between"] = "{$arabicLabel} يجب أن يحتوي على رقم واحد على الأقل وحتى 20 رقم";
                    }
                    break;
            }
        }

        return $messages;
    }

    /**
     * Get decimal validation messages for specific decimal precision and scale
     */
    private function getDecimalValidationMessages($field)
    {
        $messages = [];
        $arabicLabel = $field['ar_name'] ?? $field['name'];
        $fieldName = $field['name'];

        if (isset($field['decimal_precision']) && isset($field['decimal_scale'])) {
            $precision = $field['decimal_precision']; // إجمالي عدد الأرقام
            $scale = $field['decimal_scale']; // عدد المراتب العشرية

            // حساب عدد الأرقام قبل العلامة العشرية
            $integerDigits = $precision - $scale;

            // إنشاء مثال ديناميكي
            $exampleInteger = str_repeat('1', min($integerDigits, 3)); // حد أقصى 3 أرقام للمثال
            $exampleDecimal = str_repeat('5', $scale);
            $example = $scale > 0 ? "{$exampleInteger}.{$exampleDecimal}" : $exampleInteger;

            // رسالة regex مخصصة
            if ($scale > 0) {
                // للأرقام العشرية
                $messages["{$fieldName}.regex"] = "{$arabicLabel} يجب أن يكون رقم عشري صحيح بحد أقصى {$integerDigits} أرقام قبل الفاصلة و{$scale} أرقام بعد الفاصلة (مثال: {$example})";
            } else {
                // للأرقام الصحيحة بدون مراتب عشرية
                $messages["{$fieldName}.regex"] = "{$arabicLabel} يجب أن يكون رقم صحيح بحد أقصى {$precision} أرقام (مثال: {$example})";
            }

            // رسالة numeric عامة
            $messages["{$fieldName}.numeric"] = "{$arabicLabel} يجب أن يكون رقم صالح";

            // رسالة للحد الأقصى للأرقام (اختيارية)
            $totalLength = $precision + ($scale > 0 ? 1 : 0); // +1 للفاصلة العشرية
            $messages["{$fieldName}.max"] = "{$arabicLabel} يجب ألا يتجاوز {$totalLength} خانات إجمالية";
        }

        return $messages;
    }

    /**
     * تنظيف migration files القديمة للوحدة
     */
    private function cleanupOldMigrations($moduleName)
    {
        try {
            $tableName = Str::snake(Str::plural($moduleName));
            $migrationsPath = database_path('migrations');
            $existingMigrations = glob($migrationsPath . "/*_create_{$tableName}_table.php");

            if (!empty($existingMigrations)) {
                $this->info("🗑️ تم العثور على " . count($existingMigrations) . " migration قديمة للجدول {$tableName}");

                foreach ($existingMigrations as $existingMigration) {
                    $filename = basename($existingMigration);

                    // إنشاء نسخة احتياطية قبل الحذف
                    $backupFile = $existingMigration . '.backup.' . date('Y_m_d_H_i_s');
                    copy($existingMigration, $backupFile);

                    // حذف الملف القديم
                    unlink($existingMigration);

                    $this->info("✅ تم حذف: {$filename} (نسخة احتياطية: " . basename($backupFile) . ")");
                }

                $this->logOperation('info', 'تم تنظيف migration files القديمة', [
                    'module_name' => $moduleName,
                    'table_name' => $tableName,
                    'deleted_count' => count($existingMigrations)
                ]);
            } else {
                $this->info("ℹ️ لا توجد migration files قديمة للحذف");
            }

        } catch (\Exception $e) {
            $this->warn("⚠️ خطأ في تنظيف migrations القديمة: " . $e->getMessage());
            $this->logOperation('warning', 'خطأ في تنظيف migrations القديمة', [
                'module_name' => $moduleName,
                'error' => $e->getMessage()
            ]);
        }
    }
}
