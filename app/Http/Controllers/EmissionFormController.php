<?php

namespace App\Http\Controllers;

use App\Models\EmissionSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EmissionFormController extends Controller
{
    const STEPS = [
        'company' => 'A',
        'scope1' => 'B', 
        'scope2' => 'C',
        'scope3' => 'D',
        'evidence' => 'E',
        'review' => 'F'
    ];

    public function index()
    {
        return redirect()->route('emission-form.step', ['step' => 'company']);
    }

    public function showStep(Request $request, $step)
    {
        if (!array_key_exists($step, self::STEPS)) {
            return redirect()->route('emission-form.index');
        }

        $emissionSource = $this->getOrCreateEmissionSource($request);
        $progress = $this->calculateProgress($emissionSource);

        return view('emission-form.step', compact('step', 'emissionSource', 'progress'));
    }

    public function storeStep(Request $request, $step)
    {
        $emissionSource = $this->getOrCreateEmissionSource($request);
        
        $validationRules = $this->getValidationRules($step);
        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('step', $step);
        }

        $this->updateEmissionSource($emissionSource, $request, $step);
        
        // Handle file uploads for evidence step
        if ($step === 'evidence') {
            $this->handleFileUploads($emissionSource, $request);
        }

        // Calculate totals if we have enough data
        if ($step === 'scope1' || $step === 'scope2' || $step === 'scope3') {
            $emissionSource->updateCalculatedTotals();
        }

        $nextStep = $this->getNextStep($step);
        
        if ($nextStep) {
            return redirect()->route('emission-form.step', ['step' => $nextStep])
                ->with('success', 'Step ' . self::STEPS[$step] . ' completed successfully!');
        } else {
            return redirect()->route('emission-form.review')
                ->with('success', 'All steps completed! Please review your data.');
        }
    }

    public function review()
    {
        $emissionSource = $this->getOrCreateEmissionSource(request());
        $emissionSource->updateCalculatedTotals();
        
        return view('emission-form.review', compact('emissionSource'));
    }

    public function submit(Request $request)
    {
        $emissionSource = $this->getOrCreateEmissionSource($request);
        $emissionSource->updateCalculatedTotals();
        $emissionSource->status = 'submitted';
        $emissionSource->save();

        return redirect()->route('emission-form.success')
            ->with('success', 'Emission data submitted successfully!');
    }

    public function success()
    {
        return view('emission-form.success');
    }

    private function getOrCreateEmissionSource(Request $request)
    {
        $sessionId = $request->session()->get('emission_form_id');
        
        if ($sessionId) {
            $emissionSource = EmissionSource::find($sessionId);
            if ($emissionSource) {
                return $emissionSource;
            }
        }

        $emissionSource = EmissionSource::create([
            'company_name' => '',
            'sector' => '',
            'location' => '',
            'reporting_year' => date('Y'),
            'status' => 'draft'
        ]);

        $request->session()->put('emission_form_id', $emissionSource->id);
        
        return $emissionSource;
    }

    private function calculateProgress($emissionSource)
    {
        $completedFields = 0;
        $totalFields = 0;

        // Company info
        $companyFields = ['company_name', 'sector', 'location', 'reporting_year'];
        foreach ($companyFields as $field) {
            $totalFields++;
            if (!empty($emissionSource->$field)) $completedFields++;
        }

        // Scope 1
        $scope1Fields = ['diesel_litres', 'petrol_litres', 'natural_gas_m3', 'refrigerant_kg', 'other_emissions'];
        foreach ($scope1Fields as $field) {
            $totalFields++;
            if (!empty($emissionSource->$field)) $completedFields++;
        }

        // Scope 2
        $scope2Fields = ['electricity_kwh', 'district_cooling_kwh'];
        foreach ($scope2Fields as $field) {
            $totalFields++;
            if (!empty($emissionSource->$field)) $completedFields++;
        }

        // Scope 3
        $scope3Fields = ['business_travel_flights_km', 'car_hire_km', 'waste_tonnes', 'water_m3', 'purchased_goods'];
        foreach ($scope3Fields as $field) {
            $totalFields++;
            if (!empty($emissionSource->$field)) $completedFields++;
        }

        return round(($completedFields / $totalFields) * 100);
    }

    private function getValidationRules($step)
    {
        $rules = [];

        switch ($step) {
            case 'company':
                $rules = [
                    'company_name' => 'required|string|max:255',
                    'sector' => 'required|string|max:255',
                    'location' => 'required|string|max:255',
                    'reporting_year' => 'required|integer|min:2020|max:' . (date('Y') + 1)
                ];
                break;
            case 'scope1':
                $rules = [
                    'diesel_litres' => 'nullable|numeric|min:0',
                    'petrol_litres' => 'nullable|numeric|min:0',
                    'natural_gas_m3' => 'nullable|numeric|min:0',
                    'refrigerant_kg' => 'nullable|numeric|min:0',
                    'other_emissions' => 'nullable|numeric|min:0'
                ];
                break;
            case 'scope2':
                $rules = [
                    'electricity_kwh' => 'nullable|numeric|min:0',
                    'district_cooling_kwh' => 'nullable|numeric|min:0'
                ];
                break;
            case 'scope3':
                $rules = [
                    'business_travel_flights_km' => 'nullable|numeric|min:0',
                    'car_hire_km' => 'nullable|numeric|min:0',
                    'waste_tonnes' => 'nullable|numeric|min:0',
                    'water_m3' => 'nullable|numeric|min:0',
                    'purchased_goods' => 'nullable|numeric|min:0'
                ];
                break;
            case 'evidence':
                $rules = [
                    'files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240' // 10MB max
                ];
                break;
        }

        return $rules;
    }

    private function updateEmissionSource($emissionSource, Request $request, $step)
    {
        $data = $request->only($this->getFieldsForStep($step));
        
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $emissionSource->$key = $value;
            }
        }
        
        $emissionSource->save();
    }

    private function getFieldsForStep($step)
    {
        switch ($step) {
            case 'company':
                return ['company_name', 'sector', 'location', 'reporting_year'];
            case 'scope1':
                return ['diesel_litres', 'petrol_litres', 'natural_gas_m3', 'refrigerant_kg', 'other_emissions'];
            case 'scope2':
                return ['electricity_kwh', 'district_cooling_kwh'];
            case 'scope3':
                return ['business_travel_flights_km', 'car_hire_km', 'waste_tonnes', 'water_m3', 'purchased_goods'];
            default:
                return [];
        }
    }

    private function handleFileUploads($emissionSource, Request $request)
    {
        if ($request->hasFile('files')) {
            $uploadedFiles = $emissionSource->uploaded_files ?? [];
            
            foreach ($request->file('files') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads/emission-evidence', $filename, 'public');
                
                $uploadedFiles[] = [
                    'filename' => $filename,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'uploaded_at' => now()
                ];
            }
            
            $emissionSource->uploaded_files = $uploadedFiles;
            $emissionSource->save();
        }
    }

    private function getNextStep($currentStep)
    {
        $steps = array_keys(self::STEPS);
        $currentIndex = array_search($currentStep, $steps);
        
        if ($currentIndex !== false && $currentIndex < count($steps) - 1) {
            return $steps[$currentIndex + 1];
        }
        
        return null;
    }

    public function extractOCRData(Request $request)
    {
        // Placeholder OCR extraction function
        $file = $request->file('file');
        
        if (!$file) {
            return response()->json(['error' => 'No file provided'], 400);
        }

        // Simulate OCR processing delay
        sleep(2);

        // Return mock extracted data based on file type
        $filename = strtolower($file->getClientOriginalName());
        
        $mockData = [];
        
        if (strpos($filename, 'electricity') !== false || strpos($filename, 'power') !== false) {
            $mockData = [
                'electricity_kwh' => rand(1000, 10000),
                'district_cooling_kwh' => rand(500, 5000)
            ];
        } elseif (strpos($filename, 'fuel') !== false || strpos($filename, 'diesel') !== false) {
            $mockData = [
                'diesel_litres' => rand(100, 1000),
                'petrol_litres' => rand(50, 500)
            ];
        } elseif (strpos($filename, 'waste') !== false) {
            $mockData = [
                'waste_tonnes' => rand(1, 10)
            ];
        } elseif (strpos($filename, 'water') !== false) {
            $mockData = [
                'water_m3' => rand(100, 1000)
            ];
        } else {
            $mockData = [
                'electricity_kwh' => rand(1000, 5000),
                'diesel_litres' => rand(100, 500)
            ];
        }

        return response()->json([
            'success' => true,
            'extracted_data' => $mockData,
            'message' => 'Data extracted successfully from ' . $file->getClientOriginalName()
        ]);
    }
}
