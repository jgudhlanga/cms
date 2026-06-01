<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Work Marksheet - {{ $header['subject'] ?? 'Subject' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        .container { padding: 16px; }
        h1 { font-size: 11px; text-align: center; text-transform: uppercase; margin-bottom: 8px; }
        h2 { font-size: 10px; text-align: center; margin-bottom: 12px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { padding: 2px 4px; vertical-align: top; }
        .meta .label { font-weight: bold; width: 18%; }
        table.marks { width: 100%; border-collapse: collapse; }
        table.marks th, table.marks td { border: 1px solid #333; padding: 3px 4px; text-align: center; }
        table.marks th { background: #f3f4f6; font-size: 8px; }
        table.marks td.name { text-align: left; }
        .footnote { margin-top: 10px; font-size: 8px; color: #555; }
        .issues { margin-top: 12px; font-size: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Higher Education Examinations Council</h1>
    <h2>Coursework / Examinations Mark Schedule (Course Work)</h2>

    <table class="meta">
        <tr>
            <td class="label">Centre</td><td>{{ $header['centre'] ?? '—' }}</td>
            <td class="label">Centre No</td><td>{{ $header['centreNumber'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Level</td><td>{{ $header['level'] ?? '—' }}</td>
            <td class="label">Discipline</td><td>{{ $header['discipline'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Course</td><td>{{ $header['course'] ?? '—' }}</td>
            <td class="label">Subject Code</td><td>{{ $header['subjectCode'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Subject</td><td>{{ $header['subject'] ?? '—' }}</td>
            <td class="label">Session</td><td>{{ $header['session'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Class</td><td>{{ $header['className'] ?? '—' }}</td>
            <td class="label">Generated</td><td>{{ $header['generatedAt'] ?? '—' }}</td>
        </tr>
    </table>

    <table class="marks">
        <thead>
        <tr>
            <th>Candidate No.</th>
            <th>Name</th>
            @foreach($assessmentTypes as $type)
                <th>{{ $type['name'] }}<br>({{ $type['weightPercent'] ?? '—' }}%)</th>
            @endforeach
            <th>CW /60</th>
            <th>Exam /40</th>
            <th>Final /100</th>
            <th>Remark</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['candidateNumber'] ?? '—' }}</td>
                <td class="name">{{ $row['name'] ?? '—' }}</td>
                @foreach($assessmentTypes as $type)
                    @php
                        $component = collect($row['components'] ?? [])->firstWhere('assessmentTypeId', $type['id']);
                    @endphp
                    <td>{{ $component['rawMark'] ?? '—' }}</td>
                @endforeach
                <td>{{ $row['courseWorkTotal60'] ?? '—' }}</td>
                <td>—</td>
                <td>—</td>
                <td>{{ $row['remark'] ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p class="footnote">
        Exam (40%) and final mark (100%) columns are reserved for a later release.
        Complete: {{ $summary['completeCount'] ?? 0 }} / {{ $summary['studentCount'] ?? 0 }} students.
    </p>

    @if(!empty($issues))
        <div class="issues">
            <strong>Export warnings ({{ count($issues) }})</strong>
            <ul>
                @foreach($issues as $issue)
                    <li>{{ $issue['studentName'] }} — {{ $issue['issue'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
</body>
</html>
