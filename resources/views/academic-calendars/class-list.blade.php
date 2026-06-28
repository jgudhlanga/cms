<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $institutionName }} — Class list</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; }
        .section { page-break-after: always; }
        .section:last-child { page-break-after: auto; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        h1 { font-size: 12px; text-align: center; margin-bottom: 10px; font-weight: bold; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .meta td { padding: 2px 6px; vertical-align: top; }
        .meta .label { font-weight: bold; width: 12%; }
        table.students { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.students th, table.students td { border: 1px solid #333; padding: 2px 3px; text-align: left; font-size: 7px; }
        table.students th { background: #f3f4f6; text-align: center; font-weight: bold; }
        table.students td.num { text-align: center; width: 3%; }
        table.signatures { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.signatures td { padding: 4px 6px; vertical-align: bottom; font-size: 7px; }
        table.signatures .label { font-weight: bold; width: 14%; }
        table.signatures .line { border-bottom: 1px solid #333; min-width: 80px; height: 18px; }
    </style>
</head>
<body>
@foreach($sections as $section)
    @foreach($section['pages'] as $page)
        <div class="section page">
            <h1>{{ $institutionName }} Enrolment: Class list</h1>

            <table class="meta">
                <tr>
                    <td class="label">Department:</td>
                    <td>{{ $section['header']['department'] ?? '' }}</td>
                    <td class="label">Level:</td>
                    <td>{{ $section['header']['level'] ?? '' }}</td>
                    <td class="label">Program:</td>
                    <td>{{ $section['header']['program'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label">Mode of Study:</td>
                    <td>{{ $section['header']['modeOfStudy'] ?? '' }}</td>
                    <td class="label">Academic Year:</td>
                    <td>{{ $section['header']['academicYear'] ?? '' }}</td>
                    <td class="label">Class:</td>
                    <td>{{ $section['header']['className'] ?? '' }}</td>
                </tr>
            </table>

            <table class="students">
                <thead>
                <tr>
                    <th>No.</th>
                    <th>Surname</th>
                    <th>First name</th>
                    <th>DOB (dd/mm/yyyy)</th>
                    <th>National ID Num</th>
                    <th>Contact Num</th>
                    <th>Student Num</th>
                    <th>Appy Num (For Apprentice)</th>
                    <th>Gender</th>
                </tr>
                </thead>
                <tbody>
                @foreach($page['rows'] as $row)
                    <tr>
                        <td class="num">{{ $row['number'] }}.</td>
                        <td>{{ $row['surname'] ?? '' }}</td>
                        <td>{{ $row['firstName'] ?? '' }}</td>
                        <td>{{ $row['dateOfBirth'] ?? '' }}</td>
                        <td>{{ $row['nationalId'] ?? '' }}</td>
                        <td>{{ $row['contactNumber'] ?? '' }}</td>
                        <td>{{ $row['studentNumber'] ?? '' }}</td>
                        <td>{{ $row['applicationNumber'] ?? '' }}</td>
                        <td>{{ $row['gender'] ?? '' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <table class="signatures">
                <tr>
                    <td class="label">Compiled by:</td>
                    <td class="line"></td>
                    <td class="label">Signature</td>
                    <td class="line"></td>
                    <td class="label">Date</td>
                    <td class="line"></td>
                </tr>
                <tr>
                    <td class="label">Verified by:</td>
                    <td class="line"></td>
                    <td class="label">Signature</td>
                    <td class="line"></td>
                    <td class="label">Date</td>
                    <td class="line"></td>
                </tr>
                <tr>
                    <td class="label">Recommended by:</td>
                    <td class="line"></td>
                    <td class="label">Signature</td>
                    <td class="line"></td>
                    <td class="label">Date</td>
                    <td class="line"></td>
                </tr>
                <tr>
                    <td class="label">Approved by:</td>
                    <td class="line"></td>
                    <td class="label">Signature</td>
                    <td class="line"></td>
                    <td class="label">Date</td>
                    <td class="line"></td>
                </tr>
            </table>
        </div>
    @endforeach
@endforeach
</body>
</html>
