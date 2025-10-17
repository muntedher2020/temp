<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleField extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_name',
        'table_name',
        'module_arabic_name',
        'field_name',
        'field_type',
        'arabic_name',
        'english_name',
        'required',
        'unique',
        'searchable',
        'show_in_table',
        'show_in_search',
        'show_in_forms',
        'max_length',
        'arabic_only',
        'numeric_only',
        // حقول إعدادات النص الجديدة
        'text_content_type',
        // حقول إعدادات الأرقام الصحيحة الجديدة
        'integer_type',
        'unsigned',
        // حقول إعدادات الأرقام العشرية الجديدة
        'decimal_precision',
        'decimal_scale',
        'file_types',
        'select_options',
        'select_source',
        'select_numeric_values',
        'related_table',
        'related_key',
        'related_display',
        'validation_rules',
        'validation_messages',
        'custom_attributes',
        'created_by',
        'order',
        'active',
        'is_calculated',
        'calculation_formula',
        'calculation_type',
        'date_from_field',
        'date_to_field',
        'date_diff_unit',
        'include_end_date',
        'absolute_value',
        'remaining_only',
        'is_date_calculated',
        'date_calculation_config',
        'time_from_field',
        'time_to_field',
        'time_diff_unit',
        'is_time_calculated',
        'time_calculation_config'
    ];

    protected $casts = [
        'required' => 'boolean',
        'unique' => 'boolean',
        'searchable' => 'boolean',
        'show_in_table' => 'boolean',
        'show_in_search' => 'boolean',
        'show_in_forms' => 'boolean',
        'arabic_only' => 'boolean',
        'numeric_only' => 'boolean',
        // حقول إعدادات الأرقام الصحيحة
        'unsigned' => 'boolean',
        // حقول إعدادات الأرقام العشرية
        'decimal_precision' => 'integer',
        'decimal_scale' => 'integer',
        'select_numeric_values' => 'boolean',
        'is_calculated' => 'boolean',
        'include_end_date' => 'boolean',
        'absolute_value' => 'boolean',
        'remaining_only' => 'boolean',
        'is_date_calculated' => 'boolean',
        'select_options' => 'array',
        'validation_messages' => 'array',
        'custom_attributes' => 'array',
        'date_calculation_config' => 'array',
        'is_time_calculated' => 'boolean',
        'time_calculation_config' => 'array',
        'active' => 'boolean'
    ];

    /**
     * الحصول على جميع حقول وحدة معينة
     */
    public static function getModuleFields($moduleName, $activeOnly = true)
    {
        $query = static::where('module_name', $moduleName);

        if ($activeOnly) {
            $query->where('active', true);
        }

        return $query->orderBy('order')->orderBy('created_at')->get();
    }

    /**
     * إضافة أو تحديث حقل
     */
    public static function addOrUpdateField($moduleName, $fieldData)
    {
        return static::updateOrCreate(
            [
                'module_name' => $moduleName,
                'field_name' => $fieldData['field_name']
            ],
            $fieldData
        );
    }

    /**
     * حذف حقل
     */
    public static function removeField($moduleName, $fieldName)
    {
        return static::where('module_name', $moduleName)
                    ->where('field_name', $fieldName)
                    ->delete();
    }

    /**
     * تحويل حقول الوحدة إلى تنسيق مولد الوحدات
     */
    public static function getFieldsForGenerator($moduleName)
    {
        $fields = static::getModuleFields($moduleName);

        return $fields->map(function ($field) {
            return [
                'name' => $field->field_name,
                'type' => $field->field_type,
                'ar_name' => $field->arabic_name,
                'english_name' => $field->english_name,
                'required' => $field->required,
                'unique' => $field->unique,
                'searchable' => $field->searchable,
                'show_in_table' => $field->show_in_table,
                'show_in_search' => $field->show_in_search,
                'show_in_forms' => $field->show_in_forms,
                'max' => $field->max_length,
                'arabic_only' => $field->arabic_only,
                'numeric_only' => $field->numeric_only,
                // إعدادات النص الجديدة
                'text_content_type' => $field->text_content_type,
                // إعدادات الأرقام الصحيحة الجديدة
                'integer_type' => $field->integer_type,
                'unsigned' => $field->unsigned,
                // إعدادات الأرقام العشرية الجديدة
                'decimal_precision' => $field->decimal_precision,
                'decimal_scale' => $field->decimal_scale,
                'file_types' => $field->file_types,
                'options' => $field->select_options,
                'select_options' => $field->select_options,
                'select_source' => $field->select_source,
                'select_numeric_values' => $field->select_numeric_values,
                'related_table' => $field->related_table,
                'related_key' => $field->related_key,
                'related_display' => $field->related_display,
                'is_calculated' => $field->is_calculated,
                'calculation_formula' => $field->calculation_formula,
                'calculation_type' => $field->calculation_type,
                'date_from_field' => $field->date_from_field,
                'date_to_field' => $field->date_to_field,
                'date_diff_unit' => $field->date_diff_unit,
                'include_end_date' => $field->include_end_date,
                'absolute_value' => $field->absolute_value,
                'remaining_only' => $field->remaining_only,
                'is_date_calculated' => $field->is_date_calculated,
                'date_calculation_config' => $field->date_calculation_config,
                // خصائص حساب الوقت
                'time_from_field' => $field->time_from_field,
                'time_to_field' => $field->time_to_field,
                'time_diff_unit' => $field->time_diff_unit,
                'is_time_calculated' => $field->is_time_calculated,
                'time_calculation_config' => $field->time_calculation_config,
                'validation' => $field->validation_rules,
                'validation_messages' => $field->validation_messages,
                'custom_attributes' => $field->custom_attributes
            ];
        })->toArray();
    }

    /**
     * حفظ حقول من مولد الوحدات
     */
    public static function saveFieldsFromGenerator($moduleName, $fields, $createdBy = 'generator', $tableName = null, $moduleArabicName = null)
    {
        foreach ($fields as $field) {
            // توليد قواعد التحقق تلقائياً إذا لم تكن موجودة
            $validationRules = $field['validation'] ?? $field['validation_rules'] ?? $field['rules'] ?? null;
            if (!$validationRules) {
                $validationRules = static::generateValidationRules($field, $moduleName);
            }

            // توليد رسائل التحقق تلقائياً إذا لم تكن موجودة
            $validationMessages = $field['validation_messages'] ?? $field['messages'] ?? null;
            if (!$validationMessages) {
                $validationMessages = static::generateValidationMessages($field);
            }

            // توليد خصائص HTML مخصصة تلقائياً إذا لم تكن موجودة
            $customAttributes = $field['custom_attributes'] ?? $field['attributes'] ?? $field['custom'] ?? null;
            if (!$customAttributes) {
                $customAttributes = static::generateCustomAttributes($field);
            }

            static::addOrUpdateField($moduleName, [
                'table_name' => $tableName,
                'module_arabic_name' => $moduleArabicName,
                'field_name' => $field['name'],
                'field_type' => $field['type'] ?? 'text',
                'arabic_name' => $field['ar_name'] ?? $field['name'],
                'english_name' => $field['english_name'] ?? $field['name'],
                'required' => $field['required'] ?? false,
                'unique' => $field['unique'] ?? false,
                'searchable' => $field['searchable'] ?? true,
                'show_in_table' => $field['show_in_table'] ?? true,
                'show_in_search' => $field['show_in_search'] ?? true,
                'show_in_forms' => $field['show_in_forms'] ?? true,
                'max_length' => $field['max'] ?? $field['size'] ?? $field['max_length'] ?? null,
                'arabic_only' => $field['arabic_only'] ?? false,
                'numeric_only' => $field['numeric_only'] ?? false,
                // إعدادات النص الجديدة
                'text_content_type' => $field['text_content_type'] ?? 'any',
                // إعدادات الأرقام الصحيحة الجديدة
                'integer_type' => $field['integer_type'] ?? 'int',
                'unsigned' => $field['unsigned'] ?? false,
                // إعدادات الأرقام العشرية الجديدة
                'decimal_precision' => $field['decimal_precision'] ?? 15,
                'decimal_scale' => $field['decimal_scale'] ?? 2,
                'file_types' => $field['file_types'] ?? null,
                'select_options' => $field['select_options'] ?? $field['options'] ?? null,
                'select_source' => $field['select_source'] ?? 'manual',
                'select_numeric_values' => $field['select_numeric_values'] ?? false,
                'related_table' => $field['related_table'] ?? null,
                'related_key' => $field['related_key'] ?? 'id',
                'related_display' => $field['related_display'] ?? 'name',
                'is_calculated' => $field['is_calculated'] ?? false,
                'calculation_formula' => $field['calculation_formula'] ?? null,
                'calculation_type' => $field['calculation_type'] ?? 'none',
                'date_from_field' => $field['date_from_field'] ?? null,
                'date_to_field' => $field['date_to_field'] ?? null,
                'date_diff_unit' => $field['date_diff_unit'] ?? 'days',
                'include_end_date' => $field['include_end_date'] ?? false,
                'absolute_value' => $field['absolute_value'] ?? false,
                'remaining_only' => $field['remaining_only'] ?? false,
                'is_date_calculated' => $field['is_date_calculated'] ?? (($field['calculation_type'] ?? 'none') === 'date_diff'),
                'date_calculation_config' => $field['date_calculation_config'] ?? null,
                // حقول حساب الوقت
                'time_from_field' => $field['time_from_field'] ?? null,
                'time_to_field' => $field['time_to_field'] ?? null,
                'time_diff_unit' => $field['time_diff_unit'] ?? 'minutes',
                'is_time_calculated' => $field['is_time_calculated'] ?? (($field['calculation_type'] ?? 'none') === 'time_diff'),
                'time_calculation_config' => $field['time_calculation_config'] ?? null,
                'validation_rules' => $validationRules,
                'validation_messages' => $validationMessages,
                'custom_attributes' => $customAttributes,
                'created_by' => $createdBy,
                'active' => true
            ]);
        }
    }

    /**
     * تحويل حقول الوحدة إلى قواعد validation
     */
    public static function getValidationRules($moduleName, $isUpdate = false, $recordId = null)
    {
        $fields = static::getModuleFields($moduleName);
        $rules = [];

        foreach ($fields as $field) {
            $fieldRules = [];

            // إضافة required إذا كان الحقل مطلوب (مع معاملة خاصة للملفات في التحديث)
            if ($field->required) {
                // للملفات: في التحديث تكون nullable، في الإضافة تكون required
                if ($field->field_type === 'file' && $isUpdate) {
                    $fieldRules[] = 'nullable';
                } else {
                    $fieldRules[] = 'required';
                }
            } else {
                $fieldRules[] = 'nullable';
            }

            // إضافة unique rules
            if ($field->unique) {
                if ($isUpdate && $recordId) {
                    $tableName = \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($moduleName));
                    $fieldRules[] = "unique:{$tableName},{$field->field_name},{$recordId},id";
                } else {
                    $tableName = \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($moduleName));
                    $fieldRules[] = "unique:{$tableName},{$field->field_name}";
                }
            }

            // إضافة قواعد حسب نوع الحقل
            switch ($field->field_type) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'number':
                case 'integer':
                    $fieldRules[] = 'numeric';
                    // إضافة قواعد حسب نوع الرقم الصحيح
                    static::addIntegerValidationRules($fieldRules, $field);
                    break;
                case 'decimal':
                    $fieldRules[] = 'regex:/^\d+(\.\d{1,2})?$/';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'file':
                    $fieldRules[] = 'file';
                    if ($field->file_types) {
                        $fieldRules[] = 'mimes:' . $field->file_types;
                    }
                    break;
            }

            // إضافة قواعد إضافية من validation_rules (مع تجاهل unique إذا كان موجود)
            if ($field->validation_rules) {
                $additionalRules = explode('|', $field->validation_rules);
                // تصفية unique rules المخزنة لتجنب التضارب مع المبنية ديناميكياً
                $additionalRules = array_filter($additionalRules, function($rule) {
                    return !str_starts_with($rule, 'unique:');
                });

                // للملفات في التحديث: تجاهل required من validation_rules
                if ($field->field_type === 'file' && $isUpdate) {
                    $additionalRules = array_filter($additionalRules, function($rule) {
                        return $rule !== 'required';
                    });
                }

                $fieldRules = array_merge($fieldRules, $additionalRules);
            } else {
                // إضافة max length فقط إذا لم تكن موجودة في validation_rules
                if ($field->max_length) {
                    $fieldRules[] = "max:{$field->max_length}";
                }
            }

            // إضافة regex للأحرف العربية (الطريقة القديمة للتوافق)
            if ($field->arabic_only) {
                $fieldRules[] = 'regex:/^[\p{Arabic}\s]+$/u';
            }

            // إضافة regex للإعدادات الجديدة
            if ($field->text_content_type === 'arabic_only') {
                $fieldRules[] = 'regex:/^[\p{Arabic}\s]+$/u';
            } elseif ($field->text_content_type === 'numeric_only') {
                $fieldRules[] = 'regex:/^[0-9]+$/';
            } elseif ($field->text_content_type === 'english_only') {
                $fieldRules[] = 'regex:/^[a-zA-Z\s]+$/';
            }

            // إزالة التكرارات وتجميع القواعد
            $fieldRules = array_unique($fieldRules);
            $rules[$field->field_name] = implode('|', $fieldRules);
        }

        return $rules;
    }

    /**
     * تحويل حقول الوحدة إلى رسائل validation
     */
    public static function getValidationMessages($moduleName)
    {
        $fields = static::getModuleFields($moduleName);
        $messages = [];

        foreach ($fields as $field) {
            $arabicName = $field->arabic_name;

            if ($field->required) {
                $messages["{$field->field_name}.required"] = "يرجى إدخال {$arabicName}";
            }

            if ($field->unique) {
                $messages["{$field->field_name}.unique"] = "{$arabicName} موجود بالفعل";
            }

            if ($field->max_length) {
                $messages["{$field->field_name}.max"] = "{$arabicName} يجب أن يكون أقل من {$field->max_length} حرف";
            }

            if ($field->field_type === 'email') {
                $messages["{$field->field_name}.email"] = "يرجى إدخال بريد إلكتروني صحيح";
            }

            if ($field->field_type === 'number') {
                $messages["{$field->field_name}.numeric"] = "{$arabicName} يجب أن يكون رقم";
            }

            // إضافة رسائل للأرقام الصحيحة حسب نوعها
            if (in_array($field->field_type, ['number', 'integer'])) {
                static::addIntegerValidationMessages($messages, $field, $arabicName);
            }

            if ($field->field_type === 'decimal') {
                static::addDecimalValidationMessages($messages, $field, $arabicName);
            }

            if ($field->field_type === 'date') {
                $messages["{$field->field_name}.date"] = "يرجى إدخال تاريخ صحيح";
            }

            if ($field->arabic_only || $field->text_content_type === 'arabic_only') {
                $messages["{$field->field_name}.regex"] = "{$arabicName} يجب أن يحتوي على أحرف عربية فقط";
            }

            if ($field->text_content_type === 'numeric_only') {
                $messages["{$field->field_name}.regex"] = "{$arabicName} يجب أن يحتوي على أرقام فقط";
            }

            if ($field->text_content_type === 'english_only') {
                $messages["{$field->field_name}.regex"] = "{$arabicName} يجب أن يحتوي على أحرف إنجليزية فقط";
            }

            // دمج الرسائل المخصصة من قاعدة البيانات إذا كانت موجودة
            if ($field->validation_messages && is_array($field->validation_messages)) {
                foreach ($field->validation_messages as $rule => $message) {
                    $messages["{$field->field_name}.{$rule}"] = $message;
                }
            }
        }

        return $messages;
    }

    /**
     * توليد قواعد التحقق تلقائياً من خصائص الحقل
     */
    private static function generateValidationRules($field, $moduleName = null)
    {
        $rules = [];

        // إضافة required أو nullable
        if ($field['required'] ?? false) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // إضافة قواعد حسب نوع الحقل
        $fieldType = $field['type'] ?? 'text';
        switch ($fieldType) {
            case 'email':
                $rules[] = 'email';
                break;
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'file':
                $rules[] = 'file';
                if (!empty($field['file_types'])) {
                    $rules[] = 'mimes:' . $field['file_types'];
                }
                break;
            case 'string':
            case 'text':
                $rules[] = 'string';
                break;
        }

        // إضافة max length
        if (!empty($field['size']) || !empty($field['max_length'])) {
            $maxLength = $field['size'] ?? $field['max_length'];
            $rules[] = "max:{$maxLength}";
        }

        // إضافة unique
        if ($field['unique'] ?? false) {
            $tableName = \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($moduleName ?: 'table'));
            $rules[] = "unique:{$tableName},{$field['name']}";
        }

        // إضافة regex للأحرف العربية
        if ($field['arabic_only'] ?? false) {
            $rules[] = 'regex:/^[\p{Arabic}\s]+$/u';
        }

        return implode('|', $rules);
    }

    /**
     * توليد رسائل التحقق تلقائياً من خصائص الحقل
     */
    private static function generateValidationMessages($field)
    {
        $messages = [];
        $arabicName = $field['ar_name'] ?? $field['name'];

        // رسالة required
        if ($field['required'] ?? false) {
            $messages['required'] = "يرجى إدخال {$arabicName}";
        }

        // رسالة unique
        if ($field['unique'] ?? false) {
            $messages['unique'] = "{$arabicName} موجود بالفعل";
        }

        // رسالة max
        if (!empty($field['size']) || !empty($field['max_length'])) {
            $maxLength = $field['size'] ?? $field['max_length'];
            $messages['max'] = "{$arabicName} يجب أن يكون أقل من {$maxLength} حرف";
        }

        // رسائل حسب نوع الحقل
        $fieldType = $field['type'] ?? 'text';
        switch ($fieldType) {
            case 'email':
                $messages['email'] = "يرجى إدخال بريد إلكتروني صحيح";
                break;
            case 'number':
            case 'integer':
                $messages['numeric'] = "{$arabicName} يجب أن يكون رقم";
                // إضافة رسائل للأرقام الصحيحة حسب نوعها
                static::addIntegerValidationMessagesForField($messages, $field, $arabicName);
                break;
            case 'decimal':
                $messages['numeric'] = "{$arabicName} يجب أن يكون رقم";
                // إضافة رسائل للأرقام العشرية حسب نوعها
                static::addDecimalValidationMessagesForField($messages, $field, $arabicName);
                break;
            case 'date':
                $messages['date'] = "يرجى إدخال تاريخ صحيح";
                break;
            case 'file':
                $messages['file'] = "يرجى اختيار ملف صحيح";
                if (!empty($field['file_types'])) {
                    $messages['mimes'] = "نوع الملف يجب أن يكون: {$field['file_types']}";
                }
                break;
        }

        // رسالة regex للأحرف العربية
        if ($field['arabic_only'] ?? false) {
            $messages['regex'] = "{$arabicName} يجب أن يحتوي على أحرف عربية فقط";
        }

        return $messages;
    }

    /**
     * توليد خصائص HTML مخصصة تلقائياً من خصائص الحقل
     */
    private static function generateCustomAttributes($field)
    {
        $attributes = [];
        $arabicName = $field['ar_name'] ?? $field['name'];
        $fieldType = $field['type'] ?? 'text';

        // إضافة placeholder حسب نوع الحقل
        switch ($fieldType) {
            case 'email':
                $attributes['placeholder'] = 'example@domain.com';
                $attributes['autocomplete'] = 'email';
                break;
            case 'password':
                $attributes['placeholder'] = 'أدخل كلمة المرور';
                $attributes['autocomplete'] = 'new-password';
                break;
            case 'number':
                $attributes['placeholder'] = 'أدخل رقم';
                $attributes['inputmode'] = 'numeric';
                break;
            case 'date':
                $attributes['placeholder'] = 'YYYY-MM-DD';
                break;
            case 'file':
                if (!empty($field['file_types'])) {
                    $attributes['accept'] = '.' . str_replace(',', ',.', $field['file_types']);
                }
                break;
            case 'string':
            case 'text':
            default:
                $attributes['placeholder'] = "أدخل {$arabicName}";
                break;
        }

        // إضافة class أساسية
        $classes = ['form-control'];

        // إضافة class للحقول المطلوبة
        if ($field['required'] ?? false) {
            $classes[] = 'required';
            $attributes['required'] = 'required';
        }

        // إضافة class للحقول العربية فقط
        if ($field['arabic_only'] ?? false) {
            $classes[] = 'arabic-only';
            $attributes['dir'] = 'rtl';
        }

        // إضافة class للأرقام فقط
        if ($field['numeric_only'] ?? false) {
            $classes[] = 'numeric-only';
        }

        $attributes['class'] = implode(' ', $classes);

        // إضافة maxlength
        if (!empty($field['size']) || !empty($field['max_length'])) {
            $maxLength = $field['size'] ?? $field['max_length'];
            $attributes['maxlength'] = (string)$maxLength;
        }

        // إضافة pattern للأحرف العربية
        if ($field['arabic_only'] ?? false) {
            $attributes['pattern'] = '[\u0600-\u06FF\s]+';
            $attributes['title'] = 'يجب إدخال أحرف عربية فقط';
        }

        // إضافة pattern للأرقام فقط
        if ($field['numeric_only'] ?? false) {
            $attributes['pattern'] = '[0-9]+';
            $attributes['title'] = 'يجب إدخال أرقام فقط';
        }

        return $attributes;
    }

    /**
     * التحقق من كون الحقل محسوب للتاريخ
     */
    /* public function isDateCalculated()
    {
        return $this->calculation_type === 'date_diff' && $this->is_date_calculated;
    } */

    /**
     * الحصول على إعدادات حساب التاريخ
     */
    /* public function getDateCalculationSettings()
    {
        if (!$this->isDateCalculated()) {
            return null;
        }

        return [
            'from_field' => $this->date_from_field,
            'to_field' => $this->date_to_field,
            'unit' => $this->date_diff_unit,
            'include_end_date' => $this->include_end_date,
            'absolute_value' => $this->absolute_value,
            'remaining_only' => $this->remaining_only,
            'config' => $this->date_calculation_config
        ];
    } */

    /**
     * توليد كود حساب التاريخ للاستخدام في المولد
     */
    /* public function generateDateCalculationCode($itemVariable = '$item')
    {
        if (!$this->isDateCalculated()) {
            return null;
        }

        $fromField = $this->date_from_field;
        $toField = $this->date_to_field;
        $unit = $this->date_diff_unit;
        $includeEndDate = $this->include_end_date ? 'true' : 'false';
        $absoluteValue = $this->absolute_value ? 'true' : 'false';
        $remainingOnly = $this->remaining_only ? 'true' : 'false';

        return "
        try {
            if ({$itemVariable}->{$fromField} && {$itemVariable}->{$toField}) {
                \$fromDate = \Carbon\Carbon::parse({$itemVariable}->{$fromField});
                \$toDate = \Carbon\Carbon::parse({$itemVariable}->{$toField});

                if ({$remainingOnly}) {
                    // حساب المتبقي فقط باستخدام diff
                    \$diff = \$fromDate->diff(\$toDate);
                    if ('{$unit}' === 'days') {
                        \$result = \$diff->d; // الأيام المتبقية فقط
                    } elseif ('{$unit}' === 'months') {
                        \$result = \$diff->m; // الأشهر المتبقية فقط
                    } elseif ('{$unit}' === 'years') {
                        \$result = \$diff->y; // السنوات فقط
                    } else {
                        \$result = 0;
                    }
                } else {
                    // حساب كامل - إجمالي الفرق بالوحدة المحددة
                    \$result = \$fromDate->diffIn" . ucfirst($unit) . "(\$toDate, {$absoluteValue});
                    if ({$includeEndDate} && '{$unit}' === 'days') \$result += 1;
                }

                if ({$absoluteValue}) {
                    \$result = abs(\$result);
                }

                {$itemVariable}->{$this->field_name} = \$result;
            } else {
                {$itemVariable}->{$this->field_name} = 0;
            }
        } catch (\Exception \$e) {
            {$itemVariable}->{$this->field_name} = 0;
        }";
    }
 */
    /**
     * توليد معاينة لحساب التاريخ
     */
    /* public function generateDateCalculationPreview()
    {
        if (!$this->isDateCalculated()) {
            return 'غير محسوب للتاريخ';
        }

        $preview = "حساب الفرق بين {$this->date_from_field} و {$this->date_to_field}";

        if ($this->remaining_only) {
            if ($this->date_diff_unit === 'days') {
                $preview .= " (الأيام المتبقية من الشهر)";
            } elseif ($this->date_diff_unit === 'months') {
                $preview .= " (الأشهر المتبقية من السنة)";
            }
        } else {
            $preview .= " بوحدة " . $this->getUnitLabel();
        }

        if ($this->include_end_date) {
            $preview .= " + شمل التاريخ النهائي";
        }

        if ($this->absolute_value) {
            $preview .= " (قيمة مطلقة)";
        }

        return $preview;
    } */

    /**
     * الحصول على تسمية الوحدة بالعربية
     */
    /* private function getUnitLabel()
    {
        $units = [
            'days' => 'أيام',
            'months' => 'أشهر',
            'years' => 'سنوات',
            'hours' => 'ساعات',
            'minutes' => 'دقائق'
        ];

        return $units[$this->date_diff_unit] ?? $this->date_diff_unit;
    } */

    /**
     * التحقق من كون الحقل محسوب للوقت
     */
    /* public function isTimeCalculated()
    {
        return $this->calculation_type === 'time_diff' && $this->is_time_calculated;
    } */

    /**
     * الحصول على إعدادات حساب الوقت
     */
    /* public function getTimeCalculationSettings()
    {
        if (!$this->isTimeCalculated()) {
            return null;
        }

        return [
            'from_field' => $this->time_from_field,
            'to_field' => $this->time_to_field,
            'unit' => $this->time_diff_unit,
            'absolute_value' => $this->absolute_value,
            'config' => $this->time_calculation_config
        ];
    } */

    /**
     * توليد كود حساب الوقت للاستخدام في المولد
     */
    /* public function generateTimeCalculationCode($itemVariable = '$item')
    {
        if (!$this->isTimeCalculated()) {
            return '';
        }

        $fromField = $this->time_from_field;
        $toField = $this->time_to_field;
        $unit = $this->time_diff_unit;
        $absoluteValue = $this->absolute_value ? 'true' : 'false';
        $remainingOnly = $this->remaining_only ? 'true' : 'false';

        return "
        try {
            // التعامل مع حقول datetime أو time
            if ({$itemVariable}->{$fromField} && {$itemVariable}->{$toField}) {
                // إذا كان الحقل datetime، نستخرج جزء الوقت فقط
                \$fromDateTime = \Carbon\Carbon::parse({$itemVariable}->{$fromField});
                \$toDateTime = \Carbon\Carbon::parse({$itemVariable}->{$toField});

                // إنشاء أوقات فقط من datetime أو time
                \$fromTime = \Carbon\Carbon::createFromTime(\$fromDateTime->hour, \$fromDateTime->minute, \$fromDateTime->second);
                \$toTime = \Carbon\Carbon::createFromTime(\$toDateTime->hour, \$toDateTime->minute, \$toDateTime->second);

                if ('{$unit}' === 'hours') {
                    \$diff = \$toTime->diffInHours(\$fromTime, false);
                    if ({$remainingOnly}) {
                        \$diff = \$diff % 24; // الساعات المتبقية بعد الأيام الكاملة
                    }
                } elseif ('{$unit}' === 'minutes') {
                    \$diff = \$toTime->diffInMinutes(\$fromTime, false);
                    if ({$remainingOnly}) {
                        \$diff = \$diff % 60; // الدقائق المتبقية بعد الساعات الكاملة
                    }
                } else {
                    \$diff = \$toTime->diffInMinutes(\$fromTime, false);
                }

                if ({$absoluteValue}) {
                    \$diff = abs(\$diff);
                }

                {$itemVariable}->{$this->field_name} = \$diff;
            } else {
                {$itemVariable}->{$this->field_name} = 0;
            }
        } catch (\Exception \$e) {
            {$itemVariable}->{$this->field_name} = 0;
        }";
    } */

    /**
     * توليد معاينة لحساب الوقت
     */
    /* public function generateTimeCalculationPreview()
    {
        if (!$this->isTimeCalculated()) {
            return '';
        }

        $preview = "حساب الفرق بين {$this->time_from_field} و {$this->time_to_field}";

        $units = [
            'hours' => 'بالساعات',
            'minutes' => 'بالدقائق'
        ];

        $preview .= " " . ($units[$this->time_diff_unit] ?? $this->time_diff_unit);

        if ($this->absolute_value) {
            $preview .= " (قيمة مطلقة)";
        }

        return $preview;
    } */

    /**
     * إضافة قواعد التحقق للأرقام الصحيحة حسب نوعها
     */
    private static function addIntegerValidationRules(&$fieldRules, $field)
    {
        $integerType = $field->integer_type ?? 'int';
        $unsigned = $field->unsigned ?? false;

        // تحديد الحدود لكل نوع من أنواع الأرقام الصحيحة
        $limits = [
            'tinyint' => [
                'signed' => ['min' => -128, 'max' => 127],
                'unsigned' => ['min' => 0, 'max' => 255]
            ],
            'smallint' => [
                'signed' => ['min' => -32768, 'max' => 32767],
                'unsigned' => ['min' => 0, 'max' => 65535]
            ],
            'int' => [
                'signed' => ['min' => -2147483648, 'max' => 2147483647],
                'unsigned' => ['min' => 0, 'max' => 4294967295]
            ],
            'bigint' => [
                'signed' => ['min' => '-9223372036854775808', 'max' => '9223372036854775807'],
                'unsigned' => ['min' => 0, 'max' => '18446744073709551615']
            ]
        ];

        if (isset($limits[$integerType])) {
            $range = $unsigned ? $limits[$integerType]['unsigned'] : $limits[$integerType]['signed'];

            // إضافة قواعد min و max
            $fieldRules[] = "min:{$range['min']}";
            $fieldRules[] = "max:{$range['max']}";

            // إضافة قاعدة integer للتأكد من أنه رقم صحيح
            $fieldRules[] = 'integer';
        }
    }

    /**
     * إضافة رسائل التحقق للأرقام الصحيحة حسب نوعها
     */
    private static function addIntegerValidationMessages(&$messages, $field, $arabicName)
    {
        $integerType = $field->integer_type ?? 'int';
        $unsigned = $field->unsigned ?? false;

        // أسماء أنواع الأرقام بالعربية
        $typeNames = [
            'tinyint' => 'رقم صحيح صغير جداً',
            'smallint' => 'رقم صحيح صغير',
            'int' => 'رقم صحيح',
            'bigint' => 'رقم صحيح كبير'
        ];

        // تحديد الحدود لكل نوع من أنواع الأرقام الصحيحة
        $limits = [
            'tinyint' => [
                'signed' => ['min' => -128, 'max' => 127, 'digits' => '3 أرقام'],
                'unsigned' => ['min' => 0, 'max' => 255, 'digits' => '3 أرقام']
            ],
            'smallint' => [
                'signed' => ['min' => -32768, 'max' => 32767, 'digits' => '5 أرقام'],
                'unsigned' => ['min' => 0, 'max' => 65535, 'digits' => '5 أرقام']
            ],
            'int' => [
                'signed' => ['min' => -2147483648, 'max' => 2147483647, 'digits' => '10 أرقام'],
                'unsigned' => ['min' => 0, 'max' => 4294967295, 'digits' => '10 أرقام']
            ],
            'bigint' => [
                'signed' => ['min' => '-9223372036854775808', 'max' => '9223372036854775807', 'digits' => '19 رقماً'],
                'unsigned' => ['min' => 0, 'max' => '18446744073709551615', 'digits' => '20 رقماً']
            ]
        ];

        if (isset($limits[$integerType])) {
            $range = $unsigned ? $limits[$integerType]['unsigned'] : $limits[$integerType]['signed'];
            $typeName = $typeNames[$integerType] ?? 'رقم صحيح';
            $signText = $unsigned ? ' موجب' : '';

            // رسائل التحقق
            $messages["{$field->field_name}.integer"] = "{$arabicName} يجب أن يكون {$typeName}{$signText}";
            $messages["{$field->field_name}.numeric"] = "{$arabicName} يجب أن يكون {$typeName}{$signText}";
            $messages["{$field->field_name}.min"] = "{$arabicName} يجب أن يكون أكبر من أو يساوي {$range['min']}";
            $messages["{$field->field_name}.max"] = "{$arabicName} يجب أن يكون أصغر من أو يساوي {$range['max']} (حد أقصى {$range['digits']})";
        }
    }

    /**
     * إضافة رسائل التحقق للأرقام الصحيحة حسب نوعها - للاستخدام في generateValidationMessages
     */
    private static function addIntegerValidationMessagesForField(&$messages, $field, $arabicName)
    {
        $integerType = $field['integer_type'] ?? 'int';
        $unsigned = $field['unsigned'] ?? false;

        // أسماء أنواع الأرقام بالعربية
        $typeNames = [
            'tinyint' => 'رقم صحيح صغير جداً',
            'smallint' => 'رقم صحيح صغير',
            'int' => 'رقم صحيح',
            'bigint' => 'رقم صحيح كبير'
        ];

        // تحديد الحدود لكل نوع من أنواع الأرقام الصحيحة
        $limits = [
            'tinyint' => [
                'signed' => ['min' => -128, 'max' => 127, 'digits' => '3 أرقام'],
                'unsigned' => ['min' => 0, 'max' => 255, 'digits' => '3 أرقام']
            ],
            'smallint' => [
                'signed' => ['min' => -32768, 'max' => 32767, 'digits' => '5 أرقام'],
                'unsigned' => ['min' => 0, 'max' => 65535, 'digits' => '5 أرقام']
            ],
            'int' => [
                'signed' => ['min' => -2147483648, 'max' => 2147483647, 'digits' => '10 أرقام'],
                'unsigned' => ['min' => 0, 'max' => 4294967295, 'digits' => '10 أرقام']
            ],
            'bigint' => [
                'signed' => ['min' => '-9223372036854775808', 'max' => '9223372036854775807', 'digits' => '19 رقماً'],
                'unsigned' => ['min' => 0, 'max' => '18446744073709551615', 'digits' => '20 رقماً']
            ]
        ];

        if (isset($limits[$integerType])) {
            $range = $unsigned ? $limits[$integerType]['unsigned'] : $limits[$integerType]['signed'];
            $typeName = $typeNames[$integerType] ?? 'رقم صحيح';
            $signText = $unsigned ? ' موجب' : '';

            // رسائل التحقق
            $messages['integer'] = "{$arabicName} يجب أن يكون {$typeName}{$signText}";
            $messages['numeric'] = "{$arabicName} يجب أن يكون {$typeName}{$signText}";
            $messages['min'] = "{$arabicName} يجب أن يكون أكبر من أو يساوي {$range['min']}";
            $messages['max'] = "{$arabicName} يجب أن يكون أصغر من أو يساوي {$range['max']} (حد أقصى {$range['digits']})";
        }
    }

    /**
     * إضافة رسائل التحقق للأرقام العشرية حسب الدقة والمقياس
     */
    private static function addDecimalValidationMessages(&$messages, $field, $arabicName)
    {
        $precision = $field->decimal_precision ?? 15;
        $scale = $field->decimal_scale ?? 2;

        // حساب عدد الأرقام قبل العلامة العشرية
        $integerDigits = $precision - $scale;

        // إنشاء مثال ديناميكي
        $exampleInteger = str_repeat('1', min($integerDigits, 3)); // حد أقصى 3 أرقام للمثال
        $exampleDecimal = str_repeat('5', $scale);
        $example = $scale > 0 ? "{$exampleInteger}.{$exampleDecimal}" : $exampleInteger;

        // رسالة regex مخصصة
        if ($scale > 0) {
            // للأرقام العشرية
            $messages["{$field->field_name}.regex"] = "{$arabicName} يجب أن يكون رقم عشري صحيح بحد أقصى {$integerDigits} أرقام قبل الفاصلة و{$scale} أرقام بعد الفاصلة (مثال: {$example})";
        } else {
            // للأرقام الصحيحة بدون مراتب عشرية
            $messages["{$field->field_name}.regex"] = "{$arabicName} يجب أن يكون رقم صحيح بحد أقصى {$precision} أرقام (مثال: {$example})";
        }

        // رسالة numeric عامة
        $messages["{$field->field_name}.numeric"] = "{$arabicName} يجب أن يكون رقم صالح";

        // رسالة للحد الأقصى للأرقام (اختيارية)
        $totalLength = $precision + ($scale > 0 ? 1 : 0); // +1 للفاصلة العشرية
        $messages["{$field->field_name}.max"] = "{$arabicName} يجب ألا يتجاوز {$totalLength} خانات إجمالية";
    }

    /**
     * إضافة رسائل التحقق للأرقام العشرية حسب نوعها - للاستخدام في generateValidationMessages
     */
    private static function addDecimalValidationMessagesForField(&$messages, $field, $arabicName)
    {
        $precision = $field['decimal_precision'] ?? 15;
        $scale = $field['decimal_scale'] ?? 2;

        // حساب عدد الأرقام قبل العلامة العشرية
        $integerDigits = $precision - $scale;

        // إنشاء مثال ديناميكي
        $exampleInteger = str_repeat('1', min($integerDigits, 3)); // حد أقصى 3 أرقام للمثال
        $exampleDecimal = str_repeat('5', $scale);
        $example = $scale > 0 ? "{$exampleInteger}.{$exampleDecimal}" : $exampleInteger;

        // رسالة regex مخصصة
        if ($scale > 0) {
            // للأرقام العشرية
            $messages['regex'] = "{$arabicName} يجب أن يكون رقم عشري صحيح بحد أقصى {$integerDigits} أرقام قبل الفاصلة و{$scale} أرقام بعد الفاصلة (مثال: {$example})";
        } else {
            // للأرقام الصحيحة بدون مراتب عشرية
            $messages['regex'] = "{$arabicName} يجب أن يكون رقم صحيح بحد أقصى {$precision} أرقام (مثال: {$example})";
        }

        // رسالة للحد الأقصى للأرقام (اختيارية)
        $totalLength = $precision + ($scale > 0 ? 1 : 0); // +1 للفاصلة العشرية
        $messages['max'] = "{$arabicName} يجب ألا يتجاوز {$totalLength} خانات إجمالية";
    }

    /**
     * الحصول على معلومات الوحدة الأساسية
     */
    public static function getModuleInfo($moduleName)
    {
        $field = static::where('module_name', $moduleName)->first();

        if (!$field) {
            return [
                'table_name' => null,
                'module_arabic_name' => null,
                'exists' => false
            ];
        }

        return [
            'table_name' => $field->table_name,
            'module_arabic_name' => $field->module_arabic_name,
            'exists' => true
        ];
    }

    /**
     * الحصول على اسم الجدول للوحدة
     */
    public static function getModuleTableName($moduleName)
    {
        $info = static::getModuleInfo($moduleName);
        return $info['table_name'];
    }

    /**
     * الحصول على الاسم العربي للوحدة
     */
    public static function getModuleArabicName($moduleName)
    {
        $info = static::getModuleInfo($moduleName);
        return $info['module_arabic_name'];
    }

    /**
     * تحديث معلومات الوحدة الأساسية
     */
    public static function updateModuleInfo($moduleName, $tableName = null, $moduleArabicName = null)
    {
        return static::where('module_name', $moduleName)
                    ->update([
                        'table_name' => $tableName,
                        'module_arabic_name' => $moduleArabicName,
                        'updated_at' => now()
                    ]);
    }

    /**
     * الحصول على قائمة جميع الوحدات مع معلوماتها الأساسية
     */
    public static function getAllModulesInfo()
    {
        return static::select('module_name', 'table_name', 'module_arabic_name')
                    ->groupBy('module_name', 'table_name', 'module_arabic_name')
                    ->orderBy('module_name')
                    ->get()
                    ->keyBy('module_name');
    }


}
