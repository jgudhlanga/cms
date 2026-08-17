    @php
        $institution = $institutionName !== '' ? $institutionName : __('trans.student_id_card_institution');
        $lostName = $returnName !== '' ? $returnName : $institution;
    @endphp
    <div class="card back-card">
        <div class="back-header">
            @if ($logoSrc)
                <img src="{{ $logoSrc }}" alt="" class="back-logo">
            @endif
            <div class="back-college">{{ $institution }}</div>
        </div>
        <div class="back-bar"></div>
        <div class="back-terms-label">{{ __('trans.student_id_card_terms_title') }}</div>
        <div class="back-terms">{{ __('trans.student_id_card_terms') }}</div>
        <div class="back-nid-label">{{ __('trans.student_id_card_national_id') }}</div>
        <div class="back-nid">{{ $nationalId !== '' ? $nationalId : '—' }}</div>
        <div class="back-sign">
            @if (! empty($signatureSrc))
                <img src="{{ $signatureSrc }}" alt="" class="back-sign-image">
            @else
                <div class="back-sign-line"></div>
            @endif
            <div class="back-sign-label">{{ __('trans.student_id_card_principal_signature') }}</div>
        </div>
        <div class="back-lost-label">{{ __('trans.student_id_card_if_lost') }}</div>
        <div class="back-lost-name">{{ $lostName }}</div>
        @if ($returnAddress !== '')
            <div class="back-lost-address">{{ $returnAddress }}</div>
        @endif
        @if ($returnPhone !== '')
            <div class="back-lost-phone">Tel: {{ $returnPhone }}</div>
        @endif
        @if (! empty($qrSrc))
            <img src="{{ $qrSrc }}" alt="" class="back-qr">
        @else
            <div class="back-qr back-qr-empty"></div>
        @endif
        <div class="back-qr-label">{{ __('trans.student_id_card_scan_to_verify') }}</div>
    </div>
