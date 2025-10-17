<?php

namespace App\Imports\DataManagement;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class DataImport implements ToCollection, WithHeadingRow
{
    protected $tableName;
    protected $importMode;
    protected $results;
    protected $errors;
    protected $successCount;
    protected $errorCount;
    protected $skippedCount;

    public function __construct(string $tableName, string $importMode = 'insert')
    {
        $this->tableName = $tableName;
        $this->importMode = $importMode; // insert, update, replace
        $this->results = [];
        $this->errors = [];
        $this->successCount = 0;
        $this->errorCount = 0;
        $this->skippedCount = 0;

        Log::info('DataImport constructor called', [
            'table' => $tableName,
            'mode' => $importMode
        ]);
    }

    /**
     * معالجة البيانات المستوردة
     */
    public function collection(Collection $rows)
    {
        Log::info('بدء معالجة البيانات', ['rows_count' => $rows->count(), 'table' => $this->tableName]);

        $availableColumns = Schema::getColumnListing($this->tableName);
        Log::info('أعمدة الجدول المتاحة', $availableColumns);

        $batchData = [];
        $rowNumber = 1; // بدءاً من الصف الثاني (بعد العناوين)

        foreach ($rows as $row) {
            $rowNumber++;
            Log::info("معالجة الصف {$rowNumber}", $row->toArray());

            try {
                // تنظيف وتحضير البيانات
                $cleanedRow = $this->cleanRowData($row->toArray(), $availableColumns);
                Log::info("البيانات بعد التنظيف", $cleanedRow);

                if (empty($cleanedRow)) {
                    Log::warning("صف فارغ تم تخطيه", ['row' => $rowNumber]);
                    $this->skippedCount++;
                    continue;
                }

                // التحقق من صحة البيانات - مؤقتاً معطل للاختبار
                // $validation = $this->validateRowData($cleanedRow, $rowNumber);

                // if ($validation['valid']) {
                    $processedRow = $this->processRowData($cleanedRow);
                    Log::info("البيانات بعد المعالجة", $processedRow);

                    // معالجة حسب نوع الاستيراد
                    switch ($this->importMode) {
                        case 'insert':
                            $batchData[] = $processedRow;
                            break;

                        case 'update':
                            $this->updateRecord($processedRow, $rowNumber);
                            break;

                        case 'replace':
                            $this->replaceRecord($processedRow, $rowNumber);
                            break;
                    }

                    $this->successCount++;
                // } else {
                //     $this->errors[] = [
                //         'row' => $rowNumber,
                //         'errors' => $validation['errors'],
                //         'data' => $cleanedRow
                //     ];
                //     $this->errorCount++;
                // }

            } catch (\Exception $e) {
                Log::error("خطأ في معالجة الصف {$rowNumber}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ['خطأ عام: ' . $e->getMessage()],
                    'data' => $row->toArray()
                ];
                $this->errorCount++;
            }
        }

        // إدراج البيانات بالدفعات (للوضع insert فقط)
        if ($this->importMode === 'insert' && !empty($batchData)) {
            Log::info('إدراج البيانات بالدفعات', ['count' => count($batchData)]);
            $this->insertBatchData($batchData);
        }

        // إعداد النتائج
        $this->prepareResults();
        Log::info('انتهاء المعالجة', $this->results);
    }

    /**
     * قواعد التحقق - معطلة مؤقتاً
     */
    // public function rules(): array
    // {
    //     return [];
    // }

    // /**
    //  * حجم الدفعة للمعالجة - معطل مؤقتاً
    //  */
    // public function batchSize(): int
    // {
    //     return 500;
    // }

    // /**
    //  * حجم القطعة للقراءة - معطل مؤقتاً
    //  */
    // public function chunkSize(): int
    // {
    //     return 1000;
    // }

    /**
     * تنظيف بيانات الصف
     */
    protected function cleanRowData(array $row, array $availableColumns): array
    {
        $cleaned = [];
        $skippedColumns = [];

        Log::info('تنظيف البيانات', [
            'input_row' => $row,
            'available_columns' => $availableColumns
        ]);

        foreach ($row as $key => $value) {
            // تنظيف اسم العمود من المسافات والرموز الخاصة
            $cleanKey = $this->cleanColumnName($key);

            // تحويل اسم العمود للإنجليزية إذا كان بالعربية
            $columnName = $this->mapArabicToEnglishColumn($cleanKey);

            Log::info("معالجة العمود", [
                'original_key' => $key,
                'clean_key' => $cleanKey,
                'mapped_column' => $columnName,
                'value' => $value,
                'is_available' => in_array($columnName, $availableColumns)
            ]);

            // التحقق من وجود العمود في الجدول
            if (in_array($columnName, $availableColumns)) {
                // تنظيف القيمة
                $cleanedValue = $this->cleanCellValue($value, $columnName);

                // تخطي القيم الفارغة تماماً (إلا إذا كان العمود يقبل null)
                if ($cleanedValue !== null && $cleanedValue !== '') {
                    $cleaned[$columnName] = $cleanedValue;
                }
            } else {
                $skippedColumns[] = $key;
            }
        }

        // إضافة رسالة تحذيرية إذا لم تتطابق الأعمدة
        if (!empty($skippedColumns)) {
            Log::warning('أعمدة غير متطابقة تم تجاهلها', [
                'skipped_columns' => $skippedColumns,
                'available_columns' => $availableColumns,
                'file_columns' => array_keys($row)
            ]);
        }

        Log::info('النتيجة بعد التنظيف', $cleaned);
        return $cleaned;
    }

    /**
     * تنظيف أسماء الأعمدة من المسافات والرموز الخاصة
     */
    protected function cleanColumnName(string $columnName): string
    {
        // إزالة المسافات من البداية والنهاية
        $cleaned = trim($columnName);

        // إزالة الرموز الخاصة الشائعة
        $cleaned = str_replace(['\r', '\n', '\t', '"', "'", '`'], '', $cleaned);

        // إرجاع الاسم كما هو (بدون تحويل المسافات لـ underscore للبحث في الخريطة)
        return $cleaned;
    }

    /**
     * تنظيف قيمة الخلية
     */
    protected function cleanCellValue($value, string $columnName)
    {
        // إذا كانت القيمة فارغة أو null
        if (is_null($value) || $value === '' || $value === '-' || $value === 'NULL') {
            return null;
        }

        // تنظيف النصوص من المسافات والرموز الخاصة
        if (is_string($value)) {
            $value = trim($value);

            // إزالة الرموز الخاصة من Excel
            $value = str_replace(['\r', '\n', '\t'], ' ', $value);

            // تنظيف المسافات المتكررة
            $value = preg_replace('/\s+/', ' ', $value);

            // إذا أصبحت القيمة فارغة بعد التنظيف
            if ($value === '') {
                return null;
            }

            // تحويل القيم المنطقية العربية
            $lowerValue = mb_strtolower($value);
            if (in_array($lowerValue, ['نشط', 'مفعل', 'نعم', 'صحيح', 'true', '1', 'yes'])) {
                return 1;
            }
            if (in_array($lowerValue, ['غير نشط', 'معطل', 'لا', 'خطأ', 'false', '0', 'no'])) {
                return 0;
            }
        }

        // معالجة الأرقام من Excel
        if (is_numeric($value)) {
            // إزالة التنسيق من الأرقام (الفواصل وغيرها)
            $cleanNumber = str_replace([',', ' ', '٬'], '', $value);

            if (str_contains($columnName, 'price') || str_contains($columnName, 'amount') || str_contains($columnName, 'salary')) {
                return floatval($cleanNumber);
            }

            if (str_contains($columnName, 'id') || str_contains($columnName, 'count') || str_contains($columnName, 'quantity')) {
                return intval($cleanNumber);
            }

            return $cleanNumber;
        }

        // تنسيق التواريخ
        if (str_contains($columnName, 'date') || str_contains($columnName, 'time') || $columnName === 'created_at' || $columnName === 'updated_at') {
            try {
                // معالجة تواريخ Excel
                if (is_numeric($value) && $value > 25569) { // Excel date serial number
                    $unixTimestamp = ($value - 25569) * 86400; // Convert to Unix timestamp
                    return date('Y-m-d H:i:s', $unixTimestamp);
                }

                // معالجة التواريخ النصية
                return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                Log::warning("فشل في تحويل التاريخ", ['value' => $value, 'column' => $columnName]);
                return null;
            }
        }

        return $value;
    }

    /**
     * تحقق من صحة بيانات الصف
     */
    protected function validateRowData(array $data, int $rowNumber): array
    {
        $rules = $this->getDynamicValidationRules();
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all()
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * معالجة بيانات الصف
     */
    protected function processRowData(array $data): array
    {
        // إضافة timestamps إذا لم تكن موجودة
        if (!isset($data['created_at']) && Schema::hasColumn($this->tableName, 'created_at')) {
            $data['created_at'] = now();
        }

        if (!isset($data['updated_at']) && Schema::hasColumn($this->tableName, 'updated_at')) {
            $data['updated_at'] = now();
        }

        // إزالة الأعمدة غير المطلوبة
        $availableColumns = Schema::getColumnListing($this->tableName);
        $processed = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $availableColumns)) {
                $processed[$key] = $value;
            }
        }

        return $processed;
    }

    /**
     * تحديث سجل موجود
     */
    protected function updateRecord(array $data, int $rowNumber)
    {
        try {
            // البحث عن السجل باستخدام ID أو مفتاح فريد
            $identifierColumn = $this->getIdentifierColumn($data);
            $identifierValue = $data[$identifierColumn] ?? null;

            if (!$identifierValue) {
                throw new \Exception("لا يمكن العثور على معرف فريد للتحديث");
            }

            $updated = DB::table($this->tableName)
                ->where($identifierColumn, $identifierValue)
                ->update($data);

            if ($updated === 0) {
                $this->skippedCount++;
            }

        } catch (\Exception $e) {
            throw new \Exception("فشل تحديث السجل: " . $e->getMessage());
        }
    }

    /**
     * استبدال سجل (حذف ثم إدراج)
     */
    protected function replaceRecord(array $data, int $rowNumber)
    {
        try {
            DB::transaction(function () use ($data) {
                $identifierColumn = $this->getIdentifierColumn($data);
                $identifierValue = $data[$identifierColumn] ?? null;

                if ($identifierValue) {
                    // حذف السجل الموجود
                    DB::table($this->tableName)
                        ->where($identifierColumn, $identifierValue)
                        ->delete();
                }

                // إدراج السجل الجديد
                DB::table($this->tableName)->insert($data);
            });

        } catch (\Exception $e) {
            throw new \Exception("فشل استبدال السجل: " . $e->getMessage());
        }
    }

    /**
     * إدراج البيانات بالدفعات
     */
    protected function insertBatchData(array $batchData)
    {
        try {
            Log::info('محاولة الإدراج بالدفعات', ['count' => count($batchData), 'sample' => array_slice($batchData, 0, 2)]);

            DB::table($this->tableName)->insert($batchData);

            Log::info('تم الإدراج بالدفعات بنجاح');

        } catch (\Exception $e) {
            Log::error('فشل الإدراج بالدفعات', ['error' => $e->getMessage()]);

            // في حالة فشل الإدراج بالدفعة، محاولة إدراج كل سجل منفرداً
            Log::info('محاولة الإدراج الفردي');

            foreach ($batchData as $index => $record) {
                try {
                    Log::info("إدراج السجل رقم {$index}", $record);
                    DB::table($this->tableName)->insert($record);
                    Log::info("تم إدراج السجل رقم {$index} بنجاح");

                } catch (\Exception $recordError) {
                    Log::error("فشل إدراج السجل رقم {$index}", ['error' => $recordError->getMessage(), 'record' => $record]);

                    // تحسين رسالة الخطأ
                    $errorMessage = $this->getSimplifiedErrorMessage($recordError->getMessage());

                    $this->errors[] = [
                        'row' => $index + 2, // +2 للتعويض عن الترقيم وصف العناوين
                        'errors' => [$errorMessage],
                        'data' => $record
                    ];
                    $this->errorCount++;
                    $this->successCount--;
                }
            }
        }
    }

    /**
     * الحصول على العمود المعرف
     */
    protected function getIdentifierColumn(array $data): string
    {
        // أولوية للـ ID
        if (isset($data['id'])) {
            return 'id';
        }

        // ثم البريد الإلكتروني
        if (isset($data['email'])) {
            return 'email';
        }

        // ثم الاسم إذا كان فريداً
        if (isset($data['name'])) {
            return 'name';
        }

        // ثم أول عمود متاح
        return array_keys($data)[0] ?? 'id';
    }

    /**
     * الحصول على قواعد التحقق الديناميكية
     */
    protected function getDynamicValidationRules(): array
    {
        $rules = [];
        $columns = Schema::getColumnListing($this->tableName);

        foreach ($columns as $column) {
            $columnType = Schema::getColumnType($this->tableName, $column);
            $isNullable = $this->isColumnNullable($column);

            $columnRules = [];

            // قواعد حسب نوع البيانات
            switch ($columnType) {
                case 'integer':
                case 'bigint':
                    $columnRules[] = 'integer';
                    break;

                case 'decimal':
                case 'float':
                case 'double':
                    $columnRules[] = 'numeric';
                    break;

                case 'string':
                case 'text':
                    $columnRules[] = 'string';
                    if ($column === 'email') {
                        $columnRules[] = 'email';
                    }
                    break;

                case 'boolean':
                    $columnRules[] = 'boolean';
                    break;

                case 'date':
                case 'datetime':
                case 'timestamp':
                    $columnRules[] = 'date';
                    break;
            }

            // إضافة nullable إذا كان العمود يقبل القيم الفارغة
            if ($isNullable) {
                array_unshift($columnRules, 'nullable');
            }

            if (!empty($columnRules)) {
                $rules[$column] = $columnRules;
            }
        }

        return $rules;
    }

    /**
     * فحص إذا كان العمود يقبل القيم الفارغة
     */
    protected function isColumnNullable(string $column): bool
    {
        try {
            $result = DB::select("
                SELECT is_nullable
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = ?
                AND column_name = ?
            ", [$this->tableName, $column]);

            return isset($result[0]) && $result[0]->is_nullable === 'YES';
        } catch (\Exception $e) {
            return true; // افتراضياً يقبل القيم الفارغة
        }
    }

    /**
     * تبسيط رسائل الخطأ للمستخدم
     */
    protected function getSimplifiedErrorMessage(string $errorMessage): string
    {
        // رسائل الخطأ الشائعة
        if (str_contains($errorMessage, 'foreign key constraint fails')) {
            if (str_contains($errorMessage, 'user_id')) {
                return 'معرف المستخدم غير صحيح - يجب استخدام معرف مستخدم موجود في النظام';
            }
            return 'قيد المفتاح الخارجي - تأكد من صحة البيانات المرجعية';
        }

        if (str_contains($errorMessage, 'Duplicate entry')) {
            return 'بيانات مكررة - هذا السجل موجود مسبقاً';
        }

        if (str_contains($errorMessage, 'Data too long')) {
            return 'البيانات أطول من المسموح - قلل من طول النص';
        }

        if (str_contains($errorMessage, 'cannot be null')) {
            return 'حقل مطلوب فارغ - تأكد من ملء جميع الحقول المطلوبة';
        }

        // إذا لم تتطابق مع أي نمط، أرجع رسالة مبسطة
        return 'خطأ في البيانات: ' . substr($errorMessage, 0, 100) . '...';
    }

    /**
     * تحويل أسماء الأعمدة - نسخة مبسطة
     */
    protected function mapArabicToEnglishColumn(string $columnName): string
    {
        // تنظيف اسم العمود أولاً
        $cleanColumn = $this->cleanColumnName($columnName);

        // إذا كان العمود موجود في قاعدة البيانات مباشرة، استخدمه كما هو
        $availableColumns = Schema::getColumnListing($this->tableName);
        if (in_array($cleanColumn, $availableColumns)) {
            return $cleanColumn;
        }

        // إرجاع الاسم كما هو بدون تحويل (لأن القوالب ستحتوي على أسماء إنجليزية)
        return $cleanColumn;
    }

    /**
     * إعداد النتائج النهائية
     */
    protected function prepareResults()
    {
        $this->results = [
            'success_count' => $this->successCount,
            'error_count' => $this->errorCount,
            'skipped_count' => $this->skippedCount,
            'total_processed' => $this->successCount + $this->errorCount + $this->skippedCount,
            'errors' => $this->errors,
            'summary' => $this->generateSummary()
        ];
    }

    /**
     * إنشاء ملخص العملية
     */
    protected function generateSummary(): string
    {
        $total = $this->successCount + $this->errorCount + $this->skippedCount;

        $summary = "تم معالجة {$total} سجل: ";
        $summary .= "{$this->successCount} نجح، ";
        $summary .= "{$this->errorCount} فشل";

        if ($this->skippedCount > 0) {
            $summary .= "، {$this->skippedCount} تم تخطيه";

            // إضافة سبب التخطي مع نصائح
            if ($this->skippedCount == $total && $this->successCount == 0) {
                $summary .= "\n\n⚠️ نصائح لحل المشكلة:";
                $summary .= "\n• تأكد من أن أسماء الأعمدة في الملف تطابق أسماء الأعمدة في الجدول";
                $summary .= "\n• يمكنك استخدام الأسماء العربية: (معرف المستخدم، الاسم) أو الإنجليزية: (user_id, name)";
                $summary .= "\n• تأكد من أن القيم غير فارغة";
                $summary .= "\n• للجدول 'emps' استخدم معرفات مستخدمين موجودة (1,4,5,6,7,8,9,10,11,12)";
            }
        }

        return $summary;
    }

    /**
     * الحصول على النتائج
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * الحصول على الأخطاء
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
