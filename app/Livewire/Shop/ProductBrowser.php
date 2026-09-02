<?php

namespace App\Livewire\Shop;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class ProductBrowser extends Component
{
    use WithPagination;

    #[Url(as: 'category', history: true)]
    public string $category = '';

    #[Url(history: true)]
    public string $size = '';

    #[Url(history: true)]
    public string $color = '';

    #[Url(history: true)]
    public string $price = '';

    #[Url(history: true)]
    public string $sort = 'newest';

    #[Url(history: true)]
    public string $q = '';

    public function mount(?string $categorySlug = null): void
    {
        if ($categorySlug) {
            $this->category = $categorySlug;
        }
    }

    public function updating($property): void
    {
        if (in_array($property, ['category', 'size', 'color', 'price', 'sort', 'q'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['category', 'size', 'color', 'price', 'q']);
        $this->sort = 'newest';
        $this->resetPage();
    }

    protected function priceRange(): ?array
    {
        return match ($this->price) {
            'under-10k' => [0, 1_000_000],
            '10k-20k' => [1_000_000, 2_000_000],
            '20k-30k' => [2_000_000, 3_000_000],
            'over-30k' => [3_000_000, PHP_INT_MAX],
            default => null,
        };
    }

    protected function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->published()
            ->with(['images', 'category'])
            ->when($this->category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $this->category)))
            ->when($this->q, fn ($q) => $q->where('name', 'like', '%'.$this->q.'%'))
            ->when($this->size, fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('size', $this->size)))
            ->when($this->color, fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('color', $this->color)))
            ->when($this->priceRange(), fn ($q, $range) => $q->whereBetween('price_kobo', $range));

        $query = match ($this->sort) {
            'price_asc' => $query->orderBy('price_kobo'),
            'price_desc' => $query->orderByDesc('price_kobo'),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return $query->paginate(12);
    }

    public function render()
    {
        return view('livewire.shop.product-browser', [
            'products' => $this->products(),
            'categories' => Category::active()->orderBy('sort_order')->get(),
            'sizes' => ['S', 'M', 'L', 'XL'],
            'colors' => ['Black', 'White', 'Sand', 'Forest', 'Ochre'],
        ]);
    }
}
