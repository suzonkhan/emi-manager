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
            font-size: 10px;
            color: #333;
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
            padding: 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #ddd;
            text-transform: uppercase;
        }
        table td {
            padding: 6px 8px;
            font-size: 9px;
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
            padding: 8px;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
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
    <div class="header">
        <h1>{{ $report['report_type'] }}</h1>
        <p>EMI Manager - Product Report</p>
    </div>

    <div class="info-section">
        <p><strong>Report Period:</strong> {{ $report['start_date'] }} to {{ $report['end_date'] }}</p>
        <p><strong>Generated On:</strong> {{ date('F d, Y h:i A') }}</p>
        <p><strong>Total Product Types:</strong> {{ count($report['data']) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Product Type</th>
                <th style="width: 25%;" class="text-right">Sales Quantity</th>
                <th style="width: 25%;" class="text-right">Total Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['data'] as $row)
                <tr>
                    <td>{{ $row['product_type'] }}</td>
                    <td class="text-right">{{ number_format($row['sales_qty']) }}</td>
                    <td class="text-right">BDT {{ number_format($row['price'], 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 20px; color: #999;">
                        No data available for the selected date range
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="text-right">Total:</td>
                <td class="text-right">{{ number_format($report['total_qty']) }}</td>
                <td class="text-right">BDT {{ number_format($report['total_price'], 0) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>© {{ date('Y') }} EMI Manager. All rights reserved. | Page <span class="pagenum"></span></p>
    </div>
</body>
</html>
