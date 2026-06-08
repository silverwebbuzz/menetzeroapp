<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SitePage;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

/**
 * Super-admin management of public site content: company/contact details,
 * currency defaults, and the editable policy pages.
 */
class SiteContentController extends Controller
{
    /** Keys editable on the settings form. */
    private array $settingKeys = [
        'company_legal_name', 'brand_name', 'support_email', 'sales_email',
        'support_phone', 'address_line', 'city', 'country', 'business_hours',
        'default_currency', 'currency_auto_detect',
    ];

    public function index()
    {
        $settings = SiteSetting::allSettings();
        $pages = SitePage::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.site-content.index', compact('settings', 'pages'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'company_legal_name' => 'nullable|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'sales_email' => 'nullable|email|max:255',
            'support_phone' => 'nullable|string|max:50',
            'address_line' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'business_hours' => 'nullable|string|max:255',
            'default_currency' => 'required|in:AED,INR',
        ]);

        foreach ($this->settingKeys as $key) {
            if ($key === 'currency_auto_detect') {
                SiteSetting::put($key, $request->boolean('currency_auto_detect') ? '1' : '0');
                continue;
            }
            SiteSetting::put($key, $validated[$key] ?? '');
        }

        return redirect()->route('admin.site-content.index')
            ->with('success', 'Site settings saved.');
    }

    public function editPage($id)
    {
        $page = SitePage::findOrFail($id);

        return view('admin.site-content.page', compact('page'));
    }

    public function updatePage(Request $request, $id)
    {
        $page = SitePage::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
        ]);

        $page->update([
            'title' => $validated['title'],
            'body' => $validated['body'] ?? '',
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()->route('admin.site-content.index')
            ->with('success', $page->title . ' updated.');
    }
}
