<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('storefront.categories.index');
    }

    public function show(string $category): View
    {
        return view('storefront.categories.show', ['slug' => $category]);
    }
}
