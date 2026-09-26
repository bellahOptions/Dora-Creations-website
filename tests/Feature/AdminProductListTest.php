<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The admin product list: readable status columns instead of bare toggles,
 * and infinite loading instead of page links.
 */
class AdminProductListTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_status_is_shown_as_words_not_a_bare_toggle(): void
    {
        Product::factory()->create(['name' => 'Live Tee', 'is_published' => true]);
        Product::factory()->create(['name' => 'Draft Tee', 'is_published' => false]);

        $component = Livewire::actingAs($this->admin())->test(ListProducts::class);

        $component->assertOk()
            ->assertSee('Status')
            ->assertSee('Stock')
            ->assertSee('Featured')
            ->assertSee('Pre-order')
            ->assertSee('Live')
            ->assertSee('Draft');

        // The old layout stacked three unlabelled ToggleColumns; Filament
        // renders those as role="switch" checkboxes.
        $this->assertStringNotContainsString('role="switch"', $component->html());
    }

    public function test_the_sort_control_is_labelled(): void
    {
        // Filament's "Sort by" bar falls back to "-" when the default sort
        // column isn't one of the visible ones, which reads like a broken
        // control. (The bar itself only renders in the browser, so assert the
        // configured label rather than the markup.)
        $component = Livewire::actingAs($this->admin())->test(ListProducts::class);

        $this->assertSame('Newest first', $component->instance()->getTable()->getDefaultSortOptionLabel());
    }

    public function test_stock_is_described_in_words(): void
    {
        Product::factory()->create(['name' => 'Plenty Tee', 'stock_quantity' => 12]);

        Livewire::actingAs($this->admin())->test(ListProducts::class)
            ->assertOk()
            ->assertSee('12 in stock');
    }

    public function test_stock_for_a_variant_product_sums_its_options(): void
    {
        $product = Product::factory()->create([
            'name' => 'Varied Tee',
            'has_variants' => true,
            'stock_quantity' => 0,
        ]);

        ProductVariant::factory()->create(['product_id' => $product->id, 'size' => 'S', 'color' => 'Black', 'stock_quantity' => 5]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'size' => 'M', 'color' => 'Black', 'stock_quantity' => 3]);

        Livewire::actingAs($this->admin())->test(ListProducts::class)
            ->assertOk()
            ->assertSee('8 across 2 options');
    }

    public function test_a_variant_product_with_no_options_says_so(): void
    {
        Product::factory()->create([
            'name' => 'No Options Tee',
            'has_variants' => true,
            'stock_quantity' => 0,
        ]);

        Livewire::actingAs($this->admin())->test(ListProducts::class)
            ->assertOk()
            ->assertSee('No options yet');
    }

    public function test_the_list_loads_more_products_as_the_chunk_grows(): void
    {
        Product::factory()->count(30)->create();

        $component = Livewire::actingAs($this->admin())->test(ListProducts::class);

        // First chunk: 25 of 30, with a way to reach the rest.
        $component->assertOk()
            ->assertSee('Showing 25 of 30')
            ->assertSee('Load more products');

        // What the scroll sentinel does: grow the chunk, stay on the same page.
        $component->set('tableRecordsPerPage', ListProducts::CHUNK_SIZE * 2);

        $component->assertSee('Showing 30 of 30')
            ->assertDontSee('Load more products');
    }

    public function test_no_load_more_control_when_everything_already_fits(): void
    {
        Product::factory()->count(3)->create();

        Livewire::actingAs($this->admin())->test(ListProducts::class)
            ->assertOk()
            ->assertDontSee('Load more products');
    }
}
