<header class="header">
    <div class="header-top">
        <table>
            <tr>
                <td style="width: 20%;">
                    <img src="file://{{ public_path('assets/images/logo.jpeg') }}" alt="Left Logo" class="header-logo">
                </td>
                <td class="header-center" style="width: 70%;">
                    <h4>{{ $documentTemplate->header_line_1 }}</h4>
                    <h3>{{ $documentTemplate->header_line_2 }}</h3>
                    <p>{{ $documentTemplate->header_address_line_1  }}</p>
                    <p class="text-small">{{ $documentTemplate->header_address_line_2 }}</p>
                    <p class="text-small contact-line">
                        <span>Telephone:</span>
                        <span>{{ $documentTemplate->header_telephone }}</span>
                    </p>
                    <p class="text-small contact-line">
                        <span>Email:</span>
                        <span>{{ $documentTemplate->header_email }}</span>
                    </p>
                    <p class="text-small contact-line">
                        <span>Website:</span>
                        <span>{{ $documentTemplate->header_website }}</span>
                    </p>
                </td>
                <td style="width: 20%;">
                    <img src="file://{{ public_path('assets/images/logo.jpeg') }}" alt="Right Logo" class="header-logo">
                </td>
            </tr>
        </table>
    </div>
    <div class="divider"></div>
</header>
