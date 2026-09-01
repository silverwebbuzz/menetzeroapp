<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoice downloads.
 *
 * An invoice carries the buyer's name, address and TRN, so access is checked
 * against the company on the invoice -- never on the id alone, which would let
 * anyone enumerate other customers' billing details.
 */
class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoices)
    {
    }

    public function download(Request $request, Invoice $invoice): Response
    {
        abort_unless($this->canView($invoice), 403);

        return response($this->invoices->pdfContents($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $invoice->invoice_number . '.pdf"',
        ]);
    }

    public function view(Request $request, Invoice $invoice): Response
    {
        abort_unless($this->canView($invoice), 403);

        return response($this->invoices->pdfContents($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $invoice->invoice_number . '.pdf"',
        ]);
    }

    /**
     * A viewer must belong to the company the invoice was issued to.
     *
     * Consultant agencies are Company rows too (ConsultantAgencyPaymentService
     * writes the agency's company id), so both guards resolve through the same
     * check. Admins can view any invoice for support.
     */
    protected function canView(Invoice $invoice): bool
    {
        if (!$invoice->company_id) {
            return false;
        }

        $user = Auth::user();
        if ($user) {
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }

            $active = method_exists($user, 'getActiveCompany') ? $user->getActiveCompany() : null;
            if ($active && (int) $active->id === (int) $invoice->company_id) {
                return true;
            }
        }

        $consultant = Auth::guard('consultant')->user();
        if ($consultant && (int) ($consultant->agency_company_id ?? 0) === (int) $invoice->company_id) {
            return true;
        }

        return false;
    }
}
