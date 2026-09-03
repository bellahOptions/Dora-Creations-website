<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->is_published, 404);

        $product->load(['images', 'variants', 'category', 'approvedReviews.user']);

        ActivityLogger::visitor("Viewed \"{$product->name}\".", $product);

        $related = Product::query()
            ->published()
            ->with('images')
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('storefront.shop.show', [
            'product' => $product,
            'related' => $related,
        ]);
    }
}
