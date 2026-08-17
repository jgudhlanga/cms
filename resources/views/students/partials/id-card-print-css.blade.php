        html, body {
            margin: 0;
            padding: 0;
            width: {{ $cardWidthPt }}pt;
            height: {{ $cardHeightPt }}pt;
            font-family: DejaVu Sans, sans-serif;
            color: #1A2233;
            background: #ffffff;
        }

        .card {
            position: relative;
            width: {{ $cardWidthPt }}pt;
            height: {{ $cardHeightPt }}pt;
            overflow: hidden;
            background: #ffffff;
        }

        .page-break {
            page-break-after: always;
        }

        .header {
            position: absolute;
            top: 0;
            left: 0;
            width: {{ $cardWidthPt }}pt;
            height: 22pt;
            background: #1B3A8C;
        }

        .logo-badge {
            position: absolute;
            top: 4.5pt;
            left: 8pt;
            width: 13pt;
            height: 13pt;
        }

        .college {
            position: absolute;
            top: 6.5pt;
            left: 26pt;
            font-size: 6.5pt;
            letter-spacing: 0.3pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #ffffff;
        }

        .title {
            position: absolute;
            top: 7pt;
            right: 8pt;
            font-size: 5pt;
            letter-spacing: 0.45pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #D9A441;
        }

        .gold {
            position: absolute;
            top: 22pt;
            left: 0;
            width: {{ $cardWidthPt }}pt;
            height: 2pt;
            background: #D9A441;
        }

        .photo,
        .photo-empty {
            position: absolute;
            top: 30pt;
            left: 8pt;
            width: 38pt;
            height: 49pt;
            border: 1.1pt solid #1B3A8C;
            border-radius: 6pt;
            background: #F5F7FC;
        }

        .fields {
            position: absolute;
            top: 28pt;
            left: 52pt;
            width: 182pt;
            height: 68pt;
        }

        .field-label {
            font-size: 4.2pt;
            font-weight: bold;
            letter-spacing: 0.35pt;
            text-transform: uppercase;
            color: #647089;
        }

        .field-value {
            font-size: 6.4pt;
            font-weight: bold;
            line-height: 1.1;
            color: #1A2233;
            margin-bottom: 1.5pt;
        }

        .field-value.small {
            font-size: 5.5pt;
            font-weight: normal;
        }

        .badges {
            margin-top: 1pt;
        }

        .badge {
            display: inline-block;
            font-size: 4.6pt;
            font-weight: bold;
            padding: 1pt 4pt;
            margin-right: 2pt;
            background: #E7ECF9;
            color: #1B3A8C;
            border-radius: 8pt;
        }

        .badge-gold {
            background: #FBF1DE;
            color: #8A6417;
        }

        .barcode {
            position: absolute;
            top: 102pt;
            left: 8pt;
            width: 226pt;
            border-top: 0.5pt solid #E4E8F1;
            padding-top: 2pt;
        }

        .barcode-table {
            width: 226pt;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .barcode-table td {
            padding: 0;
            margin: 0;
        }

        .barcode-bars {
            font-size: 0;
            line-height: 0;
        }

        .barcode-bars img {
            display: block;
            width: 226pt;
            height: 11pt;
            margin: 0;
            padding: 0;
        }

        .id-number {
            font-size: 5.5pt;
            letter-spacing: 0.7pt;
            line-height: 1;
            color: #1A2233;
            text-align: center;
            padding: 1pt 0 0 0;
        }

        .footer {
            position: absolute;
            top: 128pt;
            left: 8pt;
            width: 226pt;
            height: 20pt;
        }

        .valid {
            position: absolute;
            left: 0;
            top: 0;
        }

        .web {
            position: absolute;
            right: 0;
            top: 0;
            text-align: right;
        }

        .footer-label {
            font-size: 4.4pt;
            font-weight: bold;
            letter-spacing: 0.3pt;
            text-transform: uppercase;
            color: #647089;
        }

        .footer-value {
            font-size: 6pt;
            font-weight: bold;
            color: #1A2233;
            padding-top: 0.2pt;
        }

        .footer-link {
            color: #1B3A8C;
        }

        .back-header {
            position: absolute;
            top: 0;
            left: 0;
            width: {{ $cardWidthPt }}pt;
            height: 22pt;
            background: #1A2233;
        }

        .back-logo {
            position: absolute;
            top: 4.5pt;
            left: 8pt;
            width: 13pt;
            height: 13pt;
        }

        .back-college {
            position: absolute;
            top: 7pt;
            left: 26pt;
            font-size: 6.5pt;
            letter-spacing: 0.3pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #ffffff;
        }

        .back-bar {
            position: absolute;
            top: 22pt;
            left: 0;
            width: {{ $cardWidthPt }}pt;
            height: 2pt;
            background: #9AA3B5;
        }

        .back-terms-label {
            position: absolute;
            top: 28pt;
            left: 8pt;
            font-size: 5pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
            color: #1A2233;
        }

        .back-terms {
            position: absolute;
            top: 36pt;
            left: 8pt;
            width: 226pt;
            font-size: 4.8pt;
            line-height: 1.25;
            color: #647089;
        }

        .back-nid-label {
            position: absolute;
            top: 58pt;
            left: 8pt;
            font-size: 4.6pt;
            font-weight: bold;
            letter-spacing: 0.5pt;
            text-transform: uppercase;
            color: #647089;
        }

        .back-nid {
            position: absolute;
            top: 65pt;
            left: 8pt;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
            color: #1A2233;
        }

        .back-sign {
            position: absolute;
            top: 78pt;
            left: 8pt;
            width: 110pt;
        }

        .back-sign-image {
            display: block;
            height: 14pt;
            max-width: 90pt;
        }

        .back-sign-line {
            width: 90pt;
            border-bottom: 0.6pt solid #E4E8F1;
            height: 12pt;
        }

        .back-sign-label {
            font-size: 4.6pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
            color: #647089;
            padding-top: 1pt;
        }

        .back-lost-label {
            position: absolute;
            top: 112pt;
            left: 8pt;
            font-size: 4.6pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
            color: #647089;
        }

        .back-lost-name {
            position: absolute;
            top: 119pt;
            left: 8pt;
            width: 150pt;
            font-size: 6pt;
            font-weight: bold;
            color: #1A2233;
        }

        .back-lost-address {
            position: absolute;
            top: 127pt;
            left: 8pt;
            width: 150pt;
            font-size: 5pt;
            line-height: 1.2;
            color: #647089;
        }

        .back-lost-phone {
            position: absolute;
            top: 140pt;
            left: 8pt;
            font-size: 5pt;
            color: #647089;
        }

        .back-qr {
            position: absolute;
            top: 108pt;
            right: 8pt;
            width: 28pt;
            height: 28pt;
        }

        .back-qr-empty {
            background: #F3F4F6;
        }

        .back-qr-label {
            position: absolute;
            top: 138pt;
            right: 8pt;
            width: 28pt;
            font-size: 3.8pt;
            font-weight: bold;
            letter-spacing: 0.2pt;
            text-transform: uppercase;
            color: #647089;
            text-align: center;
        }
