<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Livewire\Shop\AddToCart;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Livewire\Features\SupportTesting\Testable;
use Tests\TestCase;

/**
 * A product marked as variant-based must always expose its size/color options
 * on the storefront, and must never dead-end a customer on a "please select a
 * size/color" message with nothing to pick from.
 */
class ProductVariantsTest extends TestCase
{
    use RefreshDatabase;

    private function variantProduct(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'name' => 'Clarity Statement Tees',
            'slug' => 'clarity-statement-tees',
            'has_variants' => true,
            'stock_quantity' => 0,
        ], $attributes));
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $combinations
     */
    private function addVariants(Product $product, array $combinations, int $stock = 5): void
    {
        foreach ($combinations as [$size, $color]) {
            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'size' => $size,
                'color' => $color,
                'stock_quantity' => $stock,
            ]);
        }
    }

    /**
     * Filament's fillForm() test helper does not persist state on this
     * Filament/Livewire combination, so drive the form state directly.
     */
    private function fillProductForm(Testable $component, Category $category, bool $hasVariants): void
    {
        $component->set('data.name', 'Clarity Statement Tees');
        $component->set('data.slug', 'clarity-statement-tees');
        $component->set('data.category_id', $category->id);
        $component->set('data.price_kobo', 20000);
        $component->set('data.stock_quantity', $hasVariants ? 0 : 12);
        $component->set('data.has_variants', $hasVariants);
        $component->set('data.is_published', true);
    }

    public function test_size_and_color_options_are_visible_on_the_product_page(): void
    {
        $product = $this->variantProduct();
        $this->addVariants($product, [['S', 'Black'], ['M', 'Black'], ['L', 'White']]);

        $html = $this->get('/shop/clarity-statement-tees')->assertOk()->getContent();

        $this->assertStringContainsString('>Size<', $html);
        $this->assertStringContainsString('>Color<', $html);
        $this->assertSame(3, substr_count($html, 'selectSize'), 'Each size should render a selectable option');
        $this->assertGreaterThanOrEqual(1, substr_count($html, 'selectColor'));
    }

    public function test_the_current_selection_is_shown_to_the_customer(): void
    {
        $product = $this->variantProduct();
        $this->addVariants($product, [['S', 'Black'], ['M', 'Black']]);

        $html = $this->get('/shop/clarity-statement-tees')->assertOk()->getContent();

        $this->assertStringContainsString('Selected:', $html);
        $this->assertStringContainsString('S / Black', $html);
    }

    public function test_a_variant_product_with_no_options_can_still_be_bought(): void
    {
        // The reported dead end: flagged as variant-based, but no variant rows
        // stored. Rather than blocking the sale we take the order and confirm
        // the choice afterwards, which is why delivery takes longer.
        $product = $this->variantProduct(['is_preorder' => true]);

        $html = $this->get('/shop/clarity-statement-tees')->assertOk()->getContent();

        $this->assertStringNotContainsString('selectSize', $html);
        $this->assertStringNotContainsString('selectColor', $html);
        $this->assertStringContainsString("aren't set up on this item yet", $html);
        $this->assertStringNotContainsString('Unavailable', $html);

        Livewire::test(AddToCart::class, ['product' => $product])
            ->call('addToCart')
            ->assertHasNoErrors();

        $this->assertSame(1, CartItem::count(), 'The item should reach the cart');
        $this->assertNull(CartItem::first()->product_variant_id);
    }

    public function test_an_order_without_a_chosen_variant_is_flagged_and_takes_longer(): void
    {
        $product = $this->variantProduct();
        $cart = Cart::create(['cart_token' => (string) Str::uuid()]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_kobo' => $product->price_kobo,
        ]);

        $order = app(OrderService::class)->createFromCart(
            $cart,
            ['full_name' => 'Buyer', 'phone' => '+2348012345678', 'state' => 'Lagos', 'city' => 'Lekki', 'line1' => '1 Way'],
            null,
            'buyer@example.com',
        );

        $this->assertTrue($order->needsVariantConfirmation());
        $this->assertSame(
            SiteSetting::current()->deliveryLeadDays() + Order::EXTRA_DAYS_WITHOUT_VARIANT,
            (int) round(now()->diffInDays($order->estimated_delivery_at)),
        );
    }

    public function test_an_order_with_a_chosen_variant_uses_the_standard_delivery_window(): void
    {
        $product = $this->variantProduct();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Black',
            'stock_quantity' => 5,
        ]);

        $cart = Cart::create(['cart_token' => (string) Str::uuid()]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price_kobo' => $product->price_kobo,
        ]);

        $order = app(OrderService::class)->createFromCart(
            $cart,
            ['full_name' => 'Buyer', 'phone' => '+2348012345678', 'state' => 'Lagos', 'city' => 'Lekki', 'line1' => '1 Way'],
            null,
            'buyer@example.com',
        );

        $this->assertFalse($order->needsVariantConfirmation());
        $this->assertSame(
            SiteSetting::current()->deliveryLeadDays(),
            (int) round(now()->diffInDays($order->estimated_delivery_at)),
        );
    }

    public function test_choosing_a_variant_adds_that_variant_to_the_cart(): void
    {
        $product = $this->variantProduct();
        $this->addVariants($product, [['S', 'Black'], ['L', 'White']]);

        Livewire::test(AddToCart::class, ['product' => $product])
            ->call('selectSize', 'L')
            ->call('selectColor', 'White')
            ->call('addToCart')
            ->assertHasNoErrors();

        $variant = $product->variants()->where('size', 'L')->where('color', 'White')->first();

        $this->assertSame(1, CartItem::count());
        $this->assertSame($variant->id, CartItem::first()->product_variant_id);
    }

    public function test_selecting_a_size_moves_the_color_to_one_that_exists(): void
    {
        $product = $this->variantProduct();
        $this->addVariants($product, [['S', 'Black'], ['L', 'White']]);

        Livewire::test(AddToCart::class, ['product' => $product])
            ->call('selectSize', 'L')
            ->assertSet('color', 'White');
    }

    public function test_admin_can_save_a_variant_product_without_variants_yet(): void
    {
        // An intentionally supported state: the product stays on sale and the
        // studio confirms each buyer's size/colour afterwards.
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create();

        $component = Livewire::actingAs($admin)->test(CreateProduct::class);

        $this->fillProductForm($component, $category, hasVariants: true);
        $component->call('create');

        $this->assertSame([], $component->errors()->toArray());

        $product = Product::where('slug', 'clarity-statement-tees')->firstOrFail();

        $this->assertTrue($product->has_variants);
        $this->assertSame(0, $product->variants()->count());
        $this->assertTrue($product->canPurchase(), 'It must remain orderable with no options configured');
    }

    public function test_admin_can_save_a_variant_product_with_variants(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create();

        $component = Livewire::actingAs($admin)->test(CreateProduct::class);

        $this->fillProductForm($component, $category, hasVariants: true);
        $component->set('data.variants', [
            'first' => ['size' => 'S', 'color' => 'Black', 'stock_quantity' => 4],
            'second' => ['size' => 'M', 'color' => 'White', 'stock_quantity' => 6],
        ]);
        $component->call('create');

        $this->assertSame([], $component->errors()->toArray());

        $product = Product::where('slug', 'clarity-statement-tees')->firstOrFail();

        $this->assertTrue($product->has_variants);
        $this->assertSame(2, $product->variants()->count());
    }

    public function test_admin_can_still_save_a_product_without_variants(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create();

        $component = Livewire::actingAs($admin)->test(CreateProduct::class);

        $this->fillProductForm($component, $category, hasVariants: false);
        $component->call('create');

        $this->assertSame([], $component->errors()->toArray());

        $product = Product::where('slug', 'clarity-statement-tees')->firstOrFail();

        $this->assertFalse($product->has_variants);
        $this->assertSame(12, $product->stock_quantity);
    }
}
