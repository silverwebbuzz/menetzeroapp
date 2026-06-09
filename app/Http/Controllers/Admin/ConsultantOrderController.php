<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultantOrder;
use Illuminate\Http\Request;

class ConsultantOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = ConsultantOrder::query()
            ->with(['company', 'consultant', 'introRequest'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.consultants.orders', compact('orders'));
    }
}
