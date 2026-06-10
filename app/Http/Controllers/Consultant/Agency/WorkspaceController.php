<?php

namespace App\Http\Controllers\Consultant\Agency;

use App\Http\Controllers\Controller;
use App\Models\ConsultantClientEngagement;
use App\Services\ConsultantAgencyWorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class WorkspaceController extends Controller
{
    public function __construct(
        protected ConsultantAgencyWorkspaceService $workspace,
    ) {
    }

    public function switcher()
    {
        $user = Auth::user();
        $engagements = $this->workspace->switchableEngagements($user);
        $acting = $this->workspace->resolveActingCompany($user);

        return view('consultant.agency.workspace.switcher', compact('engagements', 'acting'));
    }

    public function enter(Request $request, int $engagement)
    {
        $user = Auth::user();
        $record = ConsultantClientEngagement::findOrFail($engagement);

        try {
            $managed = $this->workspace->enterWorkspaceFromEngagement($user, $record);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('client.dashboard')
            ->with('success', "Now working in {$managed->name} (PRY {$record->primary_reporting_year}).");
    }

    public function enterReadOnly(int $engagement)
    {
        $user = Auth::user();
        $record = ConsultantClientEngagement::findOrFail($engagement);

        try {
            $managed = $this->workspace->enterReadOnlyWorkspace($user, $record);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('client.dashboard')
            ->with('info', "Read-only view of {$managed->name} (archived engagement).");
    }

    public function exit()
    {
        $this->workspace->exitWorkspace();

        return redirect()
            ->route('consultant.dashboard')
            ->with('success', 'Returned to agency hub.');
    }
}
