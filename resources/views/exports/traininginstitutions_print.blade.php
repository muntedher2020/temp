<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير مؤسسة المدرب - طباعة</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            direction: rtl;
            font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, Arial, sans-serif;
        }

        body {
            font-family: 'Noto Sans Arabic', 'Segoe UI', Tahoma, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 20px;
            background: white;
        }

        .no-print {
            display: block;
        }

        .print-only {
            display: none;
        }

        .controls {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #4A6CF7;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3b56e0;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #4A6CF7;
        }

        .header h1 {
            font-size: 32px;
            color: #4A6CF7;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .date {
            text-align: left;
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 13px;
            direction: rtl;
        }

        th, td {
            border: 2px solid #ddd;
            padding: 12px 8px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            background-color: #4A6CF7;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        td {
            background-color: #fff;
            color: #333;
        }

        tr:nth-child(even) td {
            background-color: #f8f9fa;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 2px solid #ddd;
            padding-top: 20px;
        }

        .arabic-text {
            direction: rtl;
            text-align: right;
            font-family: 'Noto Sans Arabic', Arial, sans-serif;
        }

        .number {
            direction: ltr;
            text-align: center;
            font-family: Arial, sans-serif;
        }

        /* تحسينات للطباعة */
        @media print {
            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            body {
                margin: 0;
                padding: 15mm;
                font-size: 12px;
            }

            .header {
                margin-bottom: 30px;
                page-break-after: avoid;
            }

            table {
                font-size: 11px;
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            th {
                page-break-after: avoid;
            }

            .footer {
                page-break-before: avoid;
            }
        }

        @page {
            size: A4;
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <h3 style="margin-bottom: 15px;">أدوات الطباعة</h3>
        <button onclick="window.print()" class="btn btn-primary">
            <i>🖨️</i> طباعة التقرير
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i>❌</i> إغلاق النافذة
        </button>
        <a href="{{ route('TrainingInstitutions') }}" class="btn btn-secondary">
            <i>🔙</i> العودة للقائمة
        </a>
    </div>

    <div class="header">
        <h1>تقرير مؤسسة المدرب</h1>
    </div>

    <div class="date">
        <strong>تاريخ التقرير:</strong> {{ $generated_at ?? now()->format('Y-m-d H:i:s') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>الرقم</th>
                <th>اسم المؤسسة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
                <tr>
                    <td class="number">{{ $item->id }}</td>
                    <td class="arabic-text">{{ $item->name ?? 'غير محدد' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p><strong>إجمالي عدد السجلات:</strong> {{ count($data) }}</p>
        <p>تم إنشاء هذا التقرير بواسطة نظام إدارة البيانات</p>
        <p>&copy; {{ date('Y') }} - جميع الحقوق محفوظة</p>
    </div>

    <script>
        // Auto-print functionality (optional)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>