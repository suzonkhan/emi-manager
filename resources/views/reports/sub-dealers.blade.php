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
            font-size: 9px;
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
            padding: 7px 5px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #ddd;
            text-transform: uppercase;
        }
        table td {
            padding: 6px 5px;
            font-size: 8px;
            border: 1px solid #ddd;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
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
        <p>EMI Manager - Sub-Dealer Report</p>
    </div>

    <div class="info-section">
        <p><strong>Report Period:</strong> {{ $report['start_date'] }} to {{ $report['end_date'] }}</p>
        <p><strong>Generated On:</strong> {{ date('F d, Y h:i A') }}</p>
        <p><strong>Total Sub-Dealers:</strong> {{ count($report['data']) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Sub-Dealer ID</th>
                <th style="width: 22%;">Name</th>
                <th style="width: 13%;">Mobile</th>
                <th style="width: 15%;">District</th>
                <th style="width: 15%;">Upazila</th>
                <th style="width: 10%;" class="text-right">Used Tokens</th>
                <th style="width: 10%;" class="text-right">Available</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['data'] as $row)
                <tr>
                    <td style="font-family: monospace;">{{ $row['id'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['mobile'] }}</td>
                    <td>{{ $row['district'] }}</td>
                    <td>{{ $row['upazila'] }}</td>
                    <td class="text-right">{{ number_format($row['used_token']) }}</td>
                    <td class="text-right">{{ number_format($row['available_token']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                        No sub-dealers found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>© {{ date('Y') }} EMI Manager. All rights reserved. | Page <span class="pagenum"></span></p>
    </div>
</body>
</html>
