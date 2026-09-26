<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAndHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_has_an_h1_and_uses_the_site_meta_title(): void
    {
        SiteSetting::current()->update([
            'meta_title' => 'Dora Creations — handmade Nigerian fashion',
            'meta_description' => 'Handmade tees, totes and hoodies from Lagos.',
        ]);

        $response = $this->get('/')->assertOk();

        $response->assertSee('<h1', false);
        $response->assertSee('Dora Creations — handmade Nigerian fashion', false);
        $response->assertSee('Handmade tees, totes and hoodies from Lagos.', false);
        $response->assertSee('name="robots" content="index, follow"', false);
    }

    public function test_only_one_h1_is_rendered_on_the_home_page(): void
    {
        // Several hero slides each render a heading; only the first is an h1.
        foreach (range(1, 3) as $i) {
            \App\Models\Slide::factory()->create(['headline' => "Slide {$i}", 'sort_order' => $i]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'), 'The homepage must expose exactly one h1');
    }

    public function test_product_page_uses_the_admin_meta_title(): void
    {
        $product = Product::factory()->create([
            'name' => 'Adire Waves Tee',
            'slug' => 'adire-waves-tee',
            'meta_title' => 'Adire Waves Tee, hand-printed in Lagos',
        ]);

        $response = $this->get('/shop/adire-waves-tee')->assertOk();

        $response->assertSee('Adire Waves Tee, hand-printed in Lagos', false);
    }

    public function test_private_pages_are_noindexed(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 5, 'price_kobo' => 100000]);

        $this->actingAs($user);
        app(\App\Services\CartService::class)->addItem($product, null, 1);

        $this->get('/cart')->assertOk()->assertSee('name="robots" content="noindex, follow"', false);
        $this->get('/checkout')->assertOk()->assertSee('noindex', false);

        $this->actingAs($user)->get('/account')->assertOk()->assertSee('noindex, nofollow', false);
    }

    public function test_order_tracking_page_is_noindexed(): void
    {
        $order = Order::factory()->create();

        $this->get(route('order-tracking.show', $order->public_token))
            ->assertOk()
            ->assertSee('noindex, nofollow', false);
    }

    public function test_sitemap_lists_published_products_and_categories(): void
    {
        $category = Category::factory()->create(['slug' => 'tees']);
        $product = Product::factory()->create(['slug' => 'adire-waves-tee', 'is_published' => true]);
        Product::factory()->create(['slug' => 'hidden-tee', 'is_published' => false]);

        $response = $this->get('/sitemap.xml')->assertOk();

        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('/shop/adire-waves-tee', false);
        $response->assertSee('/collections/tees', false);
        $response->assertDontSee('/shop/hidden-tee', false);
        $response->assertSee('<changefreq>', false);
    }

    public function test_robots_disallows_private_and_tokenised_paths(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();

        foreach (['/admin', '/cart', '/checkout', '/account', '/track-order', '/wishlist', '/livewire'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}", $body);
        }

        $this->assertStringContainsString('Sitemap:', $body);
    }

    public function test_robots_blocks_everything_during_maintenance(): void
    {
        SiteSetting::current()->update(['maintenance_mode' => true]);

        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /', $body);
        $this->assertStringNotContainsString('Sitemap:', $body);
    }

    public function test_llms_txt_is_served(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertSee('Dora Creations');
    }

    public function test_hsts_is_only_sent_over_https(): void
    {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_baseline_security_headers_are_present(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
