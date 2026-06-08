<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

/**
 * Super-admin management of payment gateway credentials.
 */
class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::orderBy('sort_order')->get();

        return view('admin.payment-gateways.index', compact('gateways'));
    }

    public function update(Request $request, $id)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $validated = $request->validate([
            'mode' => 'required|in:test,live',
            'key_id' => 'nullable|string|max:255',
            'key_secret' => 'nullable|string|max:500',
            'webhook_secret' => 'nullable|string|max:500',
        ]);

        $gateway->mode = $validated['mode'];
        $gateway->key_id = $validated['key_id'] ?? null;

        // Only overwrite secrets when a new value is supplied (the form shows
        // them masked, so an empty field means "keep existing").
        if ($request->filled('key_secret')) {
            $gateway->key_secret = $validated['key_secret'];
        }
        if ($request->filled('webhook_secret')) {
            $gateway->webhook_secret = $validated['webhook_secret'];
        }

        $gateway->is_enabled = $request->boolean('is_enabled');

        // Cannot enable a gateway without credentials.
        if ($gateway->is_enabled && !$gateway->isConfigured()) {
            return back()->with('error', $gateway->label . ' needs a Key ID and Secret before it can be enabled.');
        }

        $gateway->save();

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', $gateway->label . ' settings saved.');
    }
}
