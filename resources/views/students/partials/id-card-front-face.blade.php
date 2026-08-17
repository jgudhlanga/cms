    @php
        $modeLabel = match (strtolower((string) $mode)) {
            'part time' => 'Part-time',
            'full time' => 'Full-time',
            default => (string) $mode,
        };
        $residenceLabel = match (strtolower((string) $residence)) {
            'non res' => 'Non-Resident',
            'res' => 'Resident',
            default => (string) $residence,
        };
        $showSdp = strcasecmp((string) $sdp, 'Yes') === 0;
        $institution = $institutionName !== '' ? $institutionName : __('trans.student_id_card_institution');
    @endphp
    <div class="card">
        <div class="header">
            @if ($logoSrc)
                <img src="{{ $logoSrc }}" alt="" class="logo-badge">
            @endif
            <div class="college">{{ $institution }}</div>
            <div class="title">{{ __('trans.student_id_card_title') }}</div>
        </div>
        <div class="gold"></div>
        @if ($photoSrc)
            <img src="{{ $photoSrc }}" alt="" class="photo">
        @else
            <div class="photo-empty"></div>
        @endif
        <div class="fields">
            <div class="field-label">{{ __('trans.ui_full_name') }}</div>
            <div class="field-value">{{ $studentName !== '' ? $studentName : '—' }}</div>
            <div class="field-label">{{ trans_choice('trans.department', 1) }}</div>
            <div class="field-value small">{{ $department !== '' ? $department : '—' }}</div>
            <div class="field-label">{{ trans_choice('trans.course', 1) }}</div>
            <div class="field-value small">{{ $course !== '' ? $course : '—' }}</div>
            @if ($modeLabel !== '' || $residenceLabel !== '' || $showSdp)
                <div class="badges">
                    @if ($modeLabel !== '')
                        <span class="badge">{{ $modeLabel }}</span>
                    @endif
                    @if ($residenceLabel !== '')
                        <span class="badge badge-gold">{{ $residenceLabel }}</span>
                    @endif
                    @if ($showSdp)
                        <span class="badge">{{ __('trans.student_id_card_sdp') }}</span>
                    @endif
                </div>
            @endif
        </div>
        <div class="barcode">
            <table class="barcode-table">
                <tr>
                    <td class="barcode-bars">
                        <img src="data:image/png;base64,{{ $barcode }}" alt="">
                    </td>
                </tr>
                @if ($studentNumber !== '')
                    <tr>
                        <td class="id-number">{{ $studentNumber }}</td>
                    </tr>
                @endif
            </table>
        </div>
        <div class="footer">
            <div class="valid">
                <div class="footer-label">{{ __('trans.student_id_card_valid_until') }}</div>
                <div class="footer-value">{{ $expiryDate !== '' ? $expiryDate : '—' }}</div>
            </div>
            <div class="web">
                <div class="footer-label">{{ __('trans.student_id_card_website_label') }}</div>
                <div class="footer-value footer-link">{{ $website }}</div>
            </div>
        </div>
    </div>
