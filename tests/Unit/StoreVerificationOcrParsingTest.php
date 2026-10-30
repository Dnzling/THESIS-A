<?php

use App\Services\Store\DocumentAutoValidationService;

it('extracts the reference number for supported owner ID types', function (string $type, string $text, string $expected) {
    expect(app(DocumentAutoValidationService::class)->extractLikelyIdNumber($text, $type))->toBe($expected);
})->with([
    ['national_id', 'PhilSys PCN No: 1234-5678-9012-3456', '1234-5678-9012-3456'],
    ['sss', 'SSS No: 12-3456789-0', '12-3456789-0'],
    ['tin', 'TIN: 123-456-789', '123-456-789'],
    ['passport', 'Passport No: P1234567', 'P1234567'],
    ['driver_license', 'License No: N01-23-456789', 'N01-23-456789'],
]);

it('extracts business document references and expiry dates', function () {
    $ocr = app(DocumentAutoValidationService::class);
    expect($ocr->extractDocumentReference("Registration No: DTI-123456\nExpiration Date: 12/31/2027", 'registration'))->toBe('DTI-123456')
        ->and($ocr->extractDocumentReference('TIN: 123-456-789', 'bir'))->toBe('123-456-789')
        ->and($ocr->extractDocumentReference('Permit No: BP-2026-1234', 'mayor'))->toBe('BP-2026-1234')
        ->and($ocr->extractDocumentReference("Permit Number\nBP-2026-1234", 'mayor'))->toBe('BP-2026-1234')
        ->and($ocr->extractDocumentReference('TIN 123-456-789', 'bir'))->toBe('123-456-789')
        ->and($ocr->extractDocumentExpiration('Expiration Date: 12/31/2027'))->toBe('2027-12-31');
});

it('reads the reference and expiry data printed on the demo permit fixtures', function () {
    $ocr = app(DocumentAutoValidationService::class);
    $fixture = static function (string $file): string {
        $html = file_get_contents(__DIR__ . "/../../resources/views/permits/{$file}");
        return html_entity_decode(strip_tags(preg_replace('/<\/?(?:div|br|p|strong|small|span)[^>]*>/i', "\n", $html)));
    };

    $dti = $fixture('dti_permit.html');
    $bir = $fixture('tax_permit.html');
    $mayor = $fixture('mayors_permit.html');

    expect($ocr->extractDocumentReference($dti, 'registration'))->toBe('DTI-2026-0-1234567')
        ->and($ocr->extractDocumentExpiration($dti))->toBe('2031-07-09')
        ->and($ocr->extractDocumentReference($bir, 'bir'))->toBe('123-456-789-000')
        ->and($ocr->extractDocumentReference($mayor, 'mayor'))->toBe('2026-0126752')
        ->and($ocr->extractDocumentExpiration($mayor))->toBe('2026-12-31');
});
