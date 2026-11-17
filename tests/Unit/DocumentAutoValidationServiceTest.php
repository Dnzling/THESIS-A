<?php

use App\Services\Store\DocumentAutoValidationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

it('uses OCR.space text when validating store documents', function () {
    Storage::fake('public');
    Storage::disk('public')->put('verification/permit.png', 'fake image content');
    config()->set('services.ocr_space.key', 'test-key');

    Http::fake([
        'api.ocr.space/*' => Http::response([
            'ParsedResults' => [[
                'ParsedText' => 'Republic of the Philippines City Business Permit',
            ]],
            'IsErroredOnProcessing' => false,
        ]),
    ]);

    $result = app(DocumentAutoValidationService::class)->validateDocument(
        'verification/permit.png',
        'business_permit_file'
    );

    expect($result['status'])->toBe('likely_valid')
        ->and($result['checks']['matched_keywords'])->toContain('business permit', 'city', 'permit', 'philippines');

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('apikey', 'test-key')
            && $request->isMultipart()
            && $request->url() === 'https://api.ocr.space/parse/image';
    });
});

it('combines OCR.space text from every PDF page', function () {
    Storage::fake('private');
    Storage::disk('private')->put('supplier/tax.pdf', 'fake pdf content');
    config()->set('services.ocr_space.key', 'test-key');

    Http::fake([
        'api.ocr.space/*' => Http::response([
            'ParsedResults' => [
                ['ParsedText' => 'Bureau of Internal Revenue'],
                ['ParsedText' => 'TIN Tax Certificate'],
            ],
            'IsErroredOnProcessing' => false,
        ]),
    ]);

    $result = app(DocumentAutoValidationService::class)->validateDocument(
        'supplier/tax.pdf',
        'tax_id',
        null,
        'private'
    );

    expect($result['status'])->toBe('likely_valid')
        ->and($result['checks']['matched_keywords'])->toContain('bureau of internal revenue', 'tin', 'tax', 'certificate');
});

it('does not call OCR.space when no key is configured', function () {
    Storage::fake('public');
    Storage::disk('public')->put('verification/id.png', 'fake image content');
    config()->set('services.ocr_space.key', null);
    Http::fake();

    $text = app(DocumentAutoValidationService::class)->extractTextFromDocument('verification/id.png');

    expect($text)->toBe('');
    Http::assertNothingSent();
});
