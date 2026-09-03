<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $products = $request->user()->wishlistedProducts()
            ->with('images')
            ->latest('wishlists.created_at')
            ->paginate(12);

        return view('account.wishlist', ['products' => $products]);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        $wishlist = Wishlist::where('user_id', $user->id)->where('product_id', $product->id)->first();

        if ($wishlist) {
            $wishlist->delete();
            ActivityLogger::visitor("Removed \"{$product->name}\" from wishlist.", $product);

            return response()->json(['wishlisted' => false]);
        }

        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);
        ActivityLogger::visitor("Added \"{$product->name}\" to wishlist.", $product);

        return response()->json(['wishlisted' => true]);
    }
}
