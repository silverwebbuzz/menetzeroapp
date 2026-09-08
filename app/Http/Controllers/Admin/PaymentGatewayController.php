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
            'test_key_id' => 'nullable|string|max:255',
            'test_key_secret' => 'nullable|string|max:500',
            'test_webhook_secret' => 'nullable|string|max:500',
            'live_key_id' => 'nullable|string|max:255',
            'live_key_secret' => 'nullable|string|max:500',
            'live_webhook_secret' => 'nullable|string|max:500',
        ]);

        $gateway->mode = $validated['mode'];

        // Both credential sets are saved on every submit, independently of the
        // selected mode: the whole point is that switching mode no longer means
        // retyping keys, so editing the live pair while running in test must
        // keep working.
        foreach (['test', 'live'] as $prefix) {
            $gateway->{$prefix . '_key_id'} = $validated[$prefix . '_key_id'] ?? null;

            // Only overwrite secrets when a new value is supplied (the form
            // shows them masked, so an empty field means "keep existing").
            foreach (['key_secret', 'webhook_secret'] as $secret) {
                $field = $prefix . '_' . $secret;
                if ($request->filled($field)) {
                    $gateway->{$field} = $validated[$field];
                }
            }
        }

        $gateway->is_enabled = $request->boolean('is_enabled');

        // Cannot enable a gateway whose ACTIVE mode has no credentials. The
        // other mode's keys are irrelevant here -- they are not what checkout
        // would use.
        if ($gateway->is_enabled && !$gateway->isConfigured()) {
            $modeLabel = $gateway->isLive() ? 'Live' : 'Test';

            return back()->with(
                'error',
                $gateway->label . ' needs a ' . $modeLabel . ' Key ID and Secret before it can be enabled in '
                    . $modeLabel . ' mode.'
            );
        }

        $gateway->save();

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', $gateway->label . ' settings saved.');
    }
}
