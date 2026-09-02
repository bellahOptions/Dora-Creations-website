<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CartService identifies guest carts via a cookie that's only ever
     * queued onto the response, never round-tripped back within a single
     * test process. Resolving the cart once and pinning its token onto the
     * current request makes every later currentCart() call in the test see
     * the same cart, like a real second request would.
     */
    protected function guestCartService(): CartService
    {
        $cartService = app(CartService::class);
        request()->cookies->set(CartService::COOKIE_NAME, $cartService->currentCart()->cart_token);

        return $cartService;
    }

    public function test_guest_can_add_a_product_to_their_cart(): void
    {
        $product = Product::factory()->create(['price_kobo' => 500000]);
        $cartService = $this->guestCartService();

        $item = $cartService->addItem($product, null, 2);

        $this->assertSame(2, $item->quantity);
        $this->assertSame(500000, $item->unit_price_kobo);
        $this->assertSame(2, $cartService->currentCart()->itemCount());
    }

    public function test_adding_the_same_product_twice_merges_quantities(): void
    {
        $product = Product::factory()->create();
        $cartService = $this->guestCartService();

        $cartService->addItem($product, null, 1);
        $cartService->addItem($product, null, 3);

        $cart = $cartService->currentCart();

        $this->assertSame(1, $cart->items()->count());
        $this->assertSame(4, $cart->itemCount());
    }

    public function test_different_variants_of_the_same_product_are_separate_lines(): void
    {
        $product = Product::factory()->create(['has_variants' => true]);
        $small = ProductVariant::factory()->create(['product_id' => $product->id, 'size' => 'S']);
        $large = ProductVariant::factory()->create(['product_id' => $product->id, 'size' => 'L']);

        $cartService = $this->guestCartService();
        $cartService->addItem($product, $small, 1);
        $cartService->addItem($product, $large, 1);

        $this->assertSame(2, $cartService->currentCart()->items()->count());
    }

    public function test_updating_quantity_to_zero_removes_the_item(): void
    {
        $product = Product::factory()->create();
        $cartService = $this->guestCartService();
        $item = $cartService->addItem($product, null, 1);

        $cartService->updateQuantity($item, 0);

        $this->assertSame(0, $cartService->currentCart()->items()->count());
    }

    public function test_guest_cart_merges_into_user_cart_on_login(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $token = (string) Str::uuid();
        Cart::create(['cart_token' => $token])->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price_kobo' => $product->price_kobo,
        ]);
        request()->cookies->set(CartService::COOKIE_NAME, $token);

        $cartService = app(CartService::class);
        $this->actingAs($user);
        $cartService->mergeGuestCartIntoUser($user);

        $userCart = $cartService->currentCart();
        $this->assertSame($user->id, $userCart->user_id);
        $this->assertSame(2, $userCart->itemCount());
    }
}
