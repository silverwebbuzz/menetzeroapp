<?php

namespace App\Http\Controllers;

use App\Exports\Scope12BulkTemplateExport;
use App\Models\Location;
use App\Services\Scope12BulkImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Scope12BulkImportController extends Controller
{
    public function __construct(
        protected Scope12BulkImportService $importService
    ) {}

    /**
     * Download bulk import template.
     * format=xlsx|csv  variant=blank|sample|workbook (default workbook for xlsx)
     */
    public function downloadTemplate(Request $request)
    {
        $this->requirePermission('measurements.view', null, ['measurements.*', 'manage_measurements']);

        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company) {
            abort(403, 'No active company found.');
        }

        $this->requireBulkImport($company->id);

        $format = strtolower($request->query('format', 'xlsx'));
        $variant = strtolower($request->query('variant', 'workbook'));

        $locationNames = Location::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        if ($format === 'csv') {
            return $this->downloadCsv($variant, $locationNames);
        }

        if ($variant === 'blank') {
            return Excel::download(
                new Scope12BulkTemplateExport([]),
                'scope-1-2-template-blank.xlsx'
            );
        }

        return Excel::download(
            new Scope12BulkTemplateExport($locationNames),
            'scope-1-2-bulk-import-template.xlsx'
        );
    }

    protected function downloadCsv(string $variant, array $locationNames): StreamedResponse
    {
        $filename = $variant === 'sample'
            ? 'scope-1-2-sample-data.csv'
            : 'scope-1-2-template-blank.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($variant, $locationNames) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, Scope12BulkImportService::HEADERS);

            if ($variant === 'sample') {
                foreach (Scope12BulkImportService::sampleRows() as $row) {
                    fputcsv($file, $row);
                }
            }

            if (!empty($locationNames)) {
                fputcsv($file, []);
                fputcsv($file, ['# Your locations (use exact names above):']);
                foreach ($locationNames as $name) {
                    fputcsv($file, ['# ' . $name]);
                }
            }

            fclose($file);
        }, 200, $headers);
    }

    public function import(Request $request)
    {
        $this->requirePermission('measurements.add', null, ['measurements.*', 'manage_measurements']);

        $user = Auth::user();
        $company = $user->getActiveCompany();
        if (!$company) {
            abort(403, 'No active company found.');
        }

        $this->requireBulkImport($company->id);

        if (app(\App\Services\ConsultantAgencyWorkspaceService::class)->isReadOnlyWorkspace()) {
            return back()->with('error', 'Bulk import is not available in read-only archived workspaces.');
        }

        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            if (in_array($extension, ['xlsx', 'xls'], true)) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                $namedSheets = [];
                foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                    $namedSheets[$worksheet->getTitle()] = $worksheet->toArray();
                }
                $rows = $this->importService->extractDataSheet($namedSheets);
            } else {
                $content = file_get_contents($file->getRealPath());
                $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
                $rows = array_map('str_getcsv', array_filter(explode("\n", str_replace("\r\n", "\n", $content)), fn ($line) => trim($line) !== ''));
            }

            $result = $this->importService->importRows($rows, $company->id, $user->id);

            if ($result['imported'] === 0) {
                return redirect()
                    ->route('quick-input.index')
                    ->with('error', 'Import failed. ' . implode(' ', array_slice($result['errors'], 0, 5)))
                    ->with('import_errors', $result['errors']);
            }

            $message = "Successfully imported {$result['imported']} Scope 1 & 2 " . ($result['imported'] === 1 ? 'entry' : 'entries') . '.';
            if (!empty($result['errors'])) {
                $message .= ' ' . count($result['errors']) . ' row(s) had errors and were skipped.';
            }
            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} empty row(s) skipped.";
            }

            return redirect()
                ->route('quick-input.index')
                ->with('success', $message)
                ->with('import_errors', $result['errors']);
        } catch (\Throwable $e) {
            report($e);
            return redirect()
                ->route('quick-input.index')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
