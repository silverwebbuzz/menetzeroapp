<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerClientEngagement;
use App\Services\PartnerWorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class WorkspaceController extends Controller
{
    public function __construct(
        protected PartnerWorkspaceService $workspace,
    ) {
    }

    public function switcher()
    {
        $user = Auth::user();
        $engagements = $this->workspace->switchableEngagements($user);
        $acting = $this->workspace->resolveActingCompany($user);

        return view('partner.workspace.switcher', compact('engagements', 'acting'));
    }

    public function enter(Request $request, int $engagement)
    {
        $user = Auth::user();
        $record = PartnerClientEngagement::findOrFail($engagement);

        try {
            $managed = $this->workspace->enterWorkspaceFromEngagement($user, $record);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('client.dashboard')
            ->with('success', "Now working in {$managed->name} (PRY {$record->primary_reporting_year}).");
    }

    public function enterByCompany(Request $request)
    {
        $request->validate([
            'managed_company_id' => 'required|integer|exists:companies,id',
        ]);

        try {
            $managed = $this->workspace->enterWorkspace($user = Auth::user(), (int) $request->managed_company_id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('client.dashboard')
            ->with('success', "Now working in {$managed->name}.");
    }

    public function exit()
    {
        $this->workspace->exitWorkspace();

        return redirect()
            ->route('partner.dashboard')
            ->with('success', 'Returned to agency hub.');
    }
}
