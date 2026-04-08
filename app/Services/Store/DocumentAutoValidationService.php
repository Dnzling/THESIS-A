<?php

namespace App\Services\Store;

use Illuminate\Support\Facades\Storage;

class DocumentAutoValidationService
{
    private const DOC_KEYWORDS = [
        'business_registration_file' => [
            'business',
            'registration',
            'permit',
            'dti',
            'sec',
            'philippines',
        ],
        'business_permit_file' => [
            'business permit',
            'mayor',
            'city',
            'municipality',
            'permit',
            'philippines',
        ],
        'tax_certificate_file' => [
            'bureau of internal revenue',
            'bir',
            'tin',
            'tax',
            'certificate',
            'registration',
        ],
        'gov_id_front_file' => [
            'republic of the philippines',
            'id',
            'name',
        ],
        'gov_id_back_file' => [
            'signature',
            'address',
            'id',
        ],
        'selfie_with_id_file' => [
            'manual review',
        ],
        'other_documents' => [
            'manual review',
        ],
        'business_license' => [
            'business permit',
            'license',
            'city',
            'municipality',
            'philippines',
        ],
        'tax_id' => [
            'bureau of internal revenue',
            'bir',
            'tin',
            'tax',
            'certificate',
        ],
        'company_registration' => [
            'certificate of registration',
            'dti',
            'sec',
            'company',
            'registration',
            'philippines',
        ],
        'bank_details' => [
            'bank',
            'account',
            'account name',
            'account number',
            'branch',
        ],
    ];

    public function validateDocument(string $path, string $documentKey, ?int $index = null, string $diskName = 'public'): array
    {
        $disk = Storage::disk($diskName);
        if (!$disk->exists($path)) {
            return [
                'status' => 'invalid',
                'score' => 0,
                'reason' => 'File not found in storage.',
                'checks' => ['exists' => false],
                'document_key' => $documentKey,
                'index' => $index,
                'disk' => $diskName,
            ];
        }

        $absolutePath = $disk->path($path);
        $mime = strtolower((string) ($disk->mimeType($path) ?: ''));
        $sizeKb = (int) ceil($disk->size($path) / 1024);
        $contentHash = hash_file('sha256', $absolutePath) ?: null;

        $text = $this->extractText($absolutePath, $mime);
        $textForMatch = strtolower(preg_replace('/\s+/', ' ', $text));
        $keywords = self::DOC_KEYWORDS[$documentKey] ?? [];
        $matched = [];
        foreach ($keywords as $keyword) {
            if ($keyword === 'manual review') {
                continue;
            }
            if (str_contains($textForMatch, strtolower($keyword))) {
                $matched[] = $keyword;
            }
        }

        $requiredKeywordCount = max(1, count(array_filter($keywords, fn($k) => $k !== 'manual review')));
        $matchScore = (int) round((count($matched) / $requiredKeywordCount) * 100);

        $status = 'needs_manual_review';
        $reason = 'Automatic checks completed; manual review recommended.';

        if (in_array('manual review', $keywords, true)) {
            $status = 'needs_manual_review';
            $reason = 'Document type requires manual identity review.';
        } elseif (trim($textForMatch) === '') {
            $status = 'needs_manual_review';
            $reason = 'No readable text extracted from file.';
        } elseif ($matchScore >= 60) {
            $status = 'likely_valid';
            $reason = 'Expected document keywords were detected.';
        } elseif ($matchScore >= 25) {
            $status = 'needs_manual_review';
            $reason = 'Partial keyword match; please verify manually.';
        } else {
            $status = 'invalid';
            $reason = 'Document text does not match expected content.';
        }

        return [
            'status' => $status,
            'score' => $matchScore,
            'reason' => $reason,
            'checks' => [
                'exists' => true,
                'disk' => $diskName,
                'mime_type' => $mime,
                'size_kb' => $sizeKb,
                'content_hash' => $contentHash,
                'matched_keywords' => $matched,
                'matched_count' => count($matched),
                'expected_keywords' => array_values(array_filter($keywords, fn($k) => $k !== 'manual review')),
                'extracted_text_sample' => $text ? mb_substr($text, 0, 500) : null,
            ],
            'document_key' => $documentKey,
            'index' => $index,
        ];
    }

    private function extractText(string $absolutePath, string $mime): string
    {
        if (str_contains($mime, 'pdf')) {
            $text = $this->extractPdfText($absolutePath);
            if ($text !== '') {
                return $text;
            }
        }

        if (str_contains($mime, 'image/')) {
            $text = $this->extractImageText($absolutePath);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    private function extractPdfText(string $absolutePath): string
    {
        $where = trim((string) @shell_exec('where pdftotext 2>NUL'));
        if ($where !== '') {
            $command = 'pdftotext -layout ' . escapeshellarg($absolutePath) . ' -';
            $output = (string) @shell_exec($command);
            if (trim($output) !== '') {
                return $output;
            }
        }

        return '';
    }

    private function extractImageText(string $absolutePath): string
    {
        $where = trim((string) @shell_exec('where tesseract 2>NUL'));
        if ($where === '') {
            return '';
        }

        $command = 'tesseract ' . escapeshellarg($absolutePath) . ' stdout -l eng 2>NUL';
        $output = (string) @shell_exec($command);
        return trim($output) !== '' ? $output : '';
    }
}
