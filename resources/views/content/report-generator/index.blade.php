@extends('layouts/layoutMaster')

@section('title', 'مولد التقارير')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/bootstrap-select/bootstrap-select.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('page-script')
    <script>
        // تهيئة Chart.js
        Chart.defaults.font.family = 'Tajawal, "Public Sans", sans-serif';
        Chart.defaults.font.size = 12;

        // دعم الاتجاه من اليمين لليسار
        Chart.defaults.plugins.legend.rtl = true;
        Chart.defaults.plugins.legend.textDirection = 'rtl';

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing charts...');
            setTimeout(initializeReportCharts, 500);

            // تهيئة Bootstrap Select
            if (typeof $('.selectpicker').selectpicker === 'function') {
                $('.selectpicker').selectpicker();
            }

            // تهيئة Flatpickr للتواريخ
            if (typeof flatpickr === 'function') {
                flatpickr('.date-picker', {
                    dateFormat: 'Y-m-d',
                    locale: 'ar'
                });
            }
        });

        // إعداد Toast مثل نظام Employees
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-start',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        })

        // مستمعات أحداث Livewire للرسائل - نفس طريقة Employees
        window.addEventListener('success', event => {
            Toast.fire({
                icon: 'success',
                title: event.detail.title + '<hr>' + event.detail.message,
            })
        })

        window.addEventListener('error', event => {
            Toast.fire({
                icon: 'error',
                title: event.detail.title + '<hr>' + event.detail.message,
                timer: 8000,
            })
        })

        window.addEventListener('warning', event => {
            Toast.fire({
                icon: 'warning',
                title: event.detail.title + '<hr>' + event.detail.message,
                timer: 4000,
            })
        })

        // دالة لإنشاء المخططات
        function createChart(canvasId, chartData, chartType = 'bar') {
            const ctx = document.getElementById(canvasId).getContext('2d');

            return new Chart(ctx, {
                type: chartType,
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            rtl: true,
                            labels: {
                                font: {
                                    family: 'Tajawal, "Public Sans", sans-serif'
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: chartData.title || 'مخطط بياني',
                            font: {
                                family: 'Tajawal, "Public Sans", sans-serif',
                                size: 16
                            }
                        }
                    },
                    scales: chartType !== 'pie' && chartType !== 'doughnut' ? {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    family: 'Tajawal, "Public Sans", sans-serif'
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    family: 'Tajawal, "Public Sans", sans-serif'
                                }
                            }
                        }
                    } : {}
                }
            });
        }

        // دالة لتصدير المخطط كصورة
        function downloadChart(chartId, filename) {
            const canvas = document.getElementById(chartId);
            const url = canvas.toDataURL('image/png');
            const a = document.createElement('a');
            a.href = url;
            a.download = filename + '.png';
            a.click();
        }

        // ===== كود مخططات Livewire المتقدم =====
        // تشغيل المخططات عند تحديث Livewire
        document.addEventListener('livewire:updated', function() {
            console.log('Livewire updated, reinitializing charts...');
            setTimeout(initializeReportCharts, 500);
        });

        // تشغيل المخططات عند تحميل Livewire (Livewire v2)
        document.addEventListener('livewire:load', function() {
            console.log('Livewire v2 loaded, initializing charts...');
            setTimeout(initializeReportCharts, 500);
        });

        // تشغيل المخططات عند navigated (Livewire v3)
        document.addEventListener('livewire:navigated', function() {
            console.log('Livewire v3 navigated, initializing charts...');
            setTimeout(initializeReportCharts, 500);
        });

        // استمع لحدث تحديث المخططات المخصص
        window.addEventListener('charts-updated', function() {
            console.log('Charts updated event received, reinitializing charts...');
            setTimeout(initializeReportCharts, 800);
        });

        // استمع لحدث Livewire مع البيانات
        window.addEventListener('livewire-chart-data-ready', function(event) {
            console.log('Livewire chart data ready event received:', event.detail);
            const {
                reportData,
                chartSettings,
                enableCharts,
                currentStep
            } = event.detail;

            if (currentStep === 4 && enableCharts && chartSettings && chartSettings.length > 0) {
                console.log('Processing charts from event data...');
                chartSettings.forEach((chartConfig, index) => {
                    if (chartConfig.column && chartConfig.title) {
                        setTimeout(() => createReportChart(index, chartConfig, reportData), 500);
                    }
                });
            }
        });

        // دالة تهيئة المخططات المتقدمة
        function initializeReportCharts() {
            // التحقق من تحميل Chart.js
            if (typeof Chart === 'undefined') {
                console.error('Chart.js not loaded');
                return;
            }

            console.log('Chart.js loaded successfully');

            // البحث عن عنصر Livewire لاستخراج البيانات
            const livewireComponent = document.querySelector('[wire\\:id]');
            if (!livewireComponent) {
                console.log('No Livewire component found');
                return;
            }

            // محاولة الحصول على البيانات من Livewire
            try {
                const livewireId = livewireComponent.getAttribute('wire:id');
                if (typeof window.Livewire === 'undefined') {
                    console.log('Livewire not loaded yet');
                    return;
                }

                const livewireData = window.Livewire.find(livewireId);
                if (!livewireData || !livewireData.get) {
                    console.log('Could not access Livewire component data');
                    return;
                }

                const reportData = livewireData.get('reportData') || [];
                const chartConfigs = livewireData.get('chartSettings') || [];
                const enableCharts = livewireData.get('enableCharts') || false;
                const currentStep = livewireData.get('currentStep') || 1;

                console.log('Report Data Length:', reportData.length);
                console.log('Chart Configs Length:', chartConfigs.length);
                console.log('Enable Charts:', enableCharts);
                console.log('Current Step:', currentStep);

                // التحقق من الشروط
                if (currentStep !== 4 || !enableCharts) {
                    console.log('Charts not enabled or not on step 4');
                    return;
                }

                // التحقق من وجود بيانات ومخططات
                if (!reportData || reportData.length === 0) {
                    console.log('No report data available');
                    return;
                }

                if (!chartConfigs || chartConfigs.length === 0) {
                    console.log('No chart configurations available');
                    return;
                }

                // إنشاء المخططات
                chartConfigs.forEach((chartConfig, index) => {
                    console.log(`\n=== Processing Chart ${index} ===`);
                    console.log('Chart config:', chartConfig);

                    if (chartConfig.column && chartConfig.title) {
                        console.log(`✓ Chart ${index} has column and title, creating chart...`);
                        createReportChart(index, chartConfig, reportData);
                    } else {
                        console.log(`✗ Chart ${index} missing column or title:`, {
                            column: chartConfig.column,
                            title: chartConfig.title
                        });
                    }
                });

                // فحص عدد canvas elements الموجودة
                const canvasElements = document.querySelectorAll('[id^="chart_"]');
                console.log(`Found ${canvasElements.length} canvas elements:`, Array.from(canvasElements).map(el => el.id));

            } catch (error) {
                console.error('Error accessing Livewire data:', error);
                // كحل بديل، استخدام window events
                console.log('Falling back to window event approach...');
                window.addEventListener('livewire-chart-data-ready', function(event) {
                    const {
                        reportData,
                        chartSettings,
                        enableCharts,
                        currentStep
                    } = event.detail;
                    if (currentStep === 4 && enableCharts && chartSettings.length > 0) {
                        chartSettings.forEach((chartConfig, index) => {
                            if (chartConfig.column && chartConfig.title) {
                                createReportChart(index, chartConfig, reportData);
                            }
                        });
                    }
                });
            }
        }

        function createReportChart(index, chartConfig, data) {
            console.log(`=== Creating chart ${index} ===`);
            console.log('Chart Config:', chartConfig);
            console.log('Data length:', data ? data.length : 0);
            console.log('First data item:', data && data.length > 0 ? data[0] : 'No data');

            const canvas = document.getElementById('chart_' + index);
            if (!canvas) {
                console.error('Canvas element not found for chart_' + index);
                return;
            }

            console.log('Canvas found:', canvas);

            // تدمير المخطط الموجود إن وجد
            if (window.reportCharts && window.reportCharts[index]) {
                console.log('Destroying existing chart:', index);
                window.reportCharts[index].destroy();
            }

            const ctx = canvas.getContext('2d');

            // إعداد بيانات المخطط
            console.log('Preparing chart data...');
            const chartData = prepareChartData(data, chartConfig);
            console.log('Prepared chart data:', chartData);

            if (chartData.labels.length === 0) {
                console.log('No chart data available, showing message');
                canvas.parentElement.innerHTML =
                    '<div class="text-center text-muted p-4">لا توجد بيانات رقمية لعرضها في المخطط</div>';
                return;
            }

            console.log('Creating Chart.js instance...');

            // إنشاء المخطط
            const chart = new Chart(ctx, {
                type: chartConfig.type,
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: chartConfig.title,
                            font: {
                                size: 16,
                                weight: 'bold',
                                family: 'Tajawal, "Public Sans", sans-serif'
                            }
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: chartConfig.type !== 'pie' && chartConfig.type !== 'doughnut' ? {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    } : {}
                }
            });

            // حفظ المخطط في متغير عام للمرجعية
            if (!window.reportCharts) {
                window.reportCharts = {};
            }
            window.reportCharts[index] = chart;
        }

        function prepareChartData(data, chartConfig) {
            console.log('=== Preparing Chart Data ===');
            console.log('Data type:', typeof data);
            console.log('Data is array:', Array.isArray(data));
            console.log('Data length:', data ? data.length : 0);
            console.log('Chart config column:', chartConfig.column);

            const labels = [];
            const values = [];

            if (Array.isArray(data) && data.length > 0) {
                console.log('Processing chart data for column:', chartConfig.column);
                console.log('Sample data item:', data[0]);

                data.forEach((row, rowIndex) => {
                    let columnValue;

                    // التحقق من البيانات بطرق مختلفة
                    if (Array.isArray(row)) {
                        columnValue = row[chartConfig.column];
                        console.log(`Row ${rowIndex} (array): Column ${chartConfig.column} = ${columnValue}`);
                    } else if (typeof row === 'object' && row !== null) {
                        columnValue = row[chartConfig.column];
                        console.log(`Row ${rowIndex} (object): Column ${chartConfig.column} = ${columnValue}`);
                    } else {
                        console.log(`Row ${rowIndex}: Unexpected data type:`, typeof row);
                    }

                    if (columnValue !== null && columnValue !== undefined && columnValue !== '') {
                        // محاولة تحويل القيمة إلى رقم
                        const numericValue = parseFloat(columnValue);
                        console.log(
                            `Trying to parse "${columnValue}" as number: ${numericValue}, isNaN: ${isNaN(numericValue)}`
                        );

                        if (!isNaN(numericValue)) {
                            // استخدام ID أو رقم الصف كتسمية
                            const label = row.id || row.name || row.title || `صف ${rowIndex + 1}`;
                            labels.push(label);
                            values.push(numericValue);
                            console.log(`Added: label="${label}", value=${numericValue}`);
                        } else {
                            console.log(`Skipped non-numeric value: ${columnValue}`);
                        }
                    }
                });
            } else {
                console.log('Data is not an array or is empty');
            }

            console.log(`Final result: ${labels.length} labels, ${values.length} values`);
            console.log('Labels:', labels);
            console.log('Values:', values);

            return {
                labels: labels,
                datasets: [{
                    label: chartConfig.title,
                    data: values,
                    backgroundColor: generateColors(values.length, chartConfig.color),
                    borderColor: chartConfig.color,
                    borderWidth: 2,
                    fill: chartConfig.type === 'line' ? false : true
                }]
            };
        }

        function generateColors(count, baseColor) {
            if (count === 1) {
                return [baseColor + '80'];
            }

            const colors = [];
            for (let i = 0; i < count; i++) {
                // تنويع الألوان قليلاً
                const hue = (parseInt(baseColor.substr(1, 2), 16) + i * 30) % 360;
                colors.push(`hsl(${hue}, 70%, 60%)`);
            }
            return colors;
        }
    </script>
@endsection

@section('content')

    @livewire('report-generator.report-generator-manager')

    <!-- نمط CSS مخصص -->
    <style>
        .card-chart {
            min-height: 400px;
        }

        .chart-container {
            position: relative;
            height: 350px;
            margin: 20px 0;
        }

        .chart-actions {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            margin: 0 0.5rem;
            border-radius: 0.375rem;
            background-color: #f8f9fa;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .step.active {
            background-color: #696cff;
            color: white;
        }

        .step.completed {
            background-color: #28a745;
            color: white;
        }

        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            margin-left: 0.5rem;
            font-size: 0.875rem;
            font-weight: bold;
        }

        .filter-group {
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }

        .chart-preview {
            border: 2px dashed #dee2e6;
            border-radius: 0.375rem;
            padding: 2rem;
            text-align: center;
            color: #6c757d;
            margin: 1rem 0;
        }

        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }

        .btn-chart-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.25rem;
            margin: 0.125rem;
        }

        /* تحسين عرض الجداول */
        .table th {
            background-color: #696cff;
            color: white;
            font-weight: 600;
            border: none;
            padding: 0.75rem;
        }

        .table td {
            border-color: #e9ecef;
            padding: 0.75rem;
            vertical-align: middle;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f8f9fa;
        }

        /* تحسين عرض البطاقات */
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(105, 108, 255, 0.075);
            border: 1px solid rgba(105, 108, 255, 0.1);
        }

        .card-header {
            background-color: rgba(105, 108, 255, 0.05);
            border-bottom: 1px solid rgba(105, 108, 255, 0.1);
        }

        /* تحسين عرض الرسائل */
        .alert {
            border: none;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #155724;
            border-right: 4px solid #28a745;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border-right: 4px solid #dc3545;
        }

        .alert-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #0c5460;
            border-right: 4px solid #17a2b8;
        }

        /* تحسين عرض أزرار العمليات */
        .btn-group-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
        }

        /* تحسين عرض الحقول */
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .form-control,
        .form-select {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #696cff;
            box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
        }

        /* متجاوب للأجهزة المحمولة */
        @media (max-width: 768px) {
            .step-indicator {
                flex-direction: column;
                align-items: center;
            }

            .step {
                margin: 0.25rem 0;
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .chart-container {
                height: 250px;
            }

            .btn-group-actions {
                flex-direction: column;
            }

            .btn-group-actions .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
@endsection
