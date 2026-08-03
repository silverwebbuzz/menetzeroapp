<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultantEntityRequest;
use Illuminate\Http\Request;

class ConsultantEntityRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $requests = ConsultantEntityRequest::query()
            ->with(['consultantCompany', 'user'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.entity-requests.index', compact('requests', 'status'));
    }

    public function update(Request $request, ConsultantEntityRequest $entityRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:new,contacted,quoted,activated,closed',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $entityRequest->update($data);

        return back()->with('success', 'Entity request updated.');
    }
}
