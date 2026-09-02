<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('account.orders.index');
    }
}
