<?php

namespace App\Services;

use App\Models\CompanyDisclosure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AssuranceDocumentService
{
    public const MAX_SIZE_KB = 10240;

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'application/pdf',
    ];

    /**
     * @return array{filename: string, path: string, mime: string, size: int, uploaded_at: string}|null
     */
    public function metadataFor(int $companyId, int $fiscalYear): ?array
    {
        $doc = $this->content($companyId, $fiscalYear)['assurance_document'] ?? null;

        return is_array($doc) && !empty($doc['path']) ? $doc : null;
    }

    /**
     * @return array{filename: string, path: string, mime: string, size: int, uploaded_at: string}
     */
    public function store(int $companyId, int $fiscalYear, UploadedFile $file): array
    {
        if (!$file->isValid()) {
            abort(422, 'Invalid upload.');
        }

        if ($file->getSize() > self::MAX_SIZE_KB * 1024) {
            abort(422, 'Assurance PDF must be 10 MB or smaller.');
        }

        $mime = $file->getMimeType() ?? '';
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            abort(422, 'Assurance statement must be a PDF file.');
        }

        $this->deleteFile($companyId, $fiscalYear);

        $path = $file->store("assurance-documents/{$companyId}/{$fiscalYear}", 'local');

        $meta = [
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $mime,
            'size' => $file->getSize(),
            'uploaded_at' => now()->toIso8601String(),
        ];

        $this->persistMetadata($companyId, $fiscalYear, $meta);

        return $meta;
    }

    public function delete(int $companyId, int $fiscalYear): void
    {
        $this->deleteFile($companyId, $fiscalYear);
        $this->persistMetadata($companyId, $fiscalYear, null);
    }

    /**
     * @return array{path: string, downloadName: string, mime: string}
     */
    public function resolveDownload(int $companyId, int $fiscalYear): array
    {
        $meta = $this->metadataFor($companyId, $fiscalYear);
        if (!$meta) {
            abort(404, 'Assurance document not found.');
        }

        $path = $meta['path'];
        $prefix = "assurance-documents/{$companyId}/{$fiscalYear}/";

        if (!str_starts_with($path, $prefix) || !Storage::disk('local')->exists($path)) {
            abort(404, 'File no longer available.');
        }

        return [
            'path' => $path,
            'downloadName' => $meta['filename'] ?? 'assurance-statement.pdf',
            'mime' => $meta['mime'] ?? 'application/pdf',
        ];
    }

    protected function deleteFile(int $companyId, int $fiscalYear): void
    {
        $meta = $this->metadataFor($companyId, $fiscalYear);
        if ($meta && !empty($meta['path']) && Storage::disk('local')->exists($meta['path'])) {
            Storage::disk('local')->delete($meta['path']);
        }
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    protected function persistMetadata(int $companyId, int $fiscalYear, ?array $meta): void
    {
        $record = CompanyDisclosure::firstOrCreate(
            [
                'company_id' => $companyId,
                'framework' => 'esg_report',
                'section' => 'about_report',
                'fiscal_year' => $fiscalYear,
            ],
            ['content' => [], 'status' => 'draft']
        );

        $content = $record->content ?? [];
        if ($meta === null) {
            unset($content['assurance_document']);
        } else {
            $content['assurance_document'] = $meta;
        }

        $record->update(['content' => $content]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function content(int $companyId, int $fiscalYear): array
    {
        return CompanyDisclosure::where('company_id', $companyId)
            ->where('framework', 'esg_report')
            ->where('section', 'about_report')
            ->where('fiscal_year', $fiscalYear)
            ->value('content') ?? [];
    }
}
