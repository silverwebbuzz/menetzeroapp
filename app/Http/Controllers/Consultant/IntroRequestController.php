<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IntroRequestController extends Controller
{
    public function index()
    {
        $consultant = Auth::guard('consultant')->user();

        $requests = $consultant->introRequests()
            ->with(['company', 'user'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('consultant.intro-requests', compact('consultant', 'requests'));
    }
}
