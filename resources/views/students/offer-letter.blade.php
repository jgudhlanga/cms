<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harare Polytechnic</title>
    <style>
        @page {
            margin: 12mm 14mm 16mm 14mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: #ffffff;
            font-size: 11px;
            line-height: 1.35;
            color: #111;
        }

        .container {
            width: 100%;
            background: white;
        }

        .header {
            width: 100%;
        }

        .header-top {
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
            padding: 0 6px;
        }

        .header-logo {
            height: 48px;
            width: auto;
            max-width: 100%;
            display: block;
            margin: 0 auto;
        }

        .header-center {
            text-align: center;
            width: 70%;
        }

        .header-center h4 {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .header-center h3 {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            margin: 2px 0;
            line-height: 1.15;
        }

        .header-center p {
            font-size: 10px;
            line-height: 1.15;
        }

        .header-center .text-small {
            font-size: 9px;
            line-height: 1.15;
        }

        .header-center .contact-line {
            display: flex;
            justify-content: center;
            gap: 6px;
        }

        .header-center .contact-line span:first-child {
            font-weight: bold;
        }

        .divider {
            width: 100%;
            height: 1px;
            background-color: #000;
            margin: 8px 0 10px;
        }

        .content {
            padding: 8px 4px 28px;
        }

        .content p {
            margin: 0 0 6px;
            line-height: 1.35;
        }

        .content p:last-child {
            margin-bottom: 0;
        }

        .signature {
            margin-top: 8px;
            max-height: 56px;
            width: auto;
        }

        /* DomPDF: fixed footer stays on page 1 and does not push body onto page 2. */
        .document-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            font-size: 9px;
            color: #4b5563;
            line-height: 1.2;
        }
    </style>
</head>
<body>
<div class="container">
    @include('partials.poly-header')
    <div class="content">
        {!!  str_replace(['{date}', '{studentName}', '{studentIdNumber}', '{studentNumber}','{intakePeriod}', '{department}', '{level}', '{course}', '{modeOfStudy}', '{tuition}'],
        array_map(fn ($value) => e((string) $value), ['', strtoupper($studentName), $studentIdNumber, $studentNumber, $intakePeriod, $department, $level, $course, $modeOfStudy, $tuition]),
        $documentTemplate->body) !!}
        <div>
            <img
                class="signature"
                src="file://{{ public_path('assets/images/principal.jpeg') }}"
                alt="signature"
            >
        </div>
    </div>
</div>
<div class="document-footer">Generated on {{ $generatedAt ?? now()->format('d M Y') }}</div>
</body>
</html>
