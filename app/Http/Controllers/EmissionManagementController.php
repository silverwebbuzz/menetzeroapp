<?php

namespace App\Http\Controllers;

use App\Models\EmissionSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmissionManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = EmissionSource::query();
        
        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        // Search by company name
        if ($request->has('search') && $request->search !== '') {
            $query->where('company_name', 'like', '%' . $request->search . '%');
        }
        
        // Sort by created date (newest first)
        $query->orderBy('created_at', 'desc');
        
        $emissions = $query->paginate(10);
        
        // Get statistics
        $stats = [
            'total' => EmissionSource::count(),
            'draft' => EmissionSource::where('status', 'draft')->count(),
            'submitted' => EmissionSource::where('status', 'submitted')->count(),
            'reviewed' => EmissionSource::where('status', 'reviewed')->count(),
        ];
        
        return view('emissions.management', compact('emissions', 'stats'));
    }
    
    public function show(EmissionSource $emission)
    {
        $emission->updateCalculatedTotals();
        return view('emissions.detail', compact('emission'));
    }
    
    public function edit(EmissionSource $emission)
    {
        if ($emission->status === 'submitted' || $emission->status === 'reviewed') {
            return redirect()->route('emissions.management')
                ->with('error', 'Cannot edit submitted or reviewed reports.');
        }
        
        // Set the emission in session for the form
        request()->session()->put('emission_form_id', $emission->id);
        
        return redirect()->route('emission-form.step', ['step' => 'company']);
    }
    
    public function duplicate(EmissionSource $emission)
    {
        $newEmission = $emission->replicate();
        $newEmission->company_name = $emission->company_name . ' (Copy)';
        $newEmission->status = 'draft';
        $newEmission->created_at = now();
        $newEmission->updated_at = now();
        $newEmission->save();
        
        return redirect()->route('emissions.management')
            ->with('success', 'Emission report duplicated successfully!');
    }
    
    public function delete(EmissionSource $emission)
    {
        if ($emission->status === 'submitted' || $emission->status === 'reviewed') {
            return redirect()->route('emissions.management')
                ->with('error', 'Cannot delete submitted or reviewed reports.');
        }
        
        $emission->delete();
        
        return redirect()->route('emissions.management')
            ->with('success', 'Emission report deleted successfully!');
    }
    
    public function updateStatus(EmissionSource $emission, Request $request)
    {
        $request->validate([
            'status' => 'required|in:draft,submitted,reviewed'
        ]);
        
        $emission->status = $request->status;
        $emission->save();
        
        return redirect()->route('emissions.management')
            ->with('success', 'Emission report status updated successfully!');
    }
}
