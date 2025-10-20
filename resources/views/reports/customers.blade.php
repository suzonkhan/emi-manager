<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['report_type'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #333;
        }

        .container {
            padding: 50px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 11px;
            color: #666;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .info-section p {
            margin: 3px 0;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table thead {
            background-color: #f5f5f5;
        }
        table th {
            padding: 6px 4px;
            text-align: left;
            font-size: 7px;
            font-weight: bold;
            border: 1px solid #ddd;
            text-transform: uppercase;
        }
        table td {
            padding: 5px 4px;
            font-size: 7px;
            border: 1px solid #ddd;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tfoot {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        table tfoot td {
            padding: 6px 4px;
            font-size: 8px;
        }
        .text-right {
            text-align: right;
        }
        .text-green {
            color: #059669;
        }
        .text-red {
            color: #dc2626;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #999;
            padding: 10px 0;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
    <div class="header">
        <h1>AI Locker</h1>
        <h3>{{ $report['report_type'] }}</h3>
    </div>

    <div class="info-section">
        <p><strong>Report Period:</strong> {{ $report['start_date'] }} to {{ $report['end_date'] }}</p>
        <p><strong>Generated On:</strong> {{ date('F d, Y h:i A') }}</p>
        <p><strong>Total Customers:</strong> {{ count($report['data']) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 14%;">Name</th>
                <th style="width: 10%;">Mobile</th>
                <th style="width: 10%;">District</th>
                <th style="width: 10%;">Upazila</th>
                <th style="width: 20%;">Product</th>
                <th style="width: 12%;" class="text-right">Price</th>
                <th style="width: 12%;" class="text-right">Paid</th>
                <th style="width: 12%;" class="text-right">Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['data'] as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['mobile'] }}</td>
                    <td>{{ $row['district'] }}</td>
                    <td>{{ $row['upazila'] }}</td>
                    <td>{{ $row['product_name'] }}</td>
                    <td class="text-right">BDT {{ number_format($row['price'], 0) }}</td>
                    <td class="text-right text-green">BDT {{ number_format($row['paid'], 0) }}</td>
                    <td class="text-right text-red">BDT {{ number_format($row['due'], 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #999;">
                        No data available for the selected date range
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right">Total:</td>
                <td class="text-right">BDT {{ number_format($report['total_price'], 0) }}</td>
                <td class="text-right text-green">BDT {{ number_format($report['total_paid'], 0) }}</td>
                <td class="text-right text-red">BDT {{ number_format($report['total_due'], 0) }}</td>
            </tr>
        </tfoot>
    </table>


    </div>
</body>
</html>
