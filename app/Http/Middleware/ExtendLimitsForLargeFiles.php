<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExtendLimitsForLargeFiles
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // التحقق من وجود headers خاصة بالملفات الكبيرة أو عمليات Livewire
        $isLargeFileOperation = $this->isLargeFileOperation($request);

        if ($isLargeFileOperation) {
            Log::info('🔧 MIDDLEWARE: تطبيق إعدادات خاصة للملفات الكبيرة');

            // تطبيق إعدادات PHP محسنة للملفات الكبيرة
            $this->applyLargeFileSettings();
        }

        return $next($request);
    }

    /**
     * فحص إذا كان الطلب متعلق بملفات كبيرة
     */
    private function isLargeFileOperation(Request $request): bool
    {
        // فحص headers مخصصة
        if ($request->hasHeader('X-PHP-EXTEND-LIMITS') ||
            $request->hasHeader('X-PHP-Max-Execution-Time') ||
            $request->hasHeader('X-Custom-Request')) {
            return true;
        }

        // فحص إذا كان طلب Livewire للاستيراد/التصدير
        if ($request->hasHeader('X-Livewire')) {
            $payload = json_decode($request->getContent(), true);

            if (isset($payload['calls'])) {
                foreach ($payload['calls'] as $call) {
                    if (in_array($call['method'] ?? '', [
                        'importData',
                        'exportData',
                        'testFile',
                        'downloadTemplate'
                    ])) {
                        return true;
                    }
                }
            }
        }

        // فحص حجم الملف المرفوع
        if ($request->hasFile('importFile')) {
            $file = $request->file('importFile');
            $fileSizeMB = $file->getSize() / 1024 / 1024;

            if ($fileSizeMB > 2) { // أكبر من 2 ميجابايت
                return true;
            }
        }

        // فحص المسار
        if (str_contains($request->getPathInfo(), 'data-management') &&
            in_array($request->getMethod(), ['POST', 'PUT'])) {
            return true;
        }

        return false;
    }

    /**
     * تطبيق إعدادات محسنة للملفات الكبيرة
     */
    private function applyLargeFileSettings(): void
    {
        try {
            // زيادة حد وقت التنفيذ إلى 30 دقيقة
            $maxExecutionTime = 1800; // 30 دقيقة
            if (function_exists('ini_set')) {
                ini_set('max_execution_time', $maxExecutionTime);
                Log::info("✅ تم تعيين max_execution_time إلى {$maxExecutionTime} ثانية");
            }

            // زيادة حد الذاكرة إلى 512MB
            if (function_exists('ini_set')) {
                ini_set('memory_limit', '512M');
                Log::info("✅ تم تعيين memory_limit إلى 512M");
            }

            // تحسين إعدادات رفع الملفات
            if (function_exists('ini_set')) {
                ini_set('upload_max_filesize', '50M');
                ini_set('post_max_size', '100M');
                ini_set('max_file_uploads', '20');
                Log::info("✅ تم تحسين إعدادات رفع الملفات");
            }

            // تحسين إعدادات Input
            if (function_exists('ini_set')) {
                ini_set('max_input_vars', '5000');
                ini_set('max_input_time', '600'); // 10 دقائق
                Log::info("✅ تم تحسين إعدادات الإدخال");
            }

            // إعدادات إضافية للأداء
            if (function_exists('ini_set')) {
                ini_set('pcre.backtrack_limit', '5000000');
                ini_set('pcre.recursion_limit', '100000');
                Log::info("✅ تم تحسين إعدادات PCRE");
            }

            // تنظيف Garbage Collection
            if (function_exists('gc_enable')) {
                gc_enable();
                gc_collect_cycles();
                Log::info("✅ تم تفعيل وتشغيل Garbage Collection");
            }

            Log::info('🎯 MIDDLEWARE: تم تطبيق جميع الإعدادات المحسنة للملفات الكبيرة بنجاح');

        } catch (\Exception $e) {
            Log::warning('⚠️ MIDDLEWARE: فشل في تطبيق بعض الإعدادات: ' . $e->getMessage());
        }
    }

    /**
     * الحصول على معلومات الإعدادات الحالية
     */
    public static function getCurrentSettings(): array
    {
        return [
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'max_input_vars' => ini_get('max_input_vars'),
            'max_input_time' => ini_get('max_input_time'),
        ];
    }
}
