<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store-Supplier Agreement - {{ $contractNumber ?? 'DRAFT' }}</title>
    <style>
        /* BARABARA font for FurniSync platform name only */
        @font-face {
            font-family: 'BARABARA';
            /* Use public/ so it works for both DomPDF (filesystem) and HTML fallback rendering */
            src: url('{{ public_path('fonts/BARABARA-final.otf') }}') format('opentype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }
        
        /* Professional contract styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #1a1a1a;
            background: white;
            margin: 0;
            padding: 1.5cm 1.8cm;
        }
        
        .contract-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #2c3e50;
        }
        
        .platform-name {
            font-family: 'BARABARA', 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 32pt;
            font-weight: normal;
            letter-spacing: 2px;
            color: #1a5276;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        
        .document-title {
            font-size: 16pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 8px;
        }
        
        .document-subtitle {
            font-size: 9pt;
            color: #5d6d7e;
            margin-top: 4px;
        }
        
        .agreement-date {
            text-align: right;
            margin: 15px 0 20px;
            font-size: 9pt;
        }
        
        .parties-section {
            margin: 20px 0;
            padding: 10px 0;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
        }
        
        .party-block {
            margin: 12px 0;
        }
        
        .party-label {
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 6px;
        }
        
        .facilitator-note {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            font-size: 9pt;
            text-align: center;
            border-left: 3px solid #1a5276;
        }
        
        .clause {
            margin: 14px 0;
        }
        
        .clause-number {
            font-weight: 700;
            display: inline-block;
            width: 30px;
        }
        
        .clause-title {
            font-weight: 700;
            display: inline-block;
            text-transform: uppercase;
            font-size: 9.5pt;
        }
        
        .clause-content {
            margin-left: 30px;
            margin-top: 4px;
            text-align: justify;
        }
        
        .contract-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 9pt;
        }
        
        .contract-table th,
        .contract-table td {
            border: 1px solid #999;
            padding: 8px 10px;
            vertical-align: top;
        }
        
        .contract-table th {
            background: #f0f3f5;
            font-weight: 700;
            text-align: left;
            width: 25%;
        }
        
        .commercial-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }
        
        .commercial-table th,
        .commercial-table td {
            border: 1px solid #999;
            padding: 8px 10px;
            vertical-align: top;
        }
        
        .commercial-table th {
            background: #f0f3f5;
            font-weight: 700;
            width: 33%;
        }
        
        /* Terms and Conditions styling */
        .terms-section {
            margin: 15px 0;
            padding: 12px;
            background: #fef9e6;
            border-left: 4px solid #f39c12;
            font-size: 9pt;
        }
        
        .terms-title {
            font-weight: 700;
            font-size: 10pt;
            margin-bottom: 10px;
            color: #e67e22;
        }
        
        .terms-content {
            white-space: pre-wrap;
            line-height: 1.5;
        }
        
        .signature-section {
            margin-top: 35px;
            page-break-inside: avoid;
        }
        
        .signature-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .signature-grid td {
            width: 50%;
            padding: 30px 15px 0 15px;
            vertical-align: bottom;
        }
        
        .signature-line {
            border-top: 1px solid #1a1a1a;
            margin-top: 8px;
            width: 100%;
        }
        
        .signature-label {
            font-size: 8pt;
            color: #5d6d7e;
            margin-top: 5px;
        }
        
        .footer {
            margin-top: 35px;
            padding-top: 12px;
            border-top: 1px solid #ccc;
            font-size: 7pt;
            text-align: center;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="contract-header">
        <div class="platform-name">FurniSync</div>
        <div class="document-title">STORE-SUPPLIER AGREEMENT</div>
        <div class="document-subtitle">Facilitated by FurniSync IMS Platform</div>
    </div>

    <div class="agreement-date">
        <strong>Date of Issue:</strong> {{ \Carbon\Carbon::parse($createdAt ?? 'now')->format('F d, Y') }}<br>
        <strong>Agreement Reference:</strong> {{ $contractNumber ?? 'DRAFT' }}
    </div>

    <!-- PARTIES - Only Store and Supplier -->
    <div class="parties-section">
        <div class="party-block">
            <div class="party-label">THIS AGREEMENT (the "Agreement") is made and entered into by and between:</div>
            <div style="margin-top: 8px;">
                <strong>(1) THE STORE:</strong> {{ $storeName ?? '[Store Name]' }} (hereinafter referred to as the "<strong>Store</strong>");
            </div>
        </div>
        <div class="party-block">
            <div style="margin-top: 12px;">
                <strong>(2) THE SUPPLIER:</strong> {{ $supplierName ?? '[Supplier Name]' }} (hereinafter referred to as the "<strong>Supplier</strong>").
            </div>
        </div>
        <div class="party-block" style="margin-top: 12px; font-style: italic;">
            The Store and the Supplier are each referred to individually as a "<strong>Party</strong>" and collectively as the "<strong>Parties</strong>."
        </div>
    </div>

    <!-- FACILITATOR NOTE - FurniSync as middle man -->
    <div class="facilitator-note">
        <strong>Facilitator:</strong> This Agreement is facilitated through the FurniSync IMS Platform. FurniSync acts solely as a technology platform and intermediary. FurniSync is NOT a party to this Agreement and shall have no liability or obligations under the terms set forth herein.
    </div>

    <!-- RECITALS -->
    <div class="clause">
        <div class="clause-number"></div>
        <div class="clause-title">WHEREAS:</div>
        <div class="clause-content">
            The Store operates a retail business and wishes to purchase products from the Supplier. The Supplier wishes to supply products to the Store under the terms and conditions set forth herein. The Parties agree to use the FurniSync IMS Platform to facilitate their transactions.
        </div>
    </div>

    <!-- AGREEMENT DETAILS TABLE -->
    <div class="clause">
        <div class="clause-number">1.</div>
        <div class="clause-title">Agreement Details</div>
        <div class="clause-content">
            <table class="contract-table">
                <tr>
                    <th>Agreement Number</th>
                    <td>{{ $contractNumber ?? 'PENDING' }}</td>
                    <th>Agreement Type</th>
                    <td>{{ ucfirst($contractType ?? 'standard') }}</td>
                </tr>
                <tr>
                    <th>Agreement Title</th>
                    <td colspan="3">{{ $contractTitle ?? 'Supply Agreement' }}</td>
                </tr>
                <tr>
                    <th>Store Name</th>
                    <td>{{ $storeName ?? '-' }}</td>
                    <th>Supplier Name</th>
                    <td>{{ $supplierName ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Effective Date</th>
                    <td>{{ $startDate ?? 'Not Specified' }}</td>
                    <th>Expiration Date</th>
                    <td>{{ $endDate ?? 'Not Specified' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- COMMERCIAL TERMS - Discount from Supplier to Store (No Payment Terms) -->
    <div class="clause">
        <div class="clause-number">2.</div>
        <div class="clause-title">Commercial Terms</div>
        <div class="clause-content">
            <table class="commercial-table">
                <tr>
                    <th>Discount</th>
                    <td>The Supplier agrees to grant the Store a <strong>{{ $discountPercentage ?? 0 }}% discount</strong> on all products purchased under this Agreement.<br>
                    <span style="font-size:8pt;">(This discount shall be applied to the Supplier's standard retail price)</span>
                    </td>
                </tr>
                <tr>
                    <th>Tax</th>
                    <td>A tax rate of <strong>{{ $taxRate ?? 0 }}%</strong> shall apply to all transactions in accordance with Philippine tax laws (VAT).</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- SUPPLIER'S TERMS AND CONDITIONS -->
    <div class="clause">
        <div class="clause-number">3.</div>
        <div class="clause-title">Supplier's Terms and Conditions</div>
        <div class="clause-content">
            <div class="terms-section">
                <div class="terms-title">📋 TERMS & CONDITIONS PROVIDED BY SUPPLIER</div>
                <div class="terms-content">
                    @if(!empty($termsConditions))
                        {!! nl2br(e($termsConditions)) !!}
                    @else
                        <em>No specific terms and conditions provided by the supplier. Standard terms shall apply.</em>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- STANDARD TERMS -->
    <div class="clause">
        <div class="clause-number">4.</div>
        <div class="clause-title">Term</div>
        <div class="clause-content">
            This Agreement shall take effect on {{ $startDate ?? 'the Effective Date' }} and shall remain in force until {{ $endDate ?? 'the Expiration Date' }}, unless earlier terminated in accordance with Section 5 below.
        </div>
    </div>

    <div class="clause">
        <div class="clause-number">5.</div>
        <div class="clause-title">Termination</div>
        <div class="clause-content">
            Either Party may terminate this Agreement by giving fifteen (15) days' written notice to the other Party. Either Party may terminate this Agreement immediately upon a material breach by the other Party that remains uncured for seven (7) days after written notice.
        </div>
    </div>

    <div class="clause">
        <div class="clause-number">6.</div>
        <div class="clause-title">Supplier Obligations</div>
        <div class="clause-content">
            The Supplier agrees to: (a) deliver products in good condition and on time; (b) comply with all applicable Philippine laws and regulations; (c) provide accurate product information; (d) honor the discount stated in Section 2 of this Agreement; and (e) comply with the Terms and Conditions provided in Section 3.
        </div>
    </div>

    <div class="clause">
        <div class="clause-number">7.</div>
        <div class="clause-title">Store Obligations</div>
        <div class="clause-content">
            The Store agrees to: (a) provide accurate order information; (b) communicate any issues or concerns to the Supplier in a timely manner; and (c) comply with the Supplier's Terms and Conditions as provided in Section 3.
        </div>
    </div>

    <div class="clause">
        <div class="clause-number">8.</div>
        <div class="clause-title">Limitation of Liability</div>
        <div class="clause-content">
            To the extent permitted by Philippine law, neither Party shall be liable for indirect or consequential damages. The FurniSync Platform shall have no liability whatsoever under this Agreement as it is solely a facilitator.
        </div>
    </div>

    <div class="clause">
        <div class="clause-number">9.</div>
        <div class="clause-title">Governing Law</div>
        <div class="clause-content">
            This Agreement shall be governed by and construed in accordance with the laws of the Republic of the Philippines. Any dispute arising from this Agreement shall be resolved through good-faith negotiations. If unresolved, the dispute may be brought to the proper courts of the Philippines.
        </div>
    </div>

    <div class="clause">
        <div class="clause-number">10.</div>
        <div class="clause-title">Entire Agreement</div>
        <div class="clause-content">
            This Agreement, including the Supplier's Terms and Conditions in Section 3, constitutes the entire understanding between the Store and the Supplier with respect to the subject matter hereof and supersedes all prior agreements, whether written or oral.
        </div>
    </div>

    <!-- SIGNATURE SECTION - Store and Supplier only -->
    <div class="signature-section">
        <table class="signature-grid">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        <strong>FOR AND ON BEHALF OF THE STORE</strong><br>
                        Signature: ___________________________<br>
                        Printed Name: {{ $storeName ?? '[Store Representative]' }}<br>
                        Title: Authorized Representative<br>
                        Date: ___________________
                    </div>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        <strong>FOR AND ON BEHALF OF THE SUPPLIER</strong><br>
                        Signature: ___________________________<br>
                        Printed Name: {{ $supplierName ?? '[Supplier Representative]' }}<br>
                        Title: Authorized Representative<br>
                        Date: ___________________
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        This Agreement is executed in duplicate. Each Party acknowledges receipt of a true copy.<br>
        Facilitated by FurniSync IMS Platform | Agreement ID: {{ $contractNumber ?? 'DRAFT' }} | Generated: {{ \Carbon\Carbon::parse($createdAt ?? 'now')->format('Y-m-d h:i:s A') }}
    </div>
</body>
</html>
