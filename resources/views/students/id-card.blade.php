<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student ID Card</title>
    <style>
        @page {
            margin: 0;
            size: {{ $cardWidthPt }}pt {{ $cardHeightPt }}pt;
        }

        @include('students.partials.id-card-print-css')
    </style>
</head>
<body>
    @include('students.partials.id-card-front-face')
</body>
</html>
