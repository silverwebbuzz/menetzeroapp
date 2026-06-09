<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $consultant = Auth::guard('consultant')->user();

        $orders = $consultant->orders()
            ->with('company')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('consultant.orders', compact('consultant', 'orders'));
    }
}
