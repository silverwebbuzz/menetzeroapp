<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanFeatureRow;
use App\Models\Scope3Addon;
use Illuminate\Http\Request;

/**
 * Super-admin management of the public pricing page content:
 * the feature comparison table rows and the Scope 3 add-on tiers.
 */
class PricingContentController extends Controller
{
    public function index()
    {
        $featureRows = PlanFeatureRow::orderBy('sort_order')->orderBy('id')->get();
        $addons = Scope3Addon::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.pricing.index', compact('featureRows', 'addons'));
    }

    /* ---------------- Feature comparison rows ---------------- */

    public function createFeatureRow()
    {
        $row = new PlanFeatureRow(['is_active' => true]);
        return view('admin.pricing.feature-row', compact('row'));
    }

    public function editFeatureRow($id)
    {
        $row = PlanFeatureRow::findOrFail($id);
        return view('admin.pricing.feature-row', compact('row'));
    }

    public function storeFeatureRow(Request $request)
    {
        $data = $this->validateFeatureRow($request);
        PlanFeatureRow::create($data);

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Feature row added.');
    }

    public function updateFeatureRow(Request $request, $id)
    {
        $row = PlanFeatureRow::findOrFail($id);
        $row->update($this->validateFeatureRow($request));

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Feature row updated.');
    }

    public function destroyFeatureRow($id)
    {
        PlanFeatureRow::findOrFail($id)->delete();

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Feature row deleted.');
    }

    private function validateFeatureRow(Request $request): array
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'value_starter' => 'nullable|string|max:255',
            'value_growth' => 'nullable|string|max:255',
            'value_enterprise' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['coming_soon'] = $request->boolean('coming_soon');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }

    /* ---------------- Scope 3 add-ons ---------------- */

    public function createAddon()
    {
        $addon = new Scope3Addon(['is_active' => true]);
        return view('admin.pricing.addon', compact('addon'));
    }

    public function editAddon($id)
    {
        $addon = Scope3Addon::findOrFail($id);
        return view('admin.pricing.addon', compact('addon'));
    }

    public function storeAddon(Request $request)
    {
        Scope3Addon::create($this->validateAddon($request));

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Scope 3 add-on added.');
    }

    public function updateAddon(Request $request, $id)
    {
        $addon = Scope3Addon::findOrFail($id);
        $addon->update($this->validateAddon($request));

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Scope 3 add-on updated.');
    }

    public function destroyAddon($id)
    {
        Scope3Addon::findOrFail($id)->delete();

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Scope 3 add-on deleted.');
    }

    private function validateAddon(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price_display' => 'nullable|string|max:255',
            'items' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        return [
            'name' => $validated['name'],
            'price_display' => $validated['price_display'] ?? null,
            'items' => $this->parseItems($request->input('items')),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * Parse the items textarea. One item per line; append " | soon" to mark a
     * feature as coming soon. e.g. "Supplier Mapping | soon".
     */
    private function parseItems(?string $raw): array
    {
        $items = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode('|', $line, 2);
            $label = trim($parts[0]);
            if ($label === '') {
                continue;
            }
            $soon = isset($parts[1])
                && in_array(strtolower(trim($parts[1])), ['soon', '1', 'true', 'yes'], true);
            $items[] = ['label' => $label, 'soon' => $soon];
        }

        return $items;
    }
}
