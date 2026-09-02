<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Auth::user()->orders()
            ->with('items')
            ->latest()
            ->paginate(10);

        return view('account.orders.index', ['orders' => $orders]);
    }
}
