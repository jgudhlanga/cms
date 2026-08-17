<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student ID Cards</title>
    <style>
        @page {
            margin: 0;
            size: {{ $cardWidthPt }}pt {{ $cardHeightPt }}pt;
        }

        @include('students.partials.id-card-print-css')
    </style>
</head>
<body>
    @foreach ($cards as $card)
        @include('students.partials.id-card-front-face', $card)
        <div class="page-break"></div>
        @include('students.partials.id-card-back-face', $card)
        @if (! $loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
