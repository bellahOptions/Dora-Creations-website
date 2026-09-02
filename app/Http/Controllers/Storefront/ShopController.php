<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(): View
    {
        return view('storefront.shop.index');
    }

    public function show(string $product): View
    {
        return view('storefront.shop.show', ['slug' => $product]);
    }
}
