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
        .page-shell { min-height: 178mm; position: relative; }
        .content { padding: 0 8mm; margin: 0 auto; }
        .page-main { padding-top: 4mm; padding-bottom: 18mm; }

        .header { width: 100%; }
        .header-top table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .header-top td { vertical-align: middle; text-align: center; padding: 0 6px; }
        .header-logo { height: 48px; width: auto; max-width: 100%; display: block; margin: 0 auto; }
        .header-center h4 { font-size: 7px; font-weight: bold; text-transform: uppercase; line-height: 1.2; }
        .header-center h3 { font-size: 11px; font-weight: bold; text-transform: uppercase; margin: 2px 0; line-height: 1.2; }
        .header-center p { font-size: 7px; line-height: 1.2; }
        .header-center .text-small { font-size: 6px; line-height: 1.2; }
        .header-center .contact-line span:first-child { font-weight: bold; }
        .divider { width: 100%; height: 1px; background-color: #000; margin: 6px 0 8px; }

        .document-title { font-size: 10px; text-align: center; margin-bottom: 8px; font-weight: bold; }

        .meta { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .meta td { padding: 2px 4px; vertical-align: top; font-size: 7px; }
        .meta .label { font-weight: bold; white-space: nowrap; }

        table.students { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.students th, table.students td { border: 1px solid #333; padding: 2px 3px; text-align: left; font-size: 7px; }
        table.students th { background: #f3f4f6; text-align: center; font-weight: bold; }
        table.students td.num { text-align: center; width: 3%; }

        .page-footer-section { position: absolute; left: 8mm; right: 8mm; bottom: 16mm; }

        table.signatures { width: 100%; border-collapse: collapse; }
        table.signatures td { padding: 3px 4px; vertical-align: bottom; font-size: 7px; }
        table.signatures .label { font-weight: bold; width: 12%; white-space: nowrap; }
        table.signatures .line { border-bottom: 1px solid #333; height: 16px; }

        .page-footer { position: absolute; left: 0; right: 0; bottom: 6mm; text-align: center; font-size: 7px; color: #333; }
    </style>
</head>
<body>
@foreach($sections as $section)
    @foreach($section['pages'] as $page)
        <div class="section page">
            <div class="page-shell">
                <div class="content page-main">
                    @if($page['isFirstPage'])
                        @include('partials.poly-header')

                        <div class="document-title">Enrolment: Class list</div>

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
                                <td colspan="3">{{ $section['header']['academicYear'] ?? '' }}</td>
                            </tr>
                        </table>
                    @endif

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
                </div>

                @if($page['isLastPage'])
                    <div class="content page-footer-section">
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
                @endif

                <div class="page-footer">-- {{ $page['pageNumber'] }} of {{ $page['totalPages'] }} --</div>
            </div>
        </div>
    @endforeach
@endforeach
</body>
</html>
