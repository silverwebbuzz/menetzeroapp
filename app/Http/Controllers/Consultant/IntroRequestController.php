<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IntroRequestController extends Controller
{
    public function index()
    {
        $consultant = Auth::guard('consultant')->user();

        $clientRequests = $consultant->introRequests()
            ->with(['company', 'user'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($req) => [
                'type' => 'client',
                'date' => $req->created_at,
                'name' => $req->company?->name ?? 'MenetZero client',
                'contact' => $req->user?->email,
                'pack' => $req->packLabel(),
                'status' => $req->status,
                'message' => $req->message,
            ]);

        $publicInquiries = $consultant->publicInquiries()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($inq) => [
                'type' => 'public',
                'date' => $inq->created_at,
                'name' => $inq->requester_company ?: $inq->requester_name,
                'contact' => $inq->requester_phone . ' · ' . $inq->requester_email,
                'pack' => 'Public directory',
                'status' => $inq->status,
                'message' => $inq->message,
            ]);

        $requests = $clientRequests
            ->concat($publicInquiries)
            ->sortByDesc('date')
            ->values();

        return view('consultant.intro-requests', compact('consultant', 'requests'));
    }
}
