<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MeasurementDocumentService
{
    public const MAX_FILES = 5;

    public const MAX_SIZE_KB = 10240;

    /**
     * @param  array<int, UploadedFile>  $files
     * @return list<array{filename: string, path: string, mime: string, size: int, uploaded_at: string}>
     */
    public function storeForCompany(int $companyId, array $files): array
    {
        $stored = [];

        foreach (array_slice($files, 0, self::MAX_FILES) as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $path = $file->store("measurement-documents/{$companyId}", 'local');

            $stored[] = [
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'uploaded_at' => now()->toIso8601String(),
            ];
        }

        return $stored;
    }

    /**
     * @param  list<array<string, mixed>>|null  $existing
     * @param  array<int, UploadedFile>  $newFiles
     * @return list<array<string, mixed>>
     */
    public function mergeDocuments(?array $existing, array $newFiles, int $companyId): array
    {
        $docs = $existing ?? [];

        if (count($docs) >= self::MAX_FILES) {
            return $docs;
        }

        $remaining = self::MAX_FILES - count($docs);
        $added = $this->storeForCompany($companyId, array_slice($newFiles, 0, $remaining));

        return array_merge($docs, $added);
    }

    public function deleteDocument(array $doc): void
    {
        if (!empty($doc['path']) && Storage::disk('local')->exists($doc['path'])) {
            Storage::disk('local')->delete($doc['path']);
        }
    }

    /**
     * @return array{doc: array<string, mixed>, downloadName: string}
     */
    public function resolveDownload(array $supportingDocs, int $index, int $companyId): array
    {
        if (!isset($supportingDocs[$index]) || !is_array($supportingDocs[$index])) {
            abort(404, 'Document not found.');
        }

        $doc = $supportingDocs[$index];
        $path = $doc['path'] ?? '';

        if ($path === '' || !str_starts_with($path, "measurement-documents/{$companyId}/")) {
            abort(403, 'Invalid document path.');
        }

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File no longer available.');
        }

        return [
            'doc' => $doc,
            'downloadName' => $doc['filename'] ?? 'supporting-document',
        ];
    }
}
