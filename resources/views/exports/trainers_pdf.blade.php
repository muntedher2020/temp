<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>تقرير ملاحظات</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Tahoma', 'Arial Unicode MS', sans-serif;
            direction: rtl;
            text-align: right;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4A6CF7;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #4A6CF7;
            font-size: 24px;
            margin: 0;
            font-weight: bold;
        }

        .date {
            text-align: left;
            margin-bottom: 20px;
            color: #666;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #4A6CF7;
            color: white;
            font-weight: bold;
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e3f2fd;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير ملاحظات</h1>
    </div>

    <div class="date">
        <strong>تاريخ التقرير:</strong> {{ now()->format('Y-m-d H:i:s') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>اسم المدرب</th>
                <th>مؤسسة المدرب</th>
                <th>التحصيل العلمي</th>
                <th>المجال التدريبي</th>
                <th>رقم الهاتف</th>
                <th>البريد الالكتروني</th>
                <th>ملاحظات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
                <tr>
                    <td>{{ $item->trainer_name }}</td>
                    <td>{{ $item->institution_id }}</td>
                    <td>{{ $item->ed_level_id }}</td>
                    <td>{{ $item->domain_id }}</td>
                    <td>{{ $item->phone }}</td>
                    <td>{{ $item->email }}</td>
                    <td>{{ $item->notes }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>تم إنشاء هذا التقرير بواسطة نظام إدارة البيانات</p>
        <p>© {{ date('Y') }} - جميع الحقوق محفوظة</p>
    </div>
</body>
</html>