<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function lookup(): View
    {
        return view('storefront.order-tracking.lookup');
    }
}
