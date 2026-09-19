<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harare Polytechnic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: #ffffff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Header styles */
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
            padding: 0 10px;
        }

        .header-logo {
            height: 64px;
            width: auto;
            max-width: 100%;
            display: block;
            margin: 0 auto;
        }

        .header-center {
            text-align: center;
            width: 70%; /* Center column takes more space */
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

        .header-center .contact-line {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .header-center .contact-line span:first-child {
            font-weight: bold;
        }

        .divider {
            width: 100%;
            height: 1px;
            background-color: #000;
            margin: 12px 0;
        }

        /* Content area */
        .content {
            padding: 30px 20px;
        }

        .signature-block {
            margin-top: 5px;
            page-break-before: avoid;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-block img {
            display: block;
            max-height: 64px;
            width: auto;
        }

        /*
         * DomPDF: fixed footer stays on the page and does not push the signature
         * onto a second page when it was part of the content flow.
         */
        .document-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 12px;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #4b5563;
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
        <div class="signature-block">
            <img src="file://{{ public_path('assets/images/principal.jpeg') }}" alt="signature">
        </div>
    </div>
</div>
<div class="document-footer">Generated on {{ $generatedAt ?? now()->format('d M Y') }}</div>
</body>
</html>
