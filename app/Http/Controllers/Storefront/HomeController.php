<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slide;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('storefront.home', [
            'slides' => Slide::active()->get(),
            'featuredProducts' => Product::published()->featured()->with('images')->latest()->limit(8)->get(),
            'newArrivals' => Product::published()->with('images')->latest()->limit(4)->get(),
            'categories' => Category::active()->orderBy('sort_order')->get(),
        ]);
    }
}
