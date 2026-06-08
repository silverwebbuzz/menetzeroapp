<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionCoupon;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = SubscriptionCoupon::with('plan')->orderByDesc('created_at')->get();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $plans = SubscriptionPlan::where('plan_category', 'client')->where('is_active', true)->orderBy('sort_order')->get();
        $suggestedCode = SubscriptionCoupon::generateCode();

        return view('admin.coupons.form', [
            'coupon' => new SubscriptionCoupon(['code' => $suggestedCode, 'is_active' => true]),
            'plans' => $plans,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['code'] = SubscriptionCoupon::normalizeCode($data['code']);
        $data['created_by'] = Auth::id();

        SubscriptionCoupon::create($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(SubscriptionCoupon $coupon)
    {
        $plans = SubscriptionPlan::where('plan_category', 'client')->where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.coupons.form', compact('coupon', 'plans'));
    }

    public function update(Request $request, SubscriptionCoupon $coupon)
    {
        $data = $this->validated($request, $coupon->id);
        $data['code'] = SubscriptionCoupon::normalizeCode($data['code']);
        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(SubscriptionCoupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'unique:subscription_coupons,code';
        if ($ignoreId) {
            $uniqueRule .= ',' . $ignoreId;
        }

        $request->validate([
            'code' => ['required', 'string', 'max:40', $uniqueRule],
            'name' => 'required|string|max:120',
            'type' => 'required|in:percent,fixed,free',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount_aed' => 'nullable|numeric|min:0',
            'discount_amount_inr' => 'nullable|numeric|min:0',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        return [
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'discount_percent' => $request->type === 'percent' ? $request->discount_percent : null,
            'discount_amount_aed' => $request->type === 'fixed' ? $request->discount_amount_aed : null,
            'discount_amount_inr' => $request->type === 'fixed' ? $request->discount_amount_inr : null,
            'subscription_plan_id' => $request->subscription_plan_id ?: null,
            'max_uses' => $request->max_uses ?: null,
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
