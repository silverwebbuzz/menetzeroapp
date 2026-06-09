<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultantIntroRequest;
use Illuminate\Http\Request;

class ConsultantIntroRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $requests = ConsultantIntroRequest::query()
            ->with(['company', 'user', 'consultant'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.consultants.intro-requests', compact('requests', 'status'));
    }

    public function update(Request $request, ConsultantIntroRequest $introRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:new,contacted,converted,closed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $introRequest->update($data);

        return back()->with('success', 'Intro request updated.');
    }
}
