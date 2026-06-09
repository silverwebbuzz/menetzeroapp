<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultantOrder;
use App\Services\ConsultantMarketplaceService;
use Illuminate\Http\Request;

class ConsultantOrderController extends Controller
{
    public function __construct(
        protected ConsultantMarketplaceService $marketplace,
    ) {}

    public function index(Request $request)
    {
        $orders = ConsultantOrder::query()
            ->with(['company', 'consultant', 'introRequest', 'paymentTransaction'])
            ->orderByDesc('created_at')
            ->paginate(25);

        $commissionRate = $this->marketplace->commissionRate();

        return view('admin.consultants.orders', compact('orders', 'commissionRate'));
    }

    public function markDelivered(ConsultantOrder $order)
    {
        $this->marketplace->markDelivered($order);

        return back()->with('success', 'Order marked as delivered.');
    }

    public function releaseEscrow(ConsultantOrder $order)
    {
        try {
            $this->marketplace->releaseEscrow($order);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Escrow released — consultant payout of AED ' . number_format($order->payout_aed, 0) . ' recorded.');
    }

    public function refundEscrow(ConsultantOrder $order)
    {
        try {
            $this->marketplace->refundEscrow($order);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order refunded — escrow cancelled.');
    }
}
