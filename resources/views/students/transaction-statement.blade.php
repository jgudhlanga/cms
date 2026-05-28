<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Statement - {{ $studentName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: #ffffff;
            color: #111827;
            font-size: 11px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
        }

        .header {
            width: 100%;
        }

        .header-top table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-top td {
            vertical-align: middle;
            text-align: center;
            padding: 0 10px;
        }

        .header-logo {
            height: 64px;
            width: auto;
            max-width: 100%;
            display: block;
            margin: 0 auto;
        }

        .header-center h4 {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .header-center h3 {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            margin: 4px 0;
            line-height: 1.2;
        }

        .header-center p {
            font-size: 12px;
            line-height: 1.2;
        }

        .header-center .text-small {
            font-size: 10px;
            line-height: 1.2;
        }

        .divider {
            width: 100%;
            height: 1px;
            background-color: #000;
            margin: 12px 0 16px;
        }

        .document-title {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .document-meta {
            text-align: center;
            font-size: 10px;
            color: #4b5563;
            margin-bottom: 16px;
        }

        .student-summary {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .student-summary h2 {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .student-summary .student-number {
            font-size: 10px;
            color: #4b5563;
            margin-bottom: 8px;
        }

        .profile-lines {
            font-size: 10px;
            line-height: 1.5;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 16px 0 8px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-table td.label {
            width: 35%;
            font-weight: 600;
            color: #374151;
        }

        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .summary-grid td {
            width: 25%;
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: center;
        }

        .summary-grid .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .summary-grid .summary-value {
            font-size: 12px;
            font-weight: 700;
        }

        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .ledger-table th,
        .ledger-table td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            font-size: 10px;
        }

        .ledger-table th {
            background: #f3f4f6;
            text-transform: uppercase;
            font-size: 9px;
        }

        .ledger-table td.amount {
            text-align: right;
            white-space: nowrap;
        }

        .empty-state {
            text-align: center;
            color: #6b7280;
            padding: 12px;
            border: 1px dashed #d1d5db;
        }
    </style>
</head>
<body>
<div class="container">
    @include('partials.poly-header')

    <div class="document-title">Transaction Statement</div>
    <div class="document-meta">Generated on {{ $generatedAt }}</div>

    <div class="student-summary">
        <h2>{{ strtoupper($studentName) }}</h2>
        <div class="student-number">{{ $studentNumber }}</div>
        <div class="profile-lines">
            @foreach ($profileSummary as $key => $value)
                <div><strong>{{ ucfirst(preg_replace('/([A-Z])/', ' $1', $key)) }}:</strong> {{ $value }}</div>
            @endforeach
        </div>
    </div>

    <div class="section-title">Personal Information</div>
    <table class="info-table">
        @foreach ($personalInformation as $row)
            <tr>
                <td class="label">{{ $row['label'] }}</td>
                <td>{{ $row['value'] }}</td>
            </tr>
        @endforeach
    </table>

    <div class="section-title">Contact Information</div>
    <table class="info-table">
        @foreach ($contactInformation as $row)
            <tr>
                <td class="label">{{ $row['label'] }}</td>
                <td>{{ $row['value'] }}</td>
            </tr>
        @endforeach
    </table>

    <div class="section-title">Financial Summary</div>
    <table class="summary-grid">
        <tr>
            <td>
                <div class="summary-label">Total Invoiced</div>
                <div class="summary-value">{{ $summary['totalInvoiced'] }}</div>
            </td>
            <td>
                <div class="summary-label">Total Payments</div>
                <div class="summary-value">{{ $summary['totalPayments'] }}</div>
            </td>
            <td>
                <div class="summary-label">Outstanding Balance</div>
                <div class="summary-value">{{ $summary['outstandingBalance'] }}</div>
            </td>
            <td>
                <div class="summary-label">Paid %</div>
                <div class="summary-value">{{ $summary['paidPercent'] }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Transaction Statement</div>
    @if (count($ledgerRows) > 0)
        <table class="ledger-table">
            <thead>
            <tr>
                <th style="width: 14%;">Date</th>
                <th>Description</th>
                <th style="width: 14%;">Debit (DB)</th>
                <th style="width: 14%;">Credit (CR)</th>
                <th style="width: 16%;">Balance</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($ledgerRows as $row)
                <tr>
                    <td>{{ $row['transactionDate'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td class="amount">{{ $row['debit'] }}</td>
                    <td class="amount">{{ $row['credit'] }}</td>
                    <td class="amount">{{ $row['runningBalance'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">No transactions found for this student.</div>
    @endif
</div>
</body>
</html>
