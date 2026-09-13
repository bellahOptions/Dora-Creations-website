<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\StorefrontCache;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('storefront.categories.index', [
            'categories' => StorefrontCache::remember(
                'categories:index-with-counts',
                fn () => Category::active()->orderBy('sort_order')->withCount('products')->get(),
            ),
        ]);
    }

    public function show(Category $category): View
    {
        abort_unless($category->is_active, 404);

        return view('storefront.categories.show', ['category' => $category]);
    }
}
