<?php

namespace App\Http\Controllers\Consultant;

use App\Data\ConsultantOptions;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $consultant = Auth::guard('consultant')->user();
        $consultant->load('documents');

        $requiredDocs = ConsultantOptions::REQUIRED_DOCUMENT_TYPES;
        $uploadedTypes = $consultant->documents->pluck('document_type')->all();
        $missingDocs = array_diff($requiredDocs, $uploadedTypes);

        $introCount = $consultant->introRequests()->where('status', 'new')->count();
        $orderCount = $consultant->orders()->count();

        return view('consultant.dashboard', compact(
            'consultant',
            'missingDocs',
            'introCount',
            'orderCount',
        ));
    }
}
